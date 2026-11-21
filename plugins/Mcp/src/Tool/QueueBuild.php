<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Event\BuildEvent;
use Playbloom\Plugin\Mcp\Access;
use Playbloom\Plugin\Mcp\RepositoryFinder;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Queues a build for the worker, as the admin UI's build page or a webhook
 * does, through BuildEvent, so that a plugin can still stop it.
 */
final class QueueBuild
{
    public function __construct(
        private readonly Access $access,
        private readonly RepositoryFinder $repositories,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @return array{queued: string}
     */
    #[McpTool(
        name: 'queue_build',
        description: 'Queues a build for the worker: of one repository, or of everything in satis.json. It answers at once; build_status with waitSeconds waits for the build and reports how it ended. Needs a write token.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'The URL of the repository to build, as the repositories tool lists it; empty for a full build. A repository just added or changed needs a full build before its packages are published.')]
        string $repositoryUrl = '',
    ): array {
        $this->access->requireWrite();
        $repository = '' === $repositoryUrl ? null : $this->repositories->find($repositoryUrl);
        $event = new BuildEvent($repository);
        $this->dispatcher->dispatch($event);
        if (!$event->isQueued()) {
            throw new ToolCallException('A plugin stopped the build before it was queued.');
        }

        $queued = null === $repository ? 'full' : $repositoryUrl;
        $this->access->record('queue_build', ['queued' => $queued]);

        return ['queued' => $queued];
    }
}
