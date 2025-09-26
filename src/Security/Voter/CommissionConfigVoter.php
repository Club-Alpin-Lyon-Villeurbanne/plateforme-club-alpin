<?php

namespace App\Security\Voter;

use App\Entity\Commission;
use App\Entity\User;
use App\UserRights;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommissionConfigVoter extends Voter
{
    public function __construct(protected UserRights $userRights)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 'COMMISSION_CONFIG' === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        if (!$subject instanceof Commission) {
            throw new \InvalidArgumentException('COMMISSION_CONFIG requires a commission subject');
        }

        if (!$subject->getVis()) {
            return false;
        }

        return $this->userRights->allowedOnCommission('commission_config', $subject);
    }
}
