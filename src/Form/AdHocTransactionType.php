<?php

namespace App\Form;

use App\Form\Data\AdHocTransactionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdHocTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class)
            ->add('amountPence', SignedAmountType::class)
            ->add('date', DateType::class, ['input' => 'datetime_immutable'])
            ->add('save', SubmitType::class, ['label' => 'Save']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdHocTransactionData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'ad_hoc';
    }
}
