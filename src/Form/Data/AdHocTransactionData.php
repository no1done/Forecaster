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

    public bool $hiddenFromForecast = false;

    public static function fromEntity(AdHocTransaction $transaction): self
    {
        $data = new self();
        $data->description = $transaction->getDescription();
        $data->amountPence = $transaction->getAmountPence();
        $data->date = $transaction->getDate();
        $data->hiddenFromForecast = $transaction->isHiddenFromForecast();

        return $data;
    }

    public function toEntity(): AdHocTransaction
    {
        $transaction = new AdHocTransaction($this->description, $this->amountPence, $this->date);
        $transaction->setHiddenFromForecast($this->hiddenFromForecast);

        return $transaction;
    }

    public function applyTo(AdHocTransaction $transaction): void
    {
        $transaction->setDescription($this->description);
        $transaction->setAmountPence($this->amountPence);
        $transaction->setDate($this->date);
        $transaction->setHiddenFromForecast($this->hiddenFromForecast);
    }
}
