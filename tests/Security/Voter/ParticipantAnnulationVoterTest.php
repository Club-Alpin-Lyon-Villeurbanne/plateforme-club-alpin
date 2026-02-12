<?php

namespace App\Tests\Security\Voter;

use App\Entity\EventParticipation;
use App\Entity\User;
use App\Security\Voter\ParticipantAnnulationVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ParticipantAnnulationVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $res = $voter->vote($this->getToken(null), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenNotOwnParticipation(): void
    {
        $user = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($otherUser);

        $res = $voter->vote($this->getToken($user), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenOwnParticipationAndRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_unjoin')->willReturn(true);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($user);

        $res = $voter->vote($this->getToken($user), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenOwnParticipationButNoRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_unjoin')->willReturn(false);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($user);

        $res = $voter->vote($this->getToken($user), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ParticipantAnnulationVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['PARTICIPANT_ANNULATION']);
    }
}
