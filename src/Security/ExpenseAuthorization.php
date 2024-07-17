<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;

class ExpenseAuthorization
{
    private $authorizedIds;
    private $security;

    public function __construct(string $authorizedIds, Security $security)
    {
        $this->authorizedIds = explode(',', $authorizedIds);
        $this->security = $security;
    }

    public function isAuthorized(): bool
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return false;
        }

        return \in_array($user->getId(), $this->authorizedIds, true);
    }
}
