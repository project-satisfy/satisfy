<?php

$app = require_once __DIR__ . '/bootstrap.php';

/**
 * Homepage, shows satis index.html file if available
 * and adds a link to satisfy backend.
 * @var \Silex\Application $app
 */
$app->get('/', function () use ($app) {
    $indexPath = __DIR__ . '/../web/index.html';
    if (file_exists($indexPath)) {
        return $app->sendFile($indexPath);
    }

    return <<<HTML404
<html>
    <head>
        <title>Composer Repository currently not available</title>
    </head>
    <body>
        <h1>Composer Repository currently not available.</h1>
        <p><a href="/admin/">Manage Repositories</a></p>
    </body>
</html>
HTML404;

})->bind('home');

$app->mount('/admin/', new \Playbloom\Satisfy\Controller\SecurityController());
$app->mount('/admin/', new \Playbloom\Satisfy\Controller\RepositoryController());

return $app;
