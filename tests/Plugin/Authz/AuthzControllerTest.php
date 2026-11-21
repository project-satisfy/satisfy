<?php

namespace Playbloom\Tests\Plugin\Authz;

use org\bovigo\vfs\vfsStream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The plugin enabled through SATISFY_PLUGINS, and its admin pages.
 */
class AuthzControllerTest extends WebTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup();
        $this->dir = sys_get_temp_dir() . '/satisfy-authz-ui-' . bin2hex(random_bytes(4));
        $_SERVER['SATISFY_PLUGINS'] = 'authz';
        $_SERVER['TOKEN_PEPPER'] = 'test-pepper';
        $_SERVER['AUTHZ_DATA_DIR'] = $this->dir;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['SATISFY_PLUGINS'], $_SERVER['TOKEN_PEPPER'], $_SERVER['AUTHZ_DATA_DIR']);
        (new Filesystem())->remove($this->dir);
    }

    public function testIssuingATokenShowsItOnceAndGrantingWorks(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/admin');
        $this->assertCount(1, $crawler->selectLink('Package access'));

        $crawler = $client->request('GET', '/admin/authz');
        $this->assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Issue')->form(['site' => 'site-a', 'note' => 'a test site']));
        $this->assertResponseRedirects('/admin/authz/site/site-a');

        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('.secret h2', 'Token issued');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', trim($crawler->filter('.secret pre')->first()->text()));

        $client->submit($crawler->selectButton('Grant')->form(['package' => 'acme/app']));
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Granted acme/app to site-a', $crawler->filter('.flash')->text());
        $this->assertCount(0, $crawler->filter('.secret pre'), 'The token was shown a second time');

        $crawler = $client->request('GET', '/admin/authz');
        $this->assertStringContainsString('a test site', $crawler->filter('table')->text());
    }

    public function testAPostWithoutAValidTokenChangesNothing(): void
    {
        $client = self::createClient();
        $client->request('POST', '/admin/authz', ['action' => 'issue', 'site' => 'site-b', '_token' => 'forged']);

        $this->assertResponseRedirects('/admin/authz');
        $this->assertStringNotContainsString('site-b', (string) $client->followRedirect()->filter('table')->text());
    }
}
