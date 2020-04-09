<?php

namespace Tests\Playbloom\Satisfy\Traits;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait VfsTrait
{
    /** @var vfsStreamDirectory|null */
    protected $vfsRoot;

    protected function vfsSetup(): void
    {
        $this->vfsRoot = vfsStream::setup();
    }

    protected function vfsTearDown(): void
    {
        $this->vfsRoot = null;
    }
}
