<?php

namespace Playbloom\Satisfy\Persister;

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
