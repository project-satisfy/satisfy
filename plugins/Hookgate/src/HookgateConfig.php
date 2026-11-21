<?php

namespace Playbloom\Plugin\Hookgate;

/**
 * hookgate's settings, from the environment.
 *
 *  HOOKGATE_GITLAB_API_TOKEN  a read_api token (GITLAB_API_TOKEN is read too)
 *  HOOKGATE_GITLAB_API_BASE   default https://<the project's host>/api/v4 (GITLAB_API_BASE is read too)
 *  HOOKGATE_AUTO_REQUIRE      off, observe (default) or on
 *  HOOKGATE_PACKAGE_TYPES     the Composer types published, comma-separated
 *  HOOKGATE_CHECKER           a command that checks a candidate satis.json before auto-require writes it
 *  HOOKGATE_DRUPAL_ORG_P2     where to ask whether drupal.org serves a drupal/ name
 *  HOOKGATE_REFORWARD_DELAY   seconds after a branch push to build it once more (default 35; 0 is off)
 */
final class HookgateConfig
{
    public const MODES = ['off', 'observe', 'on'];

    public const REBUILD_DELAY = 35;

    /**
     * @param string[] $packageTypes
     */
    public function __construct(
        private readonly ?string $apiToken,
        private readonly ?string $apiBase,
        private readonly string $autoRequire,
        private readonly array $packageTypes,
        private readonly ?string $checker,
        private readonly string $drupalOrg,
        private readonly int $rebuildDelay = self::REBUILD_DELAY,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $mode = strtolower(trim((string) self::env('HOOKGATE_AUTO_REQUIRE')));
        $types = self::env('HOOKGATE_PACKAGE_TYPES');
        $delay = self::env('HOOKGATE_REFORWARD_DELAY');

        return new self(
            self::env('HOOKGATE_GITLAB_API_TOKEN') ?? self::env('GITLAB_API_TOKEN'),
            self::env('HOOKGATE_GITLAB_API_BASE') ?? self::env('GITLAB_API_BASE'),
            in_array($mode, self::MODES, true) ? $mode : 'observe',
            null === $types
                ? PackageTypes::DEFAULT
                : array_values(array_filter(array_map(static fn (string $type) => strtolower(trim($type)), explode(',', $types)), static fn (string $type): bool => '' !== $type)),
            self::env('HOOKGATE_CHECKER'),
            rtrim(self::env('HOOKGATE_DRUPAL_ORG_P2') ?? 'https://packages.drupal.org/files/packages/8/p2', '/'),
            null === $delay ? self::REBUILD_DELAY : max(0, (int) $delay),
        );
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    /**
     * The API base for a project: the configured one, or https://<host>/api/v4.
     */
    public function getApiBase(string $projectUrl): ?string
    {
        if (null !== $this->apiBase) {
            return rtrim($this->apiBase, '/');
        }
        $host = parse_url($projectUrl, PHP_URL_HOST);

        return is_string($host) && '' !== $host ? 'https://' . $host . '/api/v4' : null;
    }

    public function getAutoRequire(): string
    {
        return $this->autoRequire;
    }

    /**
     * @return string[]
     */
    public function getPackageTypes(): array
    {
        return $this->packageTypes;
    }

    public function getChecker(): ?string
    {
        return $this->checker;
    }

    public function getDrupalOrg(): string
    {
        return $this->drupalOrg;
    }

    /**
     * Seconds after a GitLab branch push to build its repository once more;
     * 0 turns that off.
     */
    public function getRebuildDelay(): int
    {
        return $this->rebuildDelay;
    }

    private static function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) && '' !== $value ? $value : null;
    }
}
