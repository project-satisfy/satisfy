<?php

$app = require_once __DIR__ . '/bootstrap.php';

/**
 * Displays the Satis index, returns the unavailable page
 * when the Satis index.html isn't available.
 *
 * @param  \Silex\Application  $app
 * @return Symfony\Component\HttpFoundation\BinaryFileResponse|Symfony\Component\HttpFoundation\Response
 */
$app->get('/', function () use ($app) {
    $indexPath = __DIR__ . '/../web/index.html';

    if (! file_exists($indexPath)) {
        return $app['twig']->render('unavailable.html.twig');
    }

    return $app->sendFile($indexPath);
})->bind('home');

$app->mount('/admin/', new \Playbloom\Satisfy\Controller\SecurityController());
$app->mount('/admin/', new \Playbloom\Satisfy\Controller\RepositoryController());

return $app;
