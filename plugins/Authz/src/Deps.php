<?php

namespace Playbloom\Plugin\Authz;

/**
 * The dependency graph of the packages the catalogue hosts, read from the p2
 * metadata Satis writes, so that granting a package also grants what it needs.
 */
final class Deps
{
    public function __construct(private readonly AuthzConfig $config)
    {
    }

    /**
     * Each hosted package and the hosted packages it requires, in any version.
     * Over-approximating keeps a site pinned to an old release resolving.
     *
     * @return array<string, string[]>
     */
    public function graph(): array
    {
        $key = 'authz:deps:' . $this->signature();
        $cached = Cache::fetch($key, $found);
        if ($found && is_array($cached)) {
            return $cached;
        }

        $hosted = [];
        $requires = [];
        $root = $this->config->getCatalogueDir() . '/p2';
        if (is_dir($root)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (!$file->isFile() || !str_ends_with($file->getFilename(), '.json')) {
                    continue;
                }
                $data = json_decode((string) @file_get_contents($file->getPathname()), true);
                if (!is_array($data) || !isset($data['packages'])) {
                    continue;
                }
                foreach ($data['packages'] as $name => $versions) {
                    $name = strtolower((string) $name);
                    $hosted[$name] = true;
                    foreach ((array) $versions as $version) {
                        foreach ((array) ($version['require'] ?? []) as $dependency => $constraint) {
                            $dependency = strtolower((string) $dependency);
                            // Platform requirements: php, ext-*, lib-*, composer-*.
                            if (str_contains($dependency, '/')) {
                                $requires[$name][$dependency] = true;
                            }
                        }
                    }
                }
            }
        }

        $graph = [];
        foreach ($requires as $name => $dependencies) {
            $graph[$name] = array_values(array_filter(
                array_keys($dependencies),
                static fn (string $dependency): bool => isset($hosted[$dependency])
            ));
        }
        foreach (array_keys($hosted) as $name) {
            $graph[$name] ??= [];
        }

        Cache::store($key, $graph, AuthzConfig::DEPS_TTL);

        return $graph;
    }

    /**
     * The packages given and everything they pull in, as a lookup set.
     *
     * @param string[] $packages
     *
     * @return array<string, true>
     */
    public function closure(array $packages): array
    {
        $graph = $this->graph();
        $seen = [];
        $stack = array_map('strtolower', $packages);
        while ([] !== $stack) {
            $current = array_pop($stack);
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;
            foreach ($graph[$current] ?? [] as $dependency) {
                if (!isset($seen[$dependency])) {
                    $stack[] = $dependency;
                }
            }
        }

        return $seen;
    }

    /**
     * @return string[]
     */
    public function hosted(): array
    {
        $names = array_keys($this->graph());
        sort($names);

        return $names;
    }

    /**
     * Changes whenever Satis rewrites packages.json, which it does on every build.
     */
    private function signature(): string
    {
        $file = $this->config->getCatalogueDir() . '/packages.json';
        if (!is_readable($file)) {
            return 'none';
        }

        return md5($this->config->getCatalogueDir()) . '-' . filemtime($file) . '-' . filesize($file);
    }
}
