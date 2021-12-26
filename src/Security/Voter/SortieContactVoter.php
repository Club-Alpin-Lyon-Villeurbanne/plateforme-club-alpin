<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieContactVoter extends Voter
{
    private UserRights $userRights;

    public function __construct(UserRights $userRights)
    {
        $this->userRights = $userRights;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['SORTIE_CONTACT_PARTICIPANTS'], true);
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

        if ($user === $subject->getUser()) {
            return true;
        }
        if ($user->hasAttribute(UserAttr::SALARIE)) {
            return true;
        }
        if ($subject->getEncadrants()->contains($user)) {
            return true;
        }

        return $this->userRights->allowed('evt_contact_all');
    }
}
