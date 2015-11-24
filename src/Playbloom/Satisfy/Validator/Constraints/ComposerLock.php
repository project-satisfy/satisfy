<?php

namespace Playbloom\Satisfy\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @author Julius Beckmann <php@h4cc.de>
 */
class ComposerLock extends Constraint
{
    /**
     * Returns the path to the composer.lock Schema file.
     *
     * @return string
     */
    public function getSchemaPath()
    {
        return __DIR__ . '/../../Resources/schemas/composer_lock.json';
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
