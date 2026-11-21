<?php

namespace Playbloom\Tests\Plugin\Mcp;

use Playbloom\Plugin\Mcp\Tool\Repositories;
use Symfony\Component\HttpFoundation\Response;

/**
 * The endpoint at /admin/mcp: its token check, and its read tools.
 */
class McpServerTest extends McpTestCase
{
    public function testARequestWithoutAnIssuedTokenIsRefused(): void
    {
        $client = $this->client();

        $this->post($client, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHeaderSame('WWW-Authenticate', 'Bearer realm="Satisfy MCP"');

        $this->post($client, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], 'satisfy_mcp_' . str_repeat('0', 48));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testEveryToolIsListedForAReadToken(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->readToken);
        $this->assertResponseIsSuccessful();

        $tools = $this->post($client, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'], $this->readToken, $session);
        $this->assertSame(
            ['add_repository', 'build_status', 'package', 'packages', 'queue_build', 'remove_repository', 'remove_requirement', 'repositories', 'require_package', 'requirements', 'update_repository'],
            array_column($tools['result']['tools'], 'name'),
        );
    }

    public function testTheReadToolsAnswer(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->readToken);

        $repositories = $this->call($client, $this->readToken, $session, 'repositories');
        $this->assertSame(['git@github.com:acme/app.git', 'https://***@git.acme.test/acme/private.git'], array_column($repositories['repositories'], 'url'));

        $this->assertSame(['require_all' => false, 'require' => ['acme/app' => '^1.0']], $this->call($client, $this->readToken, $session, 'requirements'));

        $status = $this->call($client, $this->readToken, $session, 'build_status', ['logBytes' => 100]);
        $this->assertSame(0, $status['pending']);
        $this->assertFalse($status['building']);
    }

    /**
     * @dataProvider urls
     */
    public function testCredentialsAreRemovedFromURLs(string $url, string $expected): void
    {
        $this->assertSame($expected, Repositories::withoutCredentials($url));
    }

    public static function urls(): iterable
    {
        yield 'a token as the user' => ['https://ghp_abc@github.com/acme/app.git', 'https://***@github.com/acme/app.git'];
        yield 'a user and password' => ['https://deploy:secret@git.acme.test/acme/app.git', 'https://***@git.acme.test/acme/app.git'];
        yield 'no user information' => ['https://github.com/acme/app.git', 'https://github.com/acme/app.git'];
        yield 'scp style' => ['git@github.com:acme/app.git', 'git@github.com:acme/app.git'];
    }
}
