<?php

namespace Playbloom\Model;

/**
 * What became of one build: Satis's exit code, or the reason it never ran.
 */
final class BuildResult
{
    public function __construct(
        private readonly ?int $exitCode,
        private readonly float $seconds,
        private readonly ?string $cancelReason = null,
    ) {
    }

    public static function cancelled(string $reason, float $seconds): self
    {
        return new self(null, $seconds, $reason);
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function getSeconds(): float
    {
        return $this->seconds;
    }

    public function isCancelled(): bool
    {
        return null !== $this->cancelReason;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }
}
