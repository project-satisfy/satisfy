<?php

namespace Playbloom\Tests\Plugin\Diagnosis;

use PHPUnit\Framework\TestCase;
use Playbloom\Model\Configuration;
use Playbloom\Model\PackageConstraint;
use Playbloom\Model\Repository;
use Playbloom\Plugin\Diagnosis\Tool\BuildLog;
use Playbloom\Plugin\Diagnosis\Tool\SatisRepositories;
use Playbloom\Service\Manager;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\AI\Agent\Toolbox\ToolFactory\ReflectionToolFactory;

class SatisRepositoriesTest extends TestCase
{
    use ProphecyTrait;

    public function testRepositoriesAndRequirementsWithoutCredentials(): void
    {
        $this->assertSame([
            'repositories' => [
                ['type' => 'vcs', 'url' => 'https://***@git.acme.test/acme/app.git'],
                ['type' => 'git', 'url' => 'git@github.com:acme/lib.git'],
            ],
            'require' => ['acme/app' => '^1.0'],
            'require_all' => false,
        ], $this->tool()());
    }

    public function testContainsMatchesTheRedactedURL(): void
    {
        $this->assertSame(['git@github.com:acme/lib.git'], array_column($this->tool()('LIB')['repositories'], 'url'));
        $this->assertSame([], $this->tool()('s3cret')['repositories'], 'A credential could be guessed through the filter');
    }

    /**
     * Hugging Face's router refuses a tool whose schema has no parameters.
     */
    public function testEveryToolHasParameters(): void
    {
        $factory = new ReflectionToolFactory();
        foreach ([BuildLog::class, SatisRepositories::class] as $class) {
            foreach ($factory->getTool($class) as $tool) {
                $this->assertNotNull($tool->getParameters(), sprintf('%s has no parameters', $tool->getName()));
            }
        }
    }

    private function tool(): SatisRepositories
    {
        $config = new Configuration();
        $config->setRequire([new PackageConstraint('acme/app', '^1.0')]);
        $config->setRequireAll(false);
        $manager = $this->prophesize(Manager::class);
        $manager->getRepositories()->willReturn(new \ArrayIterator([
            new Repository('https://deploy:s3cret@git.acme.test/acme/app.git', 'vcs'),
            new Repository('git@github.com:acme/lib.git', 'git'),
        ]));
        $manager->getConfig()->willReturn($config);

        return new SatisRepositories($manager->reveal());
    }
}
