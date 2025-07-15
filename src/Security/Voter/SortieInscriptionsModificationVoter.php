<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\SecurityConstants;
use App\UserRights;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieInscriptionsModificationVoter extends Voter
{
    public function __construct(
        private UserRights $userRights,
        private Security $security
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        if ('SORTIE_INSCRIPTIONS_MODIFICATION' !== $attribute) {
            return false;
        }

        if (!$subject instanceof Evt) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted(SecurityConstants::ROLE_ADMIN)) {
            return true;
        }

        if ($subject->getCancelled()) {
            return false;
        }

        if ($user === $subject->getUser()) {
            return true;
        }

        if ($user->hasAttribute(UserAttr::SALARIE)) {
            return true;
        }
        foreach ($subject->getEncadrants() as $eventParticipation) {
            if ($eventParticipation->getUser() === $user) {
                return true;
            }
        }
        if ($this->userRights->allowed('evt_join_doall')) {
            return true;
        }
        if (
            (
                $this->userRights->allowed('evt_join_notme')
                || $this->userRights->allowedOnCommission('evt_unjoin_notme', $subject->getCommission())
                || $this->userRights->allowedOnCommission('evt_joining_accept', $subject->getCommission())
                || $this->userRights->allowedOnCommission('evt_joining_refuse', $subject->getCommission())
            ) && $user->hasAttribute(UserAttr::RESPONSABLE_COMMISSION, $subject->getCommission())
        ) {
            return true;
        }

        return false;
    }
}
