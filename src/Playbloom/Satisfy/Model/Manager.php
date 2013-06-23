<?php

namespace Playbloom\Satisfy\Model;

use Playbloom\Satisfy\Model\Persister;
use Playbloom\Satisfy\Model\RepositoryInterface;

/**
 * Satis configuration definition manager
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Manager
{
    private $persister;
    private $configuration;

    /**
     * Constructor
     *
     * @param PersisterInterface $persister
     */
    public function __construct(PersisterInterface $persister)
    {
        $this->persister = $persister;
        $this->configuration = $this->persister->load();
    }

    /**
     * Return the Satis repository name
     *
     * @return string
     */
    public function getName()
    {
        return $this->configuration->getName();
    }

    /**
     * Find repositories
     *
     * @return Map A RepositoryInterface map
     */
    public function findAllRepositories()
    {
        return $this->configuration->getRepositories();
    }

    /**
     * Find one repository
     *
     * @param  string $id
     *
     * @return RepositoryInterface
     */
    public function findOneRepository($id)
    {
        return $this->configuration->getRepositories()->get($id)->get();
    }

    /**
     * Add a new repository
     *
     * @param RepositoryInterface $repository
     */
    public function add(RepositoryInterface $repository)
    {
        $this->doAdd($repository);

        $this->flush();
    }

    /**
     * Adds a array of repositories.
     *
     * @param array $repositories
     */
    public function addAll(array $repositories)
    {
        foreach($repositories as $repository) {
            $this->doAdd($repository);
        }
        $this->flush();
    }

    /**
     * Update an existing repository
     *
     * @param  RepositoryInterface $repository
     * @param  string              $url
     */
    public function update(RepositoryInterface $repository, $url)
    {
        $this
            ->configuration
            ->getRepositories()
            ->get($repository->getId())
            ->get()
                ->setUrl($url)
        ;

        $this->flush();
    }

    /**
     * Delete a repository
     *
     * @param  RepositoryInterface $repository
     */
    public function delete(RepositoryInterface $repository)
    {
        $this
            ->configuration
            ->getRepositories()
            ->remove($repository->getId())
        ;

        $this->flush();
    }

    /**
     * Persist current configuration
     */
    private function flush()
    {
        $this->persister->flush($this->configuration);
    }

    /**
     * Adds a single Repository without flush
     *
     * @param RepositoryInterface $repository
     */
    private function doAdd(RepositoryInterface $repository)
    {
        $this
            ->configuration
            ->getRepositories()
            ->set($repository->getId(), $repository)
        ;
    }
}
