<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\AdminDetector;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieInscriptionsModificationVoter extends Voter
{
    private UserRights $userRights;
    private AdminDetector $adminDetector;

    public function __construct(UserRights $userRights, AdminDetector $adminDetector)
    {
        $this->userRights = $userRights;
        $this->adminDetector = $adminDetector;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['SORTIE_INSCRIPTIONS_MODIFICATION'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException('SORTIE_INSCRIPTIONS_MODIFICATION requires an event subject');
        }

        if ($subject->getCancelled()) {
            return false;
        }

        if ($user === $subject->getUser()) {
            return true;
        }
        if ($this->adminDetector->isAdmin()) {
            return true;
        }
        if ($user->hasAttribute(UserAttr::SALARIE)) {
            return true;
        }
        foreach ($subject->getEncadrants() as $evtJoin) {
            if ($evtJoin->getUser() === $user) {
                return true;
            }
        }
        if ($this->userRights->allowed('evt_join_doall')) {
            return true;
        }
        if (
            (
                $this->userRights->allowed('evt_join_notme') ||
                $this->userRights->allowed('evt_unjoin_notme') ||
                $this->userRights->allowed('evt_joining_accept') ||
                $this->userRights->allowed('evt_joining_refuse')
            ) && $user->hasAttribute(UserAttr::RESPONSABLE_COMMISSION, $subject->getCommission())
        ) {
            return true;
        }

        return false;
    }
}
