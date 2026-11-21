<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Plugin\Mcp\Access;
use Playbloom\Plugin\Mcp\Catalogue;
use Playbloom\Plugin\Mcp\RemovalGuard;
use Playbloom\Plugin\Mcp\RepositoryFinder;
use Playbloom\Service\LogRedactor;
use Playbloom\Service\Manager;

/**
 * Removes a repository from satis.json, so that the next full build stops
 * publishing the packages built from it.
 */
final class RemoveRepository
{
    public function __construct(
        private readonly Access $access,
        private readonly Manager $manager,
        private readonly RepositoryFinder $repositories,
        private readonly Catalogue $catalogue,
        private readonly RemovalGuard $guard,
    ) {
    }

    /**
     * @return array{removed: array{type: string, url: string}, stops_publishing: list<string>, fetched_recently_by: array<string, list<string>>}
     */
    #[McpTool(
        name: 'remove_repository',
        description: 'Removes a repository from satis.json; the next full build stops publishing the packages built from it. Refused, unless force is true, when a site fetched one of those packages in the last 30 days, or when there is no authz audit log to tell. Needs a write token.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'The repository URL, as the repositories tool lists it.')]
        string $url,
        #[Schema(description: 'Remove it even if a site fetched one of its packages in the last 30 days, or if that cannot be told.')]
        bool $force = false,
    ): array {
        $this->access->requireWrite();
        $repository = $this->repositories->find($url);
        $packages = $this->catalogue->packagesFrom($repository->getUrl());
        $fetches = $this->guard->check($packages, $force, sprintf('Removing %s', LogRedactor::redact($repository->getUrl())));
        $this->manager->delete($repository);

        $change = [
            'removed' => ['type' => $repository->getType(), 'url' => LogRedactor::redact($repository->getUrl())],
            'stops_publishing' => $packages,
            'fetched_recently_by' => $fetches,
        ];
        $this->access->record('remove_repository', $change + ['force' => $force]);

        return $change;
    }
}
