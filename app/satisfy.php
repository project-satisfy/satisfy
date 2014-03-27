<?php

$app = require_once __DIR__.'/bootstrap.php';

/**
 * Homepage, shows satis index.html file if available
 * and adds a link to satisfy backend.
 */
$app->get('/', function() use ($app) {
        $indexPath = __DIR__.'/../web/index.html';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath);
        }else{
            $content = <<<HTML404
<html>
    <head>
        <title>Composer Repository currently not available</title>
    </head>
    <body>
        <h1>Composer Repository currently not available.</h1>
    </body>
</html>
HTML404;
        }

        // Add Link to satisfy backend
        $content = str_ireplace('</body>', '<p><a href="/admin/">Manage Repositories</a></p></body>', $content);

        return $content;
})->bind('home');

$app->mount('/admin/', new \Playbloom\Satisfy\Controller\SecurityController());
$app->mount('/admin/', new \Playbloom\Satisfy\Controller\RepositoryController());

return $app;
