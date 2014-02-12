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
        $attr =
        $builder
            ->add(
                'type',
                'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Choice(array('choices' => array('git', 'vcs')))
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
                        'placeholder' => 'Github repository url',
                        'class' => 'input-block-level'
                    ), $options['pattern']?array('pattern' => $options['pattern']):array())
                )
            );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Playbloom\\Satisfy\\Model\\Repository',
            'pattern' => null
            ,
        ));
    }

    public function getName()
    {
        return 'repository';
    }
}
