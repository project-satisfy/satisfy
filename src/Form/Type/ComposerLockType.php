<?php

namespace Playbloom\Form\Type;

use Playbloom\Validator\Constraints\ComposerLock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Composer.lock upload form type.
 *
 * @author Julius Beckmann <php@h4cc.de>
 */
class ComposerLockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'file',
                FileType::class,
                [
                    'label' => false,
                    'constraints' => [
                        new ComposerLock(),
                    ],
                ]
            );
    }
}
