<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ForecastFilter
{
    public const string LONGEST_RANGE = '+5 years';

    public function __construct(
        public ?\DateTimeImmutable $from = null,

        #[Assert\GreaterThanOrEqual(propertyPath: 'from', message: 'The end date must be on or after the start date.')]
        public ?\DateTimeImmutable $to = null,

        #[Assert\Length(max: 255)]
        public ?string $search = null,
    ) {
    }

    #[Assert\Callback]
    public function validateRange(ExecutionContextInterface $context): void
    {
        if (null !== $this->from && null !== $this->to && $this->to > $this->from->modify(self::LONGEST_RANGE)) {
            $context->buildViolation('Pick a range of five years or less.')
                ->atPath('to')
                ->addViolation();
        }
    }
}
