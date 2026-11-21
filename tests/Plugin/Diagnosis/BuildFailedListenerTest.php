<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use Playbloom\Event\BuildFinishedEvent;
use Playbloom\Model\BuildRequest;
use Playbloom\Plugin\Diagnosis\BuildFailedListener;
use Playbloom\Plugin\Diagnosis\DiagnosisConfig;
use Playbloom\Plugin\Diagnosis\Launcher;
use Playbloom\Runner\SatisBuildRunner;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class BuildFailedListenerTest extends DiagnosisTestCase
{
    use ProphecyTrait;

    public function testAFailedBuildIsKeptAndItsDiagnosisStarted(): void
    {
        $launcher = $this->launcher();
        ($this->listener($this->config, $launcher))(new BuildFinishedEvent(BuildRequest::full(), 1));

        $this->assertCount(1, $launcher->launched);
        $this->assertStringNotContainsString('s3cret', (string) file_get_contents($this->store->logPath($launcher->launched[0])));
    }

    public function testASuccessfulBuildIsNotDiagnosed(): void
    {
        $launcher = $this->launcher();
        ($this->listener($this->config, $launcher))(new BuildFinishedEvent(BuildRequest::full(), 0));

        $this->assertSame([], $launcher->launched);
    }

    public function testNothingHappensWithoutAModel(): void
    {
        $launcher = $this->launcher();
        ($this->listener(new DiagnosisConfig($this->dir), $launcher))(new BuildFinishedEvent(BuildRequest::full(), 1));

        $this->assertSame([], $launcher->launched);
        $this->assertDirectoryDoesNotExist($this->dir);
    }

    private function listener(DiagnosisConfig $config, Launcher $launcher): BuildFailedListener
    {
        $runner = $this->prophesize(SatisBuildRunner::class);
        $runner->getLastLog(Argument::any())->willReturn(self::LOG);

        return new BuildFailedListener($config, $this->store, $runner->reveal(), $launcher);
    }

    private function launcher(): RecordingLauncher
    {
        return new RecordingLauncher($this->store, '/nowhere');
    }
}

/**
 * A launcher that records the ids it is given and starts nothing.
 */
final class RecordingLauncher extends Launcher
{
    /** @var list<string> */
    public array $launched = [];

    public function launch(string $id): void
    {
        $this->launched[] = $id;
    }
}
