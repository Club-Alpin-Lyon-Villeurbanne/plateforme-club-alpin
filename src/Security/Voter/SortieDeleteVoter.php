<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieDeleteVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['SORTIE_DELETE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an event subject', __CLASS__));
        }

        if (!$subject->isDraft()) {
            return false;
        }

        if ($subject->isPublicStatusValide()) {
            return false;
        }

        if ($subject->getExpenseReports()->count() > 0) {
            return false;
        }

        if ($user === $subject->getUser()) {
            return true;
        }

        if ($this->userRights->allowedOnCommission('evt_delete', $subject->getCommission())) {
            return true;
        }

        return false;
    }
}
