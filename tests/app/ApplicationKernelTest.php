<?php

namespace tests\app;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationKernelTest extends WebTestCase
{
    public function testUnavailablePageWhenIndexMissing()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/');
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $title = $crawler->filterXPath('//head/title');
        $this->assertEquals('Composer Repository currently not available', $title->text());
    }
}
