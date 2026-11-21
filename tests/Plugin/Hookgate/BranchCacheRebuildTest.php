<?php

namespace Playbloom\Tests\Plugin\Hookgate;

use Playbloom\Event\BuildEvent;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Model\Repository;
use Playbloom\Plugin\Hookgate\BranchCacheRebuild;
use Playbloom\Plugin\Hookgate\HookgateConfig;
use Playbloom\Plugin\Hookgate\PackageTypes;
use Playbloom\Queue\BuildQueue;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class BranchCacheRebuildTest extends HookgateTestCase
{
    private const URL = 'https://git.example.com/vendor/a.git';

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/satisfy-rebuild-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
        parent::tearDown();
    }

    public function testBranchPushQueuesALaterBuild(): void
    {
        $queue = $this->deliver('gitlab', ['ref' => 'refs/heads/main', 'after' => 'abc']);

        $this->assertSame(2, $queue->pending());
        $this->assertFileExists($this->dir . '/later-repo-' . sha1(self::URL) . '.json');
        $this->assertSame(['rebuild_scheduled'], $this->decisions());
        $this->assertSame(35, $this->logged[0]['fields']['delay']);
    }

    public function testTagPushQueuesNothingMore(): void
    {
        $queue = $this->deliver('gitlab', ['ref' => 'refs/tags/1.0.0', 'after' => 'abc']);

        $this->assertSame(1, $queue->pending());
        $this->assertSame([], $this->decisions());
    }

    public function testDeletedBranchQueuesNothingMore(): void
    {
        $queue = $this->deliver('gitlab', ['ref' => 'refs/heads/main', 'after' => str_repeat('0', 40)]);

        $this->assertSame(1, $queue->pending());
    }

    public function testOtherProvidersQueueNothingMore(): void
    {
        $queue = $this->deliver('github', ['ref' => 'refs/heads/main', 'after' => 'abc']);

        $this->assertSame(1, $queue->pending());
    }

    public function testDelayOfZeroTurnsItOff(): void
    {
        $queue = $this->deliver('gitlab', ['ref' => 'refs/heads/main', 'after' => 'abc'], 0);

        $this->assertSame(1, $queue->pending());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function deliver(string $provider, array $payload, int $delay = HookgateConfig::REBUILD_DELAY): BuildQueue
    {
        $config = new HookgateConfig('token', 'https://git.example.com/api/v4', 'on', PackageTypes::DEFAULT, null, 'https://drupal.example/p2', $delay);
        $queue = new BuildQueue($this->dir);
        $listener = new BranchCacheRebuild($config, $queue, $this->createLog());

        $listener->onWebhookReceived(new WebhookReceivedEvent($provider, Request::create('/'), $payload, true));
        $build = new BuildEvent(new Repository(self::URL));
        $queue->onBuild($build);
        $listener->onBuild($build);

        return $queue;
    }
}
