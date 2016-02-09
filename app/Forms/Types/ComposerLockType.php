<?php

namespace Playbloom\Satisfy\Forms\Types;

use Playbloom\Satisfy\Validators\Constraints\ComposerLock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

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
                'file',
                array(
                    'constraints' => array(
                        new ComposerLock(),
                    ),
                    'attr' => array(
                        'class' => 'input-block-level',
                    )
                )
            );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'upload';
    }
}
