<?php

namespace Playbloom\Plugin;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A plugin's buttons on the admin home page.
 */
#[AutoconfigureTag('satisfy.admin_link')]
interface AdminLinkInterface
{
    /**
     * @return array<string, string> label => route name
     */
    public function getAdminLinks(): array;
}
