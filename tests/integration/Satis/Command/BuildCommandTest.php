<?php

namespace Tests\integration\Satis\Command;

use Composer\Satis\Console\Application;
use JMS\Serializer\Serializer;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Playbloom\Satisfy\Model\Configuration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class BuildCommandTest extends KernelTestCase
{
    /** @var vfsStreamDirectory|null */
    protected $vfsRoot;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->vfsRoot = vfsStream::setup();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->vfsRoot = null;
    }

    public function testMissingConfigBuildMustFail()
    {
        $file = $this->vfsRoot->url() . '/satis.json';
        $input = $this->createInput($file);
        $output = $this->createOutput();
        $application = $this->createSatisApplication();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(1, $exitCode);

        $outputContent = stream_get_contents($output->getStream(), -1, 0);
        $this->assertStringStartsWith('File not found', $outputContent);
    }

    public function testMinimalConfigBuild()
    {
        $file = new vfsStreamFile('satis.json');
        $file->setContent(file_get_contents(__DIR__ . '/../../../fixtures/satis-minimal.json'));
        $this->vfsRoot->addChild($file);

        $outputDir = new vfsStreamDirectory('output');
        $this->vfsRoot->addChild($outputDir);

        $input = $this->createInput($file->url(), $outputDir->url());
        $output = $this->createOutput();
        $application = $this->createSatisApplication();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertTrue($outputDir->hasChild('index.html'));
        $this->assertTrue($outputDir->hasChild('packages.json'));
        $this->assertTrue($outputDir->hasChild('include'));
        /** @var vfsStreamDirectory $include */
        $include = $outputDir->getChild('include');
        $this->assertTrue($include->hasChildren());
    }

    public function testDefaultFormConfigBuild()
    {
        $container = self::bootKernel()->getContainer();
        /** @var Serializer $serializer */
        $serializer = $container->get('jms_serializer');
        $content = $serializer->serialize(new Configuration(), 'json');

        $file = new vfsStreamFile('satis.json');
        $file->setContent($content);
        $this->vfsRoot->addChild($file);

        $outputDir = new vfsStreamDirectory('output');
        $this->vfsRoot->addChild($outputDir);

        $input = $this->createInput($file->url(), $outputDir->url());
        $output = $this->createOutput();
        $application = $this->createSatisApplication();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertTrue($outputDir->hasChild('index.html'));
        $this->assertTrue($outputDir->hasChild('packages.json'));
        $this->assertTrue($outputDir->hasChild('include'));
        /** @var vfsStreamDirectory $include */
        $include = $outputDir->getChild('include');
        $this->assertTrue($include->hasChildren());
    }

    /**
     * @return Application
     */
    protected function createSatisApplication()
    {
        $application = new Application();
        $application->setAutoExit(false);

        return $application;
    }

    /**
     * @return StreamOutput
     */
    protected function createOutput()
    {
        return new StreamOutput(fopen('php://memory', 'w', false));
    }

    /**
     * @param string $file
     * @param string $outputDir
     *
     * @return ArrayInput
     */
    protected function createInput($file, $outputDir = '')
    {
        $input = new ArrayInput(
            [
                'command' => 'build',
                'file' => $file,
                'output-dir' => $outputDir,
                '-vvv',
            ]
        );

        return $input;
    }
}
