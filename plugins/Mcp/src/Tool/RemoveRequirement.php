<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Model\Configuration;
use Playbloom\Plugin\Mcp\Access;
use Playbloom\Plugin\Mcp\RemovalGuard;
use Playbloom\Service\Manager;

/**
 * Removes a package from satis.json's require list, so that the next full
 * build stops publishing it.
 */
final class RemoveRequirement
{
    public function __construct(
        private readonly Access $access,
        private readonly Manager $manager,
        private readonly RemovalGuard $guard,
    ) {
    }

    /**
     * @return array{removed: array{package: string, constraint: string}, fetched_recently_by: array<string, list<string>>}
     */
    #[McpTool(
        name: 'remove_requirement',
        description: 'Removes a package from satis.json\'s require list; the next full build stops publishing it, and composer update on the sites that use it stops finding it. Refused, unless force is true, when a site fetched the package in the last 30 days, or when there is no authz audit log to tell. Needs a write token.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'The package name, as the requirements tool lists it.')]
        string $package,
        #[Schema(description: 'Remove it even if a site fetched it in the last 30 days, or if that cannot be told.')]
        bool $force = false,
    ): array {
        $this->access->requireWrite();
        $constraint = null;
        foreach ($this->manager->getConfig()->getRequire() ?? [] as $existing) {
            if ($existing->getPackage() === $package) {
                $constraint = $existing->getConstraint();
            }
        }
        if (null === $constraint) {
            throw new ToolCallException(sprintf('satis.json does not require %s. The requirements tool lists what it does.', $package));
        }
        $fetches = $this->guard->check([$package], $force, sprintf('Removing %s', $package));

        $this->manager->modify(static function (Configuration $config) use ($package): void {
            $config->setRequire(array_values(array_filter(
                $config->getRequire() ?? [],
                static fn ($existing): bool => $existing->getPackage() !== $package,
            )));
        });

        $change = ['removed' => ['package' => $package, 'constraint' => $constraint], 'fetched_recently_by' => $fetches];
        $this->access->record('remove_requirement', $change + ['force' => $force]);

        return $change;
    }
}
