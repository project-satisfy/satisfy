<?php

namespace Playbloom\Tests\Plugin\Mcp;

use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Playbloom\Plugin\Authz\AuthzConfig;
use Playbloom\Plugin\Mcp\PackageUsage;
use Playbloom\Plugin\Mcp\RemovalGuard;
use Symfony\Component\Filesystem\Filesystem;

class PackageUsageTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/satisfy-mcp-usage-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $entries = [
            ['ts' => time() - 40 * 86400, 'site' => 'old-site', 'package' => 'acme/old', 'allowed' => true],
            ['ts' => time() - 2 * 86400, 'site' => 'shop', 'package' => 'acme/app', 'allowed' => true],
            ['ts' => time() - 86400, 'site' => 'blog', 'package' => 'acme/app', 'allowed' => true],
            ['ts' => time() - 3600, 'site' => 'intruder', 'package' => 'acme/lib', 'allowed' => false],
            ['ts' => time() - 60, 'site' => 'shop', 'package' => 'acme/app', 'allowed' => true],
        ];
        file_put_contents($this->dir . '/audit.jsonl', implode('', array_map(static fn (array $entry): string => json_encode($entry) . "\n", $entries)));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testRecentFetchesComeFromTheAuditLog(): void
    {
        $usage = new PackageUsage(new AuthzConfig($this->dir, $this->dir));

        $this->assertSame(
            ['acme/app' => ['shop', 'blog'], 'acme/old' => [], 'acme/lib' => []],
            $usage->recentFetches(['acme/app', 'acme/old', 'acme/lib']),
        );
    }

    public function testWithoutAuthzUsageIsUnknown(): void
    {
        $this->assertNull((new PackageUsage())->recentFetches(['acme/app']));
    }

    public function testTheGuardNeedsForceForAPackageInUse(): void
    {
        $guard = new RemovalGuard(new PackageUsage(new AuthzConfig($this->dir, $this->dir)));

        $this->assertSame(['acme/old' => []], $guard->check(['acme/old'], false, 'Removing acme/old'));
        $this->assertSame(['acme/app' => ['shop', 'blog']], $guard->check(['acme/app'], true, 'Removing acme/app'));

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('acme/app by shop, blog');
        $guard->check(['acme/app'], false, 'Removing acme/app');
    }
}
