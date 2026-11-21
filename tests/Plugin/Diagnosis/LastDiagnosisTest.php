<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use Playbloom\Model\BuildRequest;
use Playbloom\Plugin\Diagnosis\DiagnosisConfig;
use Playbloom\Plugin\Diagnosis\DiagnosisStore;
use Playbloom\Tests\Plugin\Mcp\McpTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * With the mcp plugin enabled too, the newest diagnosis is a resource of its
 * server, and the tool last_diagnosis, beside the mcp plugin's own tools.
 */
class LastDiagnosisTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['SATISFY_PLUGINS'] = 'mcp,diagnosis';
        $_SERVER['SATISFY_DIAGNOSIS_DIR'] = $this->dir . '/diagnosis';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['SATISFY_DIAGNOSIS_DIR']);
        parent::tearDown();
    }

    public function testTheNewestDiagnosisIsAResourceAndATool(): void
    {
        $client = $this->client();
        $session = $this->initialize($client, $this->readToken);

        $list = $this->post($client, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'], $this->readToken, $session);
        $this->assertContains('last_diagnosis', array_column($list['result']['tools'], 'name'));
        $list = $this->post($client, ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'resources/list'], $this->readToken, $session);
        $this->assertSame(['satisfy://build/diagnosis'], array_column($list['result']['resources'], 'uri'));

        $this->assertSame(['status' => 'none'], $this->read($client, $session));
        $this->assertSame(['status' => 'none'], $this->call($client, $this->readToken, $session, 'last_diagnosis'));

        $store = new DiagnosisStore(new DiagnosisConfig($this->dir . '/diagnosis'));
        $id = $store->snapshot(BuildRequest::full(), 1, 'Exit code 1');
        $store->save($id, ['status' => 'done', 'diagnosis' => 'Cause: x']);

        $diagnosis = $this->read($client, $session);
        $this->assertSame('done', $diagnosis['status']);
        $this->assertSame($id, $diagnosis['build']['id']);
        $this->assertSame('Cause: x', $this->call($client, $this->readToken, $session, 'last_diagnosis')['diagnosis']);
    }

    /**
     * @return array<string, mixed>
     *
     * @phpstan-impure
     */
    private function read(KernelBrowser $client, string $session): array
    {
        $read = $this->post($client, ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/read', 'params' => ['uri' => 'satisfy://build/diagnosis']], $this->readToken, $session);
        $this->assertSame('application/json', $read['result']['contents'][0]['mimeType']);

        return json_decode($read['result']['contents'][0]['text'], true);
    }
}
