<?php

namespace Playbloom\Model;

/**
 * One Satis build: either everything, or the listed repositories.
 */
final class BuildRequest
{
    /**
     * @param string[] $repositoryUrls
     * @param string[] $entries        queue files this request was claimed from
     */
    public function __construct(
        private readonly bool $full,
        private readonly array $repositoryUrls = [],
        private readonly array $entries = [],
    ) {
    }

    public static function full(): self
    {
        return new self(true);
    }

    /**
     * @param string[] $repositoryUrls
     */
    public static function forRepositories(array $repositoryUrls): self
    {
        return new self(false, array_values(array_unique($repositoryUrls)));
    }

    public function isFull(): bool
    {
        return $this->full;
    }

    /**
     * @return string[]
     */
    public function getRepositoryUrls(): array
    {
        return $this->full ? [] : $this->repositoryUrls;
    }

    /**
     * @return string[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
