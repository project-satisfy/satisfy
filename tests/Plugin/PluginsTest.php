<?php

namespace Playbloom\Tests\Plugin;

use PHPUnit\Framework\TestCase;
use Playbloom\Kernel;
use Playbloom\Plugin\Authz\AuthzBundle;
use Playbloom\Plugin\Hookgate\HookgateBundle;
use Playbloom\Plugin\Plugins;
use Symfony\Bundle\TwigBundle\TwigBundle;

class PluginsTest extends TestCase
{
    public function testNamesAndClassesBecomeBundles(): void
    {
        $this->assertSame([
            AuthzBundle::class => ['all' => true],
            HookgateBundle::class => ['all' => true],
            TwigBundle::class => ['all' => true],
        ], Plugins::bundles(' authz , Hookgate,,' . TwigBundle::class));
    }

    /**
     * Flex rewrites config/bundles.php as a plain list whenever it installs a
     * bundle, so the kernel adds the plugins itself.
     */
    public function testTheKernelEnablesPluginsThatBundlesPhpDoesNotList(): void
    {
        $_SERVER['SATISFY_PLUGINS'] = 'hookgate';
        try {
            $classes = array_map(get_class(...), iterator_to_array((new Kernel('test', false))->registerBundles(), false));
        } finally {
            unset($_SERVER['SATISFY_PLUGINS']);
        }

        $this->assertNotContains(HookgateBundle::class, array_keys(require __DIR__ . '/../../config/bundles.php'));
        $this->assertContains(HookgateBundle::class, $classes);
    }

    public function testNoPluginsByDefault(): void
    {
        $this->assertSame([], Plugins::bundles(''));
    }

    public function testAnUnknownNameIsAnError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Plugins::bundles('authz,nonesuch');
    }
}
