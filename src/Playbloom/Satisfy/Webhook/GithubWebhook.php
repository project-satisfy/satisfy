<?php

namespace Playbloom\Satisfy\Webhook;

use InvalidArgumentException;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Playbloom\Satisfy\Model\Repository;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Psr\Http\Message\RequestInterface;
use Swop\GitHubWebHook\Event\GitHubEventFactory;
use Swop\GitHubWebHook\Exception\GitHubWebHookException;
use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Swop\GitHubWebHook\Security\SignatureValidator;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class GithubWebhook extends AbstractWebhook
{
    /** @var string[] */
    protected array $sourceUrls = ['git_url', 'ssh_url', 'clone_url', 'svn_url'];

    protected bool $autoAdd = false;

    protected string $autoAddType = '';

    /**
     * @param string[] $sourceUrls
     *
     * @return $this
     */
    public function setSourceUrls(array $sourceUrls): self
    {
        $this->sourceUrls = $sourceUrls;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAutoAdd(bool $autoAdd): self
    {
        $this->autoAdd = $autoAdd;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAutoAddType(string $autoAddType): self
    {
        $this->autoAddType = $autoAddType;

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
            $this->handle($repository);
        };

        // instruct client to close connection after 2 bytes "OK"
        return new StreamedResponse($callback, Response::HTTP_OK, ['Connection' => 'close', 'Content-Length' => 2]);
    }

    protected function validate(Request $request): void
    {
        if (!empty($this->secret)) {
            $psrRequest = $this->createPsr7Request($request);
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
        $psrRequest = $this->createPsr7Request($request);
        $eventFactory = new GitHubEventFactory();
        try {
            $event = $eventFactory->buildFromRequest($psrRequest);
        } catch (GitHubWebHookException $exception) {
            throw new \InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        $payload = $event->getPayload();
        $repositoryData = $payload['repository'] ?? [];

        $urls = [];
        $originalUrls = [];
        foreach ($this->sourceUrls as $key) {
            $url = $repositoryData[$key] ?? null;
            if (!empty($url)) {
                $originalUrls[] = $url;
                $urls[] = $this->getUrlPattern($url);
            }
        }

        $repository = $this->findRepository($urls);
        if (!$repository) {
            if ($this->autoAdd && !empty($originalUrls)) {
                $repository = new Repository($originalUrls[0], $this->autoAddType);
                $this->manager->add($repository);
            } else {
                throw new \InvalidArgumentException('Cannot find specified repository');
            }
        }

        return $repository;
    }

    protected function createPsr7Request(Request $request): RequestInterface
    {
        $psr7Factory = new PsrHttpFactory(
            new ServerRequestFactory(),
            new StreamFactory(),
            new UploadedFileFactory(),
            new ResponseFactory()
        );

        return $psr7Factory->createRequest($request);
    }
}
