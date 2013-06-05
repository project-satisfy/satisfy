<?php

namespace Playbloom\Satisfy\Model;

interface PersisterInterface
{
    public function load();

    public function flush($content);
}
