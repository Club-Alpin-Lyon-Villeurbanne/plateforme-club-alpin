<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserJoinSortieVoter extends Voter
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['JOIN_SORTIE'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an event subject', __CLASS__));
        }

        if (!$subject->joinHasStarted()) {
            return false;
        }

        return null === $subject->getParticipant($user) || \count($this->userRepo->getFiliations($user)) > 0 || $subject->getRepasRestaurant();
    }
}
