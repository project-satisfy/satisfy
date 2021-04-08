<?php

namespace Playbloom\Satisfy\Webhook;

use Symfony\Component\HttpFoundation\Request;

class GiteaWebhook extends GithubWebhook
{
    protected function validate(Request $request): void
    {
        if (!empty($this->secret)) {
            if ($request->headers->has('X-Gitea-Signature')) {
                $payload = trim($request->getContent());
                $signature = hash_hmac('sha256', $payload, $this->secret);
                if ($signature != $request->headers->get('X-Gitea-Signature')) {
                    throw new \InvalidArgumentException('Invalid Gitea Signature');
                }
            } else {
                $payload = json_decode($request->getContent());
                if (!$payload || !isset($payload->secret) || $payload->secret != $this->secret) {
                    throw new \InvalidArgumentException('Invalid Gitea Signature');
                }
            }
        }
    }
}
