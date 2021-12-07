<?php

namespace App\Security\Voter;

use App\Entity\CafUser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserFlagsVoter extends Voter
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'], true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof CafUser) {
            return false;
        }

        $request = $this->requestStack->getMainRequest();

        if (!$request || !$request->hasSession()) {
            return false;
        }

        return $request->getSession()->get('admin_caf', false);
    }
}
