<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Service\Manager;

/**
 * The repositories satis.json lists.
 */
final class Repositories
{
    public function __construct(private readonly Manager $manager)
    {
    }

    /**
     * @return array{repositories: list<array{id: string, type: string, url: string}>}
     */
    #[McpTool(
        name: 'repositories',
        description: 'The repositories in satis.json, in its order: each one\'s id, type and URL. Credentials in a URL are replaced with ***.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(): array
    {
        $repositories = [];
        foreach ($this->manager->getRepositories() as $repository) {
            $repositories[] = [
                'id' => $repository->getId(),
                'type' => $repository->getType(),
                'url' => self::withoutCredentials($repository->getUrl()),
            ];
        }

        return ['repositories' => $repositories];
    }

    /**
     * A URL's user information, such as a token in https://token@host/, can be
     * a credential. An scp-style git@host:path is left as it is.
     */
    public static function withoutCredentials(string $url): string
    {
        return (string) preg_replace('#^([a-z][a-z0-9+.-]*://)[^/@]+@#i', '$1***@', $url);
    }
}
