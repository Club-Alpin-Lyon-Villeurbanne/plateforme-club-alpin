<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieValidateVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['SORTIE_VALIDATE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException('The voter requires an event subject');
        }

        if (!$subject->getCancelled() && $subject->isPublicStatusValide()) {
            return false;
        }

        if ($this->userRights->allowed('evt_validate_all') || $this->userRights->allowedOnCommission('evt_validate', $subject->getCommission())) {
            return true;
        }

        return false;
    }
}
