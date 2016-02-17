<?php

namespace Playbloom\Satisfy\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repository form type
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class RepositoryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $types = array(
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
        );
        $builder
            ->add(
                'type',
                'choice',
                array(
                    'choices' => array_combine($types, $types),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Choice(array('choices' => $types))
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
                        new Assert\NotBlank(),
                        new Assert\Regex(sprintf('#^%s$#', $options['pattern']))
                    ),
                    'attr' => array_merge(array(
                        'placeholder' => 'Repository url',
                        'class' => 'input-block-level'
                    ), $options['pattern']?array('pattern' => $options['pattern']):array())
                )
            );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Playbloom\\Satisfy\\Model\\Repository',
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
