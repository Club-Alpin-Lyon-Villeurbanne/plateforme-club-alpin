<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\RoleChecker;
use App\Security\SecurityConstants;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContentManagerVoter extends Voter
{
    private RoleChecker $roleChecker;

    public function __construct(RoleChecker $roleChecker)
    {
        $this->roleChecker = $roleChecker;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === SecurityConstants::ROLE_CONTENT_MANAGER;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Les admins ont automatiquement les droits de gestionnaire de contenu
        if ($this->roleChecker->isAdmin()) {
            return true;
        }

        return $this->roleChecker->isContentManager();
    }
}