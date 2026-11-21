<?php

/*
 * Clears authz's caches in the PHP-FPM pool, so a revocation or a grant made
 * on the command line takes effect at once. It has to be a request: APCu's
 * memory belongs to the pool, and a command-line process has its own.
 *
 * POST with the header X-Authz-Admin-Key: <ADMIN_KEY>.
 */

declare(strict_types=1);

use Playbloom\Plugin\Authz\Audit;
use Playbloom\Plugin\Authz\AuthzConfig;
use Playbloom\Plugin\Authz\Cache;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

$config = AuthzConfig::fromEnvironment($root);
$audit = new Audit($config);
$ip = (string) ($_SERVER['REAL_IP'] ?? '');

$expected = $config->getAdminKey();
if ('' === $expected || !hash_equals($expected, (string) ($_SERVER['HTTP_X_AUTHZ_ADMIN_KEY'] ?? ''))) {
    $audit->write(['event' => 'purge_denied', 'ip' => $ip]);
    http_response_code(403);

    return;
}

$cleared = Cache::clear();
$audit->write(['event' => 'purge', 'ip' => $ip, 'cleared' => $cleared]);

header('Content-Type: application/json');
echo json_encode(['cleared' => $cleared], JSON_UNESCAPED_SLASHES), "\n";
