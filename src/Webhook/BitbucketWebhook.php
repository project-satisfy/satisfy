<?php

namespace Playbloom\Webhook;

use Playbloom\Model\RepositoryInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

class BitbucketWebhook extends AbstractWebhook
{
    /**
     * Bitbucket Cloud's outgoing addresses, from https://ip-ranges.atlassian.com/
     * as of 19 October 2026. They change; a secret does not.
     */
    private const TRUSTED_IPS = [
        '3.216.235.48/32',
        '3.221.151.112/32',
        '13.52.5.96/28',
        '13.200.41.128/25',
        '13.236.8.224/28',
        '18.136.214.96/28',
        '18.184.99.224/28',
        '18.234.32.224/28',
        '18.246.31.224/28',
        '34.196.8.197/32',
        '34.199.54.113/32',
        '34.216.18.129/32',
        '34.218.156.209/32',
        '34.218.168.212/32',
        '34.231.96.243/32',
        '34.232.25.90/32',
        '34.232.119.183/32',
        '34.233.65.54/32',
        '34.236.25.177/32',
        '35.155.178.254/32',
        '35.160.177.10/32',
        '35.171.175.212/32',
        '44.194.7.14/32',
        '44.199.3.254/32',
        '44.199.45.64/32',
        '44.199.127.226/32',
        '52.41.219.63/32',
        '52.54.90.98/32',
        '52.72.137.240/32',
        '52.202.195.162/32',
        '52.203.14.55/32',
        '52.204.96.37/32',
        '52.205.184.192/32',
        '52.215.192.224/28',
        '104.192.136.0/21',
        '174.129.205.191/32',
        '185.166.140.0/22',
        '2401:1d80:3000::/36',
        '2406:da18:809:e04::/63',
        '2406:da18:809:e06::/64',
        '2406:da1c:1e0:a204::/63',
        '2406:da1c:1e0:a206::/64',
        '2600:1f14:824:304::/63',
        '2600:1f14:824:306::/64',
        '2600:1f18:2146:e304::/63',
        '2600:1f18:2146:e306::/64',
        '2600:1f1c:cc5:2304::/63',
        '2a05:d014:f99:dd04::/63',
        '2a05:d014:f99:dd06::/64',
        '2a05:d018:34d:5804::/63',
        '2a05:d018:34d:5806::/64',
        '127.0.0.1/32',
    ];

    protected function getRepository(Request $request): RepositoryInterface
    {
        $content = $this->getPayload($request);
        if (isset($content['data'])) {
            $content = $content['data'];
        }

        $fullName = $content['repository']['full_name'] ?? null;
        if (!is_string($fullName) || '' === $fullName) {
            throw new \InvalidArgumentException('Invalid request data');
        }

        // full_name is "workspace/slug"; a clone URL may carry a user name and leave off ".git".
        $name = preg_quote($fullName, '#');
        $urls = [
            sprintf('#^https://([^/@]+@)?bitbucket\.org/%s(\.git)?/?$#i', $name),
            sprintf('#^(ssh://)?git@bitbucket\.org[:/]%s(\.git)?$#i', $name),
        ];

        $repository = $this->findRepository($urls);
        if (!$repository) {
            throw new \InvalidArgumentException('Cannot find specified repository');
        }

        return $repository;
    }

    /**
     * With a secret, the signature Bitbucket Cloud sends; without one, the
     * address the delivery came from.
     */
    protected function validate(Request $request): void
    {
        if (!empty($this->secret)) {
            $this->verifySignature($request, 'X-Hub-Signature');

            return;
        }

        if (!IpUtils::checkIp((string) $request->getClientIp(), self::TRUSTED_IPS)) {
            throw new \InvalidArgumentException('Client IP address is not trusted');
        }
    }
}
