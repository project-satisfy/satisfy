<?php

namespace Tests\Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Controller\WebhookController;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Process\ProcessFactory;
use Playbloom\Satisfy\Runner\SatisBuildRunner;
use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Webhook\BitbucketWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use RDV\SymfonyContainerMocks\DependencyInjection\TestContainer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Process;
use Tests\Playbloom\Satisfy\Traits\VfsTrait;

class BitbucketWebhookTest extends KernelTestCase
{
    use VfsTrait;
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vfsSetup();
        self::bootKernel();
    }

    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequestMustThrowException($request): void
    {
        $this->expectException(BadRequestHttpException::class);
        $controller = self::$kernel->getContainer()->get(WebhookController::class);
        $controller->bitbucketAction($request);
    }

    public static function invalidRequestProvider(): \Generator
    {
        // invalid IP
        yield [self::createRequest('', '1.1.1.1')];

        // valid IP, missing content
        yield [Request::create('')];

        // valid IP, missing required param
        $content = ['repository' => ['full_name' => '']];
        yield [self::createRequest($content)];

        // valid content, unknown repository url
        $content = ['repository' => ['full_name' => 'test/test']];
        yield [self::createRequest($content)];
    }

    public function testValidRequestMustTriggerBuild(): void
    {
        /** @var TestContainer $container */
        $container = self::$kernel->getContainer();
        $rootPath = $container->getParameter('kernel.project_dir');

        $processFactory = $container->prophesize(ProcessFactory::class);
        /** @var SatisBuildRunner $builder */
        $builder = $container->get(SatisBuildRunner::class);
        $builder->setProcessFactory($processFactory->reveal());

        /** @var Manager $manager */
        $manager = $container->get(Manager::class);
        $manager->add(new Repository('git@bitbucket.org:test/test.git'));

        $process = $this->prophesize(Process::class);
        $process
            ->run()
            ->shouldBeCalled()
            ->willReturn(0);

        $command = ['bin/satis', 'build', 'vfs://root/satis.json', 'public', '--skip-errors', '--no-ansi', '--verbose'];
        $command[] = '--repository-url=git@bitbucket.org:test/test.git';
        $command[] = '--repository-strict';
        $processFactory
            ->create(Argument::exact($command), Argument::type('integer'))
            ->willReturn($process->reveal());
        $processFactory
            ->getRootPath()
            ->willReturn($rootPath);

        $request = self::createRequest(['repository' => ['full_name' => 'test/test']]);
        /** @var BitbucketWebhook $webhook */
        $webhook = $container->get(BitbucketWebhook::class);
        $response = $webhook->getResponse($request);

        $this->assertEquals(0, $response->getContent());
    }

    protected static function createRequest($content, string $ipAddress = '127.0.0.1'): Request
    {
        return Request::create('', 'GET', [], [], [], ['REMOTE_ADDR' => $ipAddress], json_encode($content));
    }
}
