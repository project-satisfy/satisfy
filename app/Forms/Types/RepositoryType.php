<?php

namespace Playbloom\Satisfy\Forms\Types;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Regex;

class RepositoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'type',
                'choice',
                array(
                    'choices' => array(
                        'git' => 'git',
                        'vcs' => 'vcs',
                    ),
                    'constraints' => array(
                        new NotBlank(),
                        new Choice(['choices' => ['git', 'vcs']])
                    ),
                    'attr' => array(
                        'class' => 'input-block-level'
                    )
                )
            )
            ->add(
                'url',
                'text',
                array(
                    'constraints' => array(
                        new NotBlank(),
                        new Regex(sprintf('#^%s$#', $options['pattern']))
                    ),
                    'attr' => array_merge(array(
                        'placeholder' => 'Github repository url',
                        'class' => 'input-block-level'
                    ), $options['pattern'] ? ['pattern' => $options['pattern']] : [])
                )
            );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Playbloom\\Satisfy\\Models\\Repository',
            'pattern' => null
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'repository';
    }
}
