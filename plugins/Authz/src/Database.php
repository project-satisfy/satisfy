<?php

namespace Playbloom\Plugin\Authz;

/**
 * The SQLite database of sites, entitlements and package requests.
 *
 * The schema is authz's own, unchanged, so an existing authz.db can be used
 * as it is: migrations are applied by filename, once each.
 */
final class Database
{
    public function __construct(private readonly AuthzConfig $config)
    {
    }

    public function open(bool $write = false): \SQLite3
    {
        $flags = $write ? (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE) : SQLITE3_OPEN_READONLY;
        $db = new \SQLite3($this->config->getDatabasePath(), $flags);
        // Issuing tokens writes; a verification should wait briefly rather than
        // fail and deny a legitimate build.
        $db->busyTimeout(3000);

        return $db;
    }

    /**
     * Opens read-write, creating and migrating the database when needed.
     */
    public function writable(): \SQLite3
    {
        $this->config->ensureDataDir();
        $db = $this->open(true);
        $this->migrate($db);

        return $db;
    }

    /**
     * Applies the migrations not yet recorded, in filename order.
     *
     * @return string[] the migrations applied
     */
    public function migrate(\SQLite3 $db): array
    {
        $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            name    TEXT PRIMARY KEY,
            applied INTEGER NOT NULL
        )');

        $applied = [];
        $result = $db->query('SELECT name FROM schema_migrations');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $applied[$row['name']] = true;
        }

        $files = glob(dirname(__DIR__) . '/migrations/*.sql') ?: [];
        sort($files);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }
            $db->exec('BEGIN IMMEDIATE');
            try {
                if (false === $db->exec((string) file_get_contents($file))) {
                    throw new \RuntimeException($db->lastErrorMsg());
                }
                $statement = $db->prepare('INSERT INTO schema_migrations (name, applied) VALUES (:n, :t)');
                $statement->bindValue(':n', $name, SQLITE3_TEXT);
                $statement->bindValue(':t', time(), SQLITE3_INTEGER);
                $statement->execute();
                $db->exec('COMMIT');
                $ran[] = $name;
            } catch (\Throwable $exception) {
                $db->exec('ROLLBACK');
                throw new \RuntimeException(sprintf('Migration %s failed: %s', $name, $exception->getMessage()), 0, $exception);
            }
        }

        return $ran;
    }
}
