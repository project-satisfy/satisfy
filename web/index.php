<?php

if('cli-server' == php_sapi_name() && is_file(__DIR__.$_SERVER['REQUEST_URI'])) {
    // Do not handle requests to static files, when using the PHP integrated webserver.
    return false;
}

$app = require_once __DIR__.'/../app/satisfy.php';
$app->run();
