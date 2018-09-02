<?php

namespace Playbloom\Satisfy\Model;

use JMS\Serializer\Annotation\Type;

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
}
