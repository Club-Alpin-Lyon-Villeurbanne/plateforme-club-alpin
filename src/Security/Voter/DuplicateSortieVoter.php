<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DuplicateSortieVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['SORTIE_DUPLICATE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException('SORTIE_DUPLICATE requires an event subject');
        }

        if (!$this->userRights->allowedOnCommission('evt_create', $subject->getCommission())) {
            return false;
        }

        foreach ($subject->getParticipants() as $evtJoin) {
            if ($evtJoin->getUser() === $user) {
                return true;
            }
        }

        return false;
    }
}
