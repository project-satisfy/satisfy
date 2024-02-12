<?php

namespace Playbloom\Satisfy\Model;

use stdClass;
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
    public const DEFAULT_OUTPUT_DIR = 'public';

    /**
     * @var string
     */
    private $name = 'localhost/repository';

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
     *
     * @SerializedName("output-dir")
     */
    private $outputDir = self::DEFAULT_OUTPUT_DIR;

    /**
     * @var bool
     *
     * @SerializedName("output-html")
     */
    private $outputHtml = true;

    /**
     * @var RepositoryInterface[]|\ArrayIterator
     */
    private $repositories;

    /**
     * @var PackageConstraint[]
     *
     * @SerializedName("require")
     */
    private $require;

    /**
     * @var bool
     *
     * @SerializedName("require-all")
     */
    private $requireAll = false;

    /**
     * @var bool
     *
     * @SerializedName("require-dependencies")
     */
    private $requireDependencies = false;

    /**
     * @var bool
     *
     * @SerializedName("require-dev-dependencies")
     */
    private $requireDevDependencies = false;

    /**
     * @var bool
     *
     * @SerializedName("require-dependency-filter")
     */
    private $requireDependencyFilter = true;

    /**
     * @var string[]
     *
     * @SerializedName("strip-hosts")
     */
    private $stripHosts;

    /**
     * @var string|null
     *
     * @SerializedName("include-filename")
     */
    private $includeFilename;

    /**
     * @var Archive|null
     */
    private $archive;

    /**
     * @var string|null
     *
     * @SerializedName("minimum-stability")
     */
    private $minimumStability = 'dev';

    /**
     * @var PackageStability[]
     *
     * @SerializedName("minimum-stability-per-package")
     */
    private $minimumStabilityPerPackage = [];

    /**
     * @var bool
     */
    private $providers = false;

    /**
     * @var int|null
     *
     * @SerializedName("providers-history-size")
     */
    private $providersHistorySize;

    /**
     * @var string|null
     *
     * @SerializedName("twig-template")
     */
    private $twigTemplate;

    /**
     * @var Abandoned[]
     */
    private array $abandoned = [];

    /**
     * @var PackageConstraint[]
     */
    private array $blacklist = [];

    /**
     * @var mixed[]|null
     */
    private $config;

    /**
     * @var string|null
     *
     * @SerializedName("notify-batch")
     */
    private $notifyBatch;

    /**
     * @var string|null
     *
     * @SerializedName("_comment")
     */
    private $comment;

    /**
     * @var bool
     *
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

    public function setDescription(?string $description = null): void
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

    public function setIncludeFilename(?string $includeFilename = null): self
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
     * @param \ArrayIterator|array|RepositoryInterface[] $repositories
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

    public function setMinimumStability(?string $minimumStability): void
    {
        $this->minimumStability = $minimumStability;
    }

    /**
     * @return PackageStability[]
     */
    public function getMinimumStabilityPerPackage(): array
    {
        return $this->minimumStabilityPerPackage;
    }

    /**
     * @param PackageStability[] $minimumStabilityPerPackage
     */
    public function setMinimumStabilityPerPackage(array $minimumStabilityPerPackage): void
    {
        $this->minimumStabilityPerPackage = $minimumStabilityPerPackage;
    }

    public function addMinimumStabilityPerPackage(string $package, string $stability): void
    {
        $this->minimumStabilityPerPackage[] = new PackageStability($package, $stability);
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

    public function setTwigTemplate(?string $twigTemplate = null): void
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

    /**
     * @return string[]|null
     */
    public function getStripHosts(): ?array
    {
        return $this->stripHosts;
    }

    /**
     * @param string[] $stripHosts
     */
    public function setStripHosts(array $stripHosts): void
    {
        $this->stripHosts = $stripHosts;
    }

    public function getProvidersHistorySize(): ?int
    {
        return $this->providersHistorySize;
    }

    public function setProvidersHistorySize(?int $providersHistorySize): void
    {
        $this->providersHistorySize = $providersHistorySize;
    }

    /**
     * @return Abandoned[]|null
     */
    public function getAbandoned(): ?array
    {
        return $this->abandoned;
    }

    /**
     * @param Abandoned[]|null $abandoned
     */
    public function setAbandoned(?array $abandoned): void
    {
        $this->abandoned = $abandoned;
    }

    public function getBlacklist(): ?array
    {
        return $this->blacklist;
    }

    public function setBlacklist(?array $blacklist): void
    {
        $this->blacklist = $blacklist;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }
}
