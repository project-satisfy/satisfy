<?php

namespace Tests\Playbloom\Satisfy\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Webhook\GithubWebhook;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GithubWebhookTest extends TestCase
{
    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequest($request)
    {
        $this->expectException(BadRequestHttpException::class);

        $handler = new GithubWebhook($this->getManagerMock()->reveal(), $this->getDispatcherMock()->reveal());
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
            ->willReturn(new Repository('git://github.com/Codertocat/Hello-World.git'))
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

        $request = $this->createRequest(file_get_contents(__DIR__ . '/../../../fixtures/github-push.json'));
        $handler = new GithubWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        ob_start();
        $response->sendContent();
        $result = ob_get_clean();

        $this->assertEquals('OK', $result);
    }

    protected function createRequest($content, string $event = 'push'): Request
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }
        $request = Request::create('', 'GET', [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $event);

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
