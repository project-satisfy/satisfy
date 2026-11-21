<?php

namespace Playbloom\Plugin\Hookgate;

/**
 * One JSON line per decision, on standard error, where the container's log
 * collects it: docker logs <container> 2>&1 | grep auto_require
 */
class HookgateLog
{
    /** @var resource|null */
    private $stream;

    public function __construct(private readonly string $target = 'php://stderr')
    {
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function write(string $decision, array $fields = []): void
    {
        $line = (string) json_encode(['ts' => gmdate('c'), 'decision' => $decision] + $fields, JSON_UNESCAPED_SLASHES);
        $this->stream ??= @fopen($this->target, 'w') ?: null;
        if (null === $this->stream) {
            error_log($line);

            return;
        }
        fwrite($this->stream, $line . "\n");
    }
}
