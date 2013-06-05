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
     * Get the repository type
     *
     * @return string
     */
    public function getType();

    /**
     * Get the repository host/url
     *
     * @return string
     */
    public function getUrl();
}
