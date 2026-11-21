<?php

namespace Playbloom\Webhook;

use Symfony\Component\HttpFoundation\Request;

class GiteaWebhook extends GithubWebhook
{
    protected function validate(Request $request): void
    {
        if (empty($this->secret)) {
            return;
        }

        if ($request->headers->has('X-Gitea-Signature')) {
            // Gitea signs the JSON, which a form-encoded delivery carries in
            // its "payload" field, not the body it sends.
            $signed = str_starts_with((string) $request->headers->get('Content-Type'), 'application/x-www-form-urlencoded')
                ? (string) $request->request->get('payload')
                : null;
            $this->verifySignature($request, 'X-Gitea-Signature', '', $signed);

            return;
        }

        // Gogs and older Gitea releases sent the secret in the payload instead.
        $secret = $this->getPayload($request)['secret'] ?? null;
        if (!is_string($secret) || !hash_equals($this->secret, $secret)) {
            throw new \InvalidArgumentException('Invalid Gitea Signature');
        }
    }
}
