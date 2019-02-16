<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Event\BuildEvent;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class GitlabWebhook extends AbstractWebhook
{
    private const HTTP_TOKEN = 'X-GITLAB-TOKEN';

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

    public function handle(Request $request): ?int
    {
        $this->validate($request);
        $content = json_decode($request->getContent(), true);

        $repositoryData = $content['repository'] ?? [];
        $urls = [];
        foreach (['git_http_url', 'git_ssh_url'] as $key) {
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

    protected function getUrlPattern(string $url): string
    {
        $pattern = '#^' . $url . '$#';
        $pattern = str_replace(['.', ':'], ['\.', '\:'], $pattern);

        return $pattern;
    }

    protected function validate(Request $request)
    {
        if ($request->headers->get(self::HTTP_TOKEN) !== $this->secret) {
            throw new \InvalidArgumentException('Invalid Token');
        }
    }
}
