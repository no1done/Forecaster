<?php

namespace App\Entity;

/**
 * Data that belongs to one user and is hidden from everyone else.
 */
interface OwnedByUser
{
    public function getOwner(): User;
}
