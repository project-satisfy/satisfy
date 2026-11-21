<?php

namespace Playbloom\Event;

use Playbloom\Model\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before a webhook auto-adds a repository it does not know.
 * A listener can refuse; the delivery is then answered 200 and skipped.
 */
class RepositoryAddingEvent extends Event
{
    private ?string $refusal = null;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly string $provider,
        private readonly array $payload,
        private readonly bool $authenticated = false,
    ) {
    }

    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Whether the delivery was checked against the webhook's secret; see
     * WebhookReceivedEvent::isAuthenticated().
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function refuse(string $reason): void
    {
        $this->refusal = $reason;
        $this->stopPropagation();
    }

    public function isRefused(): bool
    {
        return null !== $this->refusal;
    }

    public function getRefusal(): ?string
    {
        return $this->refusal;
    }
}
