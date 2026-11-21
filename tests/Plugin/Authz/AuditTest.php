<?php

namespace Playbloom\Tests\Plugin\Authz;

use PHPUnit\Framework\TestCase;
use Playbloom\Plugin\Authz\Audit;
use Playbloom\Plugin\Authz\AuthzConfig;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Reading the audit log, which grows by a line per catalogue request.
 */
class AuditTest extends TestCase
{
    private string $dir;

    private AuthzConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/satisfy-audit-' . bin2hex(random_bytes(4));
        $this->config = new AuthzConfig($this->dir, $this->dir . '/catalogue');
        $this->config->ensureDataDir();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
        parent::tearDown();
    }

    public function testTailReadsTheLastLinesAcrossBlocks(): void
    {
        $audit = new Audit($this->config);
        $lines = [];
        // About 300 KB: several of tail()'s blocks.
        for ($i = 0; $i < 3000; ++$i) {
            $audit->write(['site' => 'site-' . $i, 'uri' => '/p2/acme/' . str_repeat('x', 40) . '.json', 'ok' => true, 'ts' => $i]);
            $lines[] = (string) json_encode(['site' => 'site-' . $i, 'uri' => '/p2/acme/' . str_repeat('x', 40) . '.json', 'ok' => true, 'ts' => $i], JSON_UNESCAPED_SLASHES);
        }

        $this->assertSame(array_slice($lines, -1), $audit->tail(1));
        $this->assertSame(array_slice($lines, -40), $audit->tail(40));
        $this->assertSame(array_slice($lines, -2500), $audit->tail(2500));
        $this->assertSame($lines, $audit->tail(100000));
        $this->assertSame([], $audit->tail(0));
    }

    public function testTailOfNoLogIsEmpty(): void
    {
        $this->assertSame([], (new Audit($this->config))->tail(40));
    }

    public function testSummaryCountsWholeLinesOnly(): void
    {
        $audit = new Audit($this->config);
        $audit->write(['site' => 'site-a', 'ok' => true, 'ts' => 10]);
        $audit->write(['site' => 'site-a', 'ok' => true, 'ts' => 20]);
        $audit->write(['site' => null, 'ok' => false]);
        $audit->write(['site' => null, 'ok' => false, 'canary' => true]);
        $audit->write(['event' => 'issue', 'site' => 'site-b']);
        // A line still being written.
        file_put_contents($this->config->getAuditPath(), '{"site":"site-b","ok":tr', FILE_APPEND);

        $this->assertSame(
            ['observed' => ['site-a' => ['count' => 2, 'last' => 20]], 'unauth' => 1],
            $audit->summary()
        );
    }
}
