<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckpointData
{
    public function __construct(
        #[Assert\NotNull(message: 'Enter the date.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\NotNull(message: 'Enter the balance.')]
        public ?int $balancePence = null,
    ) {
    }
}
