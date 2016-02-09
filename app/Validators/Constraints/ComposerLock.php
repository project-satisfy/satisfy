<?php

namespace Playbloom\Satisfy\Validators\Constraints;

use Symfony\Component\Validator\Constraint;

class ComposerLock extends Constraint
{
    /**
     * Returns the path to the composer.lock Schema file.
     *
     * @return string
     */
    public function getSchemaPath()
    {
        return resources_path('schemas/composer_lock.json');
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
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
