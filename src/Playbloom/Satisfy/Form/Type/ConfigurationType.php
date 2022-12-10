<?php

namespace Playbloom\Satisfy\Form\Type;

use Playbloom\Satisfy\Form\DataTransformer\JsonTextTransformer;
use Playbloom\Satisfy\Model\Abandoned;
use Playbloom\Satisfy\Model\Configuration;
use Playbloom\Satisfy\Model\PackageStability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex('#[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9]([_.-]?[a-z0-9]+)*#'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('homepage', UrlType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('require', CollectionType::class, [
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'entry_type' => PackageConstraintType::class,
                'prototype' => true,
                'attr' => [
                    'class' => 'collection_require',
                ],
                'constraints' => [
                    new Assert\Valid(),
                ],
            ])
            ->add('blacklist', CollectionType::class, [
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'entry_type' => PackageConstraintType::class,
                'prototype' => true,
                'attr' => [
                    'class' => 'collection_require',
                    'rel' => 'tooltip',
                    'data-title' => 'Package name (keys) and version constraints (values) to exclude after selecting packages',
                ],
                'constraints' => [
                    new Assert\Valid(),
                ],
            ])
            ->add('abandoned', CollectionType::class, [
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'entry_type' => AbandonedType::class,
                'prototype' => true,
                'attr' => [
                    'class' => 'collection_require',
                    'rel' => 'tooltip',
                    'data-title' => 'List of packages marked as abandoned for this repository',
                ],
                'constraints' => [
                    new Assert\Valid(),
                ],
            ])
            ->add('requireAll', Type\CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'selects all versions of all packages in the repositories you defined',
                ],
            ])
            ->add('requireDependencies', Type\CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => <<<END
satis will attempt to resolve all the required packages from the listed repositories
END
                ],
            ])
            ->add('requireDevDependencies', Type\CheckboxType::class, [
                'required' => false,
            ])
            ->add('requireDependencyFilter', Type\CheckboxType::class, [
                'required' => false,
            ])
            ->add('minimumStability', ChoiceType::class, [
                'required' => false,
                'choices' => PackageStabilityType::STABILITY_LEVELS,
                'constraints' => [
                    new Assert\Choice(['choices' => PackageStabilityType::STABILITY_LEVELS]),
                ],
            ])
            ->add('minimumStabilityPerPackage', CollectionType::class, [
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'entry_type' => PackageStabilityType::class,
                'prototype' => true,
                'attr' => [
                    'class' => 'collection_require',
                ],
                'constraints' => [
                    new Assert\Valid(),
                ],
            ])
            ->add('includeFilename', TextType::class, [
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => <<<END
Specify filename instead of default include/all\${SHA1_HASH}.json
END
                ],
            ])
            ->add('outputDir', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'empty_data' => Configuration::DEFAULT_OUTPUT_DIR,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => <<<END
defines where to output the repository files if not provided as an argument when calling the build command
END
                ],
            ])
            ->add('outputHtml', Type\CheckboxType::class, [
                'required' => false,
                'label' => 'Output HTML',
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'If enabled, build a static web page',
                ],
            ])
            ->add('providers', Type\CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'If enabled, dump package providers',
                ],
            ])
            ->add('config', TextareaType::class, [
                'required' => false,
                'empty_data' => '',
                'trim' => true,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'a configuration options in json format',
                ],
            ])
            ->add('twigTemplate', TextType::class, [
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'a path to a personalized Twig template for the output-dir/index.html page',
                ],
            ])
            ->add('notifyBatch', UrlType::class, [
                'label' => 'Notify batch URL',
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'specify a URL that will be called every time a user installs a package',
                ],
            ])
            ->add('prettyPrint', Type\CheckboxType::class, [
                'label' => 'Pretty print',
                'required' => false,
                'attr' => [
                    'rel' => 'tooltip',
                    'data-title' => 'when not checked, the JSON_PRETTY_PRINT option will not be used on encoding.',
                ],
            ]);
        $builder->get('config')->addModelTransformer(new JsonTextTransformer());
    }
}
