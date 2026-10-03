<?php

namespace App\Entity;

use App\Forecast\Recurrence\Frequency;
use App\Forecast\Recurrence\RecurrenceRule;
use App\Repository\RecurringTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecurringTransactionRepository::class)]
class RecurringTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private string $description,

        /** Positive for money in, negative for money out. */
        #[ORM\Column]
        #[Assert\NotEqualTo(0)]
        private int $amountPence,

        #[ORM\Column(length: 32, enumType: Frequency::class)]
        private Frequency $frequency,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $startDate,

        /** Every N weeks/months/years. */
        #[ORM\Column(name: 'repeat_interval')]
        #[Assert\Positive]
        private int $interval = 1,

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
        #[Assert\GreaterThanOrEqual(propertyPath: 'startDate')]
        private ?\DateTimeImmutable $endDate = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAmountPence(): int
    {
        return $this->amountPence;
    }

    public function setAmountPence(int $amountPence): void
    {
        $this->amountPence = $amountPence;
    }

    public function getFrequency(): Frequency
    {
        return $this->frequency;
    }

    public function setFrequency(Frequency $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getRule(): RecurrenceRule
    {
        return new RecurrenceRule($this->frequency, $this->startDate, $this->interval, $this->endDate);
    }
}
