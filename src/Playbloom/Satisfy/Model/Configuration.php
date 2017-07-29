<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
use PhpCollection\Map;

/**
 * Configuration class
 *
 * Represent a satis configuration file
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Configuration
{
    /**
     * @Type("string")
     */
    private $name = 'Composer repository';

    /**
     * @var string
     * @Type("string")
     */
    private $description = '';

    /**
     * @Type("string")
     */
    private $homepage = 'http://localhost';

    /**
     * @var string
     * @Type("string")
     * @SerializedName("output-dir")
     */
    private $outputDir;

    /**
     * @var Map
     * @Type("RepositoryCollection<Playbloom\Satisfy\Model\Repository>")
     */
    private $repositories;

    /**
     * @Type("array")
     * @SerializedName("require")
     */
    private $require;

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("require-all")
     */
    private $requireAll = false;

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("require-dependencies")
     */
    private $requireDependencies = false;

    /**
     * @var boolean
     * @Type("boolean")
     * @SerializedName("require-dev-dependencies")
     */
    private $requireDevDependencies = false;

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
    private $minimumStability;

    /**
     * @var string
     * @Type("string")
     * @SerializedName("twig-template")
     */
    private $twigTemplate;

    /**
     * @var \stdClass
     * @Type("stdClass")
     */
    private $abandoned;

    /**
     * @var \stdClass
     * @Type("stdClass")
     */
    private $config;

    /**
     * @var string
     * @Type("string")
     * @SerializedName("notify-batch")
     */
    private $notifyBatch;

    /**
     * Configuration constructor.
     */
    public function __construct()
    {
        $this->repositories = new Map();
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
     * @return Map $repositories
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * Set repositories
     *
     * @param Map $repositories
     *
     * @return $this
     */
    public function setRepositories(Map $repositories)
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

    /**
     * @return array|null
     */
    public function getRequire()
    {
        if (empty($this->require)) {
            return null;
        }

        return $this->require;
    }
}
