<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/config/bootstrap.php';

(new Dotenv())->usePutenv()->loadEnv(__DIR__ . '/../.env');

$appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$appDebug = (bool)($_ENV['APP_DEBUG'] ?? ($appEnv === 'dev'));
$trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? null;
$trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? null;

if ($appDebug) {
    umask(0000);
    Debug::enable();
}
if ($trustedProxies) {
    Request::setTrustedProxies(
        explode(',', $trustedProxies),
        Request::HEADER_X_FORWARDED_TRAEFIK
    );
    unset($trustedProxies);
}
if ($trustedHosts) {
    Request::setTrustedHosts([$trustedHosts]);
    unset($trustedHosts);
}

$kernel = new Kernel($appEnv, $appDebug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
