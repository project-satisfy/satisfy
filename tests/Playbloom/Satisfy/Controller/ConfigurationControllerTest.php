<?php

namespace Tests\Playbloom\Satisfy\Controller;

use org\bovigo\vfs\vfsStream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationControllerTest extends WebTestCase
{
    protected function setUp()
    {
        vfsStream::setup();
    }

    public function testSubmitDefaultValues()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/admin/configuration');
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $form = $crawler->filterXPath('//form');
        $client->submit($form->form());
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
