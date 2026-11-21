<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use Playbloom\Plugin\Diagnosis\Command\DiagnoseCommand;
use Playbloom\Plugin\Diagnosis\Diagnoser;
use Playbloom\Service\Manager;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DiagnoseCommandTest extends DiagnosisTestCase
{
    use ProphecyTrait;

    public function testTheAnswerIsKeptAsTheLastDiagnosis(): void
    {
        $id = $this->keepFailedBuild();
        $tester = $this->tester(static fn (): string => 'Cause: the output directory is not writable.');

        $this->assertSame(Command::SUCCESS, $tester->execute(['id' => $id]));
        $last = $this->store->last();
        $this->assertSame('done', $last['status']);
        $this->assertSame('Cause: the output directory is not writable.', $last['diagnosis']);
        $this->assertSame('test-model', $last['model']);
        $this->assertSame($id, $last['build']['id']);
    }

    public function testAFailureIsKeptRedacted(): void
    {
        $id = $this->keepFailedBuild();
        $tester = $this->tester(static fn (): never => throw new \RuntimeException('POST https://key:s3cret@models.test failed'));

        $this->assertSame(Command::FAILURE, $tester->execute(['id' => $id]));
        $last = $this->store->last();
        $this->assertSame('failed', $last['status']);
        $this->assertStringContainsString('https://***@models.test', $last['error']);
        $this->assertStringNotContainsString('s3cret', $last['error'] . $tester->getDisplay());
    }

    public function testItRefusesToRunWithComposerAuth(): void
    {
        $id = $this->keepFailedBuild();
        $tester = $this->tester(fn (): string => $this->fail('The model was asked'));

        $_SERVER['COMPOSER_AUTH'] = '{}';
        try {
            $this->assertSame(Command::FAILURE, $tester->execute(['id' => $id]));
        } finally {
            unset($_SERVER['COMPOSER_AUTH']);
        }
        $this->assertStringContainsString('COMPOSER_AUTH is set', $tester->getDisplay());
        $this->assertNull($this->store->last());
    }

    private function tester(\Closure $model): CommandTester
    {
        $diagnoser = new Diagnoser($this->config, $this->store, $this->prophesize(Manager::class)->reveal(), MockPlatformFactory::createPlatform($model));

        return new CommandTester(new DiagnoseCommand($this->config, $this->store, $diagnoser));
    }
}
