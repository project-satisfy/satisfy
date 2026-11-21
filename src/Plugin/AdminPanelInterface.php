<?php

namespace Playbloom\Plugin;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A plugin's panel in the admin home page's side column.
 */
#[AutoconfigureTag('satisfy.admin_panel')]
interface AdminPanelInterface
{
    /**
     * @return array{template: string, context: array<string, mixed>}|null null when there is nothing to show
     */
    public function getAdminPanel(): ?array;
}
