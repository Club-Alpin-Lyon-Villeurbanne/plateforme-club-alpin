<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

class RoleChecker
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function isAdmin(): bool
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$request->hasSession() || $request->attributes->getBoolean('_stateless')) {
            return false;
        }

        return $request->getSession()->get(SecurityConstants::SESSION_USER_ROLE_KEY) === SecurityConstants::ROLE_ADMIN;
    }

    public function isContentManager(): bool
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$request->hasSession() || $request->attributes->getBoolean('_stateless')) {
            return false;
        }

        $userRole = $request->getSession()->get(SecurityConstants::SESSION_USER_ROLE_KEY);
        return $userRole === SecurityConstants::ROLE_CONTENT_MANAGER;
    }
}