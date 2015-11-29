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
     * @var string
     * @Type("string")
     * @SerializedName("output-dir")
     */
    private $outputDir = '';

    /**
     * @Type("RepositoryCollection<Playbloom\Satisfy\Model\Repository>")
     */
    private $repositories = array();

    /**
     * @Type("array")
     * @SerializedName("require")
     */
    private $require = array();

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("require-all")
     */
    private $requireAll = true;

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("require-dependencies")
     */
    private $requireDependencies = true;

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("require-dev-dependencies")
     */
    private $requireDevDependencies = true;

    /**
     * @var Archive
     * @Type("Playbloom\Satisfy\Model\Archive")
     */
    private $archive;

    /**
     * @var string
     * @Type("string")
     * @SerializedName("minimum-stability")
     */
    private $minimumStability = '';

    /**
     * @var string
     * @Type("string")
     * @SerializedName("twig-template")
     */
    private $twigTemplate;

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
    public function setArchive(Archive $archive = null)
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

    /**
     * @return string
     */
    public function getTwigTemplate()
    {
        return $this->twigTemplate;
    }
}
