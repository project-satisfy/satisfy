<?php

use Playbloom\Satisfy\Services\Container;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string  $make
     * @return mixed|\Silex\Application
     */
    function app($make = null)
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        return Container::getInstance()[$make];
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param  string  $path
     * @return string
     */
    function app_path($path = '')
    {
        return app('path.app').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        return app('path.base').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param  string  $path
     * @return string
     */
    function config_path($path = '')
    {
        return app('path.config').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('resources_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param  string  $path
     * @return string
     */
    function resources_path($path = '')
    {
        return app('path.resources').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        return app('path.storage').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('web_path')) {
    /**
     * Get the path to the web folder.
     *
     * @param  string  $path
     * @return string
     */
    function web_path($path = '')
    {
        return app('path.web').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
