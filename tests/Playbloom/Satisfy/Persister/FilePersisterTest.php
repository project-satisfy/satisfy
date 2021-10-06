<?php

namespace Tests\Playbloom\Satisfy\Persister;

use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\PackageConstraint;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Persister\FilePersister;
use Playbloom\Satisfy\Persister\JsonPersister;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Playbloom\Satisfy\Traits\SchemaValidatorTrait;
use Tests\Playbloom\Satisfy\Traits\VfsTrait;

class FilePersisterTest extends KernelTestCase
{
    use SchemaValidatorTrait;
    use VfsTrait;

    /** @var FilePersister|null */
    protected $persister;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vfsSetup();
        $this->persister = new FilePersister(
            new Filesystem(),
            $this->vfsRoot->url() . '/satis.json',
            $this->vfsRoot->url()
        );
    }

    protected function tearDown(): void
    {
        $this->vfsTearDown();
        $this->persister = null;
        parent::tearDown();
    }

    public function testDumpMustTruncateFile(): void
    {
        $config = [
            'name' => 'test',
            'homepage' => 'http://localhost',
            'repositories' => [
                [
                    'type' => 'git',
                    'url' => 'https://github.com/ludofleury/satisfy.git',
                ],
            ],
            'require-all' => true,
        ];
        $content = json_encode($config);
        $this->persister->flush($content);
        /** @var vfsStreamFile $configFile */
        $configFile = $this->vfsRoot->getChild('satis.json');
        $this->assertStringEqualsFile($configFile->url(), $content);
        $this->assertEquals($content, $this->persister->load());

        $this->validateSchema(json_decode($configFile->getContent()), $this->getSatisSchema());

        $config['repositories'] = [];
        $content = json_encode($config);
        $this->persister->flush($content);
        $this->assertStringEqualsFile($configFile->url(), $content);
        $this->assertEquals($content, $this->persister->load());
    }

    public function testPersisterNormalization(): void
    {
        $file = new vfsStreamFile('satis.json');
        $file->setContent(file_get_contents(__DIR__ . '/../../../fixtures/satis-full.json'));
        $this->vfsRoot->addChild($file);

        $kernel = self::bootKernel();
        $persister = new JsonPersister(
            $this->persister,
            $kernel->getContainer()->get('serializer'),
            Configuration::class
        );
        $config = $persister->load();

        // validate config
        $require = $config->getRequire();
        $this->assertIsArray($require);
        $this->assertCount(1, $require);

        $repositories = $config->getRepositories();
        $this->assertCount(1, $repositories);
        $this->assertIsString($repositories->key());
        $this->assertInstanceOf(RepositoryInterface::class, $repositories->current());
        self::assertIsArray($stability = $config->getMinimumStabilityPerPackage());
        self::assertArrayHasKey(0, $stability);

        // append additional repo
        $repositories->append(new Repository('http://localhost'));
        $config->setRepositories($repositories);

        // change existing, append additional require
        $constraint = reset($require);
        $constraint->setConstraint('^2.0');
        $config->setRequire([
            $constraint,
            new PackageConstraint('psr/log', '^1.0'),
        ]);

        // add required specific package stability
        $config->addMinimumStabilityPerPackage('phpunit/phpunit', 'alpha');

        $persister->flush($config);

        $config = $persister->load();

        $require = $config->getRequire();
        $this->assertIsArray($require);
        $this->assertCount(2, $require);

        $repositories = $config->getRepositories();
        $this->assertCount(2, $repositories);
        $this->assertIsString($repositories->key());
        $this->assertInstanceOf(RepositoryInterface::class, $repositories->current());
    }
}
