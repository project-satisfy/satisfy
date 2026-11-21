<?php

namespace Playbloom\Plugin\Mcp;

use Playbloom\Plugin\Authz\AuthzConfig;

/**
 * Who fetched a package lately, from authz's audit log, which records every
 * catalogue request with the site that made it and the package it was for.
 * Without the authz plugin, or with an audit log too long to read back that
 * far, the answer is unknown.
 */
final class PackageUsage
{
    public const DAYS = 30;

    private const BLOCK = 65536;
    private const MAX_BYTES = 64 * 1024 * 1024;

    public function __construct(private readonly ?AuthzConfig $authz = null)
    {
    }

    /**
     * The sites that fetched any of $packages in the last $days days, by
     * package, or null when that cannot be known.
     *
     * @param list<string> $packages
     *
     * @return array<string, list<string>>|null
     */
    public function recentFetches(array $packages, int $days = self::DAYS): ?array
    {
        $path = $this->authz?->getAuditPath();
        if (null === $path) {
            return null;
        }
        $fetches = array_fill_keys($packages, []);
        if (!is_file($path)) {
            return $fetches;
        }
        $handle = fopen($path, 'r');
        if (false === $handle) {
            return null;
        }

        $since = time() - $days * 86400;
        $position = (int) (fstat($handle)['size'] ?? 0);
        $read = 0;
        $carry = '';
        $reachedSince = false;
        // Back from the end, a block at a time, until an entry older than
        // $since, the start of the file, or MAX_BYTES.
        while ($position > 0 && !$reachedSince && $read < self::MAX_BYTES) {
            $length = min(self::BLOCK, $position);
            $position -= $length;
            $read += $length;
            fseek($handle, $position);
            $lines = explode("\n", (string) fread($handle, $length) . $carry);
            $carry = $position > 0 ? (string) array_shift($lines) : '';
            foreach (array_reverse($lines) as $line) {
                $entry = json_decode($line, true);
                if (!is_array($entry)) {
                    continue;
                }
                if ((int) ($entry['ts'] ?? 0) < $since) {
                    $reachedSince = true;
                    break;
                }
                $package = $entry['package'] ?? null;
                if (true === ($entry['allowed'] ?? false) && is_string($package) && isset($fetches[$package])) {
                    $fetches[$package][] = (string) ($entry['site'] ?? 'unauthenticated');
                }
            }
        }
        fclose($handle);
        if (!$reachedSince && $position > 0) {
            return null;
        }

        return array_map(static fn (array $sites): array => array_values(array_unique($sites)), $fetches);
    }
}
