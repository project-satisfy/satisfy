<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Event\BuildEvent;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class BitbucketWebhook extends AbstractWebhook
{
    public function handle(Request $request)
    {
        $this->validate($request);

        $content = json_decode($request->getContent(), true);
        if (isset($content['data'])) {
            $content = $content['data'];
        }
        $repository = $content['repository'] ?? [];
        $fullName = $repository['full_name'] ?? null;
        if (empty($fullName)) {
            throw new \InvalidArgumentException('Invalid request data');
        }

        $urls = [
            sprintf('#^https://bitbucket\.org/%s\.git$#', $fullName),
            sprintf('#^git@bitbucket.org\:%s\.git$#', $fullName),
            sprintf('#^ssh://hg@bitbucket\.org/%s$#', $fullName),
        ];
        $repository = $this->findRepository($urls);
        if (!$repository) {
            throw new \InvalidArgumentException('Cannot find specified repository');
        }

        $event = new BuildEvent($repository);
        $this->dispatcher->dispatch(BuildEvent::EVENT_NAME, $event);

        return $event->getStatus();
    }

    protected function validate(Request $request)
    {
        $ip = $request->getClientIp();
        $trusted = [
            '104.192.136.0/21',
            '34.198.203.127',
            '34.198.178.64',
            '34.198.32.85',
            '127.0.0.1/32',
        ];

        if (!IpUtils::checkIp($ip, $trusted)) {
            throw new \InvalidArgumentException('Client IP address is not trusted');
        }
    }
}
