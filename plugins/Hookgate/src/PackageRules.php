<?php

namespace Playbloom\Plugin\Hookgate;

/**
 * The rules about a tagged package itself, before anything is written.
 */
final class PackageRules
{
    public function __construct(private readonly PackageTypes $types)
    {
    }

    /**
     * The package's name, if auto-require may publish it at this tag.
     *
     * @param array<string, mixed> $composer
     * @param string[]             $rootFiles names of the files at the tag's root
     *
     * @throws AutoRequireRefused
     */
    public function validate(string $tag, array $composer, array $rootFiles): string
    {
        $name = strtolower(trim((string) ($composer['name'] ?? '')));
        if (!preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $name)) {
            throw new AutoRequireRefused('no_name', 'composer.json has no valid "name" (vendor/package), so Satis cannot publish it');
        }
        $type = PackageTypes::of($composer);
        if (!$this->types->allows($type)) {
            throw new AutoRequireRefused('project' === $type ? 'type_project' : 'type_unlisted', sprintf('%s declares type "%s", which this catalogue does not publish', $name, $type));
        }
        if (self::isLegacyDrupal($tag, $composer, $rootFiles)) {
            throw new AutoRequireRefused('drupal7', sprintf('%s at %s is a Drupal 7 project, which Composer cannot read', $name, $tag));
        }
        if (!self::isVersion($tag)) {
            throw new AutoRequireRefused('tag_not_a_version', sprintf('"%s" is not a version Composer can read (e.g. 1.2.3, v1.2.3, 1.0.0-beta1)', $tag));
        }
        if (isset($composer['version']) && ltrim((string) $composer['version'], 'vV') !== ltrim($tag, 'vV')) {
            throw new AutoRequireRefused('version_mismatch', sprintf('composer.json at %s says "version": "%s"; Composer skips a tag whose composer.json disagrees with it', $tag, $composer['version']));
        }

        return $name;
    }

    /**
     * Optional v, one to four numeric parts, an optional stability suffix.
     */
    public static function isVersion(string $tag): bool
    {
        return (bool) preg_match('/^v?\d+(\.\d+){0,3}(-?(alpha|beta|rc|patch|pl|p)\.?\d*)?$/i', $tag);
    }

    /**
     * A Drupal 7 (or older) project: a 7.x- tag, a .info file without a
     * .info.yml, or a requirement on drupal/drupal or on drupal/core 7.
     *
     * @param array<string, mixed> $composer
     * @param string[]             $rootFiles
     */
    public static function isLegacyDrupal(string $tag, array $composer, array $rootFiles): bool
    {
        if (preg_match('/^[1-7]\.x-/i', $tag)) {
            return true;
        }
        $info = false;
        $infoYml = false;
        foreach ($rootFiles as $file) {
            if (str_ends_with($file, '.info.yml')) {
                $infoYml = true;
            } elseif (str_ends_with($file, '.info')) {
                $info = true;
            }
        }
        if ($info && !$infoYml) {
            return true;
        }
        $require = is_array($composer['require'] ?? null) ? $composer['require'] : [];
        if (isset($require['drupal/drupal'])) {
            return true;
        }

        return (bool) preg_match('/^[\^~>=\s]*7(\.|$)/', (string) ($require['drupal/core'] ?? ''));
    }
}
