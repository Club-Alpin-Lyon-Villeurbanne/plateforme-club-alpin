<?php

namespace App\Tests\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait VoterTestHelperTrait
{
    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
