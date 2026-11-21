<?php

namespace Playbloom\Tests\Security;

use PHPUnit\Framework\TestCase;
use Playbloom\Security\AdminPasswordHasher;
use Psr\Log\AbstractLogger;

class AdminPasswordHasherTest extends TestCase
{
    public function testAHashIsCheckedAsAHash(): void
    {
        $logger = new RecordingLogger();
        $hasher = new AdminPasswordHasher($logger);
        $hash = $hasher->hash('secret');

        $this->assertTrue($hasher->verify($hash, 'secret'));
        $this->assertFalse($hasher->verify($hash, 'wrong'));
        $this->assertFalse($hasher->needsRehash($hash));
        $this->assertSame([], $logger->messages);
    }

    public function testTheHashItselfIsNotAPassword(): void
    {
        $hasher = new AdminPasswordHasher(new RecordingLogger());
        $hash = $hasher->hash('secret');

        $this->assertFalse($hasher->verify($hash, $hash));
    }

    public function testPlaintextStillSignsInWithAWarning(): void
    {
        $logger = new RecordingLogger();
        $hasher = new AdminPasswordHasher($logger);

        $this->assertTrue($hasher->verify('test', 'test'));
        $this->assertFalse($hasher->verify('test', 'Test'));
        $this->assertTrue($hasher->needsRehash('test'));
        $this->assertCount(2, $logger->messages);
        $this->assertStringContainsString('security:hash-password', $logger->messages[0]);
    }

    public function testAnEmptyPasswordNeverSignsIn(): void
    {
        $hasher = new AdminPasswordHasher(new RecordingLogger());

        $this->assertFalse($hasher->verify('', ''));
        $this->assertFalse($hasher->verify('test', ''));
    }
}

/**
 * Keeps what is logged, for the tests to read.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $messages = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }
}
