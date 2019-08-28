<?php

namespace Tests\Playbloom\Satisfy\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Webhook\GitlabWebhook;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GitlabWebhookTest extends TestCase
{
    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequest($request)
    {
        $this->expectException(BadRequestHttpException::class);

        $handler = new GitlabWebhook($this->getManagerMock()->reveal(), $this->getDispatcherMock()->reveal());
        $handler->getResponse($request);
    }

    public function invalidRequestProvider()
    {
        yield [$this->createRequest([], '')];

        yield [$this->createRequest([])];

        yield [$this->createRequest(['repository' => []])];
    }

    public function testValidRequest()
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
                }
            )
            ->shouldBeCalledTimes(1);

        $request = $this->createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push.json'));
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(0, $response->getContent());
    }

    public function testValidRequestWithRepoAutoAdd()
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
                }
            )
            ->shouldBeCalledTimes(1);

        $request = $this->createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push-nonexistant.json'));
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal(), null, true);
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(0, $response->getContent());
    }

    public function testDeprecatedRequestBodyValidRequest()
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
                }
            )
            ->shouldBeCalledTimes(1);

        $request = $this->createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push-deprecated.json'));
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(0, $response->getContent());
    }

    public function testInvalidTokenRequest()
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
        $request = $this->createRequest(file_get_contents(__DIR__ . '/../../../fixtures/gitlab-push.json'), 'push', 'invalid-token');
        $handler = new GitlabWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    protected function createRequest($content, string $event = 'push', string $token = null): Request
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

    protected function getManagerMock()
    {
        return $this->prophesize(Manager::class);
    }

    protected function getDispatcherMock()
    {
        return $this->prophesize(EventDispatcher::class);
    }
}
