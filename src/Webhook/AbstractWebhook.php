<?php

namespace Playbloom\Webhook;

use Playbloom\Event\BuildEvent;
use Playbloom\Event\RepositoryAddingEvent;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Exception\WebhookSkippedException;
use Playbloom\Model\RepositoryInterface;
use Playbloom\Service\Manager;
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
     * Whether deliveries are checked against a secret. Without one, anyone
     * who can reach the webhook can send one.
     */
    public function isAuthenticated(): bool
    {
        return null !== $this->secret && '' !== $this->secret;
    }

    /**
     * The provider's name, as in the webhook's path: gitlab, github, gitea, bitbucket or devops.
     */
    public function getProvider(): string
    {
        return strtolower((string) preg_replace('/Webhook$/', '', (new \ReflectionClass($this))->getShortName()));
    }

    /**
     * @throws BadRequestHttpException
     * @throws ServiceUnavailableHttpException
     */
    public function getResponse(Request $request): Response
    {
        try {
            $this->validate($request);

            $received = new WebhookReceivedEvent($this->getProvider(), $request, $this->getPayload($request), $this->isAuthenticated());
            $this->dispatcher->dispatch($received);
            if (null !== $response = $received->getResponse()) {
                return $response;
            }

            $repository = $this->getRepository($request);
            $event = $this->handle($repository);
        } catch (WebhookSkippedException $exception) {
            return new Response('skipped: ' . $exception->getMessage(), Response::HTTP_OK);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (\Throwable $exception) {
            throw new ServiceUnavailableHttpException(null, '', $exception, $exception->getCode());
        }

        if ($event->isQueued()) {
            return new Response('queued', Response::HTTP_ACCEPTED);
        }

        // No listener queued or ran the build: one stopped the event first.
        $status = $event->getStatus();
        if (null === $status) {
            return new Response('skipped', Response::HTTP_OK);
        }

        return new Response((string) $status, 0 === $status ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function handle(RepositoryInterface $repository): BuildEvent
    {
        $event = new BuildEvent($repository);
        $this->dispatcher->dispatch($event);

        return $event;
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

    /**
     * Auto-adds a repository a delivery named, unless a listener refuses it.
     * Only a delivery checked against a secret can: otherwise anyone could add
     * a repository of theirs, and publish its packages from this catalogue.
     *
     * @throws WebhookSkippedException
     */
    protected function addRepository(RepositoryInterface $repository, Request $request): void
    {
        if (!$this->isAuthenticated()) {
            throw new WebhookSkippedException('auto-add needs the webhook\'s secret to be set');
        }
        $event = new RepositoryAddingEvent($repository, $this->getProvider(), $this->getPayload($request), true);
        $this->dispatcher->dispatch($event);
        if ($event->isRefused()) {
            throw new WebhookSkippedException((string) $event->getRefusal());
        }
        $this->manager->add($repository);
    }

    /**
     * The delivery's JSON body, or its form-encoded "payload" field, which
     * GitHub and Gitea can send instead.
     *
     * @return array<string, mixed>
     */
    protected function getPayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) && $request->request->has('payload')) {
            $payload = json_decode((string) $request->request->get('payload'), true);
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * Checks the HMAC-SHA256 that GitHub, Gitea and Bitbucket Cloud send in
     * $header, after $prefix: of $signed, or of the raw body.
     *
     * @throws \InvalidArgumentException
     */
    protected function verifySignature(Request $request, string $header, string $prefix = 'sha256=', ?string $signed = null): void
    {
        $expected = $prefix . hash_hmac('sha256', $signed ?? $request->getContent(), (string) $this->secret);
        if (!hash_equals($expected, (string) $request->headers->get($header))) {
            throw new \InvalidArgumentException(sprintf('Invalid %s signature', $header));
        }
    }
}
