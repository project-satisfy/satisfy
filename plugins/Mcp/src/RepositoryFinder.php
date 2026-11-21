<?php

namespace Playbloom\Plugin\Mcp;

use Mcp\Exception\ToolCallException;
use Playbloom\Model\RepositoryInterface;
use Playbloom\Service\LogRedactor;
use Playbloom\Service\Manager;

/**
 * A repository in satis.json by its URL, as satis.json has it or as the tools
 * show it, with credentials replaced by ***.
 */
final class RepositoryFinder
{
    public function __construct(private readonly Manager $manager)
    {
    }

    /**
     * @throws ToolCallException when satis.json lists no such repository
     */
    public function find(string $url): RepositoryInterface
    {
        foreach ($this->manager->getRepositories() as $repository) {
            if ($repository->getUrl() === $url || LogRedactor::redact($repository->getUrl()) === $url) {
                return $repository;
            }
        }

        throw new ToolCallException(sprintf('satis.json lists no repository at %s. The repositories tool lists those it does.', $url));
    }

    /**
     * @throws ToolCallException for a URL that carries a credential
     */
    public static function refuseCredentials(string $url): void
    {
        if (LogRedactor::redact($url) !== $url) {
            throw new ToolCallException('The URL carries a credential, which would be written to satis.json for anyone who can read it. Give Composer the credential instead, in COMPOSER_AUTH or auth.json, and the URL without it.');
        }
    }
}
