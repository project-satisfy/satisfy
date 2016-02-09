<?php

namespace Playbloom\Satisfy\Services;

use Silex\Application;

class Container
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  \Silex\Application  $instance
     */
    public static function setInstance(Application $instance)
    {
        static::$instance = $instance;
    }
}
