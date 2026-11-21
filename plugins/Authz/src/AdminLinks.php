<?php

namespace Playbloom\Plugin\Authz;

use Playbloom\Plugin\AdminLinkInterface;

final class AdminLinks implements AdminLinkInterface
{
    public function getAdminLinks(): array
    {
        return ['Package access' => 'authz'];
    }
}
