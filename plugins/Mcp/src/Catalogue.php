<?php

namespace Playbloom\Plugin\Mcp;

use Composer\MetadataMinifier\MetadataMinifier;
use Playbloom\Service\Manager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * What the last build published, read from the output directory Satis wrote:
 * packages.json's list of packages, and each package's versions in p2/.
 */
final class Catalogue
{
    public function __construct(
        private readonly Manager $manager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $file = $this->dir() . '/packages.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        $names = is_array($data) && is_array($data['available-packages'] ?? null) ? $data['available-packages'] : [];
        $names = array_values(array_filter($names, 'is_string'));
        sort($names);

        return $names;
    }

    /**
     * Every version published of $name, stable then development, in the order
     * Satis wrote them.
     *
     * @return list<array<string, mixed>>
     */
    public function versions(string $name): array
    {
        if (1 !== preg_match('~^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$~', $name)) {
            return [];
        }
        $versions = [];
        foreach (['', '~dev'] as $suffix) {
            $file = sprintf('%s/p2/%s%s.json', $this->dir(), $name, $suffix);
            $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
            $list = is_array($data) ? ($data['packages'][$name] ?? []) : [];
            if (!is_array($list) || [] === $list) {
                continue;
            }
            if ('composer/2.0' === ($data['minified'] ?? null)) {
                $list = MetadataMinifier::expand($list);
            }
            array_push($versions, ...array_values($list));
        }

        return $versions;
    }

    /**
     * The packages whose versions were built from the repository at $url.
     *
     * @return list<string>
     */
    public function packagesFrom(string $url): array
    {
        $packages = [];
        foreach ($this->names() as $name) {
            foreach ($this->versions($name) as $version) {
                if (($version['source']['url'] ?? null) === $url) {
                    $packages[] = $name;
                    break;
                }
            }
        }

        return $packages;
    }

    private function dir(): string
    {
        $dir = (string) ($this->manager->getConfig()->getOutputDir() ?: 'public');

        return str_starts_with($dir, '/') ? $dir : $this->projectDir . '/' . $dir;
    }
}
