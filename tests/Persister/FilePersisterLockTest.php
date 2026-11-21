<?php

namespace Playbloom\Tests\Persister;

use PHPUnit\Framework\TestCase;
use Playbloom\Persister\FilePersister;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The lock and the in-place write, on real files: what they exist for is how
 * flock() and inodes behave, which a virtual filesystem does not show.
 */
class FilePersisterLockTest extends TestCase
{
    private string $dir;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/satisfy-lock-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->file = $this->dir . '/satis.json';
        file_put_contents($this->file, '{"name": "before"}');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
        parent::tearDown();
    }

    public function testFlushWritesInPlace(): void
    {
        $inode = fileinode($this->file);

        $this->createPersister()->flush('{"name": "after"}');

        clearstatcache();
        $this->assertSame($inode, fileinode($this->file), 'A single-file bind mount would lose the file');
        $this->assertSame('{"name": "after"}', file_get_contents($this->file));
    }

    public function testFlushGivesUpWhileAnotherWriterHoldsTheLock(): void
    {
        $other = fopen($this->file, 'r+');
        $this->assertTrue(flock($other, LOCK_EX));

        $started = microtime(true);
        try {
            $this->createPersister(1)->flush('{"name": "after"}');
            $this->fail('The write went ahead while another writer held the lock');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('locked by another writer', (string) $exception->getPrevious()?->getMessage());
        }
        $this->assertGreaterThanOrEqual(1, microtime(true) - $started);
        $this->assertSame('{"name": "before"}', file_get_contents($this->file));

        flock($other, LOCK_UN);
        fclose($other);
        $this->createPersister(1)->flush('{"name": "after"}');
        $this->assertSame('{"name": "after"}', file_get_contents($this->file));
    }

    public function testNestedLocksAreReleasedByTheOutermostUnlock(): void
    {
        $persister = $this->createPersister();
        $persister->lock();
        $persister->lock();
        $persister->unlock();
        $this->assertFalse($this->otherWriterCanLock(), 'The inner unlock released the lock');

        $persister->unlock();
        $this->assertTrue($this->otherWriterCanLock());
    }

    public function testLoadAndWriteInsideTheLock(): void
    {
        $persister = $this->createPersister();
        $persister->lock();
        try {
            $this->assertSame('{"name": "before"}', $persister->load());
            $persister->flush('{"name": "after"}');
            $this->assertSame('{"name": "after"}', $persister->load());
        } finally {
            $persister->unlock();
        }
        $this->assertSame('{"name": "after"}', file_get_contents($this->file));
    }

    public function testVersionFollowsTheContent(): void
    {
        $persister = $this->createPersister();
        $version = $persister->getVersion();

        $this->assertSame($version, $persister->getVersion());
        file_put_contents($this->file, '{"name": "changed elsewhere"}');
        $this->assertNotSame($version, $persister->getVersion());
    }

    private function createPersister(int $lockTimeout = 10): FilePersister
    {
        return new FilePersister(new Filesystem(), $this->file, $this->dir . '/backups', $lockTimeout);
    }

    private function otherWriterCanLock(): bool
    {
        $other = fopen($this->file, 'r');
        $locked = flock($other, LOCK_EX | LOCK_NB);
        if ($locked) {
            flock($other, LOCK_UN);
        }
        fclose($other);

        return $locked;
    }
}
