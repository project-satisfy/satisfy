<?php

namespace Playbloom\Satisfy\Form\Type;

use Playbloom\Satisfy\Model\PackageConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PackageConstraintType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'package',
                TextType::class,
                [
                    'required' => true,
                    'empty_data' => '',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'attr' => [
                        'placeholder' => 'Package name',
                    ],
                ]
            )
            ->add('constraint', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'placeholder' => 'Package version, i.e. ^1.0',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PackageConstraint::class,
            'empty_data' => new PackageConstraint('', ''),
        ]);
    }

    public function getBlockPrefix()
    {
        return 'PackageConstraintType';
    }
}
