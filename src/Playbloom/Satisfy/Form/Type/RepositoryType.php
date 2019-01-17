<?php

namespace Playbloom\Satisfy\Form\Type;

use Playbloom\Satisfy\Model\Repository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repository form type
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class RepositoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $types = [
            'artifact',
            'composer',
            'git',
            'github',
            'gitlab',
            'git-bitbucket',
            'hg',
            'hg-bitbucket',
            'package',
            'path',
            'pear',
            'perforce',
            'svn',
            'vcs',
        ];

        $package_types = [
            'zip',
            'svn',
            'vcs',
        ];

        $builder
            ->add(
                'type',
                ChoiceType::class,
                [
                    'required' => true,
                    'empty_data' => '',
                    'choices' => array_combine($types, $types),
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]
            )
            ->add(
                'url',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => 'Repository url',
                    ],
                ]
            )
            ->add(
                'packageName',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => '',
                    ],
                ]
            )
            ->add(
                'packageVersion',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => '',
                    ],
                ]
            )
            ->add(
                'packageType',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => 'Package type, e.g. library',
                    ],
                ]
            )
            ->add(
                'packageLicense',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => 'Package license, e.g. MIT, LGPL-2.1-only or GPL-3.0-or-later',
                    ],
                ]
            )
            ->add(
                'packageDistType',
                ChoiceType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'placeholder' => '',
                    'choices' => array_combine($package_types, $package_types),
                ]
            )
            ->add(
                'packageDistUrl',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => '',
                    ],
                ]
            )
            ->add(
                'packageDistSha1Checksum',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'attr' => [
                        'placeholder' => '',
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Repository::class,
            ]
        );
    }
}
