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
     */
    public function getSchemaPath(): string
    {
        return __DIR__ . '/../../Resources/schemas/composer_lock.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): string
    {
        return '';
    }
}
