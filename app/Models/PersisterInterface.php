<?php

namespace Playbloom\Satisfy\Models;

interface PersisterInterface
{
    /**
     * @return mixed
     */
    public function load();

    /**
     * @param mixed $content
     */
    public function flush($content);
}
