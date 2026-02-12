<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\SortieValidateVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieValidateVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(cancelled: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenAlreadyValidated(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(publicStatus: Evt::STATUS_PUBLISHED_VALIDE);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenDraft(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(publicStatus: Evt::STATUS_PUBLISHED_UNSEEN, isDraft: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenValidateAllRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_validate_all')->willReturn(true);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(commission: $commission, cancelled: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenCommissionValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('evt_validate', $commission)->willReturn(true);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(commission: $commission, cancelled: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRights(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(commission: $commission, cancelled: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_VALIDATE']);
    }

    private function createEvent(
        ?User $creator = null,
        ?Commission $commission = null,
        bool $cancelled = false,
        bool $finished = false,
        int $publicStatus = Evt::STATUS_PUBLISHED_VALIDE,
        int $legalStatus = Evt::STATUS_LEGAL_UNSEEN,
        bool $isDraft = false,
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
        $event->method('getExpenseReports')->willReturn(new ArrayCollection());

        return $event;
    }
}
