<?php

namespace Playbloom\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DeleteFormType
 */
class DeleteFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete repository']);
    }
}
