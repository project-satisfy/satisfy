<?php

namespace Tests\Playbloom\Satisfy\Model;

use Playbloom\Satisfy\Model\FilePersister;
use Symfony\Component\Filesystem\Filesystem;

class FilePersisterTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $fixture;

    /** @var FilePersister */
    protected $persister;

    protected function setUp()
    {
        $this->fixture = tempnam(sys_get_temp_dir(), 'fixture');
        $this->persister = new FilePersister(new Filesystem, $this->fixture, sys_get_temp_dir());
    }

    protected function tearDown()
    {
        @unlink($this->fixture);
        $this->fixture = null;
        $this->persister = null;
    }

    public function testDumpMustTruncateFile()
    {
        $config = array(
            'name' => 'test',
            'repositories' => array(
                'https://github.com/ludofleury/satisfy.git',
            ),
            'require-all' => true,
        );
        $content = json_encode($config);
        $this->persister->flush($content);
        $this->assertStringEqualsFile($this->fixture, $content);
        $this->assertEquals($content, $this->persister->load());

        $config['repositories'] = array();
        $content = json_encode($config);
        $this->persister->flush($content);
        $this->assertStringEqualsFile($this->fixture, $content);
        $this->assertEquals($content, $this->persister->load());
    }

}
