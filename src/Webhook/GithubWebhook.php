<?php

namespace Playbloom\Webhook;

use Playbloom\Exception\WebhookSkippedException;
use Playbloom\Model\Repository;
use Playbloom\Model\RepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

class GithubWebhook extends AbstractWebhook
{
    /** @var string[] */
    protected array $sourceUrls = ['clone_url', 'ssh_url', 'git_url', 'svn_url', 'html_url'];

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

    protected function validate(Request $request): void
    {
        if (!empty($this->secret)) {
            $this->verifySignature($request, 'X-Hub-Signature-256');
        }
    }

    protected function getUrlPattern(string $url): string
    {
        return '#^' . preg_quote($url, '#') . '$#';
    }

    protected function getRepository(Request $request): RepositoryInterface
    {
        // Sent once when the webhook is created; an organisation's names no repository.
        if ('ping' === $request->headers->get('X-GitHub-Event')) {
            throw new WebhookSkippedException('ping');
        }

        $payload = $this->getPayload($request);
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
                $this->addRepository($repository, $request);
            } else {
                throw new \InvalidArgumentException('Cannot find specified repository');
            }
        }

        return $repository;
    }
}
