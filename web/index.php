<?php

if ('cli-server' == php_sapi_name() && is_file(__DIR__ . $_SERVER['REQUEST_URI'])) {
    // Do not handle requests to static files, when using the PHP integrated webserver.
    return false;
}

umask(2);

use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../app/autoload.php';

$kernel = new MicroKernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
