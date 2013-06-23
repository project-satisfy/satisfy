<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Configuration class
 *
 * Represent a satis configuration file
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @Type("string")
     */
    private $name;

    /**
     * @Type("string")
     */
    private $homepage;

    /**
     * @Type("RepositoryCollection<Playbloom\Satisfy\Model\Repository>")
     */
    private $repositories;

    /**
     * @Type("boolean")
     * @SerializedName("require-all")
     */
    private $require;

    /**
     * @var Archive
     * @Type("Playbloom\Satisfy\Model\Archive")
     */
    private $archive;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->repositories = array();
        $this->require = true;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get homepage
     *
     * @return string $homepage
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * Get repositories
     *
     * @return array $repositories
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * Set repositories
     *
     * @param array $repositories
     *
     * @return static
     */
    public function setRepositories(array $repositories)
    {
        $this->repositories = $repositories;

        return $this;
    }

    /**
     * @param Archive $archive
     */
    public function setArchive(Archive $archive=null)
    {
        $this->archive = $archive;
    }

    /**
     * @return Archive
     */
    public function getArchive()
    {
        return $this->archive;
    }

}
