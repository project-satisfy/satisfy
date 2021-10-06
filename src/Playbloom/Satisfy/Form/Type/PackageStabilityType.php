<?php

namespace Playbloom\Satisfy\Form\Type;

use Playbloom\Satisfy\Model\PackageStability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PackageStabilityType extends AbstractType
{
    public const STABILITY_LEVELS = [
        'dev' => 'dev',
        'alpha' => 'alpha',
        'beta' => 'beta',
        'RC' => 'RC',
        'stable' => 'stable',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('package', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'placeholder' => 'Package name',
                ],
            ])
            ->add('stability', ChoiceType::class, [
                'required' => true,
                'choices' => self::STABILITY_LEVELS,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(['choices' => self::STABILITY_LEVELS]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PackageStability::class,
            'empty_data' => new PackageStability('', ''),
        ]);
    }

    public function getBlockPrefix()
    {
        return 'PackageStabilityType';
    }
}
