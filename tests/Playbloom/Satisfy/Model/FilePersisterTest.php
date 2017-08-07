<?php

namespace Tests\Playbloom\Satisfy\Model;

use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Playbloom\Satisfy\Model\FilePersister;

class FilePersisterTest extends \PHPUnit_Framework_TestCase
{
    /** @var FilePersister */
    protected $persister;

    protected function setUp()
    {
        $persistenceAdapter = new MemoryAdapter;
        $filesystem = new Filesystem($persistenceAdapter);
        $filesystem->write('mock-config', '');
        $this->persister = new FilePersister($filesystem, 'mock-config', 'mock-auditlog');
    }

    protected function tearDown()
    {
        $this->persister = null;
    }

    public function testLoadFlush()
    {
        $content = $this->content();
        $this->persister->flush($content);
        $load = $this->persister->load();
        $this->assertEquals($content, $load);
    }

    private function content()
    {
        return json_encode(array(
            'name' => 'test',
            'repositories' => array(
                'https://github.com/ludofleury/satisfy.git',
            ),
            'require-all' => true,
        ));
    }
}
