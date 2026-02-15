<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\SortieAnnulationVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieAnnulationVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testCancelDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCancelDeniesWhenAlreadyCancelled(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(creator: $user, cancelled: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCancelDeniesWhenNotPublicStatusValide(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(creator: $user, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCancelDeniesWhenFinished(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(creator: $user, finished: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCancelGrantsWhenCreatorWithRight(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'evt_cancel_own' === $code);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(creator: $user);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testCancelGrantsWhenEncadrantWithRight(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'evt_cancel_own' === $code);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(encadrants: [$this->makeEncadrant($user)]);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testCancelGrantsWhenCommissionCancelRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('evt_cancel', $commission)->willReturn(true);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testCancelGrantsWhenEvtCancelAnyRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'evt_cancel_any' === $code);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testCancelDeniesWhenNoRights(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CANCEL']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUncancelDeniesWhenNotCancelled(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(creator: $user, cancelled: false);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UNCANCEL']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUncancelGrantsWhenCreatorWithRight(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'evt_cancel_own' === $code);
        $voter = new SortieAnnulationVoter($userRights);

        $event = $this->createEvent(creator: $user, cancelled: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UNCANCEL']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_CANCEL']);
    }

    private function createEvent(
        ?User $creator = null,
        ?Commission $commission = null,
        bool $cancelled = false,
        bool $finished = false,
        int $publicStatus = Evt::STATUS_PUBLISHED_VALIDE,
        int $legalStatus = Evt::STATUS_LEGAL_UNSEEN,
        bool $isDraft = false,
        array $encadrants = [],
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
        $event->method('getCancelled')->willReturn($cancelled);
        $event->method('isFinished')->willReturn($finished);
        $event->method('isPublicStatusValide')->willReturn(Evt::STATUS_PUBLISHED_VALIDE === $publicStatus);
        $event->method('isLegalStatusUnseen')->willReturn(Evt::STATUS_LEGAL_UNSEEN === $legalStatus);
        $event->method('isDraft')->willReturn($isDraft);
        $event->method('getEncadrants')->willReturn($encadrants);
        $event->method('getStatus')->willReturn($publicStatus);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipations')->willReturn(new ArrayCollection());
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
