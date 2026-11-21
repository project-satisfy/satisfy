<?php

namespace Playbloom\Plugin\Authz;

/**
 * Site tokens: generation, hashing and verification.
 */
final class Token
{
    public function __construct(
        private readonly AuthzConfig $config,
        private readonly Database $database,
        private readonly Audit $audit,
    ) {
    }

    /**
     * SHA-256 with a pepper rather than a slow password hash: these are 32
     * random bytes, not passwords, and one Composer update checks one hundreds
     * of times.
     */
    public function hash(string $token): string
    {
        return hash('sha256', $this->config->getPepper() . $token);
    }

    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * The active site an HTTP Basic Authorization header names, or null.
     */
    public function verify(string $header): ?string
    {
        if (0 !== stripos($header, 'Basic ')) {
            return null;
        }

        // Keyed on a digest of the header: the credential itself is never a key.
        $key = 'authz:tok:' . hash('sha256', $header);
        $cached = Cache::fetch($key, $found);
        if ($found) {
            return '' === $cached ? null : (string) $cached;
        }

        $site = null;
        $raw = base64_decode(substr($header, 6), true);
        if (false !== $raw && str_contains($raw, ':')) {
            [$user, $token] = explode(':', $raw, 2);
            if ('' !== $user && '' !== $token) {
                try {
                    $digest = $this->hash($token);
                    $db = $this->database->open();
                    $statement = $db->prepare('SELECT token_hash FROM sites WHERE name = :n AND active = 1');
                    $statement->bindValue(':n', $user, SQLITE3_TEXT);
                    $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);
                    $db->close();
                    if (is_array($row) && hash_equals((string) $row['token_hash'], $digest)) {
                        $site = $user;
                    }
                } catch (\Throwable $exception) {
                    $this->audit->write(['event' => 'verify_error', 'error' => $exception->getMessage()]);
                }
            }
        }

        // Refusals are cached too, or one misconfigured site hammers SQLite on
        // every one of its requests.
        Cache::store($key, $site ?? '', AuthzConfig::CACHE_TTL);

        return $site;
    }
}
