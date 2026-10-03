<?php

namespace App\Form\Data;

use App\Entity\RecurringTransaction;
use App\Forecast\Recurrence\Frequency;
use Symfony\Component\Validator\Constraints as Assert;

final class RecurringTransactionData
{
    #[Assert\NotBlank(message: 'Enter a description.')]
    #[Assert\Length(max: 255)]
    public ?string $description = null;

    /** Validated by the amount field itself. */
    public ?int $amountPence = null;

    #[Assert\NotNull]
    public ?Frequency $frequency = Frequency::Monthly;

    #[Assert\NotNull(message: 'Repeat every must be at least 1.')]
    #[Assert\Positive(message: 'Repeat every must be at least 1.')]
    public ?int $interval = 1;

    #[Assert\NotNull(message: 'Enter the start date.')]
    public ?\DateTimeImmutable $startDate = null;

    #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'The end date must be on or after the start date.')]
    public ?\DateTimeImmutable $endDate = null;

    public bool $hiddenFromForecast = false;

    public static function fromEntity(RecurringTransaction $recurring): self
    {
        $data = new self();
        $data->description = $recurring->getDescription();
        $data->amountPence = $recurring->getAmountPence();
        $data->frequency = $recurring->getFrequency();
        $data->interval = $recurring->getInterval();
        $data->startDate = $recurring->getStartDate();
        $data->endDate = $recurring->getEndDate();
        $data->hiddenFromForecast = $recurring->isHiddenFromForecast();

        return $data;
    }

    public function toEntity(): RecurringTransaction
    {
        $recurring = new RecurringTransaction($this->description, $this->amountPence, $this->frequency, $this->startDate, $this->interval, $this->endDate);
        $recurring->setHiddenFromForecast($this->hiddenFromForecast);

        return $recurring;
    }

    public function applyTo(RecurringTransaction $recurring): void
    {
        $recurring->setDescription($this->description);
        $recurring->setAmountPence($this->amountPence);
        $recurring->setFrequency($this->frequency);
        $recurring->setInterval($this->interval);
        $recurring->setStartDate($this->startDate);
        $recurring->setEndDate($this->endDate);
        $recurring->setHiddenFromForecast($this->hiddenFromForecast);
    }
}
