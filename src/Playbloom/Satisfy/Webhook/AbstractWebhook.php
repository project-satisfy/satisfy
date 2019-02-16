<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractWebhook
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var Manager */
    protected $manager;

    /** @var string|null */
    protected $secret;

    public function __construct(Manager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }

    abstract public function handle(Request $request): ?int;

    protected function findRepository(array $urls): ?RepositoryInterface
    {
        foreach ($urls as $url) {
            $repository = $this->manager->findByUrl($url);
            if ($repository) {
                return $repository;
            }
        }

        return null;
    }
}
