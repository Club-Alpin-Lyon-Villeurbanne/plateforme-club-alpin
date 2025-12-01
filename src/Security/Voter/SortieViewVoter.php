<?php

namespace App\Security\Voter;

use App\Entity\Evt;
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

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['SORTIE_VIEW'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an event subject', __CLASS__));
        }

        if (Evt::STATUS_PUBLISHED_VALIDE === $subject->getStatus()) {
            return true;
        }

        if ($this->userRights->allowed('evt_validate', 'commission:' . $subject->getCommission()->getCode())) {
            return true;
        }

        if ($this->userRights->allowed('evt_validate_all')) {
            return true;
        }

        $user = $token->getUser();

        if ($user && $subject->getUser() === $user) {
            return true;
        }

        return false;
    }
}
