<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class GitlabWebhook extends AbstractWebhook
{
    private const HTTP_TOKEN = 'X-GITLAB-TOKEN';
    private const BODY_HTTP_URL_KEY = 'git_http_url';
    private const BODY_SSH_URL_KEY = 'git_ssh_url';

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

    protected function getRepository(Request $request): RepositoryInterface
    {
        $content = json_decode($request->getContent(), true);

        // Starting from GitLab 8.5:
        // - the repository key is deprecated in favor of the project key
        // - the project.ssh_url key is deprecated in favor of the project.git_ssh_url key
        // - the project.http_url key is deprecated in favor of the project.git_http_url key
        $repositoryData = $content['repository'] ?? [];
        if (empty($repositoryData)) {
            $repositoryData = $content['project'] ?? [];
        }

        $urls = [];
        $originalUrls = [];
        foreach ([self::BODY_HTTP_URL_KEY, self::BODY_SSH_URL_KEY] as $key) {
            $url = $repositoryData[$key] ?? null;
            if (!empty($url)) {
                $originalUrls[] = $url;
                $urls[] = $this->getUrlPattern($url);
            }
        }

        $repository = $this->findRepository($urls);
        if (!$repository) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find specified repository "%s"', join(' OR ', $originalUrls))
            );
        }

        return $repository;
    }

    protected function getUrlPattern(string $url): string
    {
        $pattern = '#^' . $url . '$#';
        $pattern = str_replace(['.', ':'], ['\.', '\:'], $pattern);

        return $pattern;
    }

    protected function validate(Request $request): void
    {
        if ($request->headers->get(self::HTTP_TOKEN) !== $this->secret) {
            throw new \InvalidArgumentException('Invalid Token');
        }
    }
}
