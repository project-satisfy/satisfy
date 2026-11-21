<?php

namespace Playbloom\Tests\Plugin\Mcp;

use PHPUnit\Framework\TestCase;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Component\Filesystem\Filesystem;

class TokenStoreTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/satisfy-mcp-tokens-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testATokenIsFoundByItselfAndKeptOnlyAsAHash(): void
    {
        $store = new TokenStore($this->dir);
        $token = $store->issue('laptop', TokenStore::WRITE);

        $this->assertStringStartsWith('satisfy_mcp_', $token);
        $this->assertSame(['name' => 'laptop', 'scope' => 'write'], $store->find($token));
        $this->assertNull($store->find($token . 'x'));
        $this->assertNull($store->find('not-a-token'));
        $this->assertStringNotContainsString($token, (string) file_get_contents($this->dir . '/tokens.json'));
    }

    public function testIssuingAgainReplacesTheToken(): void
    {
        $store = new TokenStore($this->dir);
        $first = $store->issue('ci', TokenStore::READ);
        $second = $store->issue('ci', TokenStore::WRITE);

        $this->assertNull($store->find($first));
        $this->assertSame(['name' => 'ci', 'scope' => 'write'], $store->find($second));
        $this->assertSame(['ci'], array_keys($store->all()));
    }

    public function testRevokingAndListing(): void
    {
        $store = new TokenStore($this->dir);
        $token = $store->issue('b', TokenStore::READ);
        $store->issue('a', TokenStore::WRITE);

        $this->assertSame(['a', 'b'], array_keys($store->all()));
        $this->assertNull($store->all()['b']['last_used']);
        $store->touch('b');
        $this->assertIsInt($store->all()['b']['last_used']);

        $this->assertTrue($store->revoke('b'));
        $this->assertFalse($store->revoke('b'));
        $this->assertNull($store->find($token));
    }

    /**
     * @dataProvider refused
     */
    public function testANameOrScopeThatIsNotOneIsRefused(string $name, string $scope): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new TokenStore($this->dir))->issue($name, $scope);
    }

    public static function refused(): iterable
    {
        yield 'uppercase' => ['Laptop', TokenStore::READ];
        yield 'a slash' => ['../x', TokenStore::READ];
        yield 'empty' => ['', TokenStore::READ];
        yield 'an unknown scope' => ['laptop', 'admin'];
    }
}
