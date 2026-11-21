<?php

namespace Playbloom\Event;

use Playbloom\Model\BuildRequest;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched by the worker before Satis runs. A listener can cancel the build.
 */
class BuildStartingEvent extends Event
{
    private ?string $cancelReason = null;

    public function __construct(private readonly BuildRequest $request)
    {
    }

    public function getRequest(): BuildRequest
    {
        return $this->request;
    }

    public function cancel(string $reason): void
    {
        $this->cancelReason = $reason;
        $this->stopPropagation();
    }

    public function isCancelled(): bool
    {
        return null !== $this->cancelReason;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }
}
