<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One occurrence's date and the size of its amount; the amount keeps the
 * recurring transaction's direction.
 */
final class OccurrenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amountPence', PenceType::class, [
                'label' => 'Amount this time',
                'help' => 'Enter 0 to skip this one.',
                'constraints' => [
                    new Assert\NotNull(message: 'Enter an amount, or 0 to skip this one.'),
                    new Assert\PositiveOrZero(message: 'Enter an amount, or 0 to skip this one.'),
                ],
            ])
            ->add('date', DateType::class, [
                'input' => 'datetime_immutable',
                'label' => 'Date this time',
                'help' => 'If it is not going out or coming in on its usual day.',
                'constraints' => [new Assert\NotNull(message: 'Enter the date.')],
            ])
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->add('reset', SubmitType::class, ['label' => 'Use usual amount and date', 'validate' => false]);
    }

    public function getBlockPrefix(): string
    {
        return 'occurrence';
    }
}
