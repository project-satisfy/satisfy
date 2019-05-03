<?php

namespace Playbloom\Satisfy\Webhook;

use Playbloom\Satisfy\Model\RepositoryInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class BitbucketWebhook extends AbstractWebhook
{
    protected function getRepository(Request $request): RepositoryInterface
    {
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

        return $repository;
    }

    protected function validate(Request $request): void
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
