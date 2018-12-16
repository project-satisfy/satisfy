<?php
/**
 * Created by Marcin.
 * Date: 16.12.2018
 * Time: 14:40
 */

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Event\BuildEvent;
use Symfony\Component\HttpFoundation\Request;

class GitlabWebhook extends AbstractWebhook
{
    private const HTTP_TOKEN = 'X-GITLAB-TOKEN';

    /** @var string|null */
    protected $secret;

    public function setSecret(string $secret = null): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function handle(Request $request)
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
