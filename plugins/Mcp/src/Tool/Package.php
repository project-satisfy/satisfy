<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Plugin\Mcp\Catalogue;
use Playbloom\Service\LogRedactor;

/**
 * One published package's versions, as Composer sees them.
 */
final class Package
{
    public function __construct(private readonly Catalogue $catalogue)
    {
    }

    /**
     * @return array{name: string, versions: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'package',
        description: 'One package the catalogue publishes: each version with its release time, requirements, and the source and archive it is built from. Credentials in URLs are replaced with ***.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(
        #[Schema(description: 'The package name, such as psr/log.')]
        string $name,
    ): array {
        $versions = $this->catalogue->versions($name);
        if ([] === $versions) {
            throw new ToolCallException(sprintf('The catalogue publishes no package named %s. The packages tool lists those it does.', $name));
        }

        return [
            'name' => $name,
            'versions' => array_map(static fn (array $version): array => [
                'version' => $version['version'] ?? null,
                'time' => $version['time'] ?? null,
                'require' => $version['require'] ?? [],
                'source' => isset($version['source']['url']) ? [
                    'type' => $version['source']['type'] ?? null,
                    'url' => LogRedactor::redact((string) $version['source']['url']),
                    'reference' => $version['source']['reference'] ?? null,
                ] : null,
                'dist' => isset($version['dist']['url']) ? [
                    'type' => $version['dist']['type'] ?? null,
                    'url' => LogRedactor::redact((string) $version['dist']['url']),
                ] : null,
            ], $versions),
        ];
    }
}
