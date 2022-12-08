<?php

namespace Playbloom\Satisfy\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Repository implements RepositoryInterface
{
    /** @var string */
    private $type;

    /** @var string */
    private $url;

    /**
     * @var string
     *
     * @SerializedName("installation-source")
     */
    private $installationSource = 'dist';

    public function __construct(string $url = '', string $type = 'git')
    {
        $this->url = $url;
        $this->type = $type;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    /**
     * Get identifier
     */
    public function getId(): string
    {
        return md5($this->getUrl());
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
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl(string $url): RepositoryInterface
    {
        $this->url = $url;

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
}
