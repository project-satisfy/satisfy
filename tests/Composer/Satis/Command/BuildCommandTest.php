<?php

namespace Tests\Composer\Satis\Command;

use Composer\Satis\Console\Application;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\AssertionFailedError;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Persister\FilePersister;
use Playbloom\Satisfy\Persister\JsonPersister;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

class BuildCommandTest extends KernelTestCase
{
    /** @var vfsStreamDirectory|null */
    protected $vfsRoot;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->vfsRoot = vfsStream::setup();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->vfsRoot = null;
        parent::tearDown();
    }

    public function testMissingConfigBuildMustFail()
    {
        $file = $this->vfsRoot->url() . '/satis.json';
        $input = $this->createInput($file);
        $output = $this->createOutput();
        $application = $this->createSatisApplication();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(1, $exitCode);

        $outputContent = $output->fetch();
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

        $this->assertEquals(0, $exitCode, $output->fetch());
        $this->assertTrue($outputDir->hasChild('index.html'));
        $this->assertTrue($outputDir->hasChild('packages.json'));
        $this->assertTrue($outputDir->hasChild('include'));
        /** @var vfsStreamDirectory $include */
        $include = $outputDir->getChild('include');
        $this->assertTrue($include->hasChildren());
    }

    public function testDefaultFormConfigBuild()
    {
        $file = new vfsStreamFile('satis.json');
        $this->vfsRoot->addChild($file);

        $container = self::bootKernel()->getContainer();
        $serializer = $container->get('serializer');

        $filePersister = new FilePersister(new Filesystem(), $file->url(), $this->vfsRoot->url());
        $persister = new JsonPersister($filePersister, $serializer, Configuration::class);
        $persister->flush(new Configuration());

        $outputDir = new vfsStreamDirectory('output');
        $this->vfsRoot->addChild($outputDir);

        $input = $this->createInput($file->url(), $outputDir->url());
        $output = $this->createOutput();
        $application = $this->createSatisApplication();
        $exitCode = $application->run($input, $output);

        try {
            $this->assertEquals(0, $exitCode, 'Exit code non null');
        } catch (AssertionFailedError $error) {
            echo $file->getContent();
            echo $output->fetch();
            throw $error;
        }

        $this->assertTrue($outputDir->hasChild('index.html'));
        $this->assertTrue($outputDir->hasChild('packages.json'));
        $this->assertTrue($outputDir->hasChild('include'));
        /** @var vfsStreamDirectory $include */
        $include = $outputDir->getChild('include');
        $this->assertTrue($include->hasChildren());
    }

    protected function createSatisApplication(): Application
    {
        $application = new Application();
        $application->setAutoExit(false);

        return $application;
    }

    protected function createOutput(): BufferedOutput
    {
        return new BufferedOutput();
    }

    protected function createInput(string $file, string $outputDir = ''): ArrayInput
    {
        $input = new ArrayInput(
            [
                'command' => 'build',
                'file' => $file,
                'output-dir' => $outputDir,
                '-vv',
            ]
        );

        return $input;
    }
}
