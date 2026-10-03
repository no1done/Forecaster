<?php

namespace App\Form;

use App\Forecast\Recurrence\Frequency;
use App\Form\Data\RecurringTransactionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RecurringTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class)
            ->add('amountPence', SignedAmountType::class)
            ->add('frequency', EnumType::class, [
                'class' => Frequency::class,
                'choice_label' => static fn (Frequency $frequency) => $frequency->label(),
            ])
            ->add('interval', IntegerType::class, [
                'label' => 'Repeat every',
                'help' => 'Weeks, months or years. 2 with weekly is fortnightly.',
                'attr' => ['min' => 1],
            ])
            ->add('startDate', DateType::class, [
                'input' => 'datetime_immutable',
                'help' => 'Monthly and yearly payments repeat on this day. Weekends and bank holidays move to the working day before.',
            ])
            ->add('endDate', DateType::class, [
                'input' => 'datetime_immutable',
                'required' => false,
                'help' => 'Leave blank if it carries on.',
            ])
            ->add('hiddenFromForecast', CheckboxType::class, [
                'label' => 'Hide from the forecast',
                'help' => 'It still counts towards the balance, it just is not listed.',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Save']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecurringTransactionData::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'recurring';
    }
}
