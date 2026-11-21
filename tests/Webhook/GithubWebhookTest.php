<?php

namespace Playbloom\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Event\BuildEvent;
use Playbloom\Event\RepositoryAddingEvent;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Model\Repository;
use Playbloom\Service\Manager;
use Playbloom\Webhook\GithubWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GithubWebhookTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider invalidRequestProvider
     */
    public function testInvalidRequest($request): void
    {
        $this->expectException(BadRequestHttpException::class);

        $handler = new GithubWebhook($this->getManagerMock()->reveal(), $this->getDispatcherMock()->reveal());
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
            ->willReturn(new Repository('git://github.com/Codertocat/Hello-World.git'))
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

        $request = self::createRequest(file_get_contents(__DIR__ . '/../fixtures/github-push.json'));
        $handler = new GithubWebhook($manager->reveal(), $dispatcher->reveal());
        $response = $handler->getResponse($request);

        // The mock ran the build in place, as 3.x did; the queue answers 202.
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('0', $response->getContent());
    }

    public function testValidRequestWithRepoAutoAdd(): void
    {
        $manager = $this->getManagerMock();
        $manager
            ->findByUrl(Argument::type('string'))
            ->willReturn(null)
            ->shouldBeCalledTimes(5);

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

        $request = self::createRequest(file_get_contents(__DIR__ . '/../fixtures/github-push.json'));
        $request->headers->set('X-Hub-Signature-256', 'sha256=' . hash_hmac('sha256', $request->getContent(), 'secret'));
        $handler = new GithubWebhook($manager->reveal(), $dispatcher->reveal());
        $handler->setSecret('secret');
        $handler->setAutoAdd(true);
        $handler->setAutoAddType('github');
        $response = $handler->getResponse($request);

        // The mock ran the build in place, as 3.x did; the queue answers 202.
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('0', $response->getContent());
    }

    protected static function createRequest($content, string $event = 'push'): Request
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }
        $request = Request::create('', 'GET', [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $event);

        return $request;
    }

    protected function getManagerMock(): ObjectProphecy
    {
        return $this->prophesize(Manager::class);
    }

    protected function getDispatcherMock(): ObjectProphecy
    {
        $dispatcher = $this->prophesize(EventDispatcher::class);
        // Dispatched on every delivery; nothing in these tests listens to them.
        $dispatcher->dispatch(Argument::type(WebhookReceivedEvent::class))->willReturnArgument(0);
        $dispatcher->dispatch(Argument::type(RepositoryAddingEvent::class))->willReturnArgument(0);

        return $dispatcher;
    }
}
