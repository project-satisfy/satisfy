<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Repository implements RepositoryInterface
{
    /**
     * @Type("string")
     */
    private $type;

    /**
     * @Type("string")
     */
    private $url;

    /**
     * @var string
     * @Type("string")
     * @SerializedName("installation-source")
     */
    private $installationSource = 'dist';
    /**
     * @Type("array")
     */
    private $package;

    public function __construct(
      $url = null,
      string $type = 'git',
      $package = null
    ) {
        $this->url = $url;
        $this->type = $type;
        $this->package = $package;

        if ('package' == $this->type) {
            $this->package = [
              'type' => '',
              'name' => '',
              'dist' => [
                'type' => '',
                'url' => '',
              ],
              'source' => [
                'type' => '',
                'url' => '',
              ],
            ];
        }
    }

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->type == 'package') {
            if (empty($this->package['name'])) {
                $context->buildViolation('This value should not be blank.')
                  ->atPath('packageName')
                  ->addViolation();
            }

            if (empty($this->package['version'])) {
                $context->buildViolation('This value should not be blank.')
                  ->atPath('packageVersion')
                  ->addViolation();
            }

            if (empty($this->package['type'])) {
                $context->buildViolation('This value should not be blank.')
                  ->atPath('packageType')
                  ->addViolation();
            }

            if (empty($this->package['dist']['type'])) {
                $context->buildViolation('This value should not be blank.')
                  ->atPath('packageDistType')
                  ->addViolation();
            }

            if (empty($this->package['dist']['url'])) {
                $context->buildViolation('This value should not be blank.')
                  ->atPath('packageDistUrl')
                  ->addViolation();
            }
        } else {
            if (empty($this->url)) {
                $context->buildViolation('This value should not be blank.')
                  ->atPath('url')
                  ->addViolation();
            }
        }
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * Get identifier
     */
    public function getId(): string
    {
        return md5($this->getLabel());
    }

    /**
     * Get repository lable
     */
    public function getLabel(): string
    {
        if ('package' == $this->type) {
            $url = $this->package['dist']['url'] ?? '';
        } else {
            $url = $this->url;
        }

        return "{$this->type}: {$url}";
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(string $type): RepositoryInterface
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl($url): RepositoryInterface
    {
        $this->url = $url;

        return $this;
    }

    public function getPackage()
    {
        if ('package' == $this->type) {
            return $this->package;
        }

        return null;
    }

    public function setPackage($package): RepositoryInterface
    {
        $this->package = $package;

        return $this;
    }
        
    /**
     * {@inheritdoc}
     */
    public function getInstallationSource(): string
    {
        return $this->installationSource;
    }

    /**
     * {@inheritdoc}
     */
    public function setInstallationSource(string $installationSource): RepositoryInterface
    {
        $this->installationSource = $installationSource;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageName(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['name'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageName($version): RepositoryInterface
    {
        $this->package['name'] = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageVersion(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['version'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageVersion($version): RepositoryInterface
    {
        $this->package['version'] = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageLicense(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['license'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageLicense($version): RepositoryInterface
    {
        $this->package['license'] = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageType(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['type'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageType($type): RepositoryInterface
    {
        $this->package['type'] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageDistType(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['dist']['type'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageDistType($type): RepositoryInterface
    {
        $this->package['dist']['type'] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageDistUrl(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['dist']['url'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageDistUrl($url): RepositoryInterface
    {
        $this->package['dist']['url'] = $url;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageDistSha1Checksum(): string
    {
        if ('package' !== $this->type) {
            return '';
        }

        return $this->package['dist']['shasum'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageDistSha1Checksum($url): RepositoryInterface
    {
        $this->package['dist']['shasum'] = $url;

        return $this;
    }
}
