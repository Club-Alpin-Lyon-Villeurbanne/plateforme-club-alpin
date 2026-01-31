<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserLoginVoter extends Voter
{
    public const string LOGIN = 'USER_LOGIN';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::LOGIN === $attribute && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $subject;

        if ($user->isDeleted() || $user->isLocked()) {
            return false;
        }

        return true;
    }
}
