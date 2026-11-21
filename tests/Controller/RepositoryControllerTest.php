<?php

namespace Playbloom\Tests\Controller;

use org\bovigo\vfs\vfsStreamFile;
use Playbloom\Tests\Traits\VfsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RepositoryControllerTest extends WebTestCase
{
    use VfsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vfsSetup();
    }

    protected function tearDown(): void
    {
        $this->vfsTearDown();
        parent::tearDown();
    }

    public function testRepositoryIndex()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/admin');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $code = $crawler->filterXPath('//code');
        $this->assertJson($code->text());
        $config = json_decode($code->text());
        $this->assertNotEmpty($config->repositories);
        if (isset($config->repositories[0]->options)) {
            $this->assertNotEmpty($config->repositories[0]->options);
        }
    }

    /**
     * A cross-site form can send _method=DELETE, but not the form's token.
     */
    public function testDeleteNeedsTheFormsToken(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $url = 'git@github.com:YourAccount/YourRepo.git';
        $crawler = $client->request('GET', '/admin/new');
        $client->submit($crawler->filterXPath('//form')->form(), ['repository' => ['type' => 'git', 'url' => $url]]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->request('POST', '/admin/delete/' . md5($url), ['_method' => 'DELETE']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $client->request('POST', '/admin/delete/' . md5($url), ['_method' => 'DELETE', 'delete_form' => ['_token' => 'forged']]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var vfsStreamFile $configHandle */
        $configHandle = $this->vfsRoot->getChild('satis.json');
        $this->assertSame($url, json_decode($configHandle->getContent())->repositories[0]->url);
    }

    public function testRepositoryCRUD()
    {
        $client = self::createClient();
        $client->disableReboot();
        $crawler = $client->request('GET', '/admin/new');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $form = $crawler->filterXPath('//form');
        $this->assertEquals(Request::METHOD_POST, strtoupper($form->attr('method')));

        // form validation must fail due to invalid url
        $client->submit($form->form());
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $url = 'git@github.com:YourAccount/YourRepo.git';
        $request = ['type' => 'git', 'url' => $url];
        $client->submit(
            $form->form(),
            ['repository' => $request]
        );
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

        $this->assertTrue($this->vfsRoot->hasChild('satis.json'));
        /** @var vfsStreamFile $configHandle */
        $configHandle = $this->vfsRoot->getChild('satis.json');
        $config = $configHandle->getContent();

        $this->assertJson($config);
        $config = json_decode($config, false);
        $this->assertNotEmpty($config);

        self::assertObjectHasProperty('repositories', $config);
        $this->assertIsArray($config->repositories);
        $this->assertEquals($url, $config->repositories[0]->url);
        $this->assertEquals('git', $config->repositories[0]->type);
        $this->assertEquals('dist', $config->repositories[0]->{'installation-source'});

        // update repository
        $params = ['type' => 'github', 'url' => $url2 = 'git@github.com:account/repository.git', 'installationSource' => 'source'];
        $crawler = $client->request('GET', '/admin/edit/' . md5($url));
        $form = $crawler->filterXPath('//form');
        $client->submit($form->form(), ['repository' => $params]);

        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());

        /** @var vfsStreamFile $configHandle */
        $configHandle = $this->vfsRoot->getChild('satis.json');
        $config = json_decode($configHandle->getContent());
        $this->assertEquals($url2, $config->repositories[0]->url);
        $this->assertEquals('github', $config->repositories[0]->type);
        $this->assertEquals('source', $config->repositories[0]->{'installation-source'});

        // remove repository
        $crawler = $client->request('GET', '/admin/delete/' . md5($url2));
        $client->submit($crawler->filterXPath('//form//button[@type="submit"]')->form());
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame([], json_decode($configHandle->getContent())->repositories);
    }
}
