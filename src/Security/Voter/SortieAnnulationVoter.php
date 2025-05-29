<?php

namespace App\Security\Voter;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
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

        if ($subject->getUser() !== $user
            && !$this->userRights->allowed('evt_cancel_any')
            && !$this->userRights->allowedOnCommission('evt_cancel', $subject->getCommission())
        ) {
            return false;
        }

        if ($subject->isFinished()) {
            return false;
        }

        $amIEncadrant = false;
        foreach ($subject->getEncadrants([EventParticipation::ROLE_ENCADRANT]) as $eventParticipation) {
            if ($eventParticipation->getUser() === $user) {
                $amIEncadrant = true;
            }
        }

        $amIResponsable = false;
        foreach ($user->getAttributes() as $attribute) {
            if (UserAttr::RESPONSABLE_COMMISSION === $attribute->getUserType()->getCode()) {
                $amIResponsable = true;
            }
        }

        if (($amIEncadrant || $amIResponsable) && $this->userRights->allowedOnCommission('evt_cancel', $subject->getCommission())) {
            return true;
        }

        return false;
    }
}
