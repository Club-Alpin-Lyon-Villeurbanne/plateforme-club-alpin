<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\User;
use App\Security\Voter\SortieCreateVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieCreateVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieCreateVoter($userRights);

        $res = $voter->vote($this->getToken(null), null, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenGeneralRight(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_create')->willReturn(true);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRight(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenCommissionRightAllowed(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_create', $commission)->willReturn(true);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenCommissionRightDenied(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_create', $commission)->willReturn(false);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }
}
