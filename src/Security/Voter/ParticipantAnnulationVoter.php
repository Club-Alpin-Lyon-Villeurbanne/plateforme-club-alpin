<?php

namespace App\Security\Voter;

use App\Entity\EvtJoin;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ParticipantAnnulationVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['PARTICIPANT_ANNULATION'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof EvtJoin) {
            throw new \InvalidArgumentException('The voter requires a participant subject');
        }

        if ($subject->getUser() !== $user) {
            return false;
        }

        return $this->userRights->allowed('evt_unjoin');
    }
}
