<?php

namespace tests\Playbloom\Satisfy\Controller;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Playbloom\Satisfy\Traits\VfsTrait;

class RepositoryControllerTest extends WebTestCase
{
    use VfsTrait;

    protected function setUp()
    {
        $this->vfsSetup();
    }

    protected function tearDown()
    {
        $this->vfsTearDown();
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
        $request = array('type' => 'git', 'url' => $url);
        $client->submit(
            $form->form(),
            array('repository' => $request)
        );
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

        $this->assertTrue($this->vfsRoot->hasChild('satis.json'));
        $config = $this->vfsRoot->getChild('satis.json')->getContent();

        $this->assertJson($config);
        $config = json_decode($config);
        $this->assertNotEmpty($config);

        $this->assertObjectHasAttribute('repositories', $config);
        $this->assertEquals([(object)$request], $config->repositories);

        // update repository
        $params = ['type' => 'git', 'url' => $url2 = 'git@github.com:account/repository.git'];
        $crawler = $client->request('GET', '/admin/edit/' . md5($url));
        $form = $crawler->filterXPath('//form');
        $client->submit($form->form(), array('repository' => $params));

        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());

        $config = json_decode($this->vfsRoot->getChild('satis.json')->getContent());
        $this->assertEquals($url2, $config->repositories[0]->url);

        // remove repository
        $crawler = $client->request('GET', '/admin/delete/' . md5($url2));
        $form = $crawler->filterXPath('//form');
        $client->submit($form->form());
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
    }
}
