<?php

namespace Playbloom\Plugin\Authz;

/**
 * The append-only audit log, one JSON object per line.
 */
final class Audit
{
    public function __construct(private readonly AuthzConfig $config)
    {
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function write(array $entry): void
    {
        $entry += ['ts' => time()];
        // LOCK_EX: concurrent builds append at once, and unlocked appends
        // interleave into unparseable half-lines.
        @file_put_contents(
            $this->config->getAuditPath(),
            json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Each site's authenticated requests (count and last time), and how many
     * requests came without a valid token, canary probes excluded.
     *
     * The log grows by a line per catalogue request, so the summary so far is
     * kept in the cache with where it stopped, and only what was appended
     * since is read. A log rotated or truncated meanwhile is read again whole.
     *
     * @return array{observed: array<string, array{count: int, last: int}>, unauth: int}
     */
    public function summary(): array
    {
        $summary = ['observed' => [], 'unauth' => 0];
        $path = $this->config->getAuditPath();
        $handle = is_readable($path) ? fopen($path, 'r') : false;
        if (false === $handle) {
            return $summary;
        }

        $stat = fstat($handle);
        $inode = is_array($stat) ? (int) $stat['ino'] : 0;
        $size = is_array($stat) ? (int) $stat['size'] : 0;
        $key = 'authz:audit:' . md5($path);
        $offset = 0;
        $cached = Cache::fetch($key, $found);
        if ($found && is_array($cached) && $cached['inode'] === $inode && $cached['offset'] <= $size
            && $cached['head'] === self::head($handle, $cached['offset'])) {
            ['offset' => $offset, 'summary' => $summary] = $cached;
        }

        fseek($handle, $offset);
        while (false !== $line = fgets($handle)) {
            if (!str_ends_with($line, "\n")) {
                // Still being written; read it next time.
                break;
            }
            $offset += strlen($line);
            $entry = json_decode($line, true);
            if (!is_array($entry) || !array_key_exists('ok', $entry)) {
                continue;
            }
            if (true === $entry['ok']) {
                $site = (string) $entry['site'];
                $summary['observed'][$site]['count'] = ($summary['observed'][$site]['count'] ?? 0) + 1;
                $summary['observed'][$site]['last'] = max($summary['observed'][$site]['last'] ?? 0, (int) ($entry['ts'] ?? 0));
            } elseif (empty($entry['canary'])) {
                ++$summary['unauth'];
            }
        }
        Cache::store($key, ['inode' => $inode, 'offset' => $offset, 'head' => self::head($handle, $offset), 'summary' => $summary], 0);
        fclose($handle);

        return $summary;
    }

    /**
     * @return string[] the last $count lines
     */
    public function tail(int $count): array
    {
        $handle = $count > 0 && is_readable($this->config->getAuditPath()) ? fopen($this->config->getAuditPath(), 'r') : false;
        if (false === $handle) {
            return [];
        }

        // Back from the end, a block at a time, until $count whole lines are in.
        $blocks = [];
        $newlines = 0;
        $stat = fstat($handle);
        $position = is_array($stat) ? (int) $stat['size'] : 0;
        while ($position > 0 && $newlines <= $count) {
            $length = min(65536, $position);
            $position -= $length;
            fseek($handle, $position);
            $block = (string) fread($handle, $length);
            $newlines += substr_count($block, "\n");
            $blocks[] = $block;
        }
        fclose($handle);

        $lines = explode("\n", implode('', array_reverse($blocks)));
        if ($position > 0) {
            // It starts part-way through a line.
            array_shift($lines);
        }

        return array_slice(array_values(array_filter($lines, static fn (string $line): bool => '' !== $line)), -$count);
    }

    /**
     * A digest of the log's first bytes, up to $length of them, which change
     * only when the log is replaced.
     *
     * @param resource $handle
     */
    private static function head($handle, int $length): string
    {
        $length = min($length, 4096);
        if ($length <= 0) {
            return '';
        }
        $position = ftell($handle);
        fseek($handle, 0);
        $head = md5((string) fread($handle, $length));
        fseek($handle, (int) $position);

        return $head;
    }
}
