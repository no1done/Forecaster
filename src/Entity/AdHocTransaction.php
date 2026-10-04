<?php

namespace App\Entity;

use App\Forecast\Movement;
use App\Repository\AdHocTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A one-off purchase or payment in.
 */
#[ORM\Entity(repositoryClass: AdHocTransactionRepository::class)]
#[ORM\Index(columns: ['owner_id', 'date'])]
class AdHocTransaction implements OwnedByUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $hiddenFromForecast = false;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $owner,

        #[ORM\Column(length: 255)]
        private string $description,

        /** Positive for money in, negative for money out. */
        #[ORM\Column]
        private int $amountPence,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $date,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
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

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function toMovement(): Movement
    {
        return new Movement($this->date, $this->amountPence, $this->description, adHocTransactionId: $this->id, hidden: $this->hiddenFromForecast);
    }
}
