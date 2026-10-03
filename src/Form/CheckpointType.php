<?php

namespace App\Form;

use App\Form\Data\CheckpointData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CheckpointType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'input' => 'datetime_immutable',
                'help' => 'Your actual balance at the end of this day.',
            ])
            ->add('balancePence', PenceType::class, ['label' => 'Balance'])
            ->add('save', SubmitType::class, ['label' => 'Record balance']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckpointData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'checkpoint';
    }
}
