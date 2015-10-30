<?php

namespace MyLittle\MylittlePackages\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Playbloom\Satisfy\Model\RepositoryInterface;

/**
 * Repository validator
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class RepositoryValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($data, Constraint $constraint)
    {
        if (!is_object($data) || !$data instanceof RepositoryInterface) {
            throw new InvalidArgumentException(sprintf('The class "%s" must implement Playbloom\Satisfy\Model\RepositoryInterface', get_class($data)));
        }

        if ('git' === $data->getType() &&
            !(bool) preg_match('#^git@github.com:MyLittleParis/[a-zA-Z0-9-_]+.git$#', $data->getUrl())) {
            $this->context->addViolation(sprintf('Invalid url "%s" for a "%s" repository .', $data->getUrl(), $data->getType()));
        }
    }
}
