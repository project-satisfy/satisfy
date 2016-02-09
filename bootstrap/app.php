<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we create a new Silex application instance, this is the central
| part of the application and is used to combine the different components
| of the application.
|
*/

$app = new \Silex\Application([
    'path.base' => rtrim(realpath(__DIR__.'/../'), '\/'),
]);

/*
|--------------------------------------------------------------------------
| Application paths
|--------------------------------------------------------------------------
|
| We define some application path for easy access. We don't like hard coded
| paths that pop in various places in the application.
|
*/

foreach (['app', 'config', 'resources', 'storage', 'web'] as $path) {
    $app['path.'.$path] = $app['path.base'].DIRECTORY_SEPARATOR.$path;
}

/*
|--------------------------------------------------------------------------
| Share the application
|--------------------------------------------------------------------------
|
| We make the application globally available by binding it to a container.
| This makes it possible to retreive the active instance of the application
| in later stages of the application.
|
*/

\Playbloom\Satisfy\Services\Container::setInstance($app);

/*
|--------------------------------------------------------------------------
| Register annotation loader
|--------------------------------------------------------------------------
|
| The annotation registry requires a class loader we define it here.
|
*/

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([
    require base_path('vendor/autoload.php'),
    'loadClass'
]);

/*
|--------------------------------------------------------------------------
| Register config provider
|--------------------------------------------------------------------------
|
| We register the application's config provider first to make sure the
| remaining application providers get their respective configurations.
|
*/

$app->register(new \Playbloom\Satisfy\Providers\ConfigServiceProvider);

$app['debug'] = isset($app['config']['debug']) ? $app['config']['debug'] : false;

/*
|--------------------------------------------------------------------------
| Register the application providers
|--------------------------------------------------------------------------
|
| We register the application providers maintaining various parts of the
| application. This makes the bootstrapping process of the application
| more manageable and cleaner.
|
*/

$app->register(new \Silex\Provider\FormServiceProvider);
$app->register(new \Silex\Provider\SerializerServiceProvider);
$app->register(new \Silex\Provider\SessionServiceProvider);
$app->register(new \Silex\Provider\TranslationServiceProvider);
$app->register(new \Silex\Provider\TwigServiceProvider, ['twig.path' => resources_path('views')]);
$app->register(new \Silex\Provider\UrlGeneratorServiceProvider);
$app->register(new \Silex\Provider\ValidatorServiceProvider);

$app->register(new \Playbloom\Satisfy\Providers\AuthServiceProvider);
$app->register(new \Playbloom\Satisfy\Providers\RouteServiceProvider);
$app->register(new \Playbloom\Satisfy\Providers\SatisServiceProvider);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
