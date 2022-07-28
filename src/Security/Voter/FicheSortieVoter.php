<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
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

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['FICHE_SORTIE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
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

        if ($this->userRights->allowedOnCommission('evt_print', $subject->getCommission())) {
            return true;
        }

        foreach ($subject->getEncadrants() as $evtJoin) {
            if ($evtJoin->getUser() === $user) {
                return true;
            }
        }

        return false;
    }
}
