<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

abstract class AbstractWebhook
{
    protected EventDispatcherInterface $dispatcher;

    protected Manager $manager;

    protected ?string $secret = null;

    public function __construct(Manager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }

    public function setSecret(?string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @throws BadRequestHttpException
     * @throws ServiceUnavailableHttpException
     */
    public function getResponse(Request $request): Response
    {
        try {
            $this->validate($request);
            $repository = $this->getRepository($request);
            $status = $this->handle($repository);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (\Throwable $exception) {
            throw new ServiceUnavailableHttpException(null, '', $exception, $exception->getCode());
        }

        return new Response((string) $status, 0 === $status ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function handle(RepositoryInterface $repository): ?int
    {
        $event = new BuildEvent($repository);
        $this->dispatcher->dispatch($event);

        return $event->getStatus();
    }

    /**
     * @throws \InvalidArgumentException
     */
    abstract protected function validate(Request $request): void;

    /**
     * @throws \InvalidArgumentException
     */
    abstract protected function getRepository(Request $request): RepositoryInterface;

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
