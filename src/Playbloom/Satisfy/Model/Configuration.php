<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use PhpCollection\Map;
use Webmozart\Assert\Assert;

/**
 * Configuration class
 *
 * Represent a satis configuration file
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Configuration
{
    public const DEFAULT_OUTPUT_DIR = 'web';

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
    private $outputDir = self::DEFAULT_OUTPUT_DIR;

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
     * @var PackageConstraint[]
     * @Type("RequireCollection<Playbloom\Satisfy\Model\PackageConstraint>")
     * @SerializedName("require")
     */
    private $require;

    /**
     * @var bool
     * @Type("boolean")
     * @SerializedName("require-all")
     */
    private $requireAll = false;

    /**
     * @var bool
     * @Type("boolean")
     * @SerializedName("require-dependencies")
     */
    private $requireDependencies = false;

    /**
     * @var bool
     * @Type("boolean")
     * @SerializedName("require-dev-dependencies")
     */
    private $requireDevDependencies = false;

    /**
     * @var bool
     * @Type("boolean")
     * @SerializedName("require-dependency-filter")
     */
    private $requireDependencyFilter = true;

    /**
     * @var string[]
     * @Type("array")
     * @SerializedName("strip-hosts")
     */
    private $stripHosts;

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
     * @var int|null
     * @Type("integer")
     * @SerializedName("providers-history-size")
     */
    private $providersHistorySize;

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
     * @var array|null
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
     * @var string|null
     * @Type("string")
     * @SerializedName("_comment")
     */
    private $comment;

    /**
     * @var bool
     * @Type("boolean")
     * @SerializedName("pretty-print")
     */
    private $prettyPrint = true;

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

    public function setOutputDir(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function isOutputHtml(): bool
    {
        return $this->outputHtml;
    }

    public function setOutputHtml(bool $outputHtml)
    {
        $this->outputHtml = $outputHtml;

        return $this;
    }

    public function isRequireAll(): bool
    {
        return $this->requireAll;
    }

    public function setRequireAll(bool $requireAll)
    {
        $this->requireAll = $requireAll;
    }

    public function isRequireDependencies(): bool
    {
        return $this->requireDependencies;
    }

    public function setRequireDependencies(bool $requireDependencies)
    {
        $this->requireDependencies = $requireDependencies;
    }

    public function isRequireDevDependencies(): bool
    {
        return $this->requireDevDependencies;
    }

    public function setRequireDevDependencies(bool $requireDevDependencies)
    {
        $this->requireDevDependencies = $requireDevDependencies;
    }

    public function isRequireDependencyFilter(): bool
    {
        return $this->requireDependencyFilter;
    }

    /**
     * @return $this
     */
    public function setRequireDependencyFilter(bool $requireDependencyFilter)
    {
        $this->requireDependencyFilter = $requireDependencyFilter;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIncludeFilename()
    {
        return $this->includeFilename;
    }

    /**
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
     * @return $this
     */
    public function setRepositories(Map $repositories)
    {
        $this->repositories = $repositories;

        return $this;
    }

    /**
     * @return PackageConstraint[]
     */
    public function getRequire(): array
    {
        return $this->require ?: [];
    }

    /**
     * @param PackageConstraint[] $require
     *
     * @return $this
     */
    public function setRequire(array $require)
    {
        Assert::allIsInstanceOf($require, PackageConstraint::class);
        $this->require = $require;

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

    public function getConfig(): string
    {
        if (empty($this->config)) {
            return '';
        }

        return json_encode($this->config);
    }

    public function setConfig(string $config)
    {
        if (empty($config)) {
            $this->config = null;
        } else {
            $this->config = json_decode($config, true);
        }
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

    public function isPrettyPrint(): bool
    {
        return $this->prettyPrint;
    }

    /**
     * @return $this
     */
    public function setPrettyPrint(bool $prettyPrint)
    {
        $this->prettyPrint = $prettyPrint;

        return $this;
    }
}
