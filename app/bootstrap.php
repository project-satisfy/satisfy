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

require __DIR__.'/config.php';

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

$app['auth.use_login_form'] = isset($app['auth.use_login_form']) ? $app['auth.use_login_form'] : false;

if ($app['auth.use_login_form']) {

    $users = array();
    foreach ($app['auth.users'] as $username => $password) {
        $users[$username] = array('ROLE_ADMIN', $password);
    }


    $app['security.firewalls'] = array(
        //no authentication for the most part of the app
        'login' => array(
            'pattern' => '^/admin/login$',
            'anonymous' => true
        ),
        //admin section is protected by a authentication form
        'secured' => array(
            'pattern' => '^/admin/.*$',
            'form' => array(
                'login_path' => '/admin/login',
                'check_path' => '/admin/login_check'
            ),
            'logout' => array(
                'logout_path' => '/admin/logout',
                'target_url' => '/admin/'
            ),
            'users' => $users
        ),
    );

    $app->register(new \Silex\Provider\SecurityServiceProvider());

    $app['security.encoder.digest'] = $app->share(function () {
        return new Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder('sha1', false, 0);
    });

}

// Allow PUT & DELETE http method
use Symfony\Component\HttpFoundation\Request;

Request::enableHttpMethodParameterOverride();

// Default value for a new composer repository
$app['composer.repository.type_default'] = isset($app['composer.repository.type_default']) ? $app['composer.repository.type_default'] : 'git';
$app['composer.repository.url_default'] = isset($app['composer.repository.url_default']) ? $app['composer.repository.url_default'] : '';

return $app;
