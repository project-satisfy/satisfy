<?php

namespace Tests\Playbloom\Satisfy\Model;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\Repository;

class ConfigurationTest extends TestCase
{

    public function testSetRepositoriesByArrayOnConfigrationIndexByIdRepositoreis()
    {
        $configuration = new Configuration();
        /** @var Repository[] $repositoryArray */
        $repositoryArray = [
            new Repository('https://github.com/ludofleury/satisfy'),
            new Repository('https://github.com/composer/satis')
        ];
        $falseRepository = new Repository('https://github.com/sebastianbergmann/phpunit');
        $configuration->setRepositories($repositoryArray);

        $this->assertTrue($configuration->getRepositories()->offsetExists($repositoryArray[0]->getId()));
        $this->assertTrue($configuration->getRepositories()->offsetExists($repositoryArray[1]->getId()));
        $this->assertFalse($configuration->getRepositories()->offsetExists($falseRepository->getId()));
    }

    public function testSetRepositoriesByArrayIteratorOnConfigrationIndexByIdRepositoreis()
    {
        $configuration = new Configuration();
        /** @var Repository[] $repositoryArray */
        $repositoryArray = new ArrayIterator([
            new Repository('https://github.com/ludofleury/satisfy'),
            new Repository('https://github.com/composer/satis')
        ]);
        $falseRepository = new Repository('https://github.com/sebastianbergmann/phpunit');
        $configuration->setRepositories($repositoryArray);

        $this->assertTrue($configuration->getRepositories()->offsetExists($repositoryArray[0]->getId()));
        $this->assertTrue($configuration->getRepositories()->offsetExists($repositoryArray[1]->getId()));
        $this->assertFalse($configuration->getRepositories()->offsetExists($falseRepository->getId()));
    }

}