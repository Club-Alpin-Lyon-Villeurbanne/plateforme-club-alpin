<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\AdminDetector;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserFlagsVoter extends Voter
{
    private AdminDetector $adminDetector;

    public function __construct(AdminDetector $adminDetector)
    {
        $this->adminDetector = $adminDetector;
    }

    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ('ROLE_ALLOWED_TO_SWITCH' === $attribute && $user->hasAttribute(UserAttr::DEVELOPPEUR)) {
            return true;
        }

        if ($token->hasAttribute('is_admin') && $token->getAttribute('is_admin')) {
            return true;
        }

        return $this->adminDetector->isAdmin();
    }
}
