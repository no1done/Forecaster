<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Hands every date field to the datepicker Stimulus controller, so dates show
 * as DD/MM/YYYY instead of in the browser's own locale.
 */
final class DatePickerExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['data-controller'] = trim(($view->vars['attr']['data-controller'] ?? '').' datepicker');
    }

    public static function getExtendedTypes(): iterable
    {
        return [DateType::class];
    }
}
