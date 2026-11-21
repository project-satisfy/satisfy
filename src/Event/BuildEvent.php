<?php

namespace Playbloom\Event;

use Playbloom\Model\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BuildEvent extends Event
{
    public const EVENT_NAME = 'satis_build';

    /** @var RepositoryInterface|null */
    private $repository;

    /** @var int|null */
    private $status;

    private bool $queued = false;

    public function __construct(?RepositoryInterface $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * @return RepositoryInterface|null
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    /**
     * The build was queued for the worker rather than run.
     */
    public function markQueued(): void
    {
        $this->queued = true;
    }

    public function isQueued(): bool
    {
        return $this->queued;
    }
}
