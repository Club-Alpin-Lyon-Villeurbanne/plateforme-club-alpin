<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\RoleChecker;
use App\Security\SecurityConstants;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminVoter extends Voter
{
    private RoleChecker $roleChecker;

    public function __construct(RoleChecker $roleChecker)
    {
        $this->roleChecker = $roleChecker;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [SecurityConstants::ROLE_ADMIN, 'ROLE_ALLOWED_TO_SWITCH'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ('ROLE_ALLOWED_TO_SWITCH' === $attribute && $user->hasAttribute(UserAttr::DEVELOPPEUR)) {
            return true;
        }

        if ($token->hasAttribute(SecurityConstants::SESSION_USER_ROLE_KEY) && 
            $token->getAttribute(SecurityConstants::SESSION_USER_ROLE_KEY) === SecurityConstants::ROLE_ADMIN) {
            return true;
        }

        return $this->roleChecker->isAdmin();
    }
}