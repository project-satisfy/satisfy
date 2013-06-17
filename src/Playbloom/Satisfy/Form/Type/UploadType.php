<?php

namespace Playbloom\Satisfy\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repository form type
 *
 * @author Julius Beckmann <php@h4cc.de>
 */
class UploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'file',
                'file'
            );
    }

    public function getName()
    {
        return 'upload';
    }
}
