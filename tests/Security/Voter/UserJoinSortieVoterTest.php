<?php

namespace App\Tests\Security\Voter;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\UserJoinSortieVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserJoinSortieVoterTest extends TestCase
{
    public function testDeniesWhenAnonymous(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $voter = new UserJoinSortieVoter($userRepo);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['JOIN_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenJoinNotStarted(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $voter = new UserJoinSortieVoter($userRepo);

        $user = $this->createMock(User::class);
        $event = $this->createEvent(joinStarted: false);

        $res = $voter->vote($this->getToken($user), $event, ['JOIN_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenDoitRenouveler(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $voter = new UserJoinSortieVoter($userRepo);

        $user = $this->createMock(User::class);
        $user->method('getDoitRenouveler')->willReturn(true);

        $event = $this->createEvent();

        $res = $voter->vote($this->getToken($user), $event, ['JOIN_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenNotAlreadyParticipating(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $voter = new UserJoinSortieVoter($userRepo);

        $user = $this->createMock(User::class);
        $user->method('getDoitRenouveler')->willReturn(false);

        $event = $this->createMock(Evt::class);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipation')->willReturn(null);

        $res = $voter->vote($this->getToken($user), $event, ['JOIN_SORTIE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenAlreadyParticipatingButHasFiliations(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getDoitRenouveler')->willReturn(false);

        $participation = $this->createMock(EventParticipation::class);

        $event = $this->createMock(Evt::class);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipation')->willReturn($participation);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('getFiliations')->with($user)->willReturn([$this->createMock(User::class)]);
        $voter = new UserJoinSortieVoter($userRepo);

        $res = $voter->vote($this->getToken($user), $event, ['JOIN_SORTIE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenAlreadyParticipatingAndNoFiliations(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getDoitRenouveler')->willReturn(false);

        $participation = $this->createMock(EventParticipation::class);

        $event = $this->createMock(Evt::class);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipation')->willReturn($participation);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('getFiliations')->with($user)->willReturn([]);
        $voter = new UserJoinSortieVoter($userRepo);

        $res = $voter->vote($this->getToken($user), $event, ['JOIN_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRepo = $this->createMock(UserRepository::class);
        $voter = new UserJoinSortieVoter($userRepo);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['JOIN_SORTIE']);
    }

    // Helper methods

    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function createEvent(bool $joinStarted = true): Evt
    {
        $event = $this->createMock(Evt::class);
        $event->method('joinHasStarted')->willReturn($joinStarted);

        return $event;
    }
}
