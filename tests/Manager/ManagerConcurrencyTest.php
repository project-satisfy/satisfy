<?php

namespace Playbloom\Tests\Manager;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Playbloom\Exception\ConfigurationChangedException;
use Playbloom\Model\Repository;
use Playbloom\Service\Manager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Another program writes satis.json between this request reading it and
 * writing it, as a webhook in another process or a filter in front of the
 * webhooks does.
 */
class ManagerConcurrencyTest extends KernelTestCase
{
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = vfsStream::setup();
        $this->writeRepositories(['https://git.example.com/a.git']);
        self::bootKernel();
    }

    public function testRepositoryChangesKeepWhatAnotherWriterAdded(): void
    {
        $manager = $this->getManager();
        $manager->getConfig();

        $this->writeRepositories(['https://git.example.com/a.git', 'https://git.example.com/b.git']);
        $manager->add(new Repository('https://git.example.com/c.git', 'vcs'));

        $this->assertSame([
            'https://git.example.com/a.git',
            'https://git.example.com/b.git',
            'https://git.example.com/c.git',
        ], $this->readRepositoryUrls());
    }

    public function testUpdatingARepositoryAnotherWriterRemovedFails(): void
    {
        $manager = $this->getManager();
        $repository = $manager->findByUrl('#a\.git$#');
        $this->assertNotNull($repository);

        $this->writeRepositories(['https://git.example.com/b.git']);

        $this->expectExceptionMessage('Unknown repository');
        $manager->update($repository, new Repository('https://git.example.com/a2.git', 'vcs'));
    }

    public function testFlushRefusesWhenTheFileChangedSinceItsVersion(): void
    {
        $manager = $this->getManager();
        $version = $manager->getVersion();
        $manager->getConfig()->setName('edited/here');

        $this->writeRepositories(['https://git.example.com/a.git', 'https://git.example.com/b.git']);

        try {
            $manager->flush($version);
            $this->fail('The edit was saved over a newer satis.json');
        } catch (ConfigurationChangedException) {
        }
        $this->assertSame(['https://git.example.com/a.git', 'https://git.example.com/b.git'], $this->readRepositoryUrls());
    }

    public function testFlushWritesWhenTheFileIsUnchanged(): void
    {
        $manager = $this->getManager();
        $version = $manager->getVersion();
        $manager->getConfig()->setName('edited/here');

        $manager->flush($version);

        $this->assertSame('edited/here', $this->read()['name']);
    }

    private function getManager(): Manager
    {
        return self::getContainer()->get(Manager::class);
    }

    /**
     * @param string[] $urls
     */
    private function writeRepositories(array $urls): void
    {
        $config = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/satis-minimal.json'), true);
        $config['repositories'] = array_map(static fn (string $url) => ['type' => 'vcs', 'url' => $url], $urls);
        file_put_contents($this->root->url() . '/satis.json', json_encode($config, JSON_PRETTY_PRINT));
    }

    private function read(): array
    {
        return json_decode((string) file_get_contents($this->root->url() . '/satis.json'), true);
    }

    /**
     * @return string[]
     */
    private function readRepositoryUrls(): array
    {
        return array_column($this->read()['repositories'] ?? [], 'url');
    }
}
