<?php

namespace Playbloom\Plugin\Mcp;

use Mcp\Exception\ToolCallException;

/**
 * Stops a change that would stop publishing packages someone may still need,
 * unless the call says force: a package a site fetched in the last 30 days,
 * by authz's audit log, or any package when there is no way to tell.
 *
 * A package that is no longer published can still be installed from a lock
 * file while its archive stays in dist/, but composer update stops finding it.
 */
final class RemovalGuard
{
    public function __construct(private readonly PackageUsage $usage)
    {
    }

    /**
     * @param list<string> $packages the packages the change stops publishing
     *
     * @throws ToolCallException
     *
     * @return array<string, list<string>> the sites that fetched each, when known
     */
    public function check(array $packages, bool $force, string $what): array
    {
        if ([] === $packages) {
            return [];
        }
        $fetches = $this->usage->recentFetches($packages);
        if ($force) {
            return $fetches ?? [];
        }
        if (null === $fetches) {
            throw new ToolCallException(sprintf('%s would stop publishing %s, and without the authz plugin\'s audit log there is no telling who still fetches %s. Call again with force set to true to do it anyway.', $what, implode(', ', $packages), 1 === count($packages) ? 'it' : 'them'));
        }
        $used = array_filter($fetches);
        if ([] !== $used) {
            throw new ToolCallException(sprintf('%s would stop publishing packages fetched in the last %d days: %s. Call again with force set to true to do it anyway.', $what, PackageUsage::DAYS, implode('; ', array_map(static fn (string $package, array $sites): string => $package . ' by ' . implode(', ', $sites), array_keys($used), $used))));
        }

        return $fetches;
    }
}
