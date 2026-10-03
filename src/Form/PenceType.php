<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Pounds and pence in the browser, integer pence in the model.
 */
final class PenceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'currency' => 'GBP',
            'divisor' => 100,
            'input' => 'integer',
            'html5' => true,
            'attr' => ['step' => '0.01'],
        ]);
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }
}
