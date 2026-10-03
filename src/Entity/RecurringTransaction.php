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

    /** @var Collection<int, RecurringAmountOverride> */
    #[ORM\OneToMany(targetEntity: RecurringAmountOverride::class, mappedBy: 'recurringTransaction', cascade: ['persist'], orphanRemoval: true)]
    private Collection $amountOverrides;

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
        $this->amountOverrides = new ArrayCollection();
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

    /**
     * @return Collection<int, RecurringAmountOverride>
     */
    public function getAmountOverrides(): Collection
    {
        return $this->amountOverrides;
    }

    /**
     * The amount for one occurrence, identified by the date it was scheduled for.
     */
    public function getAmountPenceFor(\DateTimeImmutable $scheduledDate): int
    {
        return $this->findOverride($scheduledDate)?->getAmountPence() ?? $this->amountPence;
    }

    /**
     * Change the amount of a single occurrence. Zero skips it; the usual
     * amount removes the one-off.
     */
    public function overrideAmount(\DateTimeImmutable $scheduledDate, int $amountPence): void
    {
        if (!$this->getRule()->isScheduledOn($scheduledDate)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not scheduled on %s.', $this->description, $scheduledDate->format('Y-m-d')));
        }

        $override = $this->findOverride($scheduledDate);
        if ($amountPence === $this->amountPence) {
            if (null !== $override) {
                $this->amountOverrides->removeElement($override);
            }

            return;
        }

        if (null !== $override) {
            $override->setAmountPence($amountPence);
        } else {
            $this->amountOverrides->add(new RecurringAmountOverride($this, $scheduledDate, $amountPence));
        }
    }

    private function findOverride(\DateTimeImmutable $scheduledDate): ?RecurringAmountOverride
    {
        $key = $scheduledDate->format('Y-m-d');

        return $this->amountOverrides->findFirst(
            static fn (int $i, RecurringAmountOverride $o) => $o->getScheduledDate()->format('Y-m-d') === $key,
        );
    }
}
