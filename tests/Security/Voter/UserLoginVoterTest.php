<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\Voter\UserLoginVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserLoginVoterTest extends TestCase
{
    public function testGrantsForActiveUser(): void
    {
        $voter = new UserLoginVoter();
        $res = $voter->vote($this->getToken(), $this->createUser(), [UserLoginVoter::LOGIN]);

        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesForDeletedUser(): void
    {
        $voter = new UserLoginVoter();
        $res = $voter->vote($this->getToken(), $this->createUser(isDeleted: true), [UserLoginVoter::LOGIN]);

        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesForLockedUser(): void
    {
        $voter = new UserLoginVoter();
        $res = $voter->vote($this->getToken(), $this->createUser(isLocked: true), [UserLoginVoter::LOGIN]);

        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesForDeletedAndLockedUser(): void
    {
        $voter = new UserLoginVoter();
        $res = $voter->vote($this->getToken(), $this->createUser(isDeleted: true, isLocked: true), [UserLoginVoter::LOGIN]);

        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = new UserLoginVoter();
        $res = $voter->vote($this->getToken(), $this->createUser(), ['SOME_OTHER_ATTRIBUTE']);

        $this->assertSame(Voter::ACCESS_ABSTAIN, $res);
    }

    // Helper methods

    private function getToken(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    private function createUser(bool $isDeleted = false, bool $isLocked = false): User
    {
        $user = $this->createMock(User::class);
        $user->method('isDeleted')->willReturn($isDeleted);
        $user->method('isLocked')->willReturn($isLocked);

        return $user;
    }
}
