<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var $loader ClassLoader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

/**
 * Load annotation
 */
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$app = new Silex\Application();

/**
 * Import configuration
 */
if (!file_exists(__DIR__.'/config.php')) {
    throw new RuntimeException('You must create your own configuration file ("app/config.php"). See "app/config.php.dist" for an example config file.');
}

require_once __DIR__.'/config.php';

// Debug mode
$app['debug'] = true;

// Service providers
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SerializerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Playbloom\Satisfy\Provider\SatisServiceProvider;
use Playbloom\Satisfy\Provider\SecurityServiceProvider;

$app->register(new UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => array(
        __DIR__.'/../src/Playbloom/Satisfy/Resources/views',
    ),
));
$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new SerializerServiceProvider());
$app->register(new TranslationServiceProvider(), array(
    'translator.messages' => array(),
));
$app->register(new SatisServiceProvider(), array(
    'satis.filename' => $app['satis.filename'],
    'satis.auditlog' => $app['satis.auditlog'],
    'satis.class' =>  $app['satis.class']
));
$app->register(new SecurityServiceProvider());

// Allow PUT & DELETE http method
use Symfony\Component\HttpFoundation\Request;

Request::enableHttpMethodParameterOverride();

// Define the Satis repository name
$app->before(function() use ($app) {
    // $app['twig']->addGlobal('title', $app['satis']->getName());
});

return $app;
