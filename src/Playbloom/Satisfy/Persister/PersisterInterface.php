<?php

namespace Playbloom\Satisfy\Persister;

interface PersisterInterface
{
    /**
     * @return string
     */
    public function load();

    /**
     * @param string $content
     */
    public function flush($content);
}
