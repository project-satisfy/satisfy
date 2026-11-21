<?php

namespace Playbloom\Tests\Plugin\Authz;

use PHPUnit\Framework\TestCase;
use Playbloom\Plugin\Authz\Uri;

/**
 * Request URIs as nginx passes them, and the package each is for.
 */
class UriTest extends TestCase
{
    /**
     * @dataProvider uris
     */
    public function testPackage(string $uri, ?string $path, ?string $package): void
    {
        $this->assertSame($path, Uri::path($uri));
        $this->assertSame($package, Uri::package($uri));
    }

    public static function uris(): iterable
    {
        yield ['/p2/acme/app.json', '/p2/acme/app.json', 'acme/app'];
        yield ['/p2/Acme/App~dev.json', '/p2/Acme/App~dev.json', 'acme/app'];
        yield ['/dist/acme/app/acme-app-1.0.0-abc123.zip', '/dist/acme/app/acme-app-1.0.0-abc123.zip', 'acme/app'];
        yield ['/p/acme/app$0123abcd.json', '/p/acme/app$0123abcd.json', 'acme/app'];
        yield ['/packages.json?x=1', '/packages.json', null];
        yield ['/include/all$0123abcd.json', '/include/all$0123abcd.json', null];
        yield ['/p/provider-latest$0123abcd.json', '/p/provider-latest$0123abcd.json', null];
        yield ['/', '/', null];
        yield ['/p2/x/../acme/app.json', '/p2/acme/app.json', 'acme/app'];
        yield ['//p2//acme/./app.json', '/p2/acme/app.json', 'acme/app'];
        yield ['/p2/acme/app%2Ejson', '/p2/acme/app.json', 'acme/app'];
        yield ['/include/', '/include/', null];
        yield ['/../p2/acme/app.json', null, null];
        yield ['/p2/acme/app.json#x', null, null];
        yield ['/p2/acme/app.json%23x', null, null];
        yield ['/p2/acme/app.json%3Fx', null, null];
        yield ['/p2/acme\\app.json', null, null];
        yield ['/p2/acme/app.json%00', null, null];
        yield ['', null, null];
    }
}
