<?php

namespace Playbloom\Plugin\Authz;

/**
 * The principals that hold tokens: sites, and anyone else, such as a
 * developer's laptop, issued one the same way.
 */
final class Sites
{
    public const NAME_PATTERN = '/^[A-Za-z0-9._-]{1,64}$/';

    public function __construct(
        private readonly Database $database,
        private readonly Token $token,
    ) {
    }

    public function exists(string $site): bool
    {
        return null !== $this->find($site);
    }

    /**
     * Issues a token, or replaces an existing site's, which stops the old one
     * working at once. The plaintext is returned once; only its hash is kept.
     */
    public function issue(string $site, ?string $note = null): string
    {
        if (1 !== preg_match(self::NAME_PATTERN, $site)) {
            throw new \InvalidArgumentException('A site name is 1 to 64 of A-Z a-z 0-9 . _ -');
        }
        $token = Token::generate();
        $db = $this->database->writable();
        $statement = $db->prepare('INSERT INTO sites (name, token_hash, active, created, updated, note)
            VALUES (:n, :h, 1, :t, :t, :note)
            ON CONFLICT(name) DO UPDATE SET token_hash = excluded.token_hash, active = 1, updated = excluded.updated');
        $statement->bindValue(':n', $site, SQLITE3_TEXT);
        $statement->bindValue(':h', $this->token->hash($token), SQLITE3_TEXT);
        $statement->bindValue(':t', time(), SQLITE3_INTEGER);
        $statement->bindValue(':note', (string) $note, SQLITE3_TEXT);
        $statement->execute();
        $db->close();

        return $token;
    }

    /**
     * Revokes (false) or restores (true) a site. Returns whether it exists.
     */
    public function setActive(string $site, bool $active): bool
    {
        $db = $this->database->writable();
        $statement = $db->prepare('UPDATE sites SET active = :a, updated = :t WHERE name = :n');
        $statement->bindValue(':a', $active ? 1 : 0, SQLITE3_INTEGER);
        $statement->bindValue(':t', time(), SQLITE3_INTEGER);
        $statement->bindValue(':n', $site, SQLITE3_TEXT);
        $statement->execute();
        $changed = $db->changes() > 0;
        $db->close();

        return $changed;
    }

    public function setNote(string $site, string $note): bool
    {
        $db = $this->database->writable();
        $statement = $db->prepare('UPDATE sites SET note = :x WHERE name = :n');
        $statement->bindValue(':x', $note, SQLITE3_TEXT);
        $statement->bindValue(':n', $site, SQLITE3_TEXT);
        $statement->execute();
        $changed = $db->changes() > 0;
        $db->close();

        return $changed;
    }

    /**
     * Deletes a site, its grants and its requests.
     */
    public function forget(string $site): void
    {
        $db = $this->database->writable();
        foreach (['sites WHERE name', 'entitlements WHERE site', 'package_requests WHERE site'] as $where) {
            $statement = $db->prepare('DELETE FROM ' . $where . ' = :n');
            $statement->bindValue(':n', $site, SQLITE3_TEXT);
            $statement->execute();
        }
        $db->close();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $site): ?array
    {
        $db = $this->database->writable();
        $statement = $db->prepare('SELECT name, active, created, updated, note, package_enforce FROM sites WHERE name = :n');
        $statement->bindValue(':n', $site, SQLITE3_TEXT);
        $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);
        $db->close();

        return is_array($row) ? $row : null;
    }

    /**
     * Every site, with its grant count and open requests.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $db = $this->database->writable();
        $result = $db->query('SELECT s.name, s.active, s.created, s.updated, s.note, s.package_enforce,
                (SELECT COUNT(*) FROM entitlements e WHERE e.site = s.name) AS grants,
                (SELECT COUNT(*) FROM package_requests r WHERE r.site = s.name AND r.resolved = 0) AS pending
            FROM sites s ORDER BY s.name');
        $sites = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $sites[(string) $row['name']] = $row;
        }
        $db->close();

        return $sites;
    }

    /**
     * Whether a token is the site's, and whether the site is active.
     *
     * @return array{exists: bool, matches: bool, active: bool}
     */
    public function check(string $site, string $token): array
    {
        $row = null;
        $db = $this->database->writable();
        $statement = $db->prepare('SELECT token_hash, active FROM sites WHERE name = :n');
        $statement->bindValue(':n', $site, SQLITE3_TEXT);
        $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);
        $db->close();
        if (!is_array($row)) {
            return ['exists' => false, 'matches' => false, 'active' => false];
        }

        return [
            'exists' => true,
            'matches' => hash_equals((string) $row['token_hash'], $this->token->hash($token)),
            'active' => 1 === (int) $row['active'],
        ];
    }
}
