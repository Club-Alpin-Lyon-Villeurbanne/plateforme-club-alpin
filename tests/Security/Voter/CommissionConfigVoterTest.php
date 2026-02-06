<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\User;
use App\Security\Voter\CommissionConfigVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommissionConfigVoterTest extends TestCase
{
    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new CommissionConfigVoter($userRights);

        $commission = $this->createMock(Commission::class);
        $res = $voter->vote($this->getToken(null), $commission, ['COMMISSION_CONFIG']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenCommissionNotVisible(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new CommissionConfigVoter($userRights);

        $commission = $this->createMock(Commission::class);
        $commission->method('getVis')->willReturn(false);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['COMMISSION_CONFIG']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenAllowedOnCommission(): void
    {
        $commission = $this->createMock(Commission::class);
        $commission->method('getVis')->willReturn(true);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('commission_config', $commission)->willReturn(true);
        $voter = new CommissionConfigVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['COMMISSION_CONFIG']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNotAllowedOnCommission(): void
    {
        $commission = $this->createMock(Commission::class);
        $commission->method('getVis')->willReturn(true);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('commission_config', $commission)->willReturn(false);
        $voter = new CommissionConfigVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['COMMISSION_CONFIG']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new CommissionConfigVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['COMMISSION_CONFIG']);
    }
}
