<?php

namespace App\Security\Voter;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieAnnulationVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['SORTIE_CANCEL', 'SORTIE_UNCANCEL'], true);
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

        if ('SORTIE_UNCANCEL' === $attribute && !$subject->getCancelled()) {
            return false;
        }

        if ('SORTIE_CANCEL' === $attribute && ($subject->getCancelled() || !$subject->isPublicStatusValide())) {
            return false;
        }

        if ($subject->isFinished()) {
            return false;
        }

        $isCurrentUserEncadrant = false;
        foreach ($subject->getEncadrants(EventParticipation::ROLES_ENCADREMENT_ETENDU) as $eventParticipation) {
            if ($eventParticipation->getUser() === $user) {
                $isCurrentUserEncadrant = true;
                break;
            }
        }

        return ($subject->getUser() === $user
            || $isCurrentUserEncadrant) && $this->userRights->allowed('evt_cancel_own')
            || $this->userRights->allowedOnCommission('evt_cancel', $subject->getCommission())
            || $this->userRights->allowed('evt_cancel_any')
        ;
    }
}
