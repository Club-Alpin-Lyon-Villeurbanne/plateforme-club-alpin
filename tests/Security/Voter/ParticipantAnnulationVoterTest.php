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

    public function testGrantsWhenReferentCancelsAffilieParticipation(): void
    {
        $referent = $this->createMock(User::class);
        $referent->method('getCafnum')->willReturn('740012345678');
        $affilie = $this->createMock(User::class);
        $affilie->method('getCafnumParent')->willReturn('740012345678');

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_unjoin')->willReturn(true);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($affilie);

        $res = $voter->vote($this->getToken($referent), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenReferentCancelsAffilieWithoutRight(): void
    {
        $referent = $this->createMock(User::class);
        $referent->method('getCafnum')->willReturn('740012345678');
        $affilie = $this->createMock(User::class);
        $affilie->method('getCafnumParent')->willReturn('740012345678');

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_unjoin')->willReturn(false);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($affilie);

        $res = $voter->vote($this->getToken($referent), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenParticipantIsAffilieOfSomeoneElse(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCafnum')->willReturn('740012345678');
        $affilieAutreReferent = $this->createMock(User::class);
        $affilieAutreReferent->method('getCafnumParent')->willReturn('740099999999');

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_unjoin')->willReturn(true);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($affilieAutreReferent);

        $res = $voter->vote($this->getToken($user), $participation, ['PARTICIPANT_ANNULATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenUserHasNoCafnumAndParticipantHasNoParent(): void
    {
        // un numéro CAF vide ne doit pas rattacher tous les adhérents sans référent
        $user = $this->createMock(User::class);
        $user->method('getCafnum')->willReturn('');
        $otherUser = $this->createMock(User::class);
        $otherUser->method('getCafnumParent')->willReturn('');

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_unjoin')->willReturn(true);
        $voter = new ParticipantAnnulationVoter($userRights);

        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($otherUser);

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
