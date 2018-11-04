<?php

namespace Tests\app;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class ApplicationKernelTest extends WebTestCase
{
    public function testUnavailablePageWhenIndexMissing()
    {
        $client = self::createClient();
        $webDir = $client->getContainer()->getParameter('kernel.project_dir').'/web';
        $filesystem = new Filesystem();
        if ($filesystem->exists($webDir.'/index.html')) {
            $filesystem->rename($webDir.'/index.html', $renamed = $webDir.'/_index.html');
        }

        try {
            $crawler = $client->request('GET', '/');
            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

            $title = $crawler->filterXPath('//head/title');
            $this->assertEquals('Composer Repository currently not available', $title->text());
        } finally {
            if (isset($renamed)) {
                $filesystem->rename($renamed, $webDir.'/index.html');
            }
        }
    }
}
