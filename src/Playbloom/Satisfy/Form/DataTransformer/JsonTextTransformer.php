<?php

namespace Playbloom\Satisfy\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class JsonTextTransformer implements DataTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        if (empty($value)) {
            return '';
        }

        try {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TransformationFailedException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        $value = trim($value);
        if (empty($value)) {
            return null;
        }
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TransformationFailedException($exception->getMessage(), 0, $exception);
        }
        if (empty($decoded)) {
            return null;
        }

        return $decoded;
    }
}
