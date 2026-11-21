<?php

namespace Playbloom\Plugin\Authz;

/**
 * APCu, which every worker in the PHP-FPM pool shares, so one verification
 * serves a whole build. Where APCu is not enabled, as in the CLI unless
 * apc.enable_cli is on, nothing is cached.
 */
final class Cache
{
    /**
     * @param-out bool $found whether the key was in the cache; callers may pass an unset variable
     */
    public static function fetch(string $key, ?bool &$found = null): mixed
    {
        $found = false;
        if (!self::enabled()) {
            return null;
        }
        $value = apcu_fetch($key, $success);
        $found = true === $success;

        return $found ? $value : null;
    }

    public static function store(string $key, mixed $value, int $ttl): void
    {
        if (self::enabled()) {
            apcu_store($key, $value, $ttl);
        }
    }

    public static function exists(string $key): bool
    {
        return self::enabled() && apcu_exists($key);
    }

    public static function delete(string $key): void
    {
        if (self::enabled()) {
            apcu_delete($key);
        }
    }

    /**
     * Drops what a change to sites or grants makes stale: verified
     * credentials, entitlements, and the flags that keep the approval queue
     * from being written on every request. The dependency graph and the audit
     * summary are kept, since no grant changes them and both cost a full read
     * to build again; so is everything else in the pool's APCu.
     */
    public static function clear(): bool
    {
        return self::enabled() && apcu_delete(new \APCUIterator('/^authz:(?!deps:|audit:)/', APC_ITER_KEY));
    }

    public static function enabled(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }
}
