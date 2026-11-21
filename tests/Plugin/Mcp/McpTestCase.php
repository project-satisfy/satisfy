<?php

namespace Playbloom\Tests\Plugin\Mcp;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The mcp plugin enabled, with a satis.json in memory, a token store and an
 * output directory of their own, and a read token and a write token.
 */
abstract class McpTestCase extends WebTestCase
{
    protected const SATIS = [
        'name' => 'acme/packages',
        'homepage' => 'https://packages.acme.test',
        'require-all' => false,
        'require' => ['acme/app' => '^1.0'],
        'repositories' => [
            ['type' => 'vcs', 'url' => 'git@github.com:acme/app.git'],
            ['type' => 'git', 'url' => 'https://deploy:secret@git.acme.test/acme/private.git'],
        ],
    ];

    protected vfsStreamDirectory $root;
    protected string $dir;
    protected string $readToken;
    protected string $writeToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/satisfy-mcp-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/public', 0777, true);
        $this->root = vfsStream::setup();
        $this->writeSatis(static::SATIS);
        $_SERVER['SATISFY_PLUGINS'] = 'mcp';
        $_SERVER['SATISFY_MCP_DATA_DIR'] = $this->dir . '/mcp';

        $tokens = new TokenStore($this->dir . '/mcp');
        $this->readToken = $tokens->issue('reader', TokenStore::READ);
        $this->writeToken = $tokens->issue('writer', TokenStore::WRITE);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['SATISFY_PLUGINS'], $_SERVER['SATISFY_MCP_DATA_DIR']);
        (new Filesystem())->remove($this->dir);
    }

    /**
     * @param array<string, mixed> $satis
     */
    protected function writeSatis(array $satis): void
    {
        vfsStream::newFile('satis.json')->at($this->root)->setContent((string) json_encode(['output-dir' => $this->dir . '/public'] + $satis));
    }

    /**
     * @return array<string, mixed>
     */
    protected function satis(): array
    {
        return (array) json_decode((string) file_get_contents($this->root->url() . '/satis.json'), true);
    }

    protected function client(): KernelBrowser
    {
        $client = self::createClient();
        $client->disableReboot();

        return $client;
    }

    /**
     * Starts an MCP session with $token and returns its id.
     */
    protected function initialize(KernelBrowser $client, string $token): string
    {
        $this->post($client, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'test', 'version' => '1']],
        ], $token);
        $session = (string) $client->getResponse()->headers->get('Mcp-Session-Id');
        $this->post($client, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $token, $session);

        return $session;
    }

    /**
     * Calls a tool and returns its result: the decoded structured content, or
     * the text of an error with 'error' set.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    protected function call(KernelBrowser $client, string $token, string $session, string $tool, array $arguments = []): array
    {
        $response = $this->post($client, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => (object) $arguments]], $token, $session);
        $result = $response['result'] ?? null;
        $this->assertIsArray($result, (string) json_encode($response));
        if (true === ($result['isError'] ?? false)) {
            return ['error' => $result['content'][0]['text'] ?? ''];
        }

        return $result['structuredContent'] ?? (array) json_decode((string) ($result['content'][0]['text'] ?? 'null'), true);
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    protected function post(KernelBrowser $client, array $message, ?string $token = null, ?string $session = null): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json, text/event-stream'];
        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }
        if (null !== $session) {
            $server['HTTP_MCP_SESSION_ID'] = $session;
            $server['HTTP_MCP_PROTOCOL_VERSION'] = '2025-06-18';
        }
        $client->request('POST', '/admin/mcp', [], [], $server, (string) json_encode($message));

        return json_decode((string) $client->getResponse()->getContent(), true) ?? [];
    }
}
