<?php

namespace App\Tests\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\NomadJoinSortieVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NomadJoinSortieVoterTest extends TestCase
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
        $voter = new NomadJoinSortieVoter($userRights);

        $token = $this->getToken(null);
        $event = $this->createMock(Evt::class);

        $res = $voter->vote($token, $event, ['EVENT_NOMAD_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testSupportsWrongSubjectThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new NomadJoinSortieVoter($userRights);

        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        // Subject is not an event
        $voter->vote($token, new \stdClass(), ['EVENT_NOMAD_JOINING_ADD']);
    }

    public function testGrantsWhenRightAllowed(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_nomad_add')->willReturn(true);
        $voter = new NomadJoinSortieVoter($userRights);

        $user = $this->createMock(User::class);
        $token = $this->getToken($user);
        $event = $this->createMock(Evt::class);

        $res = $voter->vote($token, $event, ['EVENT_NOMAD_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenRightNotAllowed(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_nomad_add')->willReturn(false);
        $voter = new NomadJoinSortieVoter($userRights);

        $user = $this->createMock(User::class);
        $token = $this->getToken($user);
        $event = $this->createMock(Evt::class);

        $res = $voter->vote($token, $event, ['EVENT_NOMAD_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }
}
