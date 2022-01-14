<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

class AdminDetector
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function isAdmin(): bool
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$request->hasSession()) {
            return false;
        }

        return $request->getSession()->get('admin_caf', false);
    }
}
