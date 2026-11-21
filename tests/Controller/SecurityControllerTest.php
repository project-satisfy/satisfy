<?php

namespace Playbloom\Tests\Controller;

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
        $response = $client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
        $this->assertStringEndsWith('/login', $response->getTargetUrl());

        $client->submit($form, ['_username' => 'test', '_password' => 'test']);
        $response = $client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
        $this->assertStringEndsWith('/admin', $response->getTargetUrl());

        // authenticated and able to see admin section
        $client->request(Request::METHOD_GET, '/admin');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testAHashedPasswordSignsIn(): void
    {
        $client = self::createClient(['environment' => 'testsecure']);
        $form = $client->request(Request::METHOD_GET, '/login')->filterXPath('//form')->form();

        $client->submit($form, ['_username' => 'hashed', '_password' => '$2y$12$PAwDxdhvnyJuQDixTg40OesDrR5A7kUGvEk5R1zjiqLyuSgSkXlBK']);
        $this->assertStringEndsWith('/login', (string) $client->getResponse()->headers->get('location'), 'The stored hash signed in as a password');

        $client->submit($form, ['_username' => 'hashed', '_password' => 'secret']);
        $this->assertStringEndsWith('/admin', (string) $client->getResponse()->headers->get('location'));
    }

    public function testRepeatedFailuresAreThrottled(): void
    {
        $client = self::createClient(['environment' => 'testsecure']);
        $client->disableReboot();
        $form = $client->request(Request::METHOD_GET, '/login')->filterXPath('//form')->form();

        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $client->submit($form, ['_username' => 'test', '_password' => 'wrong']);
        }
        $client->submit($form, ['_username' => 'test', '_password' => 'test']);
        $this->assertStringEndsWith('/login', (string) $client->getResponse()->headers->get('location'), 'The right password signed in after five failures');
        $crawler = $client->followRedirect();

        $this->assertStringContainsString('Too many failed login attempts, please try again in 1 minute', $crawler->text());
        $this->assertStringNotContainsString('%minutes%', $crawler->text());
    }
}
