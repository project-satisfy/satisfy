<?php

namespace Tests\Playbloom\Satisfy\Webhook;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Webhook\GitlabWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GitlabWebhookTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequest($request): void
    {
        $this->expectException(BadRequestHttpException::class);

        $handler = new GitlabWebhook($this->getManagerMock()->reveal(), $this->getDispatcherMock()->reveal());
        $handler->getResponse($request);
    }

    public static function invalidRequestProvider(): \Generator
    {
        yield [self::createRequest([], '')];

        yield [self::createRequest([])];

        yield [self::createRequest(['repository' => []])];
    }

    public function testValidRequest(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->willReturn(new Repository('git@gitlab.com:doctrine/orm.git'))
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

        $request = self::createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push.json'));
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(0, $response->getContent());
    }

    public function testValidRequestWithRepoAutoAdd(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->willReturn(null)
            ->shouldBeCalledTimes(2);

        $manager
            ->add(Argument::type(Repository::class))
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

        $request = self::createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push-nonexistant.json'));
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true);
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(0, $response->getContent());
    }

    public function testDeprecatedRequestBodyValidRequest(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->willReturn(new Repository('git@gitlab.com:doctrine/orm.git'))
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

        $request = self::createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push-deprecated.json'));
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(0, $response->getContent());
    }

    public function testInvalidTokenRequest(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->shouldBeCalledTimes(0);

        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->shouldBeCalledTimes(0);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid Token');
        $request = self::createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push.json'), 'push', 'invalid-token');
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAutoAddOnlyHttp(): void
    {
        $url = 'https://gitlab.com/example/nonexistant.git';
        $request = self::createRequest([
            'project' => [
                'git_http_url' => $url,
            ],
        ]);

        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::exact('#^https\://gitlab\.com/example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->add(Argument::type(Repository::class))
            ->shouldBeCalledTimes(1);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->will(function ($args) {
                $event = $args[0];
                Assert::assertInstanceOf(BuildEvent::class, $event);
                $event->setStatus(0);

                return $event;
            });
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true);
        $response = $handler->getResponse($request);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAutoAddPreferSsh(): void
    {
        $httpUrl = 'https://gitlab.com/example/nonexistant.git';
        $sshUrl = 'git@gitlab.com:example/nonexistant.git';
        $request = self::createRequest([
            'project' => [
                'git_http_url' => $httpUrl,
                'git_ssh_url' => $sshUrl,
            ],
        ]);

        $repository = new Repository($sshUrl, 'git');

        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::exact('#^git@gitlab\.com\:example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->findByUrl(Argument::exact('#^https\://gitlab\.com/example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->add(Argument::exact($repository))
            ->shouldBeCalledTimes(1);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->willReturnArgument(0);

        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true, 'git', true);
        $response = $handler->getResponse($request);
    }

    public function testAutoAddPreferSshFallback(): void
    {
        $httpUrl = 'https://gitlab.com/example/nonexistant.git';
        $request = self::createRequest([
            'project' => [
                'git_http_url' => $httpUrl,
            ],
        ]);

        $repository = new Repository($httpUrl, 'git');

        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::exact('#^https\://gitlab\.com/example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->add(Argument::exact($repository))
            ->shouldBeCalledTimes(1);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->willReturnArgument(0);

        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true, 'git', true);
        $response = $handler->getResponse($request);
    }

    public function testAutoAddPreferHttps(): void
    {
        $httpUrl = 'https://gitlab.com/example/nonexistant.git';
        $sshUrl = 'git@gitlab.com:example/nonexistant.git';
        $request = self::createRequest([
            'project' => [
                'git_http_url' => $httpUrl,
                'git_ssh_url' => $sshUrl,
            ],
        ]);

        $repository = new Repository($httpUrl, 'git');

        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::exact('#^git@gitlab\.com\:example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->findByUrl(Argument::exact('#^https\://gitlab\.com/example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->add(Argument::exact($repository))
            ->shouldBeCalledTimes(1);
        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->willReturnArgument(0);

        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true, 'git', false);
        $response = $handler->getResponse($request);
    }

    public function testAutoAddPreferHttpsFallback(): void
    {
        $sshUrl = 'git@gitlab.com:example/nonexistant.git';
        $request = self::createRequest([
            'project' => [
                'git_ssh_url' => $sshUrl,
            ],
        ]);

        $repository = new Repository($sshUrl, 'git');

        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::exact('#^git@gitlab\.com\:example/nonexistant\.git$#'))
            ->shouldBeCalledTimes(1);
        $manager
            ->add(Argument::exact($repository))
            ->shouldBeCalledTimes(1);

        $dispatcher = $this->getDispatcherMock();
        $dispatcher
            ->dispatch(Argument::type(BuildEvent::class))
            ->willReturnArgument(0);

        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true, 'git', false);
        $response = $handler->getResponse($request);
    }

    protected static function createRequest($content, string $event = 'push', ?string $token = null): Request
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }
        $request = Request::create('', 'GET', [], [], [], [], $content);
        $request->headers->set('X-Gitlab-Event', $event);
        if (null !== $token) {
            $request->headers->set('X-Gitlab-Token', $event);
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
