<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Plugin\Mcp\Catalogue;

/**
 * The packages the last build published, with their versions.
 */
final class Packages
{
    public function __construct(private readonly Catalogue $catalogue)
    {
    }

    /**
     * @return array{packages: array<string, list<string>>}
     */
    #[McpTool(
        name: 'packages',
        description: 'The packages the catalogue publishes, as the last successful build wrote them, each with its versions. A package in the require list but not here was not built: check build_status.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(
        #[Schema(description: 'Only the packages whose name contains this text; empty for all.')]
        string $contains = '',
    ): array {
        $packages = [];
        foreach ($this->catalogue->names() as $name) {
            if ('' === $contains || false !== stripos($name, $contains)) {
                $packages[$name] = array_map(static fn (array $version): string => (string) ($version['version'] ?? '?'), $this->catalogue->versions($name));
            }
        }

        return ['packages' => $packages];
    }
}
