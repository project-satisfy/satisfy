<?php

use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/config/bootstrap.php';

$appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$appDebug = (bool)($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? true);
$trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false;
$trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false;

if ($appDebug) {
    umask(0000);
    Debug::enable();
}
if ($trustedProxies) {
    Request::setTrustedProxies(
        explode(',', $trustedProxies),
        Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
    );
}
if ($trustedHosts) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($appEnv, $appDebug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
