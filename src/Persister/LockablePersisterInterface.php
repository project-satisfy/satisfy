<?php

namespace Playbloom\Persister;

use Symfony\Component\Filesystem\Exception\IOException;

/**
 * A persister whose file other programs may write too. Changes are made under
 * an exclusive flock() on the file itself, which those programs can also take.
 */
interface LockablePersisterInterface extends PersisterInterface
{
    /**
     * Takes the exclusive lock. Nested calls only count; the lock is released
     * by the matching outermost unlock().
     *
     * @throws IOException when the lock cannot be had in time
     */
    public function lock(): void;

    public function unlock(): void;

    /**
     * A hash of what is stored now, to tell whether it changed since it was read.
     */
    public function getVersion(): string;
}
