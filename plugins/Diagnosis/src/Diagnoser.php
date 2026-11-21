<?php

namespace Playbloom\Plugin\Diagnosis;

use Playbloom\Plugin\Diagnosis\Tool\BuildLog;
use Playbloom\Plugin\Diagnosis\Tool\SatisRepositories;
use Playbloom\Service\Manager;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\InputProcessor\SystemPromptInputProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\Generic\Factory;
use Symfony\AI\Platform\PlatformInterface;

/**
 * Asks a model, through Symfony AI's agent, why a kept build failed. The
 * model is given the end of the redacted log, and tools to search the rest of
 * it and to read satis.json's repositories; it is given nothing else.
 */
final class Diagnoser
{
    private const TAIL_BYTES = 6000;
    private const MAX_TOOL_CALLS = 12;

    private const PROMPT = <<<'PROMPT'
        You find why a Satis build of a private Composer repository failed.
        You are given the end of the build's log. Your tools search the whole log, read lines of it, and list the repositories and requirements in satis.json. Credentials in both are replaced with ***.

        Answer in at most 150 words, in this order:
        Cause: one sentence.
        At fault: the repository or package, or "unknown".
        Evidence: up to three lines quoted exactly from the log, with their line numbers.
        Fix: what a person should change, and where.

        Quote only what the log or the tools return. If the log does not show the cause, say so, and say what to check next.
        PROMPT;

    public function __construct(
        private readonly DiagnosisConfig $config,
        private readonly DiagnosisStore $store,
        private readonly Manager $manager,
        private readonly ?PlatformInterface $platform = null,
    ) {
    }

    public function diagnose(string $id): string
    {
        $agent = new Agent(
            $this->platform ?? Factory::createPlatform((string) $this->config->getBaseUrl(), $this->config->getApiKey()),
            (string) $this->config->getModel(),
            [new SystemPromptInputProcessor(self::PROMPT)],
            name: 'satisfy-diagnosis',
            toolbox: new Toolbox([new BuildLog($this->store->logPath($id)), new SatisRepositories($this->manager)]),
            maxToolCalls: self::MAX_TOOL_CALLS,
        );

        return trim((string) $agent->call($this->question($id))->getContent());
    }

    private function question(string $id): string
    {
        $build = $this->store->build($id);
        $log = (string) file_get_contents($this->store->logPath($id));
        $lines = substr_count($log, "\n");
        $tail = strlen($log) > self::TAIL_BYTES ? substr($log, -self::TAIL_BYTES) : $log;
        $scope = 'full' === $build['scope']
            ? 'every repository'
            : 'these repositories: ' . implode(', ', (array) $build['repositories']);

        return sprintf(
            "Build %s exited with code %d. It built %s. Its log has %d lines; the last %d bytes follow.\n\n%s",
            $id,
            $build['exit_code'],
            $scope,
            $lines,
            strlen($tail),
            $tail,
        );
    }
}
