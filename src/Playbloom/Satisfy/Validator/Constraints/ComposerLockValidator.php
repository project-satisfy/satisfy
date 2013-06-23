<?php

namespace Playbloom\Satisfy\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Composer.lock validator
 *
 * @author Julius Beckmann <php@h4cc.de>
 */
class ComposerLockValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($data, Constraint $constraint)
    {
        if (!is_object($data) || !$data instanceof UploadedFile) {
            throw new InvalidArgumentException(sprintf(
                'This validator expects a UploadedFile, given "%s"',
                get_class($data)
            ));
        }

        $composerData = json_decode(file_get_contents($data->openFile()->getRealPath()));
        $schema = $this->getSchema($constraint->getSchemaPath());

        // In version 1.1.0 of the validator, "required" attributes are not used.
        // So data structure might be partially unset.
        $validator = new \JsonSchema\Validator();
        $validator->check($composerData, $schema);

        if (!$validator->isValid()) {
            $this->context->addViolation("Invalid composer.lock file given:");
        }

        foreach ($validator->getErrors() as $error) {
            $this->context->addViolation(sprintf("[%s] %s\n", $error['property'], $error['message']));
        }
    }

    /**
     * Returns schema data for validation.
     *
     * @param $path
     * @return mixed
     */
    private function getSchema($path)
    {
        $schema_json = file_get_contents($path);
        return json_decode($schema_json);
    }

}
