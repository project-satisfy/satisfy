<?php

namespace Playbloom\Satisfy\Model;

interface RepositoryInterface
{
    /**
     * Get unique identifier.
     */
    public function getId(): string;

    /**
     * Get the repository type
     */
    public function getType(): string;

    /**
     * Set repository type.
     */
    public function setType(string $type): self;

    /**
     * Get the repository host/url
     */
    public function getUrl(): string;

    /**
     * Set repository host/url.
     */
    public function setUrl(string $url): self;
}
