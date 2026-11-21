<?php

namespace Playbloom\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched for every webhook delivery that passed its secret check, before
 * Satisfy looks up the repository it names.
 *
 * A listener can answer the delivery itself: skip() it, or accept() it and
 * finish the work after the response has gone, on kernel.terminate.
 */
class WebhookReceivedEvent extends Event
{
    private ?Response $response = null;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $provider,
        private readonly Request $request,
        private readonly array $payload,
        private readonly bool $authenticated = false,
    ) {
    }

    /**
     * gitlab, github, gitea, bitbucket or devops.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Whether the delivery was checked against the webhook's secret. Without
     * one, anyone who can reach the webhook could have sent it, and what it
     * names, URLs and hosts included, is theirs to choose.
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * The tag this delivery reports as pushed, or null for anything else,
     * including a deleted tag. Of several, the first.
     */
    public function getCreatedTag(): ?string
    {
        foreach ($this->getRefUpdates() as [$ref, $deleted]) {
            if (!$deleted && str_starts_with($ref, 'refs/tags/') && 'refs/tags/' !== $ref) {
                return substr($ref, strlen('refs/tags/'));
            }
        }

        return null;
    }

    /**
     * The branch this delivery reports as pushed, or null for anything else,
     * including a deleted branch. Of several, the first.
     */
    public function getPushedBranch(): ?string
    {
        foreach ($this->getRefUpdates() as [$ref, $deleted]) {
            if (!$deleted && str_starts_with($ref, 'refs/heads/') && 'refs/heads/' !== $ref) {
                return substr($ref, strlen('refs/heads/'));
            }
        }

        return null;
    }

    /**
     * The refs a push delivery updated, each as [ref, deleted], in the shape
     * each provider sends them.
     *
     * @return list<array{string, bool}>
     */
    private function getRefUpdates(): array
    {
        $updates = [];
        switch ($this->provider) {
            case 'bitbucket':
                // push.changes[]: "new" is null when a ref is deleted.
                foreach ((array) ($this->payload['push']['changes'] ?? []) as $change) {
                    $new = $change['new'] ?? null;
                    if (is_array($new) && is_string($new['name'] ?? null)) {
                        $prefix = 'tag' === ($new['type'] ?? null) ? 'refs/tags/' : 'refs/heads/';
                        $updates[] = [$prefix . $new['name'], false];
                    }
                }
                break;
            case 'devops':
                // resource.refUpdates[]: newObjectId is all zeros when a ref is deleted.
                foreach ((array) ($this->payload['resource']['refUpdates'] ?? []) as $update) {
                    $updates[] = [(string) ($update['name'] ?? ''), self::isNullSha($update['newObjectId'] ?? '')];
                }
                break;
            default:
                // GitLab, GitHub and Gitea: ref, with "after" all zeros when it is deleted.
                $deleted = !empty($this->payload['deleted']) || self::isNullSha($this->payload['after'] ?? '');
                $updates[] = [(string) ($this->payload['ref'] ?? ''), $deleted];
        }

        return $updates;
    }

    private static function isNullSha(mixed $sha): bool
    {
        return 1 === preg_match('/^0+$/', (string) $sha);
    }

    /**
     * Answers 200 and does nothing more with the delivery.
     */
    public function skip(string $reason): void
    {
        $this->response = new Response('skipped: ' . $reason, Response::HTTP_OK);
        $this->stopPropagation();
    }

    /**
     * Answers 202; the listener does the work itself.
     */
    public function accept(string $message): void
    {
        $this->response = new Response($message, Response::HTTP_ACCEPTED);
        $this->stopPropagation();
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
