<?php

namespace App\Form;

use App\Form\Data\ForecastFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The date range and search box above the forecast, submitted as a plain
 * query string (?from=…&to=…&q=…) so a filtered view can be bookmarked.
 */
final class ForecastFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \DateTimeImmutable $earliest */
        $earliest = $options['earliest'];
        $notBeforeEarliest = new Assert\GreaterThanOrEqual(
            value: $earliest,
            message: \sprintf('The forecast starts from your first balance, on %s.', $earliest->format('j M Y')),
        );
        $dateOptions = [
            'input' => 'datetime_immutable',
            'required' => false,
            'attr' => ['min' => $earliest->format('Y-m-d')],
            'constraints' => [$notBeforeEarliest],
        ];

        $builder
            ->add('from', DateType::class, $dateOptions + [
                'empty_data' => $options['default_from']->format('Y-m-d'),
            ])
            ->add('to', DateType::class, $dateOptions + [
                // Twelve months from whichever start date was picked.
                'empty_data' => static function (FormInterface $to): string {
                    $from = $to->getParent()?->get('from')->getData();

                    return $from instanceof \DateTimeImmutable ? $from->modify('+1 year -1 day')->format('Y-m-d') : '';
                },
            ])
            ->add('search', SearchType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Description'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => ForecastFilter::class,
                'method' => 'GET',
                'csrf_protection' => false,
                'allow_extra_fields' => true,
            ])
            ->setRequired(['earliest', 'default_from'])
            ->setAllowedTypes('earliest', \DateTimeImmutable::class)
            ->setAllowedTypes('default_from', \DateTimeImmutable::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
