<?php

namespace Playbloom\Plugin\Authz;

/**
 * Which packages a site may have, and the queue of packages sites asked for
 * without being granted them.
 */
final class Entitlement
{
    public const ALLOW = 'allow';

    /**
     * Refused, and recorded for approval.
     */
    public const DENY = 'deny';

    /**
     * Would have been refused, but the site's package mode is observe.
     */
    public const OBSERVE = 'observe';

    public function __construct(
        private readonly Database $database,
        private readonly Deps $deps,
        private readonly Audit $audit,
    ) {
    }

    /**
     * The packages granted directly, before dependencies are added.
     *
     * @return string[]
     */
    public function granted(\SQLite3 $db, string $site): array
    {
        $statement = $db->prepare('SELECT package FROM entitlements WHERE site = :s ORDER BY package');
        $statement->bindValue(':s', $site, SQLITE3_TEXT);
        $result = $statement->execute();
        $packages = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $packages[] = (string) $row['package'];
        }

        return $packages;
    }

    /**
     * Every package the site may reach: its grants, a vendor/* grant expanded
     * over the hosted packages, and the dependencies of all of them.
     *
     * @return array<string, true>
     */
    public function effective(\SQLite3 $db, string $site): array
    {
        $direct = $this->granted($db, $site);
        if ([] === $direct) {
            return [];
        }

        $expanded = [];
        $prefixes = [];
        foreach ($direct as $package) {
            if (str_ends_with($package, '/*')) {
                $prefixes[] = substr($package, 0, -1);
            } else {
                $expanded[] = $package;
            }
        }
        if ([] !== $prefixes) {
            foreach ($this->deps->hosted() as $hosted) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($hosted, $prefix)) {
                        $expanded[] = $hosted;
                    }
                }
            }
        }

        return $this->deps->closure($expanded);
    }

    public function enforcedFor(\SQLite3 $db, string $site): bool
    {
        $statement = $db->prepare('SELECT package_enforce FROM sites WHERE name = :s');
        $statement->bindValue(':s', $site, SQLITE3_TEXT);
        $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);

        return is_array($row) && 1 === (int) $row['package_enforce'];
    }

    /**
     * Decides one site's access to one package. Deny by default; refusals are
     * recorded in both modes, so the approval queue fills before enforcement.
     *
     * @return string one of ALLOW, DENY and OBSERVE
     */
    public function check(string $site, string $package): string
    {
        $set = Cache::fetch('authz:ent:' . $site, $found);
        $enforce = Cache::fetch('authz:entmode:' . $site, $modeFound);

        if (!$found || !$modeFound) {
            try {
                $db = $this->database->open();
                $set = $this->effective($db, $site);
                $enforce = $this->enforcedFor($db, $site);
                $db->close();
            } catch (\Throwable $exception) {
                $this->audit->write(['event' => 'entitlement_error', 'site' => $site, 'error' => $exception->getMessage()]);

                // The credential is already valid; a database fault should not break a build.
                return self::ALLOW;
            }
            Cache::store('authz:ent:' . $site, $set, AuthzConfig::CACHE_TTL);
            Cache::store('authz:entmode:' . $site, $enforce, AuthzConfig::CACHE_TTL);
        }

        if (isset($set[$package])) {
            return self::ALLOW;
        }

        $this->flag($site, $package);

        return $enforce ? self::DENY : self::OBSERVE;
    }

    /**
     * Records that a site asked for a package it is not granted: one row per
     * site and package, however often it asks.
     */
    public function flag(string $site, string $package): void
    {
        $guard = 'authz:flagged:' . $site . ':' . $package;
        if (Cache::exists($guard)) {
            return;
        }
        Cache::store($guard, true, AuthzConfig::CACHE_TTL);
        try {
            $db = $this->database->open(true);
            $statement = $db->prepare('INSERT INTO package_requests (site, package, first_seen, last_seen, resolved)
                VALUES (:s, :p, :t, :t, 0)
                ON CONFLICT(site, package) DO UPDATE SET last_seen = :t');
            $statement->bindValue(':s', $site, SQLITE3_TEXT);
            $statement->bindValue(':p', $package, SQLITE3_TEXT);
            $statement->bindValue(':t', time(), SQLITE3_INTEGER);
            $statement->execute();
            $db->close();
        } catch (\Throwable $exception) {
            $this->audit->write(['event' => 'flag_error', 'site' => $site, 'package' => $package, 'error' => $exception->getMessage()]);
        }
    }

    /**
     * Grants a package, or a vendor/*, and resolves any request for it.
     */
    public function grant(\SQLite3 $db, string $site, string $package, string $by): void
    {
        $package = strtolower(trim($package));
        $statement = $db->prepare('INSERT OR IGNORE INTO entitlements (site, package, granted, by_whom) VALUES (:s, :p, :t, :b)');
        $statement->bindValue(':s', $site, SQLITE3_TEXT);
        $statement->bindValue(':p', $package, SQLITE3_TEXT);
        $statement->bindValue(':t', time(), SQLITE3_INTEGER);
        $statement->bindValue(':b', $by, SQLITE3_TEXT);
        $statement->execute();

        $this->resolve($db, $site, $package);
        $this->invalidate($site);
    }

    public function revoke(\SQLite3 $db, string $site, string $package): int
    {
        $statement = $db->prepare('DELETE FROM entitlements WHERE site = :s AND package = :p');
        $statement->bindValue(':s', $site, SQLITE3_TEXT);
        $statement->bindValue(':p', strtolower(trim($package)), SQLITE3_TEXT);
        $statement->execute();
        $changed = $db->changes();
        $this->invalidate($site);

        return $changed;
    }

    /**
     * Requests not yet approved or dismissed.
     *
     * @return array<int, array{site: string, package: string, first_seen: int, last_seen: int}>
     */
    public function openRequests(\SQLite3 $db, ?string $site = null): array
    {
        $sql = 'SELECT site, package, first_seen, last_seen FROM package_requests WHERE resolved = 0';
        if (null !== $site) {
            $sql .= ' AND site = :s';
        }
        $statement = $db->prepare($sql . ' ORDER BY site, package');
        if (null !== $site) {
            $statement->bindValue(':s', $site, SQLITE3_TEXT);
        }
        $result = $statement->execute();
        $requests = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $requests[] = $row;
        }

        return $requests;
    }

    /**
     * Resolves a request without granting it.
     */
    public function dismiss(\SQLite3 $db, string $site, string $package): void
    {
        $this->resolve($db, $site, strtolower(trim($package)));
    }

    /**
     * Sets a site's package mode: enforce (refuse with 403) or observe.
     */
    public function setEnforce(\SQLite3 $db, string $site, bool $on): int
    {
        $statement = $db->prepare('UPDATE sites SET package_enforce = :v WHERE name = :s');
        $statement->bindValue(':v', $on ? 1 : 0, SQLITE3_INTEGER);
        $statement->bindValue(':s', $site, SQLITE3_TEXT);
        $statement->execute();
        $changed = $db->changes();
        $this->invalidate($site);

        return $changed;
    }

    /**
     * Drops a site's cached entitlements, in this process's cache only: the
     * command line cannot reach PHP-FPM's, which purge.php or the TTL clears.
     */
    public function invalidate(string $site): void
    {
        Cache::delete('authz:ent:' . $site);
        Cache::delete('authz:entmode:' . $site);
    }

    private function resolve(\SQLite3 $db, string $site, string $package): void
    {
        $statement = $db->prepare('UPDATE package_requests SET resolved = 1 WHERE site = :s AND package = :p');
        $statement->bindValue(':s', $site, SQLITE3_TEXT);
        $statement->bindValue(':p', $package, SQLITE3_TEXT);
        $statement->execute();
    }
}
