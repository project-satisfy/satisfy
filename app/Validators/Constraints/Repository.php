<?php

namespace Playbloom\Satisfy\Validators\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class Repository extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return array();
    }
}
