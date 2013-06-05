<?php

$app = require_once __DIR__.'/bootstrap.php';

/**
 * Homepage, lists repositories
 */
$app->get('/', function() use ($app) {
    return $app['twig']->render('home.html.twig');
})->bind('home');

$app->mount('/', new \Playbloom\Satisfy\Controller\RepositoryController());

return $app;
