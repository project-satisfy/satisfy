<?php

namespace Tests\Playbloom\Satisfy\Webhook;

use PHPUnit\Framework\TestCase;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Webhook\AbstractWebhook;
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

    public function createWebhook(int $status): AbstractWebhook
    {
        $manager = $this->prophesize(Manager::class);

        $webhook = new class($manager->reveal(), new EventDispatcher()) extends AbstractWebhook {
            public $status;

            public function handle(RepositoryInterface $repository): ?int
            {
                return $this->status;
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

        return $webhook;
    }
}
