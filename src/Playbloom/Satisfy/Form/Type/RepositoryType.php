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
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class RepositoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $urlPattern = 'git@github.com:[a-zA-Z0-9-_]+/[a-zA-Z0-9-_]+.git';

        $builder
            ->add(
                'type',
                'text',
                array(
                    'disabled' => 'disabled',
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Choice(array('choices' => array('git')))
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
                        new Assert\Regex(sprintf('#^%s$#', $urlPattern))
                    ),
                    'attr' => array(
                        'pattern' => $urlPattern,
                        'title' => 'Github repository: git@github.com:YourName/your-github-repository.git',
                        'placeholder' => 'Github repository url',
                        'class' => 'input-block-level'
                    )
                )
            )
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Playbloom\\Satisfy\\Model\\Repository',
        ));
    }

    public function getName()
    {
        return 'repository';
    }
}
