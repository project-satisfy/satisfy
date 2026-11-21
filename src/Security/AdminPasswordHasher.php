<?php

namespace Playbloom\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * The hasher for the users in admin.users. A password there is a hash, as
 * `bin/console security:hash-password` makes one, or plaintext, as in 3.x.
 *
 * A hash is checked with password_verify(). Plaintext is compared in constant
 * time, and each sign-in with it logs a warning. A value that is a hash is
 * never compared as plaintext, so typing the hash itself does not sign in, as
 * it would through Symfony's migrate_from with the plaintext hasher.
 */
final class AdminPasswordHasher implements PasswordHasherInterface
{
    private const MAX_LENGTH = 4096;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return password_hash($plainPassword, \PASSWORD_DEFAULT);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ('' === $plainPassword || '' === $hashedPassword || \strlen($plainPassword) > self::MAX_LENGTH) {
            return false;
        }
        if (self::isHash($hashedPassword)) {
            return password_verify($plainPassword, $hashedPassword);
        }

        $this->logger->warning('A password in admin.users is plaintext. Replace it with the output of bin/console security:hash-password.');

        return hash_equals($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return !self::isHash($hashedPassword) || password_needs_rehash($hashedPassword, \PASSWORD_DEFAULT);
    }

    private static function isHash(string $value): bool
    {
        return null !== password_get_info($value)['algo'];
    }
}
