<?php

namespace Playbloom\Tests\Plugin\Mcp;

use PHPUnit\Framework\TestCase;
use Playbloom\Plugin\Mcp\Tool\BuildStatus;
use Playbloom\Queue\BuildQueue;
use Playbloom\Runner\SatisBuildRunner;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class BuildStatusTest extends TestCase
{
    use ProphecyTrait;

    private const LOG = <<<'LOG'
        $ 'bin/satis' 'build' 'satis.json' 'public' '--skip-errors' '--no-ansi' '--verbose'
        Scanning packages
        Skipping Exception 'Failed to execute git clone --mirror -- https://deploy:s3cret@git.acme.test/acme/app.git /cache/vcs/app.git/
        fatal: unable to access 'https://git.acme.test/acme/app.git/': Could not resolve host: git.acme.test
        Writing web view

        Exit code 0

        LOG;

    public function testTheLastBuildIsReportedWithoutCredentials(): void
    {
        $status = $this->tool(self::LOG)(logBytes: 40);

        $this->assertSame(0, $status['exit_code']);
        $this->assertSame(['Failed to execute git clone --mirror -- https://***@git.acme.test/acme/app.git /cache/vcs/app.git/'], $status['skipped']);
        $this->assertSame(40, strlen($status['log']));
        $this->assertStringNotContainsString('s3cret', json_encode($status) ?: '');
    }

    public function testNoBuildYet(): void
    {
        $status = $this->tool('')();

        $this->assertNull($status['exit_code']);
        $this->assertSame([], $status['skipped']);
        $this->assertSame(0, $status['waited']);
    }

    private function tool(string $log): BuildStatus
    {
        $queue = $this->prophesize(BuildQueue::class);
        $queue->isBuilding()->willReturn(false);
        $queue->pending()->willReturn(0);
        $runner = $this->prophesize(SatisBuildRunner::class);
        $runner->getLastLog(Argument::any())->willReturn($log);

        return new BuildStatus($queue->reveal(), $runner->reveal());
    }
}
