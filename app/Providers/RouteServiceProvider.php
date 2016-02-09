<?php

namespace Playbloom\Satisfy\Providers;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

class RouteServiceProvider implements ServiceProviderInterface
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
        // ...
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
        $app['routes'] = $app->extend('routes', function (RouteCollection $routes, Application $app) {
            $loader = new YamlFileLoader(new FileLocator(config_path()));
            $collection = $loader->load('routes.yml');
            $routes->addCollection($collection);

            return $routes;
        });
    }
}
