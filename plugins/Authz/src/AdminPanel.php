<?php

namespace Playbloom\Plugin\Authz;

use Playbloom\Plugin\AdminPanelInterface;

/**
 * The sites asking for packages, on the admin home page.
 */
final class AdminPanel implements AdminPanelInterface
{
    public function __construct(
        private readonly AuthzConfig $config,
        private readonly Database $database,
        private readonly Entitlement $entitlement,
    ) {
    }

    /**
     * @return array{template: string, context: array<string, mixed>}
     */
    public function getAdminPanel(): array
    {
        $db = $this->database->writable();
        $requests = [];
        foreach ($this->entitlement->openRequests($db) as $row) {
            $requests[$row['site']][] = $row['package'];
        }
        $db->close();

        return [
            'template' => '@Authz/_panel.html.twig',
            'context' => ['requests' => $requests, 'enforcing' => $this->config->isEnforcing()],
        ];
    }
}
