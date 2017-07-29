<?php

namespace Playbloom\Satisfy\Service;

use PhpCollection\Map;
use PhpOption\None;
use Playbloom\Satisfy\Exception\MissingConfigException;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Persister\PersisterInterface;

/**
 * Satis configuration definition manager
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Manager
{
    /** @var PersisterInterface */
    private $persister;

    /** @var Configuration */
    private $configuration;

    /**
     * Constructor
     *
     * @param PersisterInterface $persister
     */
    public function __construct(PersisterInterface $persister)
    {
        $this->persister = $persister;
    }

    /**
     * Find repositories
     *
     * @return Map A RepositoryInterface map
     */
    public function getRepositories()
    {
        return $this->getConfig()->getRepositories();
    }

    /**
     * Find one repository
     *
     * @param  string $id
     *
     * @return RepositoryInterface|null
     */
    public function findOneRepository($id)
    {
        $repository = $this->getRepositories()->get($id);
        if ($repository instanceof None) {
            return null;
        }

        return $repository->get();
    }

    /**
     * Add a new repository
     *
     * @param RepositoryInterface $repository
     */
    public function add(RepositoryInterface $repository)
    {
        $this
            ->doAdd($repository)
            ->flush();
    }

    /**
     * Adds a array of repositories.
     *
     * @param array $repositories
     */
    public function addAll(array $repositories)
    {
        foreach ($repositories as $repository) {
            $this->doAdd($repository);
        }
        $this->flush();
    }

    /**
     * Update an existing repository
     *
     * @param RepositoryInterface $repository
     * @param string              $url
     * @throws \RuntimeException
     */
    public function update(RepositoryInterface $repository, $url)
    {
        $repos = $this->getRepositories();

        $option = $repos->get($repository->getId());
        if ($option instanceof None) {
            throw new \RuntimeException('Unknown repository');
        }

        $repos->remove($repository->getId());
        $repository->setUrl($url);
        $repos->set($repository->getId(), $repository);

        $this->flush();
    }

    /**
     * Delete a repository
     *
     * @param RepositoryInterface $repository
     */
    public function delete(RepositoryInterface $repository)
    {
        $this
            ->getConfig()
            ->getRepositories()
            ->remove($repository->getId());

        $this->flush();
    }

    /**
     * Persist current configuration
     */
    private function flush()
    {
        $this->persister->flush($this->getConfig());
    }

    /**
     * Adds a single Repository without flush
     *
     * @param RepositoryInterface $repository
     * @return $this
     */
    private function doAdd(RepositoryInterface $repository)
    {
        $this
            ->getConfig()
            ->getRepositories()
            ->set($repository->getId(), $repository);

        return $this;
    }

    /**
     * @return Configuration
     */
    protected function getConfig()
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
}
