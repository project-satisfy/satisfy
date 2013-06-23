<?php

$app = require_once __DIR__.'/bootstrap.php';

/**
 * Homepage, shows satis index.html file if available.
 */
$app->get('/', function() use ($app) {
        $indexPath = __DIR__.'/../web/index.html';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath);
        }else{
            $content = '<h1>Composer Repository currently not available.</h1>';
        }

        return $content;
})->bind('home');

$app->mount('/admin/', new \Playbloom\Satisfy\Controller\RepositoryController());

return $app;
