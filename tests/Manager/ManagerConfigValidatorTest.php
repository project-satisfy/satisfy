<?php

namespace Playbloom\Tests\Manager;

use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Playbloom\Model\Configuration;
use Playbloom\Persister\JsonPersister;
use Playbloom\Service\Manager;
use Playbloom\Tests\Traits\SchemaValidatorTrait;
use Playbloom\Tests\Traits\VfsTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ManagerConfigValidatorTest extends TestCase
{
    use SchemaValidatorTrait;
    use VfsTrait;
    use ProphecyTrait;

    /** @var vfsStreamFile */
    protected $config;

    protected function setUp(): void
    {
        $this->vfsSetup();
        $this->vfsRoot->addChild($this->config = new vfsStreamFile('satis.json'));
    }

    protected function tearDown(): void
    {
        $this->vfsTearDown();
    }

    /**
     * @dataProvider configFileProvider
     */
    public function testConfigIsMatchingSatisSchema($configFilename): void
    {
        $this->assertTrue(copy($configFilename, $this->config->url()));
        $persister = $this->prophesize(JsonPersister::class);
        $persister
            ->load()
            ->willReturn(new Configuration());
        $persister
            ->flush(Argument::type(Configuration::class))
            ->shouldBeCalled();
        $persister->lock()->shouldBeCalled();
        $persister->unlock()->shouldBeCalled();

        /** @var Manager $manager */
        $manager = new Manager($persister->reveal());
        $manager->addAll([]);

        $this->validateSchema(json_decode($this->config->getContent()), $this->getSatisSchema());
        $this->assertJsonFileEqualsJsonFile($configFilename, $this->config->url());
    }

    public static function configFileProvider(): array
    {
        return [
            [__DIR__ . '/../fixtures/satis-minimal.json'],
            [__DIR__ . '/../fixtures/satis-full.json'],
        ];
    }
}
