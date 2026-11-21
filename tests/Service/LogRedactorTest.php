<?php

namespace Playbloom\Tests\Service;

use PHPUnit\Framework\TestCase;
use Playbloom\Service\LogRedactor;

class LogRedactorTest extends TestCase
{
    /**
     * @dataProvider lines
     */
    public function testCredentialsAreReplaced(string $line, string $expected): void
    {
        $this->assertSame($expected, LogRedactor::redact($line));
    }

    public static function lines(): iterable
    {
        yield 'a user and password in a URL' => [
            "Failed to execute git clone --mirror -- 'https://deploy:s3cret@git.acme.test/acme/app.git'",
            "Failed to execute git clone --mirror -- 'https://***@git.acme.test/acme/app.git'",
        ];
        yield 'a token as a URL\'s user' => [
            'Cloning https://glpat-AbCdEf1234567890xyz@gitlab.acme.test/acme/app.git',
            'Cloning https://***@gitlab.acme.test/acme/app.git',
        ];
        yield 'a GitHub token on its own' => [
            'token ghp_AbCdEf1234567890AbCdEf1234567890abcd rejected',
            'token ghp_*** rejected',
        ];
        yield 'an Authorization header' => [
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.e30.abc',
            'Authorization: Bearer ***',
        ];
        yield 'a PRIVATE-TOKEN header' => ['PRIVATE-TOKEN: abc123def', 'PRIVATE-TOKEN: ***'];
        yield 'COMPOSER_AUTH on a command line' => ['COMPOSER_AUTH={"x":1} composer install', 'COMPOSER_AUTH=*** composer install'];
        yield 'http-basic in COMPOSER_AUTH' => [
            '{"http-basic": {"git.acme.test": {"username": "deploy", "password": "s3cret"}}}',
            '{"http-basic": {"***": "***"}}',
        ];
        yield 'github-oauth in an auth.json' => ['"github-oauth": {"github.com": "ghp_abc"}', '"github-oauth": {"***": "***"}'];
        yield 'a password in JSON' => ['{"password": "s3cret", "user": "deploy"}', '{"password": "***", "user": "deploy"}'];
        yield 'scp style, which carries no credential' => ['git@github.com:acme/app.git', 'git@github.com:acme/app.git'];
        yield 'an ordinary line' => [
            'Could not find a version of package acme/app matching your minimum-stability (stable).',
            'Could not find a version of package acme/app matching your minimum-stability (stable).',
        ];
    }
}
