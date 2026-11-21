<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Model\Repository;
use Playbloom\Plugin\Mcp\Access;
use Playbloom\Plugin\Mcp\Catalogue;
use Playbloom\Plugin\Mcp\RemovalGuard;
use Playbloom\Plugin\Mcp\RepositoryFinder;
use Playbloom\Service\LogRedactor;
use Playbloom\Service\Manager;

/**
 * Changes a repository's URL or type in satis.json. A new URL is treated as
 * a removal of the old one: the packages built from the old URL are published
 * again only if the new one holds them.
 */
final class UpdateRepository
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
     * @return array{from: array{type: string, url: string}, to: array{type: string, url: string}, fetched_recently_by: array<string, list<string>>}
     */
    #[McpTool(
        name: 'update_repository',
        description: 'Changes a repository\'s URL or type in satis.json, for instance to correct a misspelt URL. A new URL is refused, unless force is true, when a site fetched a package built from the old one in the last 30 days, or when there is no authz audit log to tell. A URL carrying a credential is refused. Needs a write token.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'The repository\'s URL now, as the repositories tool lists it.')]
        string $url,
        #[Schema(description: 'Its new URL; empty to keep it.')]
        string $newUrl = '',
        #[Schema(description: 'Its new type; empty to keep it.', enum: ['', ...AddRepository::TYPES])]
        string $newType = '',
        #[Schema(description: 'Change the URL even if a site fetched a package built from the old one in the last 30 days, or if that cannot be told.')]
        bool $force = false,
    ): array {
        $this->access->requireWrite();
        $repository = $this->repositories->find($url);
        $newUrl = '' === trim($newUrl) ? $repository->getUrl() : trim($newUrl);
        $newType = '' === $newType ? $repository->getType() : $newType;
        if ($newUrl === $repository->getUrl() && $newType === $repository->getType()) {
            throw new ToolCallException('Neither the URL nor the type would change.');
        }
        RepositoryFinder::refuseCredentials($newUrl);
        $updated = new Repository($newUrl, $newType);
        if ($newUrl !== $repository->getUrl() && null !== $this->manager->findOneRepository($updated->getId())) {
            throw new ToolCallException(sprintf('satis.json already lists %s.', $newUrl));
        }

        $fetches = [];
        if ($newUrl !== $repository->getUrl()) {
            $packages = $this->catalogue->packagesFrom($repository->getUrl());
            $fetches = $this->guard->check($packages, $force, sprintf('Moving away from %s', LogRedactor::redact($repository->getUrl())));
        }
        $this->manager->update($repository, $updated);

        $change = [
            'from' => ['type' => $repository->getType(), 'url' => LogRedactor::redact($repository->getUrl())],
            'to' => ['type' => $newType, 'url' => $newUrl],
            'fetched_recently_by' => $fetches,
        ];
        $this->access->record('update_repository', $change + ['force' => $force]);

        return $change;
    }
}
