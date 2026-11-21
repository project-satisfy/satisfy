<?php

namespace Playbloom\Plugin;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * plugin_admin_links(): the admin navigation's links from enabled plugins.
 * plugin_admin_panels(): their panels on the admin home page.
 */
class PluginLinksExtension extends AbstractExtension
{
    /**
     * @param iterable<AdminLinkInterface>  $providers
     * @param iterable<AdminPanelInterface> $panels
     */
    public function __construct(
        #[AutowireIterator('satisfy.admin_link')]
        private readonly iterable $providers,
        #[AutowireIterator('satisfy.admin_panel')]
        private readonly iterable $panels = [],
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('plugin_admin_links', [$this, 'getLinks']),
            new TwigFunction('plugin_admin_panels', [$this, 'getPanels']),
        ];
    }

    /**
     * @return array<string, string> label => route name
     */
    public function getLinks(): array
    {
        $links = [];
        foreach ($this->providers as $provider) {
            $links += $provider->getAdminLinks();
        }

        return $links;
    }

    /**
     * @return list<array{template: string, context: array<string, mixed>}>
     */
    public function getPanels(): array
    {
        $panels = [];
        foreach ($this->panels as $provider) {
            if (null !== $panel = $provider->getAdminPanel()) {
                $panels[] = $panel;
            }
        }

        return $panels;
    }
}
