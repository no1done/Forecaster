<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The size of one occurrence's amount; it keeps the recurring transaction's direction.
 */
final class OccurrenceAmountType extends AbstractType
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
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->add('reset', SubmitType::class, ['label' => 'Use usual amount', 'validate' => false]);
    }

    public function getBlockPrefix(): string
    {
        return 'occurrence';
    }
}
