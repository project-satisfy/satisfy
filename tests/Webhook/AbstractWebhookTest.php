<?php

namespace Playbloom\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Event\BuildEvent;
use Playbloom\Model\Repository;
use Playbloom\Model\RepositoryInterface;
use Playbloom\Service\Manager;
use Playbloom\Webhook\AbstractWebhook;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class AbstractWebhookTest extends TestCase
{
    use ProphecyTrait;

    public function testGetResponseReturnsTheCommandExitCode()
    {
        $webhook = $this->createWebhook(0);

        $response = $webhook->getResponse(Request::create('/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('0', $response->getContent());
    }

    public function testGetResponseWithErrorsReturns500()
    {
        $webhook = $this->createWebhook(1);

        $response = $webhook->getResponse(Request::create('/'));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('1', $response->getContent());
    }

    public function testQueuedBuildReturns202()
    {
        $webhook = $this->createWebhook(null, true);

        $response = $webhook->getResponse(Request::create('/'));

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('queued', $response->getContent());
    }

    public function testBuildNobodyTookIsSkipped()
    {
        $webhook = $this->createWebhook(null);

        $response = $webhook->getResponse(Request::create('/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('skipped', $response->getContent());
    }

    public function createWebhook(?int $status, bool $queued = false): AbstractWebhook
    {
        $manager = $this->prophesize(Manager::class);

        $webhook = new class($manager->reveal(), new EventDispatcher()) extends AbstractWebhook {
            public $status;

            public $queued = false;

            public function handle(RepositoryInterface $repository): BuildEvent
            {
                $event = new BuildEvent($repository);
                if (null !== $this->status) {
                    $event->setStatus($this->status);
                }
                if ($this->queued) {
                    $event->markQueued();
                }

                return $event;
            }

            protected function validate(Request $request): void
            {
            }

            protected function getRepository(Request $request): RepositoryInterface
            {
                return new Repository('git@git.example.com', 'git');
            }
        };

        $webhook->status = $status;
        $webhook->queued = $queued;

        return $webhook;
    }
}
