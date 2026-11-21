<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Queue\BuildQueue;
use Playbloom\Runner\SatisBuildRunner;
use Playbloom\Service\LogRedactor;

/**
 * The build queue and the last build, as the admin UI's build page shows
 * them, with the log's credentials redacted. It can wait for the queue to
 * empty, so that a client which queued a build can see how it ended.
 */
final class BuildStatus
{
    private const MAX_LOG_BYTES = 65536;
    private const MAX_WAIT = 30;
    private const SCAN_BYTES = 262144;
    private const MAX_SKIPPED = 20;

    public function __construct(
        private readonly BuildQueue $queue,
        private readonly SatisBuildRunner $runner,
    ) {
    }

    /**
     * @return array{building: bool, pending: int, waited: int, exit_code: int|null, skipped: list<string>, log: string}
     */
    #[McpTool(
        name: 'build_status',
        description: 'Whether the worker is running a build and how many build requests wait, then the last build: its exit code (0 for success, null before any build), the repositories it skipped because they could not be read (Satisfy builds with --skip-errors, so those do not fail it), and the end of its log. Credentials are replaced with ***.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(
        #[Schema(description: 'How much of the last build\'s log to return, in bytes counted from its end.', minimum: 0, maximum: self::MAX_LOG_BYTES)]
        int $logBytes = 8192,
        #[Schema(description: 'Wait up to this many seconds for the queue to empty and the build to finish before answering; 0 answers at once.', minimum: 0, maximum: self::MAX_WAIT)]
        int $waitSeconds = 0,
    ): array {
        $started = time();
        $deadline = $started + max(0, min($waitSeconds, self::MAX_WAIT));
        while (($this->queue->isBuilding() || $this->queue->pending() > 0) && time() < $deadline) {
            usleep(500000);
        }

        $log = LogRedactor::redact($this->runner->getLastLog(self::SCAN_BYTES));
        preg_match_all('/^Exit code (\d+)$/m', $log, $codes);
        preg_match_all("/^Skipping Exception '(.+)$/m", $log, $skipped);

        return [
            'building' => $this->queue->isBuilding(),
            'pending' => $this->queue->pending(),
            'waited' => time() - $started,
            'exit_code' => [] === $codes[1] ? null : (int) end($codes[1]),
            'skipped' => array_slice(array_values(array_unique($skipped[1])), 0, self::MAX_SKIPPED),
            'log' => substr($log, -max(1, min($logBytes, self::MAX_LOG_BYTES))),
        ];
    }
}
