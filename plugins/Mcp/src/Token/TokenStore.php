<?php

namespace Playbloom\Plugin\Mcp\Token;

/**
 * The MCP server's tokens, each with a name and a scope: read, or write, which
 * also reads. A token is shown once when it is issued; only its SHA-256 hash
 * is kept, in tokens.json under SATISFY_MCP_DATA_DIR (default var/mcp).
 * Satisfy keeps no database, so this file is the store; changes are written
 * under a lock and renamed into place.
 */
final class TokenStore
{
    public const READ = 'read';
    public const WRITE = 'write';
    public const NAME = '[a-z0-9][a-z0-9._-]{0,63}';

    private const PREFIX = 'satisfy_mcp_';
    private const TOUCH_EVERY = 60;

    public function __construct(private readonly string $dir)
    {
    }

    public static function fromEnvironment(string $projectDir): self
    {
        $value = $_SERVER['SATISFY_MCP_DATA_DIR'] ?? $_ENV['SATISFY_MCP_DATA_DIR'] ?? getenv('SATISFY_MCP_DATA_DIR');

        return new self(is_string($value) && '' !== $value ? $value : $projectDir . '/var/mcp');
    }

    /**
     * Issues a token under $name, replacing any the name had, and returns it.
     */
    public function issue(string $name, string $scope): string
    {
        if (1 !== preg_match('/^' . self::NAME . '$/', $name)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a token name: lowercase letters, digits, ".", "_" and "-", at most 64.', $name));
        }
        if (!in_array($scope, [self::READ, self::WRITE], true)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a scope: read or write.', $scope));
        }

        $token = self::PREFIX . bin2hex(random_bytes(24));
        $this->change(static function (array $tokens) use ($name, $scope, $token): array {
            $tokens[$name] = ['scope' => $scope, 'hash' => hash('sha256', $token), 'created' => time(), 'last_used' => null];

            return $tokens;
        });

        return $token;
    }

    public function revoke(string $name): bool
    {
        $revoked = false;
        $this->change(static function (array $tokens) use ($name, &$revoked): array {
            $revoked = isset($tokens[$name]);
            unset($tokens[$name]);

            return $tokens;
        });

        return $revoked;
    }

    /**
     * @return array<string, array{scope: string, created: int, last_used: int|null}>
     */
    public function all(): array
    {
        $tokens = [];
        foreach ($this->read() as $name => $token) {
            $tokens[$name] = ['scope' => $token['scope'], 'created' => $token['created'], 'last_used' => $token['last_used']];
        }
        ksort($tokens);

        return $tokens;
    }

    /**
     * The name and scope of the token given, or null if it is not one.
     *
     * @return array{name: string, scope: string}|null
     */
    public function find(string $token): ?array
    {
        if (!str_starts_with($token, self::PREFIX)) {
            return null;
        }
        $hash = hash('sha256', $token);
        $found = null;
        foreach ($this->read() as $name => $stored) {
            if (hash_equals($stored['hash'], $hash)) {
                $found = ['name' => (string) $name, 'scope' => $stored['scope']];
            }
        }

        return $found;
    }

    /**
     * Records that a token was used, at most once a minute. A store the web
     * server cannot write is left as it is.
     */
    public function touch(string $name): void
    {
        $token = $this->read()[$name] ?? null;
        if (null === $token || time() - (int) $token['last_used'] < self::TOUCH_EVERY) {
            return;
        }
        try {
            $this->change(static function (array $tokens) use ($name): array {
                if (isset($tokens[$name])) {
                    $tokens[$name]['last_used'] = time();
                }

                return $tokens;
            });
        } catch (\RuntimeException) {
        }
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * @return array<string, array{scope: string, hash: string, created: int, last_used: int|null}>
     */
    private function read(): array
    {
        $file = $this->dir . '/tokens.json';
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) && is_array($data['tokens'] ?? null) ? $data['tokens'] : [];
    }

    /**
     * @param callable(array<string, array{scope: string, hash: string, created: int, last_used: int|null}>): array<string, array{scope: string, hash: string, created: int, last_used: int|null}> $change
     */
    private function change(callable $change): void
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new \RuntimeException(sprintf('Cannot create %s.', $this->dir));
        }
        $lock = @fopen($this->dir . '/tokens.lock', 'c');
        if (false === $lock || !flock($lock, \LOCK_EX)) {
            throw new \RuntimeException(sprintf('Cannot lock the tokens in %s.', $this->dir));
        }
        try {
            $json = json_encode(['tokens' => $change($this->read())], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR) . "\n";
            $file = $this->dir . '/tokens.json';
            $temporary = $file . '.' . bin2hex(random_bytes(4));
            if (false === @file_put_contents($temporary, $json) || !@rename($temporary, $file)) {
                @unlink($temporary);
                throw new \RuntimeException(sprintf('Cannot write %s.', $file));
            }
        } finally {
            flock($lock, \LOCK_UN);
            fclose($lock);
        }
    }
}
