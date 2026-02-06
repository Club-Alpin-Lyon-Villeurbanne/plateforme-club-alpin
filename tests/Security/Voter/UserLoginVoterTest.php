<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\Voter\UserLoginVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserLoginVoterTest extends TestCase
{
    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testGrantsWhenUserNotDeleted(): void
    {
        $voter = new UserLoginVoter();

        $user = $this->createMock(User::class);
        $user->method('isDeleted')->willReturn(false);

        $res = $voter->vote($this->getToken($user), $user, [UserLoginVoter::LOGIN]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenUserDeleted(): void
    {
        $voter = new UserLoginVoter();

        $user = $this->createMock(User::class);
        $user->method('isDeleted')->willReturn(true);

        $res = $voter->vote($this->getToken($user), $user, [UserLoginVoter::LOGIN]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testAbstainsForNonUserSubject(): void
    {
        $voter = new UserLoginVoter();

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), new \stdClass(), [UserLoginVoter::LOGIN]);
        $this->assertSame(Voter::ACCESS_ABSTAIN, $res);
    }
}
