<?php

namespace Tests\Playbloom\Satisfy\Manager;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Persister\PersisterInterface;
use Playbloom\Satisfy\Service\Manager;
use Tests\Playbloom\Satisfy\Traits\SchemaValidatorTrait;

class ManagerConfigValidatorTest extends TestCase
{
    use SchemaValidatorTrait;

    /** @var vfsStreamFile */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $root = vfsStream::setup();
        $root->addChild($this->config = new vfsStreamFile('satis.json'));
    }

    /**
     * @dataProvider configFileProvider
     */
    public function testConfigIsMatchingSatisSchema($configFilename)
    {
        $this->assertTrue(copy($configFilename, $this->config->url()));
        $persister = $this->prophesize(PersisterInterface::class);
        /** @var Manager $manager */
        $manager = new Manager($persister->reveal());
        $manager->addAll(array());

        $this->validateSchema(json_decode($this->config->getContent()), $this->getSatisSchema());
        $this->assertJsonFileEqualsJsonFile($configFilename, $this->config->url());
    }

    /**
     * @return array
     */
    public function configFileProvider()
    {
        return [
            [__DIR__.'/../../../fixtures/satis-minimal.json'],
            [__DIR__.'/../../../fixtures/satis-full.json'],
        ];
    }
}
