<?php

namespace Playbloom\Plugin\Hookgate;

/**
 * The Composer types the catalogue publishes. An allowlist, so it fails
 * closed: a type nobody listed reads as "not a package", and the log says so.
 * "project" is left out on purpose: sites and scaffolds declare it, and they
 * carry copied composer.json files that would otherwise register.
 */
final class PackageTypes
{
    public const DEFAULT = [
        'drupal-module',
        'drupal-theme',
        'drupal-recipe',
        'library',
        'drupal-custom-module',
        'drupal-custom-theme',
        'drupal-profile',
        'drupal-drush',
        'drupal-library',
        'metapackage',
    ];

    public function __construct(private readonly HookgateConfig $config)
    {
    }

    /**
     * The type a composer.json declares, read as Composer reads it: absent means library.
     *
     * @param array<string, mixed> $composer
     */
    public static function of(array $composer): string
    {
        $type = $composer['type'] ?? 'library';

        return is_string($type) && '' !== trim($type) ? strtolower(trim($type)) : 'library';
    }

    public function allows(string $type): bool
    {
        return in_array($type, $this->config->getPackageTypes(), true);
    }

    /**
     * The list as a PHP file for an external checker, in the form
     * check-satis.php reads: PACKAGE_TYPES_ALLOWED, package_type() and package_type_allowed().
     */
    public function writeFile(string $path): void
    {
        file_put_contents($path, sprintf(<<<'PHP'
            <?php
            const PACKAGE_TYPES_ALLOWED = %s;
            function package_type(array $composerJson): string
            {
                $t = $composerJson['type'] ?? 'library';
                return (is_string($t) && $t !== '') ? strtolower(trim($t)) : 'library';
            }
            function package_type_allowed(string $type): bool
            {
                return in_array($type, PACKAGE_TYPES_ALLOWED, true);
            }

            PHP, var_export($this->config->getPackageTypes(), true)));
    }
}
