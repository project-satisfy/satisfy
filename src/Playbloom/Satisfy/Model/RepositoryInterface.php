<?php

namespace Playbloom\Satisfy\Model;

interface RepositoryInterface
{
    /**
     * Get unique identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the repository type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set repository type.
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Get the repository host/url
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set repository host/url.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self;
}
