<?php

namespace Playbloom\Plugin\Hookgate;

/**
 * A package auto-require will not publish. The require entry is left for a person.
 */
final class AutoRequireRefused extends \RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly string $detail = '',
    ) {
        parent::__construct($message);
    }
}
