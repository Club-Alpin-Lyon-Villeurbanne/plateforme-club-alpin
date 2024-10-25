<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\SecurityConstants;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContentManagerVoter extends Voter
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return SecurityConstants::ROLE_CONTENT_MANAGER === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Les admins ont automatiquement les droits de gestionnaire de contenu
        if ($this->security->isGranted(SecurityConstants::ROLE_ADMIN)) {
            return true;
        }

        if (
            $token->hasAttribute(SecurityConstants::SESSION_USER_ROLE_KEY)
            && SecurityConstants::ROLE_CONTENT_MANAGER === $token->getAttribute(SecurityConstants::SESSION_USER_ROLE_KEY)
        ) {
            return true;
        }
        // Not sure if having this code is really necessary as we don't use the admin role for the API
        $request = $this->requestStack->getMainRequest();
        if (!$request || !$request->hasSession() || $request->attributes->getBoolean('_stateless')) {
            return false;
        }

        return SecurityConstants::ROLE_CONTENT_MANAGER === $request->getSession()->get(SecurityConstants::SESSION_USER_ROLE_KEY);
    }
}
