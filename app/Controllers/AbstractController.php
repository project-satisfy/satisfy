<?php

namespace Playbloom\Satisfy\Controllers;

use Playbloom\Satisfy\Services\Container;

abstract class AbstractController
{
    protected $app;

    public function __construct()
    {
        $this->app = Container::getInstance();
    }
}
