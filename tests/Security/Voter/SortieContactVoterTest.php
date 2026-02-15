<?php

namespace App\Tests\Security\Voter;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\Voter\SortieContactVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieContactVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenCreator(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent(creator: $user);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenSalarie(): void
    {
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->with(UserAttr::SALARIE)->willReturn(true);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenEncadrant(): void
    {
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent(encadrants: [$this->makeEncadrant($user)]);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenEvtContactAllRight(): void
    {
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_contact_all')->willReturn(true);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRight(): void
    {
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_CONTACT_PARTICIPANTS']);
    }

    private function createEvent(
        ?User $creator = null,
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
        $event->method('getCommission')->willReturn(null);
        $event->method('getCancelled')->willReturn(false);
        $event->method('isFinished')->willReturn(false);
        $event->method('isPublicStatusValide')->willReturn(true);
        $event->method('isLegalStatusUnseen')->willReturn(true);
        $event->method('isDraft')->willReturn(false);
        $event->method('getEncadrants')->willReturn($encadrants);
        $event->method('getStatus')->willReturn(Evt::STATUS_PUBLISHED_VALIDE);
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
