<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class HelloAssoMireVoter extends Voter
{
    public function __construct(protected UserRights $userRights)
    {
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['HELLO_ASSO_MIRE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /* @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->userRights->allowed('ha_mire_autorisation');
    }
}
