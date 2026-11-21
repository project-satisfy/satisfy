<?php

namespace Playbloom\Event;

use Playbloom\Model\BuildRequest;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched by the worker after Satis exits.
 */
class BuildFinishedEvent extends Event
{
    public function __construct(
        private readonly BuildRequest $request,
        private readonly int $exitCode,
    ) {
    }

    public function getRequest(): BuildRequest
    {
        return $this->request;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }
}
