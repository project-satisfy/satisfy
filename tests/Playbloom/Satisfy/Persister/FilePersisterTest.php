<?php

namespace Tests\Playbloom\Satisfy\Persister;

use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Persister\FilePersister;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Playbloom\Satisfy\Traits\SchemaValidatorTrait;
use Tests\Playbloom\Satisfy\Traits\VfsTrait;

class FilePersisterTest extends TestCase
{
    use SchemaValidatorTrait;
    use VfsTrait;

    /** @var FilePersister */
    protected $persister;

    protected function setUp()
    {
        $this->vfsSetup();
        $this->persister = new FilePersister(
            new Filesystem,
            $this->vfsRoot->url() . '/satis.json',
            $this->vfsRoot->url()
        );
    }

    protected function tearDown()
    {
        $this->vfsTearDown();
        $this->persister = null;
    }

    public function testDumpMustTruncateFile()
    {
        $config = [
            'name'         => 'test',
            'homepage'     => 'http://localhost',
            'repositories' => [
                [
                    'type' => 'git',
                    'url'  => 'https://github.com/ludofleury/satisfy.git',
                ],
            ],
            'require-all'  => true,
        ];
        $content = json_encode($config);
        $this->persister->flush($content);
        $configFile = $this->vfsRoot->getChild('satis.json');
        $this->assertStringEqualsFile($configFile->url(), $content);
        $this->assertEquals($content, $this->persister->load());

        $this->validateSchema(json_decode($configFile->getContent()), $this->getSatisSchema());

        $config['repositories'] = array();
        $content = json_encode($config);
        $this->persister->flush($content);
        $this->assertStringEqualsFile($configFile->url(), $content);
        $this->assertEquals($content, $this->persister->load());
    }
}
