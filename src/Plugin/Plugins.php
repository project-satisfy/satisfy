<?php

namespace Playbloom\Plugin;

use Playbloom\Plugin\Authz\AuthzBundle;
use Playbloom\Plugin\Diagnosis\DiagnosisBundle;
use Playbloom\Plugin\Hookgate\HookgateBundle;
use Playbloom\Plugin\Mcp\McpServerBundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Reads SATISFY_PLUGINS, a comma-separated list of plugin names or bundle
 * classes, for Kernel::registerBundles().
 */
final class Plugins
{
    /**
     * The plugins in this repository, by the names SATISFY_PLUGINS uses.
     */
    public const BUILT_IN = [
        'authz' => AuthzBundle::class,
        'diagnosis' => DiagnosisBundle::class,
        'hookgate' => HookgateBundle::class,
        'mcp' => McpServerBundle::class,
    ];

    /**
     * @return array<class-string<BundleInterface>, array<string, bool>>
     */
    public static function bundles(?string $list = null): array
    {
        $bundles = [];
        foreach (self::names($list) as $name) {
            $class = self::BUILT_IN[strtolower($name)] ?? $name;
            if (!is_a($class, BundleInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf('SATISFY_PLUGINS names "%s", which is neither a plugin in this release nor a bundle class.', $name));
            }
            if (is_a($class, AbstractPlugin::class, true)) {
                foreach ($class::requiredBundles() as $required) {
                    $bundles[$required] = ['all' => true];
                }
            }
            $bundles[$class] = ['all' => true];
        }

        return $bundles;
    }

    /**
     * The entries of SATISFY_PLUGINS, or of $list when given.
     *
     * @return string[]
     */
    public static function names(?string $list = null): array
    {
        $list ??= (string) ($_SERVER['SATISFY_PLUGINS'] ?? $_ENV['SATISFY_PLUGINS'] ?? '');

        return array_values(array_filter(array_map('trim', explode(',', $list)), static fn (string $name): bool => '' !== $name));
    }
}
