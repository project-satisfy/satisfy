<?php

namespace Playbloom\Satisfy\Service;

use Playbloom\Satisfy\Exception\MissingConfigException;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Persister\JsonPersister;
use Playbloom\Satisfy\Persister\PersisterInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Lock\LockInterface;

/**
 * Satis configuration definition manager
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Manager
{
    private LockInterface $lock;

    private PersisterInterface $persister;

    private ?Configuration $configuration = null;

    /**
     * Constructor
     */
    public function __construct(LockInterface $lock, JsonPersister $persister)
    {
        $this->lock = $lock;
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
        $lock = $this->acquireLock();
        try {
            $this
                ->doAdd($repository)
                ->flush();
        } finally {
            $lock->release();
        }
    }

    /**
     * Adds a array of repositories.
     *
     * @param RepositoryInterface[] $repositories
     */
    public function addAll(array $repositories): void
    {
        $lock = $this->acquireLock();
        try {
            foreach ($repositories as $repository) {
                $this->doAdd($repository);
            }
            $this->flush();
        } finally {
            $lock->release();
        }
    }

    /**
     * Update an existing repository
     *
     * @throws \RuntimeException
     */
    public function update(RepositoryInterface $repository, RepositoryInterface $updated): void
    {
        $repos = $this->getRepositories();
        if (!$repos->offsetExists($repository->getId())) {
            throw new \RuntimeException('Unknown repository');
        }

        $lock = $this->acquireLock();
        try {
            $repos->offsetUnset($repository->getId());
            $repos->offsetSet($updated->getId(), $updated);
            $this->flush();
        } finally {
            $lock->release();
        }
    }

    /**
     * Delete a repository
     */
    public function delete(RepositoryInterface $repository): void
    {
        $lock = $this->acquireLock();
        try {
            $this
                ->getConfig()
                ->getRepositories()
                ->offsetUnset($repository->getId());
            $this->flush();
        } finally {
            $lock->release();
        }
    }

    /**
     * Persist current configuration
     */
    public function flush(): void
    {
        $this->persister->flush($this->getConfig());
    }

    /**
     * Adds a single Repository without flush
     *
     * @return $this
     */
    private function doAdd(RepositoryInterface $repository): self
    {
        $this
            ->getConfig()
            ->getRepositories()
            ->offsetSet($repository->getId(), $repository);

        return $this;
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

    public function acquireLock(): LockInterface
    {
        if (!$this->lock->acquire()) {
            throw new IOException('Cannot acquire lock for satis configuration file');
        }

        return $this->lock;
    }
}
