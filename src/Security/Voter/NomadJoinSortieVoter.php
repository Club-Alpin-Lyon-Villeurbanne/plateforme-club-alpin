<?php

namespace App\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NomadJoinSortieVoter extends Voter
{
    public function __construct(protected UserRights $userRights)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'EVENT_NOMAD_JOINING_ADD' === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Evt) {
            throw new \InvalidArgumentException(sprintf('The voter "%s" requires an event subject', __CLASS__));
        }

        return $this->userRights->allowed('evt_nomad_add');
    }
}
