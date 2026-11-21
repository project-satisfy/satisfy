<?php

namespace Playbloom\Service;

use Playbloom\Exception\ConfigurationChangedException;
use Playbloom\Exception\MissingConfigException;
use Playbloom\Model\Configuration;
use Playbloom\Model\RepositoryInterface;
use Playbloom\Persister\JsonPersister;
use Playbloom\Persister\LockablePersisterInterface;

/**
 * Satis configuration definition manager
 *
 * satis.json may be written by other programs too: webhooks in other processes,
 * scripts, a filter in front of the webhooks. So every change to repositories
 * is applied to the file as it is once the lock is held, not to the copy this
 * request read earlier, and nothing another writer added in between is lost.
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Manager
{
    private LockablePersisterInterface $persister;

    private ?Configuration $configuration = null;

    /**
     * Constructor
     */
    public function __construct(JsonPersister $persister)
    {
        $this->persister = $persister;
    }

    /**
     * Find repositories
     *
     * @return RepositoryInterface[]|\ArrayIterator
     */
    public function getRepositories(): \ArrayIterator
    {
        return $this->getConfig()->getRepositories();
    }

    /**
     * Find one repository
     *
     * @return RepositoryInterface|null
     */
    public function findOneRepository(string $id)
    {
        return $this->getRepositories()[$id] ?? null;
    }

    /**
     * @return RepositoryInterface|null
     */
    public function findByUrl(string $pattern)
    {
        foreach ($this->getRepositories() as $repository) {
            if (preg_match($pattern, $repository->getUrl())) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Add a new repository
     */
    public function add(RepositoryInterface $repository): void
    {
        $this->modify(function (Configuration $config) use ($repository): void {
            $config->getRepositories()->offsetSet($repository->getId(), $repository);
        });
    }

    /**
     * Adds a array of repositories.
     *
     * @param RepositoryInterface[] $repositories
     */
    public function addAll(array $repositories): void
    {
        $this->modify(function (Configuration $config) use ($repositories): void {
            foreach ($repositories as $repository) {
                $config->getRepositories()->offsetSet($repository->getId(), $repository);
            }
        });
    }

    /**
     * Update an existing repository
     *
     * @throws \RuntimeException
     */
    public function update(RepositoryInterface $repository, RepositoryInterface $updated): void
    {
        $this->modify(function (Configuration $config) use ($repository, $updated): void {
            $repos = $config->getRepositories();
            if (!$repos->offsetExists($repository->getId())) {
                throw new \RuntimeException('Unknown repository');
            }
            $repos->offsetUnset($repository->getId());
            $repos->offsetSet($updated->getId(), $updated);
        });
    }

    /**
     * Delete a repository
     */
    public function delete(RepositoryInterface $repository): void
    {
        $this->modify(function (Configuration $config) use ($repository): void {
            $config->getRepositories()->offsetUnset($repository->getId());
        });
    }

    /**
     * Persist the configuration as edited in memory, replacing the file.
     *
     * @param string|null $expectedVersion the version of the file the edit started from;
     *                                     if the file has changed since, nothing is written
     *
     * @throws ConfigurationChangedException
     */
    public function flush(?string $expectedVersion = null): void
    {
        $this->persister->lock();
        try {
            if (null !== $expectedVersion && $expectedVersion !== $this->persister->getVersion()) {
                throw new ConfigurationChangedException('satis.json changed after it was read for this edit');
            }
            $this->persister->flush($this->getConfig());
        } finally {
            $this->persister->unlock();
        }
    }

    /**
     * The version of satis.json on disk, for flush()'s check.
     */
    public function getVersion(): string
    {
        return $this->persister->getVersion();
    }

    public function getConfig(): Configuration
    {
        if ($this->configuration) {
            return $this->configuration;
        }

        try {
            $this->configuration = $this->persister->load();
        } catch (MissingConfigException $e) {
            // use default config if file is missing or empty
            $this->configuration = new Configuration();
        }

        return $this->configuration;
    }

    /**
     * Drops the cached configuration and reads the file again.
     */
    public function reload(): Configuration
    {
        $this->configuration = null;

        return $this->getConfig();
    }

    /**
     * Applies a change to satis.json as it is once the lock is held.
     *
     * @param callable(Configuration): void $change
     */
    public function modify(callable $change): void
    {
        $this->persister->lock();
        try {
            $config = $this->reload();
            $change($config);
            $this->persister->flush($config);
        } finally {
            $this->persister->unlock();
        }
    }
}
