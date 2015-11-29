<?php

namespace Playbloom\Satisfy\Model;

/**
 * Configuration interface
 *
 * Represent a satis configuration file
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
interface ConfigurationInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getHomepage();

    /**
     * @return Repository[]
     */
    public function getRepositories();
}
