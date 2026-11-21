<?php

namespace Playbloom\Plugin\Mcp;

use Playbloom\Plugin\AbstractPlugin;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * An MCP server at /admin/mcp, for agents such as Claude Code: tools that read
 * satis.json, the catalogue and the builds, and, for a token issued with write
 * scope, tools that change satis.json and queue builds.
 *
 * Symfony's MCP bundle serves the protocol; this plugin enables it, names the
 * tools it exposes, and checks the token in Security\BearerTokenListener.
 */
class McpServerBundle extends AbstractPlugin
{
    public const PATH = '/admin/mcp';
    public const VERSION = '4.0.0-dev';

    public static function requiredBundles(): array
    {
        return [McpBundle::class];
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $server = [
            'version' => self::VERSION,
            'instructions' => 'Satisfy hosts a private Composer repository built by Satis from satis.json. The read tools show its repositories, its require list, the packages the last build published, and the builds. With a write token, the other tools change satis.json and queue builds; a change is published by the next full build.',
            'http' => ['path' => self::PATH],
            'registry' => ['tools' => [__NAMESPACE__ . '\\Tool\\']],
        ];
        // Read when the container is compiled, which the image does on every
        // start. Without it, the bundle answers only requests for localhost.
        $hosts = $_SERVER['SATISFY_MCP_ALLOWED_HOSTS'] ?? $_ENV['SATISFY_MCP_ALLOWED_HOSTS'] ?? getenv('SATISFY_MCP_ALLOWED_HOSTS');
        if (is_string($hosts) && '' !== trim($hosts)) {
            $server['http']['allowed_hosts'] = array_values(array_unique(array_filter(array_map('trim', explode(',', 'localhost,' . $hosts)))));
        }

        $builder->prependExtensionConfig('mcp', ['servers' => ['satisfy' => $server]]);
    }
}
