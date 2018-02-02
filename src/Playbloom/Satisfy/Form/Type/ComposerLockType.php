<?php

namespace Playbloom\Satisfy\Form\Type;

use Playbloom\Satisfy\Validator\Constraints\ComposerLock;
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
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'file',
                FileType::class,
                array(
                    'label' => false,
                    'constraints' => array(
                        new ComposerLock(),
                    ),
                )
            );
    }
}
