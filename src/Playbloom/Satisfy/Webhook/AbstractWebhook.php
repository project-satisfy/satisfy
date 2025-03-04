<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Model\BuildContext;
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
    protected bool $debug = false;

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

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
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
            $status = $this->handle($repository, $context);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (\Throwable $exception) {
            throw new ServiceUnavailableHttpException(null, '', $exception, $exception->getCode());
        }

        if ($this->debug && null !== $context) {
            $content = [
                'status' => $status,
                'exit_code' => $context->getExitCode(),
                'command' => $context->getCommand(),
                'output' => $context->getOutput(),
                'error_output' => $context->getErrorOutput(),
                'exception' => null,
            ];

            if (null !== $context->getThrowable()) {
                $content['exception'] = [
                    'message' => $context->getThrowable()->getMessage(),
                    'code' => $context->getThrowable()->getCode(),
                    'file' => $context->getThrowable()->getFile(),
                    'line' => $context->getThrowable()->getLine(),
                    'trace' => $context->getThrowable()->getTrace(),
                ];
            }

            $status = json_encode($content);
        }

        return new Response((string) $status, 0 === $status ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function handle(RepositoryInterface $repository, ?BuildContext &$context): ?int
    {
        $event = new BuildEvent($repository);
        $this->dispatcher->dispatch($event);
        $context = $event->getContext();

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
