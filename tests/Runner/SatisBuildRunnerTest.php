<?php

namespace Playbloom\Tests\Runner;

use PHPUnit\Framework\TestCase;
use Playbloom\Event\BuildFinishedEvent;
use Playbloom\Event\BuildStartingEvent;
use Playbloom\Model\BuildRequest;
use Playbloom\Model\Configuration;
use Playbloom\Process\ProcessFactory;
use Playbloom\Runner\SatisBuildRunner;
use Playbloom\Service\Manager;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Process\Process;

class SatisBuildRunnerTest extends TestCase
{
    use ProphecyTrait;

    private string $logFile;

    private EventDispatcher $dispatcher;

    /** @var ObjectProphecy<ProcessFactory> */
    private ObjectProphecy $processFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = sys_get_temp_dir() . '/satisfy-runner-' . bin2hex(random_bytes(4)) . '/build.log';
        $this->dispatcher = new EventDispatcher();
        $this->processFactory = $this->prophesize(ProcessFactory::class);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(dirname($this->logFile));
        parent::tearDown();
    }

    public function testFullBuildHasNoRepositoryFilter(): void
    {
        $command = $this->createRunner()->getCommandLine(BuildRequest::full());

        $this->assertSame(
            ['bin/satis', 'build', 'satis.json', 'public', '--skip-errors', '--no-ansi', '--verbose'],
            $command
        );
    }

    public function testRepositoriesAreBuiltTogether(): void
    {
        $request = BuildRequest::forRepositories(['git@example.com:vendor/a.git', 'git@example.com:vendor/b.git']);

        $command = $this->createRunner()->getCommandLine($request);

        $this->assertSame([
            'bin/satis', 'build', 'satis.json', 'public', '--skip-errors', '--no-ansi', '--verbose',
            '--repository-url=git@example.com:vendor/a.git',
            '--repository-url=git@example.com:vendor/b.git',
            '--repository-strict',
        ], $command);
    }

    public function testBuildRunsSatisAndReportsTheExitCode(): void
    {
        $process = $this->prophesize(Process::class);
        $process->getCommandLine()->willReturn("'bin/satis' 'build'");
        $process
            ->run(Argument::type('callable'))
            ->will(function ($args) {
                $args[0](Process::OUT, "Scanning packages\n");

                return 2;
            });
        $this->processFactory
            ->create(Argument::type('array'), 600)
            ->willReturn($process->reveal());

        $finished = null;
        $this->dispatcher->addListener(BuildFinishedEvent::class, function (BuildFinishedEvent $event) use (&$finished) {
            $finished = $event;
        });

        $output = '';
        $result = $this->createRunner()->build(BuildRequest::full(), function (string $buffer) use (&$output) {
            $output .= $buffer;
        });

        $this->assertSame(2, $result->getExitCode());
        $this->assertFalse($result->isSuccessful());
        $this->assertSame("Scanning packages\n", $output);
        $this->assertNotNull($finished);
        $this->assertSame(2, $finished->getExitCode());

        $log = $this->createRunner()->getLastLog();
        $this->assertStringContainsString("Scanning packages\n", $log);
        $this->assertStringContainsString('Exit code 2', $log);
    }

    public function testCancelledBuildNeverRunsSatis(): void
    {
        $this->processFactory->create(Argument::cetera())->shouldNotBeCalled();
        $this->dispatcher->addListener(BuildStartingEvent::class, function (BuildStartingEvent $event) {
            $event->cancel('satis.json failed its checks');
        });

        $result = $this->createRunner()->build(BuildRequest::full());

        $this->assertTrue($result->isCancelled());
        $this->assertNull($result->getExitCode());
        $this->assertSame('satis.json failed its checks', $result->getCancelReason());
        $this->assertStringContainsString('Build cancelled', $this->createRunner()->getLastLog());
    }

    private function createRunner(): SatisBuildRunner
    {
        $manager = $this->prophesize(Manager::class);
        $manager->reload()->willReturn(new Configuration());

        return new SatisBuildRunner(
            'satis.json',
            new LockFactory(new InMemoryStore()),
            $this->processFactory->reveal(),
            $manager->reveal(),
            $this->dispatcher,
            600,
            $this->logFile,
        );
    }
}
