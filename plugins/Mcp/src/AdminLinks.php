<?php

namespace Playbloom\Plugin\Mcp;

use Playbloom\Plugin\AdminLinkInterface;

/**
 * The tokens page, in the admin navigation.
 */
final class AdminLinks implements AdminLinkInterface
{
    public function getAdminLinks(): array
    {
        return ['MCP tokens' => 'mcp_tokens'];
    }
}
