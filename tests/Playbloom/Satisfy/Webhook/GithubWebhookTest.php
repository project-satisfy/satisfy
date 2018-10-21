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

class GithubWebhookTest extends TestCase
{
    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequest($request)
    {
        $this->expectException(\InvalidArgumentException::class);

        $handler = new GithubWebhook($this->getManagerMock()->reveal(), $this->getDispatcherMock()->reveal());
        $handler->handle($request);
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
            ->dispatch(Argument::exact(BuildEvent::EVENT_NAME), Argument::type(BuildEvent::class))
            ->will(
                function ($args) {
                    $args[1]->setStatus(0);
                }
            )
            ->shouldBeCalledTimes(1);

        $request = $this->createRequest(file_get_contents(__DIR__ . '/../../../fixtures/github-push.json'));
        $handler = new GithubWebhook($manager->reveal(), $dispatcher->reveal());
        $result = $handler->handle($request);

        $this->assertEquals(0, $result);
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
