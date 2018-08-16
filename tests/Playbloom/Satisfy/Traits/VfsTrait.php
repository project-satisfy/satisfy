<?php

namespace Tests\Playbloom\Satisfy\Traits;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait VfsTrait
{
    /** @var vfsStreamDirectory */
    protected $vfsRoot;

    protected function vfsSetup()
    {
        $this->vfsRoot = vfsStream::setup();
    }

    protected function vfsTearDown()
    {
        $this->vfsRoot = null;
    }
}
