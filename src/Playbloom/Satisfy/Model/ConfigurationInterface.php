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
    public function getName();

    public function getHomepage();

    public function getRepositories();
}
