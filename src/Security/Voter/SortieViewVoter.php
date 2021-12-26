<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieViewVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['SORTIE_VIEW'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an event subject', __CLASS__));
        }

        if (Evt::STATUS_PUBLISHED_VALIDE === $subject->getStatus()) {
            return true;
        }

        if ($this->userRights->allowed('evt_validate')) {
            return true;
        }

        if ($this->userRights->allowed('evt_validate_all')) {
            return true;
        }

        if ($subject->getUser() === $user) {
            return true;
        }

        return false;
    }
}
