<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * "Money out / Money in" plus a positive amount, mapped to signed integer
 * pence (negative for money out).
 */
final class SignedAmountType extends AbstractType
{
    private const string OUT = 'out';
    private const string IN = 'in';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('direction', ChoiceType::class, [
                'label' => false,
                'expanded' => true,
                'choices' => ['Money out' => self::OUT, 'Money in' => self::IN],
            ])
            ->add('amount', PenceType::class, [
                'label' => 'Amount',
                'constraints' => [
                    new Assert\NotNull(message: 'Enter an amount above zero.'),
                    new Assert\Positive(message: 'Enter an amount above zero.'),
                ],
            ])
            ->addModelTransformer(new CallbackTransformer(
                static fn (?int $pence) => [
                    'direction' => null !== $pence && $pence > 0 ? self::IN : self::OUT,
                    'amount' => null === $pence ? null : abs($pence),
                ],
                static fn (array $value) => match (true) {
                    null === $value['amount'] => null,
                    self::IN === $value['direction'] => $value['amount'],
                    default => -$value['amount'],
                },
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'error_bubbling' => false,
        ]);
    }
}
