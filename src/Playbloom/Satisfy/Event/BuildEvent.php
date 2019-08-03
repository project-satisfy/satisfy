<?php

namespace Playbloom\Satisfy\Event;

use Playbloom\Satisfy\Model\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BuildEvent extends Event
{
    public const EVENT_NAME = 'satis_build';

    /** @var RepositoryInterface|null */
    private $repository;

    /** @var int|null */
    private $status;

    public function __construct(RepositoryInterface $repository = null)
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
}
