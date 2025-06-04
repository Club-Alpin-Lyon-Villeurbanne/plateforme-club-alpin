<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FicheSortieVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['FICHE_SORTIE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /* @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException('FICHE_SORTIE requires an event subject');
        }

        if ($subject->getCancelled()) {
            return false;
        }

        foreach ($user->getAttributes() as $attribute) {
            if (UserAttr::SALARIE === $attribute->getUserType()->getCode()) {
                return true;
            }
        }

        $amIEncadrant = false;
        foreach ($subject->getEncadrants() as $eventParticipation) {
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

        if (($amIEncadrant || $amIResponsable) && $this->userRights->allowedOnCommission('evt_print', $subject->getCommission())) {
            return true;
        }

        return false;
    }
}
