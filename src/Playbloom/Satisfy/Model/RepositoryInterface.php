<?php

namespace Playbloom\Satisfy\Model;

/**
 * Repository interface
 *
 * Represent a composer repository definition
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
interface RepositoryInterface
{
    /**
     * Get unique identifier.
     *
     * @return string
     */
    public function getId();

    /**
     * Get the repository type
     *
     * @return string
     */
    public function getType();

    /**
     * Set repository type.
     *
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Get the repository host/url
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set repository host/url.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url);
}
