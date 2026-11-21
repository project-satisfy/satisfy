<?php

namespace Playbloom\Tests\Controller;

use org\bovigo\vfs\vfsStream;
use Playbloom\Queue\BuildQueue;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SatisControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup();
    }

    public function testBuildPageQueuesAFullBuild(): void
    {
        $client = self::createClient();
        /** @var BuildQueue $queue */
        $queue = self::getContainer()->get(BuildQueue::class);
        $queue->recover();
        while (null !== $leftover = $queue->claim()) {
            $queue->complete($leftover);
        }

        $crawler = $client->request('GET', '/admin/satis/build');
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertSame(0, $queue->pending(), 'Opening the page must not start a build');

        $client->submit($crawler->selectButton('Queue a full build')->form());
        $this->assertTrue($client->getResponse()->isRedirect('/admin/satis/build'));

        $request = $queue->claim();
        $this->assertNotNull($request);
        $this->assertTrue($request->isFull());
        $queue->complete($request);
    }

    public function testStatusReportsTheQueue(): void
    {
        $client = self::createClient();
        $client->request('GET', '/admin/satis/build/status');

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $status = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('pending', $status);
        $this->assertArrayHasKey('building', $status);
        $this->assertArrayHasKey('log', $status);
    }
}
