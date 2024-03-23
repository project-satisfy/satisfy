<?php

/** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);

namespace Tests\Playbloom\Satisfy\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Webhook\DevOpsWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DevOpsWebhookTest extends TestCase
{
    use ProphecyTrait;

    protected const secret = '12345';

    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequest(Request $request): void
    {
        $this->expectException(BadRequestHttpException::class);

        /** @noinspection PhpParamsInspection */
        $handler = new DevOpsWebhook($this->getManagerMock()->reveal(), $this->getDispatcherMock()->reveal(), self::secret);
        $handler->getResponse($request);
    }

    public function testValidRequest(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->willReturn(new Repository('https://dev.azure.com/path/to/repo/_git/satisfy'))
            ->shouldBeCalledTimes(1);

        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->will(
                function ($args) {
                    $args[0]->setStatus(0);

                    return $args[0];
                }
            )
            ->shouldBeCalledTimes(1);

        $request = self::createRequest(file_get_contents(__DIR__ . '/../../../fixtures/devops-push.json'), self::secret);
        /** @noinspection PhpParamsInspection */
        $handler = new DevOpsWebhook($manager->reveal(), $dispatcher->reveal(), self::secret);
        $response = $handler->getResponse($request);

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(0, $response->getContent());
    }

    public function testInvalidTokenRequest(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->willReturn(new Repository('https://dev.azure.com/path/to/repo/_git/satisfy'))
            ->shouldBeCalledTimes(0);

        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->shouldBeCalledTimes(0);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid Token');
        $request = self::createRequest(file_get_contents(__DIR__ . '/../../../fixtures/devops-push.json'), 'invalid-token');
        /** @noinspection PhpParamsInspection */
        $handler = new DevOpsWebhook($manager->reveal(), $dispatcher->reveal(), self::secret);
        $response = $handler->getResponse($request);

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public static function invalidRequestProvider(): \Generator
    {
        yield [self::createRequest([], '')];

        yield [self::createRequest([], self::secret)];

        yield [self::createRequest(['resource' => ['repository' => ['url' => '']]])];
    }

    protected static function createRequest($content, ?string $token = null): Request
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }
        $request = Request::create('', 'GET', [], [], [], [], $content);
        if (null !== $token) {
            $request->headers->set('X-DEVOPS-TOKEN', $token);
        }

        return $request;
    }

    protected function getManagerMock(): ObjectProphecy
    {
        return $this->prophesize(Manager::class);
    }

    protected function getDispatcherMock(): ObjectProphecy
    {
        return $this->prophesize(EventDispatcher::class);
    }
}
