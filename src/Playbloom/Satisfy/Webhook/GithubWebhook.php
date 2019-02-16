<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Service\Manager;
use Psr\Http\Message\ServerRequestInterface;
use Swop\GitHubWebHook\Event\GitHubEventFactory;
use Swop\GitHubWebHook\Exception\GitHubWebHookException;
use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Swop\GitHubWebHook\Security\SignatureValidator;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

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

    public function handle(Request $request)
    {
        $psr7Factory = new DiactorosFactory();
        $psrRequest = $psr7Factory->createRequest($request);

        $this->validate($psrRequest);

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

        $event = new BuildEvent($repository);
        $this->dispatcher->dispatch(BuildEvent::EVENT_NAME, $event);

        return $event->getStatus();
    }

    protected function validate(ServerRequestInterface $request)
    {
        if (!empty($this->secret)) {
            $validator = new SignatureValidator();
            try {
                $validator->validate($request, $this->secret);
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
}
