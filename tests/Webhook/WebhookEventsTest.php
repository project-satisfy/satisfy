<?php

namespace Playbloom\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Event\BuildEvent;
use Playbloom\Event\RepositoryAddingEvent;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Model\Repository;
use Playbloom\Service\Manager;
use Playbloom\Webhook\GitlabWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * The points plugins attach to: every verified delivery, and every auto-add.
 */
class WebhookEventsTest extends TestCase
{
    use ProphecyTrait;

    private const SECRET = 'secret';

    public function testAListenerCanSkipADelivery(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(WebhookReceivedEvent::class, function (WebhookReceivedEvent $event): void {
            $this->assertSame('gitlab', $event->getProvider());
            $this->assertSame('refs/heads/main', $event->getPayload()['ref']);
            $event->skip('not for us');
        });
        $dispatcher->addListener(BuildEvent::class, fn () => $this->fail('A skipped delivery was built'));

        $response = $this->createWebhook($dispatcher)->getResponse($this->createRequest(['ref' => 'refs/heads/main']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('skipped: not for us', $response->getContent());
    }

    public function testAListenerCanAcceptADelivery(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(WebhookReceivedEvent::class, function (WebhookReceivedEvent $event): void {
            $this->assertSame('1.2.0', $event->getCreatedTag());
            $event->accept('accepted: on it');
        });

        $response = $this->createWebhook($dispatcher)->getResponse($this->createRequest([
            'object_kind' => 'tag_push',
            'ref' => 'refs/tags/1.2.0',
            'after' => 'b5f1b6a8',
        ]));

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('accepted: on it', $response->getContent());
    }

    public function testADeletedTagIsNotACreatedOne(): void
    {
        $event = new WebhookReceivedEvent('gitlab', Request::create('/'), [
            'ref' => 'refs/tags/1.2.0',
            'after' => '0000000000000000000000000000000000000000',
        ]);

        $this->assertNull($event->getCreatedTag());
    }

    public function testARefusedAutoAddIsSkippedAndNotWritten(): void
    {
        $manager = $this->prophesize(Manager::class);
        $manager->findByUrl(Argument::any())->willReturn(null);
        $manager->add(Argument::any())->shouldNotBeCalled();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(RepositoryAddingEvent::class, function (RepositoryAddingEvent $event): void {
            $this->assertSame('https://git.example.com/site/scaffold.git', $event->getRepository()->getUrl());
            $event->refuse('declares type "project"');
        });

        $webhook = new GitlabWebhook($manager->reveal(), $dispatcher, self::SECRET, true, 'vcs');
        $response = $webhook->getResponse($this->createRequest(['ref' => 'refs/heads/main']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('skipped: declares type "project"', $response->getContent());
    }

    public function testAnAllowedAutoAddIsWrittenAndQueued(): void
    {
        $manager = $this->prophesize(Manager::class);
        $manager->findByUrl(Argument::any())->willReturn(null);
        $manager->add(Argument::type(Repository::class))->shouldBeCalledOnce();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(BuildEvent::class, fn (BuildEvent $event) => $event->markQueued());

        $webhook = new GitlabWebhook($manager->reveal(), $dispatcher, self::SECRET, true, 'vcs');
        $response = $webhook->getResponse($this->createRequest(['ref' => 'refs/heads/main']));

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testListenersAreToldWhetherTheDeliveryWasCheckedAgainstASecret(): void
    {
        $seen = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(WebhookReceivedEvent::class, function (WebhookReceivedEvent $event) use (&$seen): void {
            $seen[] = $event->isAuthenticated();
            $event->skip('seen');
        });
        $manager = $this->prophesize(Manager::class)->reveal();

        (new GitlabWebhook($manager, $dispatcher, self::SECRET))->getResponse($this->createRequest([]));
        (new GitlabWebhook($manager, $dispatcher))->getResponse(Request::create('/webhook/gitlab', 'POST', [], [], [], [], '{}'));

        $this->assertSame([true, false], $seen);
    }

    private function createWebhook(EventDispatcher $dispatcher): GitlabWebhook
    {
        $manager = $this->prophesize(Manager::class);
        $manager->findByUrl(Argument::any())->willReturn(new Repository('https://git.example.com/site/scaffold.git'));

        return new GitlabWebhook($manager->reveal(), $dispatcher, self::SECRET);
    }

    private function createRequest(array $payload): Request
    {
        $payload['project'] = ['git_http_url' => 'https://git.example.com/site/scaffold.git'];

        return Request::create('/webhook/gitlab', 'POST', [], [], [], ['HTTP_X_GITLAB_TOKEN' => self::SECRET], (string) json_encode($payload));
    }
}
