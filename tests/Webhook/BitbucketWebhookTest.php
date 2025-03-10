<?php

namespace Playbloom\Tests\Webhook;

use Playbloom\Controller\WebhookController;
use Playbloom\Model\Repository;
use Playbloom\Process\ProcessFactory;
use Playbloom\Runner\SatisBuildRunner;
use Playbloom\Service\Manager;
use Playbloom\Tests\Traits\VfsTrait;
use Playbloom\Webhook\BitbucketWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Process;

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
        $container = self::$kernel->getContainer();
        $rootPath = $container->getParameter('kernel.project_dir');

        $processFactory = $this->prophesize(ProcessFactory::class);
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
