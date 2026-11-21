<?php

namespace Playbloom\Tests\Plugin\Hookgate;

use Playbloom\Event\RepositoryAddingEvent;
use Playbloom\Model\Repository;
use Playbloom\Plugin\Hookgate\PackageTypes;
use Playbloom\Plugin\Hookgate\RegistrationGate;

class RegistrationGateTest extends HookgateTestCase
{
    private const PROJECT = '/projects/acme%2Fthing';

    public function testAProjectWithoutAComposerJsonIsRefused(): void
    {
        $event = $this->check([
            self::PROJECT . '/repository/files/' => [404, ''],
            self::PROJECT . '/repository/' => [200, '[{"name": "main"}]'],
            self::PROJECT => [200, '{"default_branch": "main"}'],
        ]);

        $this->assertSame('no composer.json on any branch or tag', $event->getRefusal());
        $this->assertSame(['skipped_not_a_package'], $this->decisions());
    }

    public function testASiteDeclaringProjectIsRefused(): void
    {
        $event = $this->check([
            self::PROJECT . '/repository/files/' => [200, '{"name": "acme/site", "type": "project"}'],
            self::PROJECT . '/repository/' => [200, '[]'],
            self::PROJECT => [200, '{"default_branch": "main"}'],
        ]);

        $this->assertTrue($event->isRefused());
        $this->assertSame(['skipped_type_project'], $this->decisions());
    }

    public function testAPackageIsAdded(): void
    {
        $event = $this->check([
            self::PROJECT . '/repository/files/' => [200, '{"name": "acme/thing"}'],
            self::PROJECT . '/repository/' => [200, '[]'],
            self::PROJECT => [200, '{"default_branch": "main"}'],
        ]);

        $this->assertFalse($event->isRefused());
        $this->assertSame(['registered_new_package'], $this->decisions());
    }

    public function testWhenGitLabCannotSayTheRepositoryIsAdded(): void
    {
        $event = $this->check([self::PROJECT => [502, '']]);

        $this->assertFalse($event->isRefused());
        $this->assertSame(['registered_undetermined'], $this->decisions());
    }

    public function testWithoutATokenTheRepositoryIsAdded(): void
    {
        $event = $this->check([], false);

        $this->assertFalse($event->isRefused());
        $this->assertSame(['registered_no_api_token'], $this->decisions());
    }

    public function testAnUnauthenticatedDeliveryIsRefusedBeforeGitLabIsAsked(): void
    {
        $event = $this->check([self::PROJECT => [200, '{"default_branch": "main"}']], true, false);

        $this->assertTrue($event->isRefused());
        $this->assertSame(['skipped_unauthenticated'], $this->decisions());
    }

    /**
     * @param array<string, array{0: int, 1: string}> $responses
     */
    private function check(array $responses, bool $token = true, bool $authenticated = true): RepositoryAddingEvent
    {
        $config = $this->createConfig();
        $gate = new RegistrationGate($this->createHttp($config, $responses, false, $token), new PackageTypes($config), $this->createLog());
        $event = new RepositoryAddingEvent(
            new Repository('https://git.example.com/acme/thing.git', 'vcs'),
            'gitlab',
            ['project' => ['path_with_namespace' => 'acme/thing']],
            $authenticated
        );
        $gate->onRepositoryAdding($event);

        return $event;
    }
}
