<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use Playbloom\Plugin\Diagnosis\Diagnoser;
use Playbloom\Service\Manager;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\AI\Platform\Test\MockPlatformFactory;

class DiagnoserTest extends DiagnosisTestCase
{
    use ProphecyTrait;

    public function testTheAgentSearchesTheLogAndAnswers(): void
    {
        $payloads = [];
        $platform = MockPlatformFactory::createPlatform(static function (Model $model, array|string|object $payload) use (&$payloads): ResultInterface|string {
            $payloads[] = json_encode($payload);
            if (1 === count($payloads)) {
                return new ToolCallResult([new ToolCall('call_1', 'log_search', ['pattern' => 'not writable'])]);
            }

            return 'Cause: the output directory is not writable.';
        });
        $id = $this->keepFailedBuild();

        $answer = (new Diagnoser($this->config, $this->store, $this->prophesize(Manager::class)->reveal(), $platform))->diagnose($id);

        $this->assertSame('Cause: the output directory is not writable.', $answer);
        $this->assertCount(2, $payloads);
        $this->assertStringContainsString('exited with code 1', $payloads[0]);
        $this->assertStringContainsString('6:   The archive directory is not writable.', $payloads[1], 'The tool\'s answer was not passed back');
        foreach ($payloads as $payload) {
            $this->assertStringNotContainsString('s3cret', $payload);
        }
    }
}
