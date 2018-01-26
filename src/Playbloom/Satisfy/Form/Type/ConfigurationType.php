<?php

namespace Playbloom\Satisfy\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigurationType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('homepage', UrlType::class)
            ->add('requireAll', CheckboxType::class, [
                'required' => false,
            ])
            ->add('requireDependencies', CheckboxType::class, [
                'required' => false,
            ])
            ->add('requireDevDependencies', CheckboxType::class, [
                'required' => false,
            ])
            ->add('minimumStability', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'dev' => 'dev',
                    'alpha' => 'alpha',
                    'beta' => 'beta',
                    'RC' => 'RC',
                    'stable' => 'stable',
                ],
            ])
            ->add('outputDir', TextType::class, [
                'required' => false,
            ])
            ->add('twigTemplate', TextType::class, [
                'required' => false,
                'attr' => [
                    'help' => 'optional, a path to a personalized Twig template for the output-dir/index.html page',
                ],
            ])
            ->add('notifyBatch', UrlType::class, [
                'label' => 'Notify batch URL',
                'required' => false,
                'attr' => [
                    'help' => 'optional, specify a URL that will be called every time a user installs a package',
                ],
            ])
        ;
    }
}
