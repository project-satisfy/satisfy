<?php

/*
 * nginx's auth_request endpoint for the catalogue. Plain PHP without the
 * kernel, because Composer asks it about every file of every update.
 *
 * nginx must pass the Authorization header explicitly (PHP does not populate
 * it behind FastCGI), the original URI as ORIGINAL_URI and the client address
 * as REAL_IP; docker/nginx.conf does.
 */

declare(strict_types=1);

use Playbloom\Plugin\Authz\AuthzConfig;
use Playbloom\Plugin\Authz\Verifier;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

[$status, $headers] = Verifier::create(AuthzConfig::fromEnvironment($root))->decide(
    (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''),
    (string) ($_SERVER['ORIGINAL_URI'] ?? ''),
    (string) ($_SERVER['REAL_IP'] ?? '')
);

foreach ($headers as $name => $value) {
    header($name . ': ' . $value);
}
http_response_code($status);
