<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\DuplicateSortieVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DuplicateSortieVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new DuplicateSortieVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_DUPLICATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenParticipantWithCreateRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_create', $commission)->willReturn(true);
        $voter = new DuplicateSortieVoter($userRights);

        $event = $this->createEvent(commission: $commission, participations: [$this->makeEncadrant($user)]);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DUPLICATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNotParticipant(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(true);
        $voter = new DuplicateSortieVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DUPLICATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenParticipantButNoCreateRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_create', $commission)->willReturn(false);
        $voter = new DuplicateSortieVoter($userRights);

        $event = $this->createEvent(commission: $commission, participations: [$this->makeEncadrant($user)]);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DUPLICATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new DuplicateSortieVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_DUPLICATE']);
    }

    private function createEvent(
        ?User $creator = null,
        ?Commission $commission = null,
        array $participations = [],
    ): Evt {
        $event = $this->getMockBuilder(Evt::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getUser', 'getCommission', 'getCancelled', 'isFinished',
                'isPublicStatusValide', 'isLegalStatusUnseen', 'isDraft',
                'getEncadrants', 'getExpenseReports', 'getStatus', 'joinHasStarted',
                'getParticipations',
            ])
            ->getMock();

        $event->method('getUser')->willReturn($creator);
        $event->method('getCommission')->willReturn($commission);
        $event->method('getCancelled')->willReturn(false);
        $event->method('isFinished')->willReturn(false);
        $event->method('isPublicStatusValide')->willReturn(true);
        $event->method('isLegalStatusUnseen')->willReturn(true);
        $event->method('isDraft')->willReturn(false);
        $event->method('getEncadrants')->willReturn([]);
        $event->method('getStatus')->willReturn(Evt::STATUS_PUBLISHED_VALIDE);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipations')->willReturn(new ArrayCollection($participations));
        $event->method('getExpenseReports')->willReturn(new ArrayCollection());

        return $event;
    }

    private function makeEncadrant(User $user): EventParticipation
    {
        $participation = $this->createMock(EventParticipation::class);
        $participation->method('getUser')->willReturn($user);

        return $participation;
    }
}
