<?php

namespace App\Entity;

use App\Forecast\Recurrence\Frequency;
use App\Forecast\Recurrence\RecurrenceRule;
use App\Repository\RecurringTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecurringTransactionRepository::class)]
class RecurringTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $hiddenFromForecast = false;

    /** @var Collection<int, RecurringOccurrenceOverride> */
    #[ORM\OneToMany(targetEntity: RecurringOccurrenceOverride::class, mappedBy: 'recurringTransaction', cascade: ['persist'], orphanRemoval: true)]
    private Collection $occurrenceOverrides;

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $description,

        /** Positive for money in, negative for money out. */
        #[ORM\Column]
        private int $amountPence,

        #[ORM\Column(length: 32, enumType: Frequency::class)]
        private Frequency $frequency,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $startDate,

        /** Every N weeks/months/years. */
        #[ORM\Column(name: 'repeat_interval')]
        private int $interval = 1,

        #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
        private ?\DateTimeImmutable $endDate = null,
    ) {
        $this->occurrenceOverrides = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Hidden transactions still count towards the balance but are not listed on the forecast.
     */
    public function isHiddenFromForecast(): bool
    {
        return $this->hiddenFromForecast;
    }

    public function setHiddenFromForecast(bool $hiddenFromForecast): void
    {
        $this->hiddenFromForecast = $hiddenFromForecast;
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

    /**
     * @return Collection<int, RecurringOccurrenceOverride>
     */
    public function getOccurrenceOverrides(): Collection
    {
        return $this->occurrenceOverrides;
    }

    /**
     * The amount for the occurrence the rule schedules on $scheduledDate.
     */
    public function getAmountPenceFor(\DateTimeImmutable $scheduledDate): int
    {
        return $this->findOverride($scheduledDate)?->getAmountPence() ?? $this->amountPence;
    }

    /**
     * The date the occurrence scheduled on $scheduledDate was moved to, if it was.
     */
    public function getMovedDateFor(\DateTimeImmutable $scheduledDate): ?\DateTimeImmutable
    {
        return $this->findOverride($scheduledDate)?->getDate();
    }

    /**
     * Occurrences moved to another date, keyed by scheduled date (Y-m-d).
     *
     * @return array<string, \DateTimeImmutable>
     */
    public function getMovedDates(): array
    {
        $moved = [];
        foreach ($this->occurrenceOverrides as $override) {
            if (null !== $override->getDate()) {
                $moved[$override->getScheduledDate()->format('Y-m-d')] = $override->getDate();
            }
        }

        return $moved;
    }

    /**
     * Changes the amount of one occurrence; the usual amount clears the change.
     */
    public function overrideAmount(\DateTimeImmutable $scheduledDate, int $amountPence): void
    {
        $oneOff = $amountPence === $this->amountPence ? null : $amountPence;
        $this->changeOccurrence($scheduledDate, static fn (RecurringOccurrenceOverride $o) => $o->setAmountPence($oneOff));
    }

    /**
     * Moves one occurrence to another date; null puts it back on its usual date.
     */
    public function moveOccurrence(\DateTimeImmutable $scheduledDate, ?\DateTimeImmutable $date): void
    {
        $this->changeOccurrence($scheduledDate, static fn (RecurringOccurrenceOverride $o) => $o->setDate($date));
    }

    /**
     * @param \Closure(RecurringOccurrenceOverride): void $change
     */
    private function changeOccurrence(\DateTimeImmutable $scheduledDate, \Closure $change): void
    {
        if (!$this->getRule()->isScheduledOn($scheduledDate)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not scheduled on %s.', $this->description, $scheduledDate->format('Y-m-d')));
        }

        $override = $this->findOverride($scheduledDate) ?? new RecurringOccurrenceOverride($this, $scheduledDate);
        $change($override);

        if ($override->isEmpty()) {
            $this->occurrenceOverrides->removeElement($override);
        } elseif (!$this->occurrenceOverrides->contains($override)) {
            $this->occurrenceOverrides->add($override);
        }
    }

    private function findOverride(\DateTimeImmutable $scheduledDate): ?RecurringOccurrenceOverride
    {
        $key = $scheduledDate->format('Y-m-d');

        return $this->occurrenceOverrides->findFirst(
            static fn (int $i, RecurringOccurrenceOverride $o) => $o->getScheduledDate()->format('Y-m-d') === $key,
        );
    }
}
