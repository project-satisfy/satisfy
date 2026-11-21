<?php

namespace Playbloom\Form\Type;

use Playbloom\Form\DataTransformer\JsonTextTransformer;
use Playbloom\Model\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'help' => 'The repository\'s name, in vendor/name form. The catalogue page shows it.',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex('#[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9]([_.-]?[a-z0-9]+)*#'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'help' => 'Shown on the catalogue page under the name.',
                'required' => false,
            ])
            ->add('homepage', UrlType::class, [
                'help' => 'The URL this repository is served from. Composer reads packages from here, and the home page\'s composer.json snippet uses it.',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('require', CollectionType::class, [
                'help' => 'Packages to include, each with the versions to include. Leave empty and turn on require-all to include everything.',
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
                'help' => 'Packages and versions to leave out after the packages above are selected.',
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
            ->add('abandoned', CollectionType::class, [
                'help' => 'Packages to mark as abandoned, each with the package to use instead, if there is one.',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'entry_type' => AbandonedType::class,
                'prototype' => true,
                'attr' => [
                    'class' => 'collection_require',
                ],
                'constraints' => [
                    new Assert\Valid(),
                ],
            ])
            ->add('requireAll', Type\CheckboxType::class, [
                'help' => 'Include every version of every package in the repositories.',
                'required' => false,
            ])
            ->add('requireDependencies', Type\CheckboxType::class, [
                'help' => 'Also include the packages the selected packages require, resolved from the listed repositories.',
                'required' => false,
            ])
            ->add('requireDevDependencies', Type\CheckboxType::class, [
                'help' => 'With require-dependencies, also include their require-dev packages.',
                'required' => false,
            ])
            ->add('requireDependencyFilter', Type\CheckboxType::class, [
                'help' => 'When off, every version of a dependency is included, not only the versions its constraints allow.',
                'required' => false,
            ])
            ->add('minimumStability', ChoiceType::class, [
                'help' => 'The least stable versions to include.',
                'required' => false,
                'choices' => PackageStabilityType::STABILITY_LEVELS,
                'constraints' => [
                    new Assert\Choice(['choices' => PackageStabilityType::STABILITY_LEVELS]),
                ],
            ])
            ->add('minimumStabilityPerPackage', CollectionType::class, [
                'help' => 'Overrides minimum-stability for single packages.',
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
                'help' => 'File name for the package list, instead of include/all$<sha1>.json.',
                'required' => false,
            ])
            ->add('outputDir', TextType::class, [
                'help' => 'Where the build writes packages.json and the catalogue, unless the build command is given another directory.',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'empty_data' => Configuration::DEFAULT_OUTPUT_DIR,
            ])
            ->add('outputHtml', Type\CheckboxType::class, [
                'help' => 'Build a static HTML catalogue page beside packages.json.',
                'required' => false,
                'label' => 'Output HTML',
            ])
            ->add('providers', Type\CheckboxType::class, [
                'help' => 'Write provider files, the Composer 1 metadata format.',
                'required' => false,
            ])
            ->add('config', TextareaType::class, [
                'help' => 'Composer configuration to apply during the build, as JSON.',
                'required' => false,
                'empty_data' => '',
                'trim' => true,
            ])
            ->add('twigTemplate', TextType::class, [
                'help' => 'Path to a Twig template for the catalogue\'s index.html.',
                'required' => false,
            ])
            ->add('notifyBatch', UrlType::class, [
                'help' => 'A URL Composer calls every time a package is installed from this repository.',
                'label' => 'Notify batch URL',
                'required' => false,
            ])
            ->add('prettyPrint', Type\CheckboxType::class, [
                'help' => 'Indent the JSON the build writes. Turn off for smaller files.',
                'label' => 'Pretty print',
                'required' => false,
            ]);
        $builder->get('config')->addModelTransformer(new JsonTextTransformer());
    }
}
