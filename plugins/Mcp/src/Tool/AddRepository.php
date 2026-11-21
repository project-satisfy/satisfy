<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Event\RepositoryAddingEvent;
use Playbloom\Model\Repository;
use Playbloom\Plugin\Mcp\Access;
use Playbloom\Plugin\Mcp\RepositoryFinder;
use Playbloom\Service\Manager;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Adds a repository to satis.json, through RepositoryAddingEvent, so that a
 * plugin such as hookgate can refuse it, as it can a webhook's auto-add.
 */
final class AddRepository
{
    /** The types the admin UI's repository form offers. */
    public const TYPES = ['artifact', 'composer', 'git', 'github', 'gitlab', 'git-bitbucket', 'hg', 'hg-bitbucket', 'package', 'path', 'pear', 'perforce', 'svn', 'vcs'];

    public function __construct(
        private readonly Access $access,
        private readonly Manager $manager,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @return array{added: array{type: string, url: string}}
     */
    #[McpTool(
        name: 'add_repository',
        description: 'Adds a repository to satis.json. Its packages are published only once the require list names them (require_package), or require-all is on, and a full build has run (queue_build). A URL carrying a credential is refused. Needs a write token.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'The repository URL, such as https://github.com/php-fig/log.git or git@gitlab.example.com:acme/app.git.')]
        string $url,
        #[Schema(description: 'The Composer repository type. vcs suits a Git repository on any host, but reads one on GitHub through its API, which allows 60 requests an hour without a GitHub token in COMPOSER_AUTH, and then tries to clone over SSH. git clones the URL as given, with no API.', enum: self::TYPES)]
        string $type = 'vcs',
    ): array {
        $this->access->requireWrite();
        $url = trim($url);
        if ('' === $url) {
            throw new ToolCallException('A repository needs a URL.');
        }
        RepositoryFinder::refuseCredentials($url);
        $repository = new Repository($url, $type);
        if (null !== $this->manager->findOneRepository($repository->getId())) {
            throw new ToolCallException(sprintf('satis.json already lists %s.', $url));
        }

        $token = $this->access->token();
        $event = new RepositoryAddingEvent($repository, 'mcp', ['token' => $token['name'] ?? null], true);
        $this->dispatcher->dispatch($event);
        if ($event->isRefused()) {
            throw new ToolCallException(sprintf('A plugin refused the repository: %s', $event->getRefusal()));
        }
        $this->manager->add($repository);

        $added = ['type' => $type, 'url' => $url];
        $this->access->record('add_repository', ['added' => $added]);

        return ['added' => $added];
    }
}
