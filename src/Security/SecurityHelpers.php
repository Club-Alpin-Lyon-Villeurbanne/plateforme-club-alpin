<?php
namespace App\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides helper methods for security-related operations.
 */
class SecurityHelpers
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Checks if the current user has a specific permission.
     */
    public function isGranted($attribute, $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $subject);
    }

    /**
     * Checks if the current user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->isGranted(SecurityConstants::ROLE_ADMIN);
    }

    /**
     * Checks if the current user is a content manager.
     */
    public function isContentManager(): bool
    {
        return $this->isGranted(SecurityConstants::ROLE_CONTENT_MANAGER);
    }

    /**
     * Checks if the current user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->isGranted($role);
    }
}