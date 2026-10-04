<?php

namespace App\Security;

use App\Entity\OwnedByUser;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Only the user who owns a record may see or change it.
 *
 * @extends Voter<string, OwnedByUser>
 */
final class OwnerVoter extends Voter
{
    public const string OWNER = 'OWNER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::OWNER === $attribute && $subject instanceof OwnedByUser;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return $user instanceof User && $user->getId() === $subject->getOwner()->getId();
    }
}
