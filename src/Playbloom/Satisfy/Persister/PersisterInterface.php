<?php

namespace Playbloom\Satisfy\Persister;

interface PersisterInterface
{
    public function load();

    public function flush($content);
}
