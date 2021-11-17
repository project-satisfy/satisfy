<?php

namespace Tests\Playbloom\Satisfy\Controller;

use org\bovigo\vfs\vfsStream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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

    public function testAddStabilityPerPackage(): void
    {
        $values = $this->getMinimalValues();
        $values['configuration']['minimumStabilityPerPackage'] = [
            [
                'package' => $package = 'psr/log',
                'stability' => $stability = 'alpha',
            ],
        ];

        $client = self::createClient();
        $client->request('POST', '/admin/configuration', $values);
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $form = $client->getCrawler()->filterXPath('//form')->form();
        $values = $form->getValues();

        self::assertArrayHasKey('configuration[minimumStabilityPerPackage][0][package]', $values);
        self::assertArrayHasKey('configuration[minimumStabilityPerPackage][0][stability]', $values);
        self::assertEquals($package, $values['configuration[minimumStabilityPerPackage][0][package]']);
        self::assertEquals($stability, $values['configuration[minimumStabilityPerPackage][0][stability]']);
    }

    protected function getMinimalValues(): array
    {
        $values = json_decode(file_get_contents(__DIR__ . '/../../../fixtures/satis-minimal.json'), true);

        return [
            'configuration' => $values,
        ];
    }
}
