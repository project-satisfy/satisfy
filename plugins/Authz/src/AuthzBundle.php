<?php

namespace Playbloom\Plugin\Authz;

use Playbloom\Plugin\AbstractPlugin;

/**
 * Per-token access to the catalogue, with per-package grants whose dependency
 * closure is computed from the catalogue's p2 metadata.
 *
 * nginx asks public/verify.php about every catalogue request through
 * auth_request; that endpoint runs without the kernel. Sites and grants are
 * managed with `bin/console authz` and at /admin/authz.
 */
class AuthzBundle extends AbstractPlugin
{
}
