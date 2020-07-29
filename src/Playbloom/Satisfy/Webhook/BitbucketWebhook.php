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
            // https://ip-ranges.atlassian.com/
            '52.41.219.63/32',
            '34.216.18.129/32',
            '13.236.8.128/25',
            '18.246.31.128/25',
            '34.236.25.177/32',
            '185.166.140.0/22',
            '34.199.54.113/32',
            '35.155.178.254/32',
            '52.204.96.37/32',
            '35.160.177.10/32',
            '52.203.14.55/32',
            '18.184.99.128/25',
            '52.215.192.128/25',
            '104.192.136.0/21',
            '18.205.93.0/27',
            '35.171.175.212/32',
            '18.136.214.0/25',
            '52.202.195.162/32',
            '13.52.5.0/25',
            '34.218.168.212/32',
            '18.234.32.128/25',
            '34.218.156.209/32',
            '52.54.90.98/32',
            '34.232.119.183/32',
            '34.232.25.90/32',
            '127.0.0.1/32',
        ];

        if (!IpUtils::checkIp($ip, $trusted)) {
            throw new \InvalidArgumentException('Client IP address is not trusted');
        }
    }
}
