<?php

namespace Tests\Playbloom\Satisfy\Manager;

use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Persister\JsonPersister;
use Playbloom\Satisfy\Service\Manager;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Tests\Playbloom\Satisfy\Traits\SchemaValidatorTrait;
use Tests\Playbloom\Satisfy\Traits\VfsTrait;

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

        $lockFactory = new LockFactory(new FlockStore());
        $lock = $lockFactory->createLock('satis');
        /** @var Manager $manager */
        $manager = new Manager($lock, $persister->reveal());
        $manager->addAll([]);

        $this->validateSchema(json_decode($this->config->getContent()), $this->getSatisSchema());
        $this->assertJsonFileEqualsJsonFile($configFilename, $this->config->url());
    }

    public static function configFileProvider(): array
    {
        return [
            [__DIR__ . '/../../../fixtures/satis-minimal.json'],
            [__DIR__ . '/../../../fixtures/satis-full.json'],
        ];
    }
}
