<?php

namespace Playbloom\Satisfy\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;
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
     * @var string
     */
    private $name = 'Composer repository';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string
     */
    private $homepage = 'http://localhost';

    /**
     * @var string
     * @SerializedName("output-dir")
     */
    private $outputDir = self::DEFAULT_OUTPUT_DIR;

    /**
     * @var bool
     * @SerializedName("output-html")
     */
    private $outputHtml = true;

    /**
     * @var RepositoryInterface[]|\ArrayIterator
     */
    private $repositories;

    /**
     * @var PackageConstraint[]
     * @SerializedName("require")
     */
    private $require;

    /**
     * @var bool
     * @SerializedName("require-all")
     */
    private $requireAll = false;

    /**
     * @var bool
     * @SerializedName("require-dependencies")
     */
    private $requireDependencies = false;

    /**
     * @var bool
     * @SerializedName("require-dev-dependencies")
     */
    private $requireDevDependencies = false;

    /**
     * @var bool
     * @SerializedName("require-dependency-filter")
     */
    private $requireDependencyFilter = true;

    /**
     * @var string[]
     * @SerializedName("strip-hosts")
     */
    private $stripHosts;

    /**
     * @var string|null
     * @SerializedName("include-filename")
     */
    private $includeFilename;

    /**
     * @var Archive|null
     */
    private $archive;

    /**
     * @var string
     * @SerializedName("minimum-stability")
     */
    private $minimumStability = 'dev';

    /**
     * @var bool
     */
    private $providers = false;

    /**
     * @var int|null
     * @SerializedName("providers-history-size")
     */
    private $providersHistorySize;

    /**
     * @var string|null
     * @SerializedName("twig-template")
     */
    private $twigTemplate;

    /**
     * @var \stdClass
     */
    private $abandoned;

    /**
     * @var array|null
     */
    private $config;

    /**
     * @var string|null
     * @SerializedName("notify-batch")
     */
    private $notifyBatch;

    /**
     * @var string|null
     * @SerializedName("_comment")
     */
    private $comment;

    /**
     * @var bool
     * @SerializedName("pretty-print")
     */
    private $prettyPrint = true;

    /**
     * Configuration constructor.
     */
    public function __construct()
    {
        $this->repositories = new \ArrayIterator();
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description = null): void
    {
        $this->description = $description;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function setHomepage(string $homepage): void
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

    public function setOutputDir(string $outputDir): void
    {
        $this->outputDir = $outputDir;
    }

    public function isOutputHtml(): bool
    {
        return $this->outputHtml;
    }

    public function setOutputHtml(bool $outputHtml): void
    {
        $this->outputHtml = $outputHtml;
    }

    public function isRequireAll(): bool
    {
        return $this->requireAll;
    }

    public function setRequireAll(bool $requireAll): void
    {
        $this->requireAll = $requireAll;
    }

    public function isRequireDependencies(): bool
    {
        return $this->requireDependencies;
    }

    public function setRequireDependencies(bool $requireDependencies): void
    {
        $this->requireDependencies = $requireDependencies;
    }

    public function isRequireDevDependencies(): bool
    {
        return $this->requireDevDependencies;
    }

    public function setRequireDevDependencies(bool $requireDevDependencies): void
    {
        $this->requireDevDependencies = $requireDevDependencies;
    }

    public function isRequireDependencyFilter(): bool
    {
        return $this->requireDependencyFilter;
    }

    public function setRequireDependencyFilter(bool $requireDependencyFilter): self
    {
        $this->requireDependencyFilter = $requireDependencyFilter;

        return $this;
    }

    public function getIncludeFilename(): ?string
    {
        return $this->includeFilename;
    }

    public function setIncludeFilename(string $includeFilename = null): self
    {
        if (empty($includeFilename)) {
            $includeFilename = null;
        }
        $this->includeFilename = $includeFilename;

        return $this;
    }

    /**
     * @return \ArrayIterator&iterable<RepositoryInterface>
     */
    public function getRepositories(): \ArrayIterator
    {
        return $this->repositories;
    }

    /**
     * @param RepositoryInterface[] $repositories
     *
     * @return $this
     */
    public function setRepositories($repositories): self
    {
        if (is_array($repositories)) {
            $repositories = new \ArrayIterator($repositories);
        }
        $this->repositories = $repositories;

        return $this;
    }

    /**
     * @return PackageConstraint[]|null
     */
    public function getRequire(): ?array
    {
        return $this->require;
    }

    /**
     * @param PackageConstraint[] $require
     *
     * @return $this
     */
    public function setRequire(array $require): self
    {
        Assert::allIsInstanceOf($require, PackageConstraint::class);
        $this->require = $require;

        return $this;
    }

    public function setArchive(?Archive $archive = null): void
    {
        $this->archive = $archive;
    }

    public function getArchive(): ?Archive
    {
        return $this->archive;
    }

    public function getMinimumStability(): ?string
    {
        return $this->minimumStability;
    }

    public function setMinimumStability(string $minimumStability): void
    {
        $this->minimumStability = $minimumStability;
    }

    public function isProviders(): bool
    {
        return $this->providers;
    }

    public function setProviders(bool $providers): self
    {
        $this->providers = $providers;

        return $this;
    }

    public function getTwigTemplate(): ?string
    {
        return $this->twigTemplate;
    }

    public function setTwigTemplate(string $twigTemplate = null): void
    {
        $this->twigTemplate = $twigTemplate;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig($config): void
    {
        if (empty($config)) {
            $this->config = null;
        } elseif (is_string($config)) {
            $this->config = json_decode($config, true);
        } else {
            $this->config = $config;
        }
    }

    public function getNotifyBatch(): ?string
    {
        return $this->notifyBatch;
    }

    public function setNotifyBatch(?string $notifyBatch = null)
    {
        $this->notifyBatch = $notifyBatch;
    }

    public function isPrettyPrint(): bool
    {
        return $this->prettyPrint;
    }

    public function setPrettyPrint(bool $prettyPrint): self
    {
        $this->prettyPrint = $prettyPrint;

        return $this;
    }
}
