<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation as Serializer;
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
    private $description;

    /**
     * @Type("string")
     */
    private $homepage = 'http://localhost';

    /**
     * @var string
     * @Type("string")
     * @SerializedName("output-dir")
     */
    private $outputDir = 'web';

    /**
     * @var bool
     * @Type("boolean")
     * @SerializedName("output-html")
     */
    private $outputHtml = true;

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
     * @var string|null
     * @Type("string")
     * @SerializedName("include-filename")
     */
    private $includeFilename;

    /**
     * @var Archive|null
     * @Type("Playbloom\Satisfy\Model\Archive")
     */
    private $archive;

    /**
     * @var string
     * @Type("string")
     * @SerializedName("minimum-stability")
     */
    private $minimumStability = 'dev';

    /**
     * @var bool
     * @Type("boolean")
     */
    private $providers = false;

    /**
     * @var string|null
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
     * @var array
     * @Type("array")
     */
    private $config;

    /**
     * @var string|null
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(string $description = null)
    {
        $this->description = $description;
    }

    /**
     * Get homepage
     *
     * @return string $homepage
     */
    public function getHomepage(): string
    {
        return $this->homepage;
    }

    /**
     * @param string $homepage
     */
    public function setHomepage(string $homepage)
    {
        $this->homepage = $homepage;
    }

    /**
     * @return string|null
     */
    public function getOutputDir()
    {
        return $this->outputDir;
    }

    /**
     * @param string $outputDir
     */
    public function setOutputDir(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    /**
     * @return bool
     */
    public function isOutputHtml(): bool
    {
        return $this->outputHtml;
    }

    /**
     * @param bool $outputHtml
     * @return $this
     */
    public function setOutputHtml(bool $outputHtml)
    {
        $this->outputHtml = $outputHtml;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequireAll(): bool
    {
        return $this->requireAll;
    }

    /**
     * @param bool $requireAll
     */
    public function setRequireAll(bool $requireAll)
    {
        $this->requireAll = $requireAll;
    }

    /**
     * @return bool
     */
    public function isRequireDependencies(): bool
    {
        return $this->requireDependencies;
    }

    /**
     * @param bool $requireDependencies
     */
    public function setRequireDependencies(bool $requireDependencies)
    {
        $this->requireDependencies = $requireDependencies;
    }

    /**
     * @return bool
     */
    public function isRequireDevDependencies(): bool
    {
        return $this->requireDevDependencies;
    }

    /**
     * @param bool $requireDevDependencies
     */
    public function setRequireDevDependencies(bool $requireDevDependencies)
    {
        $this->requireDevDependencies = $requireDevDependencies;
    }

    /**
     * @return string|null
     */
    public function getIncludeFilename()
    {
        return $this->includeFilename;
    }

    /**
     * @param string|null $includeFilename
     * @return $this
     */
    public function setIncludeFilename(string $includeFilename = null)
    {
        if (empty($includeFilename)) {
            $includeFilename = null;
        }
        $this->includeFilename = $includeFilename;

        return $this;
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
     * @return Archive|null
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * @return string|null
     */
    public function getMinimumStability()
    {
        return $this->minimumStability;
    }

    /**
     * @param string $minimumStability
     */
    public function setMinimumStability(string $minimumStability)
    {
        $this->minimumStability = $minimumStability;
    }

    /**
     * @return bool
     */
    public function isProviders()
    {
        return $this->providers;
    }

    /**
     * @param bool $providers
     * @return $this
     */
    public function setProviders(bool $providers)
    {
        $this->providers = $providers;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwigTemplate()
    {
        return $this->twigTemplate;
    }

    /**
     * @param string $twigTemplate
     */
    public function setTwigTemplate(string $twigTemplate = null)
    {
        $this->twigTemplate = $twigTemplate;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        if (empty ($this->config)) {
            return '';
        }

        return json_encode($this->config);
    }

    /**
     * @param string $config
     */
    public function setConfig(string $config)
    {
        if (empty($config)) {
            $this->config = null;
        } else {
            $this->config = json_decode($config, true);
        }
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

    /**
     * @return string|null
     */
    public function getNotifyBatch()
    {
        return $this->notifyBatch;
    }

    /**
     * @param string $notifyBatch
     */
    public function setNotifyBatch(string $notifyBatch = null)
    {
        $this->notifyBatch = $notifyBatch;
    }
}
