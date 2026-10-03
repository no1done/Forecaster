<?php

namespace App\Form\Data;

use App\Entity\AdHocTransaction;
use Symfony\Component\Validator\Constraints as Assert;

final class AdHocTransactionData
{
    #[Assert\NotBlank(message: 'Enter a description.')]
    #[Assert\Length(max: 255)]
    public ?string $description = null;

    /** Validated by the amount field itself. */
    public ?int $amountPence = null;

    #[Assert\NotNull(message: 'Enter the date.')]
    public ?\DateTimeImmutable $date = null;

    public static function fromEntity(AdHocTransaction $transaction): self
    {
        $data = new self();
        $data->description = $transaction->getDescription();
        $data->amountPence = $transaction->getAmountPence();
        $data->date = $transaction->getDate();

        return $data;
    }

    public function toEntity(): AdHocTransaction
    {
        return new AdHocTransaction($this->description, $this->amountPence, $this->date);
    }

    public function applyTo(AdHocTransaction $transaction): void
    {
        $transaction->setDescription($this->description);
        $transaction->setAmountPence($this->amountPence);
        $transaction->setDate($this->date);
    }
}
