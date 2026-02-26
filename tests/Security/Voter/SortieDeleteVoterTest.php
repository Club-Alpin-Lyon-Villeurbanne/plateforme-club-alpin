<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\SortieDeleteVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieDeleteVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenNotDraft(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: false);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenPublicStatusValide(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_VALIDE);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenHasExpenseReports(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN, hasExpenseReports: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenCreatorAndDraft(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenCommissionDeleteRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_delete', $commission)->willReturn(true);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(commission: $commission, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNotCreatorAndNoRights(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(commission: $commission, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_DELETE']);
    }

    private function createEvent(
        ?User $creator = null,
        ?Commission $commission = null,
        bool $cancelled = false,
        bool $finished = false,
        int $publicStatus = Evt::STATUS_PUBLISHED_VALIDE,
        int $legalStatus = Evt::STATUS_LEGAL_UNSEEN,
        bool $isDraft = false,
        bool $hasExpenseReports = false,
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
        $event->method('getEncadrants')->willReturn([]);
        $event->method('getStatus')->willReturn($publicStatus);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipations')->willReturn(new ArrayCollection());

        $reports = new ArrayCollection($hasExpenseReports ? [new \stdClass()] : []);
        $event->method('getExpenseReports')->willReturn($reports);

        return $event;
    }
}
