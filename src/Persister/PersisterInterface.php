<?php

namespace Playbloom\Persister;

interface PersisterInterface
{
    public function load();

    public function flush($content): void;
}
