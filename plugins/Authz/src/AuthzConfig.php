<?php

namespace Playbloom\Plugin\Authz;

/**
 * Where authz keeps its state, and its tunables.
 *
 * Read from the process environment rather than from Symfony's parameters,
 * because the verification endpoint runs without the kernel.
 */
final class AuthzConfig
{
    /**
     * Seconds a credential verification or an entitlement set stays in APCu:
     * the upper bound on how long a revocation takes when the cache is not purged.
     */
    public const CACHE_TTL = 60;

    /**
     * Seconds a dependency graph stays in APCu. Its key includes a signature of
     * the metadata, so it changes when the catalogue is rebuilt.
     */
    public const DEPS_TTL = 900;

    /**
     * Requests for this path always enforce, whatever the global flag says,
     * so the whole nginx to PHP chain can be proven while enforcement is off.
     */
    public const CANARY_PREFIX = '/_authzcheck';

    public function __construct(
        private readonly string $dataDir,
        private readonly string $catalogueDir,
    ) {
    }

    /**
     * AUTHZ_DATA_DIR (default var/authz) and AUTHZ_CATALOGUE_DIR (default public/).
     */
    public static function fromEnvironment(string $projectDir): self
    {
        return new self(
            self::env('AUTHZ_DATA_DIR') ?? $projectDir . '/var/authz',
            self::env('AUTHZ_CATALOGUE_DIR') ?? $projectDir . '/public',
        );
    }

    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    public function getDatabasePath(): string
    {
        return $this->dataDir . '/authz.db';
    }

    public function getAuditPath(): string
    {
        return $this->dataDir . '/audit.jsonl';
    }

    /**
     * The catalogue Satis writes; its p2/ is the source of the dependency graph.
     */
    public function getCatalogueDir(): string
    {
        return $this->catalogueDir;
    }

    /**
     * The server-side pepper mixed into every token hash.
     *
     * @throws \RuntimeException if unset, rather than hashing with an empty pepper
     */
    public function getPepper(): string
    {
        $pepper = self::env('TOKEN_PEPPER');
        if (null === $pepper) {
            throw new \RuntimeException('TOKEN_PEPPER is not set.');
        }

        return $pepper;
    }

    /**
     * The key guarding the cache purge endpoint.
     */
    public function getAdminKey(): string
    {
        return (string) self::env('ADMIN_KEY');
    }

    /**
     * Whether requests without a valid token are refused. The flag file wins
     * over ENFORCE so it can be switched without restarting anything, and
     * anything but "true" is permissive.
     */
    public function isEnforcing(): bool
    {
        $file = $this->dataDir . '/enforce';
        if (is_readable($file)) {
            return 'true' === trim((string) file_get_contents($file));
        }

        return 'true' === strtolower(trim((string) self::env('ENFORCE')));
    }

    public function setEnforcing(bool $on): void
    {
        $this->ensureDataDir();
        file_put_contents($this->dataDir . '/enforce', $on ? "true\n" : "false\n");
    }

    public function ensureDataDir(): void
    {
        if (!is_dir($this->dataDir) && !@mkdir($this->dataDir, 0770, true) && !is_dir($this->dataDir)) {
            throw new \RuntimeException(sprintf('Cannot create the authz data directory "%s".', $this->dataDir));
        }
    }

    private static function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) && '' !== $value ? $value : null;
    }
}
