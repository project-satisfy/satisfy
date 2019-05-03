<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Swop\GitHubWebHook\Event\GitHubEventFactory;
use Swop\GitHubWebHook\Exception\GitHubWebHookException;
use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Swop\GitHubWebHook\Security\SignatureValidator;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class GithubWebhook extends AbstractWebhook
{
    public function __construct(Manager $manager, EventDispatcherInterface $dispatcher, ?string $secret = null)
    {
        parent::__construct($manager, $dispatcher);
        $this->secret = $secret;
    }

    public function setSecret(string $secret = null): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getResponse(Request $request): Response
    {
        try {
            $this->validate($request);
            $repository = $this->getRepository($request);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (\Throwable $exception) {
            throw new ServiceUnavailableHttpException();
        }

        $callback = function () use ($repository) {
            echo 'OK';
            if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
                ignore_user_abort(true);
                Response::closeOutputBuffers(0, true);
            }
            $this->handle($repository);
        };

        // instruct client to close connection after 2 bytes "OK"
        return new StreamedResponse($callback, Response::HTTP_OK, ['Connection' => 'close', 'Content-Length' => 2]);
    }

    protected function validate(Request $request): void
    {
        if (!empty($this->secret)) {
            $psr7Factory = new DiactorosFactory();
            $psrRequest = $psr7Factory->createRequest($request);
            $validator = new SignatureValidator();
            try {
                $validator->validate($psrRequest, $this->secret);
            } catch (InvalidGitHubRequestSignatureException $exception) {
                throw new \InvalidArgumentException($exception->getMessage());
            }
        }
    }

    protected function getUrlPattern(string $url): string
    {
        $pattern = '#^' . $url . '$#';
        $pattern = str_replace(['.', ':'], ['\.', '\:'], $pattern);

        return $pattern;
    }

    protected function getRepository(Request $request): RepositoryInterface
    {
        $psr7Factory = new DiactorosFactory();
        $psrRequest = $psr7Factory->createRequest($request);
        $eventFactory = new GitHubEventFactory();
        try {
            $event = $eventFactory->buildFromRequest($psrRequest);
        } catch (GitHubWebHookException $exception) {
            throw new \InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        $payload = $event->getPayload();
        $repositoryData = $payload['repository'] ?? [];

        $urls = [];
        foreach (['git_url', 'ssh_url', 'clone_url', 'svn_url'] as $key) {
            $url = $repositoryData[$key] ?? null;
            if (!empty($url)) {
                $urls[] = $this->getUrlPattern($url);
            }
        }

        $repository = $this->findRepository($urls);
        if (!$repository) {
            throw new \InvalidArgumentException('Cannot find specified repository');
        }

        return $repository;
    }
}
