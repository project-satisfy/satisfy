<?php

namespace Playbloom\Satisfy\Providers;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param  \Silex\Application  $app
     */
    public function register(Application $app)
    {
        if (! class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
        }

        $app['config'] = Yaml::parse(config_path('config.yml'));
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param  \Silex\Application  $app
     */
    public function boot(Application $app)
    {
        // ...
    }
}
