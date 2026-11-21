<?php

namespace Playbloom\Tests\Plugin\Hookgate;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Model\Configuration;
use Playbloom\Persister\FilePersister;
use Playbloom\Persister\JsonPersister;
use Playbloom\Plugin\Hookgate\AutoRequire;
use Playbloom\Plugin\Hookgate\Checker;
use Playbloom\Plugin\Hookgate\HookgateConfig;
use Playbloom\Plugin\Hookgate\PackageRules;
use Playbloom\Plugin\Hookgate\PackageTypes;
use Playbloom\Queue\BuildQueue;
use Playbloom\Service\Manager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class AutoRequireTest extends HookgateTestCase
{
    private const URL = 'https://git.example.com/acme/thing.git';
    private const FILES = '/projects/acme%2Fthing/repository/files/composer%2Ejson/raw?ref=1.0.0';
    private const TREE = '/projects/acme%2Fthing/repository/tree';

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = vfsStream::setup();
        file_put_contents($this->satisFile(), (string) file_get_contents(__DIR__ . '/../../fixtures/satis-minimal.json'));
        self::bootKernel();
        $queue = $this->queue();
        $queue->recover();
        while (null !== $leftover = $queue->claim()) {
            $queue->complete($leftover);
        }
    }

    public function testObserveLogsWhatItWouldAddAndWritesNothing(): void
    {
        $before = (string) file_get_contents($this->satisFile());
        $event = $this->receive($this->createAutoRequire('observe', $this->package('acme/thing')));

        $this->assertNull($event->getResponse());
        $this->assertSame(['auto_require_would_add'], $this->decisions());
        $this->assertSame($before, file_get_contents($this->satisFile()));
    }

    public function testOnAnswersFirstThenWritesAndQueuesTheBuild(): void
    {
        $autoRequire = $this->createAutoRequire('on', $this->package('acme/thing'));
        $event = $this->receive($autoRequire);

        $this->assertSame(202, $event->getResponse()?->getStatusCode());
        $this->assertArrayNotHasKey('acme/thing', $this->readSatis()['require'] ?? [], 'Written before the response');

        $autoRequire->onTerminate();

        $satis = $this->readSatis();
        $this->assertSame('*', $satis['require']['acme/thing'] ?? null);
        $this->assertContains(self::URL, array_column($satis['repositories'], 'url'));
        $this->assertSame(['auto_require_added'], $this->decisions());
        $this->assertSame([self::URL], $this->queue()->claim()?->getRepositoryUrls());
    }

    public function testATagOnAPublishedPackageNeedsNothing(): void
    {
        $this->createAutoRequire('on', $this->package('acme/thing'))->apply(self::URL, 'acme/thing');
        $this->logged = [];

        $event = $this->receive($this->createAutoRequire('on', $this->package('acme/thing')));

        $this->assertNull($event->getResponse());
        $this->assertSame([], $this->decisions());
    }

    public function testExpectedRefusalsAreLoggedAsSkipped(): void
    {
        $this->receive($this->createAutoRequire('on', $this->package('acme/site', ['type' => 'project'])));
        $this->receive($this->createAutoRequire('on', $this->package('drupal/views'), true));

        $this->assertSame(['auto_require_skipped', 'auto_require_skipped'], $this->decisions());
        $this->assertSame(['type_project', 'served_by_drupal_org'], array_map(fn ($entry) => $entry['fields']['reason'], $this->logged));
    }

    public function testAVersionMismatchIsRefused(): void
    {
        $event = $this->receive($this->createAutoRequire('on', $this->package('acme/thing', ['version' => '0.9.0'])));

        $this->assertNull($event->getResponse());
        $this->assertSame('version_mismatch', $this->logged[0]['fields']['reason']);
    }

    public function testABlockingCheckWritesNothing(): void
    {
        $before = (string) file_get_contents($this->satisFile());
        $autoRequire = $this->createAutoRequire('on', $this->package('acme/thing'), false, [1, '  COLLISION acme/thing']);
        $this->receive($autoRequire);
        $autoRequire->onTerminate();

        $this->assertSame($before, file_get_contents($this->satisFile()));
        $this->assertSame('check_blocking', $this->logged[0]['fields']['reason']);
        $this->assertNull($this->queue()->claim(), 'A repository not in the catalogue was queued');
    }

    /**
     * Without GITLAB_SECRET anyone could send the delivery, and the project URL
     * in it picks the host the API token goes to.
     */
    public function testAnUnauthenticatedDeliveryIsRefusedBeforeGitLabIsAsked(): void
    {
        $before = file_get_contents($this->satisFile());
        $autoRequire = $this->createAutoRequire('observe', $this->package('acme/thing'));
        $event = $this->receive($autoRequire, false);
        $autoRequire->onTerminate();

        $this->assertNull($event->getResponse());
        $this->assertSame(['auto_require_refused'], $this->decisions());
        $this->assertSame('unauthenticated', $this->logged[0]['fields']['reason']);
        $this->assertSame($before, file_get_contents($this->satisFile()));
    }

    public function testDeliveriesThatAreNotGitLabTagPushesAreIgnored(): void
    {
        $autoRequire = $this->createAutoRequire('on', $this->package('acme/thing'));
        $event = new WebhookReceivedEvent('gitlab', Request::create('/'), ['ref' => 'refs/heads/main', 'project' => $this->project()]);
        $autoRequire->onWebhookReceived($event);
        $event = new WebhookReceivedEvent('github', Request::create('/'), ['ref' => 'refs/tags/1.0.0', 'after' => 'abc', 'project' => $this->project()]);
        $autoRequire->onWebhookReceived($event);

        $this->assertSame([], $this->decisions());
    }

    /**
     * @param array<string, array{0: int, 1: string}> $responses
     * @param array{0: int, 1: string}|null           $check
     */
    private function createAutoRequire(string $mode, array $responses, bool $drupalOrgServes = false, ?array $check = null): AutoRequire
    {
        $config = $this->createConfig($mode);
        $types = new PackageTypes($config);
        $checker = new class($config, $types, $check) extends Checker {
            public function __construct(HookgateConfig $config, PackageTypes $types, private readonly ?array $result)
            {
                parent::__construct($config, $types);
            }

            public function check(string $candidateJson, string $projectUrl): array
            {
                return $this->result ?? [0, ''];
            }
        };

        return new AutoRequire(
            $config,
            $this->createHttp($config, $responses, $drupalOrgServes),
            new PackageRules($types),
            $checker,
            $this->manager(),
            new JsonPersister(new FilePersister(new Filesystem(), $this->satisFile(), $this->root->url()), self::getContainer()->get('serializer'), Configuration::class),
            $this->queue(),
            $this->createLog()
        );
    }

    /**
     * The API answers for a tag 1.0.0 whose composer.json has this name.
     *
     * @param array<string, string> $composer
     *
     * @return array<string, array{0: int, 1: string}>
     */
    private function package(string $name, array $composer = []): array
    {
        return [
            self::FILES => [200, (string) json_encode(['name' => $name] + $composer)],
            self::TREE => [200, '[{"type": "blob", "name": "composer.json"}]'],
        ];
    }

    private function receive(AutoRequire $autoRequire, bool $authenticated = true): WebhookReceivedEvent
    {
        $event = new WebhookReceivedEvent('gitlab', Request::create('/'), [
            'object_kind' => 'tag_push',
            'ref' => 'refs/tags/1.0.0',
            'after' => 'b5f1b6a8',
            'project' => $this->project(),
        ], $authenticated);
        $autoRequire->onWebhookReceived($event);

        return $event;
    }

    private function project(): array
    {
        return ['git_http_url' => self::URL, 'path_with_namespace' => 'acme/thing'];
    }

    private function manager(): Manager
    {
        return self::getContainer()->get(Manager::class);
    }

    private function queue(): BuildQueue
    {
        return self::getContainer()->get(BuildQueue::class);
    }

    private function satisFile(): string
    {
        return $this->root->url() . '/satis.json';
    }

    private function readSatis(): array
    {
        return json_decode((string) file_get_contents($this->satisFile()), true);
    }
}
