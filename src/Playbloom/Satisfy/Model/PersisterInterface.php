<?php

namespace Playbloom\Satisfy\Model;

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
