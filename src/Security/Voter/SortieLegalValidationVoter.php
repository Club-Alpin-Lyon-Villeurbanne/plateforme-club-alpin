<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieLegalValidationVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['SORTIE_LEGAL_VALIDATION'], true);
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

        $commission = $subject->getCommission();
        if (!$commission) {
            return false;
        }
        $hasRight = $this->userRights->allowedOnCommission('evt_legal_accept', $commission)
            || $this->userRights->allowedOnCommission('evt_legal_refuse', $commission);

        if ($hasRight && $subject->isLegalStatusUnseen() && $subject->isPublicStatusValide()) {
            return true;
        }

        return false;
    }
}
