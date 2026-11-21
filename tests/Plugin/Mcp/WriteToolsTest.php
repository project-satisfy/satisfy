<?php

namespace Playbloom\Tests\Plugin\Mcp;

/**
 * The tools that change satis.json and queue builds, which need a write token.
 */
class WriteToolsTest extends McpTestCase
{
    public function testAReadTokenCannotWrite(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->readToken);

        $result = $this->call($client, $this->readToken, $session, 'require_package', ['package' => 'psr/log', 'constraint' => '^3.0']);
        $this->assertStringContainsString('This token may only read', $result['error']);
        $this->assertSame(['acme/app' => '^1.0'], $this->satis()['require']);
    }

    public function testRequirePackageAddsAndChangesAConstraint(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);

        $this->assertSame(['package' => 'psr/log', 'from' => null, 'to' => '^3.0 || ^2.0'], $this->call($client, $this->writeToken, $session, 'require_package', ['package' => 'psr/log', 'constraint' => '^3.0 || ^2.0']));
        $this->assertSame(['package' => 'acme/app', 'from' => '^1.0', 'to' => '^1.2'], $this->call($client, $this->writeToken, $session, 'require_package', ['package' => 'acme/app', 'constraint' => '^1.2']));
        $this->assertSame(['acme/app' => '^1.2', 'psr/log' => '^3.0 || ^2.0'], $this->satis()['require']);

        $audit = array_map(static fn (string $line): array => json_decode($line, true), file($this->dir . '/mcp/audit.jsonl', \FILE_IGNORE_NEW_LINES) ?: []);
        $this->assertSame(['writer', 'writer'], array_column($audit, 'token'));
        $this->assertSame(['package' => 'acme/app', 'from' => '^1.0', 'to' => '^1.2'], $audit[1]['change']);

        $this->assertStringContainsString('Composer cannot read the constraint', $this->call($client, $this->writeToken, $session, 'require_package', ['package' => 'psr/log', 'constraint' => '^3.0 ||| ^2.0'])['error']);
        $this->assertStringContainsString('is not a Composer package name', $this->call($client, $this->writeToken, $session, 'require_package', ['package' => 'not a name', 'constraint' => '*'])['error']);
    }

    public function testRequirePackageRefusesWhileRequireAllIsOn(): void
    {
        $this->writeSatis(['require-all' => true] + self::SATIS);
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);

        $this->assertStringContainsString('require-all is on', $this->call($client, $this->writeToken, $session, 'require_package', ['package' => 'psr/log', 'constraint' => '*'])['error']);
    }

    public function testAddRepository(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);

        $this->assertSame(['added' => ['type' => 'vcs', 'url' => 'https://github.com/php-fig/log.git']], $this->call($client, $this->writeToken, $session, 'add_repository', ['url' => 'https://github.com/php-fig/log.git']));
        $this->assertContains('https://github.com/php-fig/log.git', array_column($this->satis()['repositories'], 'url'));

        $this->assertStringContainsString('already lists', $this->call($client, $this->writeToken, $session, 'add_repository', ['url' => 'https://github.com/php-fig/log.git'])['error']);
        $this->assertStringContainsString('carries a credential', $this->call($client, $this->writeToken, $session, 'add_repository', ['url' => 'https://ghp_abcdef123456@github.com/acme/secret.git'])['error']);
        $this->assertCount(3, $this->satis()['repositories']);
    }

    public function testQueueBuild(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);
        $queue = (string) self::getContainer()->getParameter('satisfy.queue_dir');

        $this->assertSame(['queued' => 'full'], $this->call($client, $this->writeToken, $session, 'queue_build'));
        $this->assertFileExists($queue . '/full.json');
        $this->assertSame(['queued' => 'git@github.com:acme/app.git'], $this->call($client, $this->writeToken, $session, 'queue_build', ['repositoryUrl' => 'git@github.com:acme/app.git']));
        $this->assertStringContainsString('lists no repository', $this->call($client, $this->writeToken, $session, 'queue_build', ['repositoryUrl' => 'https://nowhere.test/x.git'])['error']);

        array_map('unlink', glob($queue . '/*.json') ?: []);
    }

    public function testRemovalsNeedForceWithoutAnAuditLog(): void
    {
        $this->publish('acme/app', 'git@github.com:acme/app.git');
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);

        $this->assertStringContainsString('no telling who still fetches it', $this->call($client, $this->writeToken, $session, 'remove_requirement', ['package' => 'acme/app'])['error']);
        $this->assertStringContainsString('no telling', $this->call($client, $this->writeToken, $session, 'remove_repository', ['url' => 'git@github.com:acme/app.git'])['error']);
        $this->assertSame(['acme/app' => '^1.0'], $this->satis()['require']);

        $removed = $this->call($client, $this->writeToken, $session, 'remove_requirement', ['package' => 'acme/app', 'force' => true]);
        $this->assertSame(['package' => 'acme/app', 'constraint' => '^1.0'], $removed['removed']);
        $this->assertSame([], $this->satis()['require'] ?? []);

        $removed = $this->call($client, $this->writeToken, $session, 'remove_repository', ['url' => 'git@github.com:acme/app.git', 'force' => true]);
        $this->assertSame(['acme/app'], $removed['stops_publishing']);
        $this->assertNotContains('git@github.com:acme/app.git', array_column($this->satis()['repositories'], 'url'));
    }

    public function testARepositoryThatPublishesNothingIsRemovedWithoutForce(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);

        $removed = $this->call($client, $this->writeToken, $session, 'remove_repository', ['url' => 'https://***@git.acme.test/acme/private.git']);
        $this->assertSame(['type' => 'git', 'url' => 'https://***@git.acme.test/acme/private.git'], $removed['removed']);
        $this->assertSame([], $removed['stops_publishing']);
        $this->assertCount(1, $this->satis()['repositories']);
    }

    public function testUpdateRepositoryCorrectsAUrl(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->writeToken);

        $updated = $this->call($client, $this->writeToken, $session, 'update_repository', ['url' => 'https://***@git.acme.test/acme/private.git', 'newUrl' => 'git@git.acme.test:acme/private.git', 'newType' => 'vcs']);
        $this->assertSame(['type' => 'vcs', 'url' => 'git@git.acme.test:acme/private.git'], $updated['to']);
        $this->assertSame(['git@github.com:acme/app.git', 'git@git.acme.test:acme/private.git'], array_column($this->satis()['repositories'], 'url'));

        $this->assertStringContainsString('carries a credential', $this->call($client, $this->writeToken, $session, 'update_repository', ['url' => 'git@git.acme.test:acme/private.git', 'newUrl' => 'https://user:pass@git.acme.test/acme/private.git'])['error']);
        $this->assertStringContainsString('would change', $this->call($client, $this->writeToken, $session, 'update_repository', ['url' => 'git@github.com:acme/app.git'])['error']);
    }

    public function testThePackageToolsReadTheCatalogue(): void
    {
        $this->publish('acme/app', 'git@github.com:acme/app.git');
        $client = $this->client();
        $session = $this->initialize($client, $this->readToken);

        $this->assertSame(['packages' => ['acme/app' => ['1.1.0', '1.0.0']]], $this->call($client, $this->readToken, $session, 'packages'));
        $package = $this->call($client, $this->readToken, $session, 'package', ['name' => 'acme/app']);
        $this->assertSame(['1.1.0', '1.0.0'], array_column($package['versions'], 'version'));
        $this->assertSame(['php' => '>=8.2'], $package['versions'][1]['require']);
        $this->assertSame('git@github.com:acme/app.git', $package['versions'][1]['source']['url']);
        $this->assertStringContainsString('publishes no package named', $this->call($client, $this->readToken, $session, 'package', ['name' => 'acme/none'])['error']);
    }

    /**
     * Writes the catalogue Satis would for two versions of $name, in its
     * minified p2 format, built from $url.
     */
    private function publish(string $name, string $url): void
    {
        mkdir(dirname($this->dir . '/public/p2/' . $name), 0777, true);
        file_put_contents($this->dir . '/public/packages.json', (string) json_encode(['packages' => [], 'available-packages' => [$name]]));
        file_put_contents($this->dir . '/public/p2/' . $name . '.json', (string) json_encode([
            'minified' => 'composer/2.0',
            'packages' => [$name => [
                ['name' => $name, 'version' => '1.1.0', 'require' => ['php' => '>=8.2'], 'source' => ['type' => 'git', 'url' => $url, 'reference' => 'b'], 'dist' => ['type' => 'zip', 'url' => 'https://packages.acme.test/dist/b.zip']],
                ['version' => '1.0.0', 'source' => ['type' => 'git', 'url' => $url, 'reference' => 'a'], 'dist' => ['type' => 'zip', 'url' => 'https://packages.acme.test/dist/a.zip']],
            ]],
        ]));
    }
}
