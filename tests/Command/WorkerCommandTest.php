<?php

namespace Playbloom\Tests\Command;

use PHPUnit\Framework\TestCase;
use Playbloom\Command\WorkerCommand;
use Playbloom\Model\BuildRequest;
use Playbloom\Model\BuildResult;
use Playbloom\Queue\BuildQueue;
use Playbloom\Runner\SatisBuildRunner;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class WorkerCommandTest extends TestCase
{
    use ProphecyTrait;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/satisfy-worker-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
        parent::tearDown();
    }

    public function testOnceRunsWhatIsQueuedAndEmptiesTheQueue(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');

        $runner = $this->prophesize(SatisBuildRunner::class);
        $runner
            ->build(Argument::that(function (BuildRequest $request) {
                return ['git@example.com:vendor/a.git'] === $request->getRepositoryUrls();
            }), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn(new BuildResult(0, 1.25));

        $tester = new CommandTester(new WorkerCommand($queue, $runner->reveal()));
        $exitCode = $tester->execute(['--once' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, $queue->pending());
        $this->assertFalse($queue->isBuilding());

        $summary = json_decode(trim($tester->getDisplay()), true);
        $this->assertSame('repositories', $summary['scope']);
        $this->assertSame(['git@example.com:vendor/a.git'], $summary['repositories']);
        $this->assertSame(0, $summary['exit_code']);
        $this->assertNull($summary['error']);
    }

    public function testRequestsLeftByAStoppedWorkerAreRunOnStart(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue();
        $queue->claim();

        $runner = $this->prophesize(SatisBuildRunner::class);
        $runner
            ->build(Argument::that(fn (BuildRequest $request) => $request->isFull()), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn(new BuildResult(0, 0.5));

        $tester = new CommandTester(new WorkerCommand(new BuildQueue($this->dir), $runner->reveal()));
        $tester->execute(['--once' => true]);

        $this->assertFalse($queue->isBuilding());
    }

    public function testAFailingBuildIsReportedAndNotRetriedForever(): void
    {
        $queue = new BuildQueue($this->dir);
        $queue->enqueue('git@example.com:vendor/a.git');

        $runner = $this->prophesize(SatisBuildRunner::class);
        $runner
            ->build(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(new \RuntimeException('satis.json is not valid JSON'));

        $tester = new CommandTester(new WorkerCommand($queue, $runner->reveal()));
        $tester->execute(['--once' => true]);

        $summary = json_decode(trim($tester->getDisplay()), true);
        $this->assertSame('satis.json is not valid JSON', $summary['error']);
        $this->assertSame(0, $queue->pending());
        $this->assertFalse($queue->isBuilding());
    }
}
