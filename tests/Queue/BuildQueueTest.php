<?php

namespace Playbloom\Tests\Queue;

use PHPUnit\Framework\TestCase;
use Playbloom\Event\BuildEvent;
use Playbloom\Model\Repository;
use Playbloom\Queue\BuildQueue;
use Symfony\Component\Filesystem\Filesystem;

class BuildQueueTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/satisfy-queue-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
        parent::tearDown();
    }

    public function testClaimEmptyQueueReturnsNull(): void
    {
        $queue = new BuildQueue($this->dir);

        $this->assertNull($queue->claim());
        $this->assertSame(0, $queue->pending());
        $this->assertFalse($queue->isBuilding());
    }

    public function testRepositoryRequestIsClaimedAndCompleted(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');

        $this->assertSame(1, $queue->pending());

        $request = $queue->claim();
        $this->assertNotNull($request);
        $this->assertFalse($request->isFull());
        $this->assertSame(['git@example.com:vendor/a.git'], $request->getRepositoryUrls());
        $this->assertSame(0, $queue->pending());
        $this->assertTrue($queue->isBuilding());

        $queue->complete($request);
        $this->assertFalse($queue->isBuilding());
    }

    public function testRepeatedRequestsForOneRepositoryMakeOneBuild(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');
        $queue->enqueue('git@example.com:vendor/a.git');
        $queue->enqueue('git@example.com:vendor/b.git');

        $this->assertSame(2, $queue->pending());

        $request = $queue->claim();
        $urls = $request->getRepositoryUrls();
        sort($urls);
        $this->assertSame(['git@example.com:vendor/a.git', 'git@example.com:vendor/b.git'], $urls);
    }

    public function testFullRequestCoversWaitingRepositories(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');
        $queue->enqueue();

        $request = $queue->claim();

        $this->assertTrue($request->isFull());
        $this->assertSame([], $request->getRepositoryUrls());
        $this->assertCount(2, $request->getEntries());
    }

    public function testUnreadableEntryMeansAFullBuild(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');
        file_put_contents($this->dir . '/broken.json', '{not json');

        $request = $queue->claim();

        $this->assertTrue($request->isFull());
    }

    public function testRecoverReturnsWhatAStoppedWorkerHadClaimed(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');
        $queue->claim();

        $restarted = new BuildQueue($this->dir);
        $restarted->recover();

        $this->assertSame(1, $restarted->pending());
        $this->assertFalse($restarted->isBuilding());
        $this->assertSame(['git@example.com:vendor/a.git'], $restarted->claim()->getRepositoryUrls());
    }

    public function testRecoverKeepsTheNewerOfTwoRequestsForOneRepository(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');
        $queue->claim();
        $queue->enqueue('git@example.com:vendor/a.git');

        $queue->recover();

        $this->assertSame(1, $queue->pending());
        $this->assertFalse($queue->isBuilding());
    }

    public function testBuildEventIsQueuedNotRun(): void
    {
        $queue = new BuildQueue($this->dir);
        $event = new BuildEvent(new Repository('git@example.com:vendor/a.git'));

        $queue->onBuild($event);

        $this->assertTrue($event->isQueued());
        $this->assertNull($event->getStatus());
        $this->assertSame(['git@example.com:vendor/a.git'], $queue->claim()->getRepositoryUrls());
    }

    public function testBuildEventWithoutRepositoryQueuesAFullBuild(): void
    {
        $queue = new BuildQueue($this->dir);

        $queue->onBuild(new BuildEvent());

        $this->assertTrue($queue->claim()->isFull());
    }
    public function testDelayedRequestWaitsUntilDue(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git', 60);

        $this->assertSame(1, $queue->pending());
        $this->assertNull($queue->claim());

        $file = $this->dir . '/later-repo-' . sha1('git@example.com:vendor/a.git') . '.json';
        $data = json_decode((string) file_get_contents($file), true);
        $data['not_before'] = time() - 1;
        file_put_contents($file, json_encode($data));

        $request = $queue->claim();
        $this->assertNotNull($request);
        $this->assertSame(['git@example.com:vendor/a.git'], $request->getRepositoryUrls());
    }

    public function testDelayedAndImmediateRequestsForOneRepositoryAreKeptApart(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');
        $queue->enqueue('git@example.com:vendor/a.git', 60);
        $queue->enqueue('git@example.com:vendor/a.git', 60);

        $this->assertSame(2, $queue->pending());

        $request = $queue->claim();
        $this->assertCount(1, $request->getEntries());
        $queue->complete($request);

        $this->assertSame(1, $queue->pending());
        $this->assertNull($queue->claim());
    }
}
