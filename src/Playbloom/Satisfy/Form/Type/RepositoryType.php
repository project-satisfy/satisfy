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
                        new Assert\Choice(['choices' => $types]),
                    ],
                ]
            )
            ->add(
                'url',
                TextType::class,
                [
                    'required' => true,
                    'empty_data' => '',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'attr' => [
                        'placeholder' => 'Repository url',
                    ],
                ]
            )
            ->add(
                'installationSource',
                ChoiceType::class,
                [
                    'required' => false,
                    'placeholder' => false,
                    'choices' => [
                        'dist' => 'dist',
                        'source' => 'source',
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
