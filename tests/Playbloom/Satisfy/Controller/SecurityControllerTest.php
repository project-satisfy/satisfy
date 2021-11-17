<?php

namespace Tests\Playbloom\Satisfy\Controller;

use org\bovigo\vfs\vfsStream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup();
    }

    public function testAdminLogin()
    {
        $client = self::createClient(['environment' => 'testsecure']);

        // must redirect to login form
        $client->request(Request::METHOD_GET, '/admin');
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith('/login', $response->headers->get('location'));

        $crawler = $client->request(Request::METHOD_GET, '/login');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        // submit empty form
        $form = $crawler->filterXPath('//form')->form();
        $client->submit(
            $form,
            [
                '_username' => 'test',
                '_password' => '',
            ]
        );
        /** @var RedirectResponse $response */
        $response = $client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
        $this->assertStringEndsWith('/login', $response->getTargetUrl());

        $client->submit($form, ['_username' => 'test', '_password' => 'test']);
        /** @var RedirectResponse $response */
        $response = $client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
        $this->assertStringEndsWith('/admin', $response->getTargetUrl());

        // authenticated and able to see admin section
        $client->request(Request::METHOD_GET, '/admin');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }
}
