<?php

namespace Playbloom\Satisfy\Form\Type;

use Playbloom\Satisfy\Model\Abandoned;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AbandonedType extends AbstractType
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
                        'placeholder' => 'Abandoned package name',
                    ],
                ]
            )
            ->add('replacement', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Package name/URL pointing to a recommended alternative(can be empty)',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Abandoned::class,
            'empty_data' => new Abandoned('', null),
        ]);
    }

    public function getBlockPrefix()
    {
        return 'AbandonedType';
    }
}
