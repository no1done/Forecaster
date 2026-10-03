<?php

namespace App\Entity;

use App\Forecast\Checkpoint;
use App\Repository\BalanceCheckpointRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The real account balance at the end of a day. The earliest one is the
 * starting balance.
 */
#[ORM\Entity(repositoryClass: BalanceCheckpointRepository::class)]
class BalanceCheckpoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(type: Types::DATE_IMMUTABLE, unique: true)]
        private \DateTimeImmutable $date,

        #[ORM\Column]
        private int $balancePence,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getBalancePence(): int
    {
        return $this->balancePence;
    }

    public function setBalancePence(int $balancePence): void
    {
        $this->balancePence = $balancePence;
    }

    public function toCheckpoint(): Checkpoint
    {
        return new Checkpoint($this->date, $this->balancePence);
    }
}
