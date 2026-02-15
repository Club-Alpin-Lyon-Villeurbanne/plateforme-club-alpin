<?php

namespace App\Tests\Security\Voter;

use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\SortieLegalValidationVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieLegalValidationVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenRightAndCorrectStatus(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_legal_accept')->willReturn(true);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent(
            publicStatus: Evt::STATUS_PUBLISHED_VALIDE,
            legalStatus: Evt::STATUS_LEGAL_UNSEEN,
        );
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenNotPublicStatusValide(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_legal_accept')->willReturn(true);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent(
            publicStatus: Evt::STATUS_PUBLISHED_UNSEEN,
            legalStatus: Evt::STATUS_LEGAL_UNSEEN,
        );
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenWrongLegalStatus(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_legal_accept')->willReturn(true);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent(
            publicStatus: Evt::STATUS_PUBLISHED_VALIDE,
            legalStatus: Evt::STATUS_LEGAL_VALIDE,
        );
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieLegalValidationVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_LEGAL_VALIDATION']);
    }

    private function createEvent(
        int $publicStatus = Evt::STATUS_PUBLISHED_VALIDE,
        int $legalStatus = Evt::STATUS_LEGAL_UNSEEN,
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

        $event->method('getUser')->willReturn(null);
        $event->method('getCommission')->willReturn(null);
        $event->method('getCancelled')->willReturn(false);
        $event->method('isFinished')->willReturn(false);
        $event->method('isPublicStatusValide')->willReturn(Evt::STATUS_PUBLISHED_VALIDE === $publicStatus);
        $event->method('isLegalStatusUnseen')->willReturn(Evt::STATUS_LEGAL_UNSEEN === $legalStatus);
        $event->method('isDraft')->willReturn(false);
        $event->method('getEncadrants')->willReturn([]);
        $event->method('getStatus')->willReturn($publicStatus);
        $event->method('joinHasStarted')->willReturn(true);
        $event->method('getParticipations')->willReturn(new ArrayCollection());
        $event->method('getExpenseReports')->willReturn(new ArrayCollection());

        return $event;
    }
}
