<?php

use RDV\SymfonyContainerMocks\DependencyInjection\TestContainer;

class TestKernel extends Kernel
{
    protected function getContainerBaseClass(): string
    {
        if ('test' === $this->environment) {
            return TestContainer::class;
        }

        return parent::getContainerBaseClass();
    }
}
