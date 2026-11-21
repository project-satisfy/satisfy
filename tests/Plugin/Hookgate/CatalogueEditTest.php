<?php

namespace Playbloom\Tests\Plugin\Hookgate;

use PHPUnit\Framework\TestCase;
use Playbloom\Model\Configuration;
use Playbloom\Model\PackageConstraint;
use Playbloom\Model\Repository;
use Playbloom\Plugin\Hookgate\CatalogueEdit;

class CatalogueEditTest extends TestCase
{
    private const URL = 'https://git.example.com/acme/app.git';

    public function testANewPackageGetsARepositoryAndARequireEntry(): void
    {
        $config = new Configuration();

        $changes = CatalogueEdit::plan($config, self::URL, 'acme/app');

        $this->assertSame(['repositories: added ' . self::URL, 'require: added acme/app'], $changes);
        $this->assertSame([self::URL], array_map(fn ($r) => $r->getUrl(), array_values($config->getRepositories()->getArrayCopy())));
        $this->assertSame('*', $config->getRequire()[0]->getConstraint());
    }

    public function testAPublishedPackageNeedsNothing(): void
    {
        $config = new Configuration();
        $config->setRepositories([new Repository(self::URL, 'vcs')]);
        $config->setRequire([new PackageConstraint('ACME/App', '*')]);

        $this->assertSame([], CatalogueEdit::plan($config, self::URL, 'acme/app'));
    }

    public function testOtherSpellingsOfTheProjectAreCollapsedOntoGitLabs(): void
    {
        $config = new Configuration();
        $old = new Repository('git@git.example.com:acme/app.git', 'git');
        $duplicate = new Repository('https://git.example.com/acme/app', 'vcs');
        $config->setRepositories([$old->getId() => $old, $duplicate->getId() => $duplicate]);

        $changes = CatalogueEdit::plan($config, self::URL, 'acme/app');

        $this->assertContains('repositories: respelled git@git.example.com:acme/app.git as ' . self::URL, $changes);
        $this->assertContains('repositories: removed duplicate https://git.example.com/acme/app', $changes);
        $repositories = array_values($config->getRepositories()->getArrayCopy());
        $this->assertCount(1, $repositories);
        $this->assertSame(self::URL, $repositories[0]->getUrl());
        $this->assertSame('git', $repositories[0]->getType(), 'The kept entry changed type');
    }

    public function testProjectKeysIgnoreSchemeCaseAndDotGit(): void
    {
        $this->assertSame('git.example.com/acme/app', CatalogueEdit::projectKey('https://git.example.com/Acme/App.git'));
        $this->assertSame('git.example.com/acme/app', CatalogueEdit::projectKey('git@git.example.com:acme/app.git'));
        $this->assertNull(CatalogueEdit::projectKey('not a url'));
    }
}
