<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\Voter\DuplicateSortieVoter;
use App\Security\Voter\FicheSortieVoter;
use App\Security\Voter\SortieAnnulationVoter;
use App\Security\Voter\SortieContactVoter;
use App\Security\Voter\SortieCreateVoter;
use App\Security\Voter\SortieDeleteVoter;
use App\Security\Voter\SortieLegalValidationVoter;
use App\Security\Voter\SortieUpdateVoter;
use App\Security\Voter\SortieValidateVoter;
use App\Security\Voter\SortieViewVoter;
use App\UserRights;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieVotersTest extends TestCase
{
    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
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
        bool $hasExpenseReports = false,
        bool $joinStarted = true,
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
        $event->method('getCancelled')->willReturn($cancelled);
        $event->method('isFinished')->willReturn($finished);
        $event->method('isPublicStatusValide')->willReturn(Evt::STATUS_PUBLISHED_VALIDE === $publicStatus);
        $event->method('isLegalStatusUnseen')->willReturn(Evt::STATUS_LEGAL_UNSEEN === $legalStatus);
        $event->method('isDraft')->willReturn($isDraft);
        $event->method('getEncadrants')->willReturn($encadrants);
        $event->method('getStatus')->willReturn($publicStatus);
        $event->method('joinHasStarted')->willReturn($joinStarted);
        $event->method('getParticipations')->willReturn(new ArrayCollection($participations));

        $reports = new ArrayCollection($hasExpenseReports ? [new \stdClass()] : []);
        $event->method('getExpenseReports')->willReturn($reports);

        return $event;
    }

    private function makeEncadrant(User $user): object
    {
        return new class($user) {
            public function __construct(private $u)
            {
            }

            public function getUser()
            {
                return $this->u;
            }
        };
    }

    // ==========================================
    // SortieCreateVoter
    // ==========================================

    public function testCreateDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieCreateVoter($userRights);

        $res = $voter->vote($this->getToken(null), null, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCreateGrantsWhenGeneralRight(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_create')->willReturn(true);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testCreateDeniesWhenNoRight(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCreateChecksCommissionWhenProvided(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_create', $commission)->willReturn(true);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    // ==========================================
    // SortieViewVoter
    // ==========================================

    public function testViewDeniesWhenAnonymousAndUnpublished(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieViewVoter($userRights);

        $event = $this->createEvent(commission: $commission, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_VIEW']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testViewGrantsWhenPublished(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieViewVoter($userRights);

        $event = $this->createEvent(publicStatus: Evt::STATUS_PUBLISHED_VALIDE);
        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testViewGrantsWhenCommissionValidateRight(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_validate', $commission)->willReturn(true);
        $voter = new SortieViewVoter($userRights);

        $user = $this->createMock(User::class);
        $event = $this->createEvent(commission: $commission, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testViewGrantsWhenValidateAllRight(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->with('evt_validate_all')->willReturn(true);
        $voter = new SortieViewVoter($userRights);

        $user = $this->createMock(User::class);
        $event = $this->createEvent(commission: $commission, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testViewGrantsWhenCreator(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieViewVoter($userRights);

        $event = $this->createEvent(creator: $user, commission: $commission, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testViewDeniesWhenUnpublishedAndNotCreator(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieViewVoter($userRights);

        $event = $this->createEvent(commission: $commission, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VIEW']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    // ==========================================
    // SortieUpdateVoter
    // ==========================================

    public function testUpdateDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieUpdateVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_UPDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUpdateDeniesWhenFinished(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieUpdateVoter($userRights);

        $event = $this->createEvent(creator: $user, finished: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UPDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUpdateGrantsWhenCreator(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieUpdateVoter($userRights);

        $event = $this->createEvent(creator: $user);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UPDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUpdateGrantsWhenValidateAllRight(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_validate_all')->willReturn(true);
        $voter = new SortieUpdateVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UPDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUpdateGrantsWhenCommissionValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('evt_validate', $commission)->willReturn(true);
        $voter = new SortieUpdateVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UPDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUpdateDeniesWhenNotCreatorAndNoRights(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new SortieUpdateVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_UPDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    // ==========================================
    // SortieDeleteVoter
    // ==========================================

    public function testDeleteDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteDeniesWhenNotDraft(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: false);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteDeniesWhenPublicStatusValide(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_VALIDE);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteDeniesWhenHasExpenseReports(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN, hasExpenseReports: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteGrantsWhenCreatorAndDraft(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $event = $this->createEvent(creator: $user, isDraft: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_DELETE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeleteGrantsWhenCommissionDeleteRight(): void
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

    public function testDeleteDeniesWhenNotCreatorAndNoRights(): void
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

    // ==========================================
    // SortieValidateVoter
    // ==========================================

    public function testValidateDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(cancelled: true, publicStatus: Evt::STATUS_PUBLISHED_UNSEEN);
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testValidateDeniesWhenAlreadyValidated(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(publicStatus: Evt::STATUS_PUBLISHED_VALIDE);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testValidateDeniesWhenDraft(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $event = $this->createEvent(publicStatus: Evt::STATUS_PUBLISHED_UNSEEN, isDraft: true);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_VALIDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testValidateGrantsWhenValidateAllRight(): void
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

    public function testValidateGrantsWhenCommissionValidateRight(): void
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

    public function testValidateDeniesWhenNoRights(): void
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

    // ==========================================
    // SortieAnnulationVoter
    // ==========================================

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

    // ==========================================
    // SortieContactVoter
    // ==========================================

    public function testContactDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testContactGrantsWhenCreator(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent(creator: $user);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testContactGrantsWhenSalarie(): void
    {
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->with(UserAttr::SALARIE)->willReturn(true);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testContactGrantsWhenEncadrant(): void
    {
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $event = $this->createEvent(encadrants: [$this->makeEncadrant($user)]);
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_CONTACT_PARTICIPANTS']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testContactGrantsWhenEvtContactAllRight(): void
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

    public function testContactDeniesWhenNoRight(): void
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

    // ==========================================
    // DuplicateSortieVoter
    // ==========================================

    public function testDuplicateDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new DuplicateSortieVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_DUPLICATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDuplicateGrantsWhenParticipantWithCreateRight(): void
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

    public function testDuplicateDeniesWhenNotParticipant(): void
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

    public function testDuplicateDeniesWhenParticipantButNoCreateRight(): void
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

    // ==========================================
    // FicheSortieVoter
    // ==========================================

    public function testFicheDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new FicheSortieVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['FICHE_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testFicheDeniesWhenCancelled(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new FicheSortieVoter($userRights);

        $event = $this->createEvent(cancelled: true);
        $res = $voter->vote($this->getToken($user), $event, ['FICHE_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testFicheGrantsWhenPrintRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_print', $commission)->willReturn(true);
        $voter = new FicheSortieVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['FICHE_SORTIE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testFicheGrantsWhenEncadrant(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new FicheSortieVoter($userRights);

        $event = $this->createEvent(commission: $commission, encadrants: [$this->makeEncadrant($user)]);
        $res = $voter->vote($this->getToken($user), $event, ['FICHE_SORTIE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testFicheDeniesWhenNoPrintRightAndNotEncadrant(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new FicheSortieVoter($userRights);

        $event = $this->createEvent(commission: $commission);
        $res = $voter->vote($this->getToken($user), $event, ['FICHE_SORTIE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    // ==========================================
    // SortieLegalValidationVoter
    // ==========================================

    public function testLegalValidationDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testLegalValidationGrantsWhenRightAndCorrectStatus(): void
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

    public function testLegalValidationDeniesWhenNoRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new SortieLegalValidationVoter($userRights);

        $event = $this->createEvent();
        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_LEGAL_VALIDATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testLegalValidationDeniesWhenNotPublicStatusValide(): void
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

    public function testLegalValidationDeniesWhenWrongLegalStatus(): void
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

    // ==========================================
    // Additional edge-case tests
    // ==========================================

    public function testCreateDeniesWhenCommissionRightDenied(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->with('evt_create', $commission)->willReturn(false);
        $voter = new SortieCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), $commission, ['SORTIE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testViewThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieViewVoter($userRights);

        $voter->vote($this->getToken(null), new \stdClass(), ['SORTIE_VIEW']);
    }

    public function testUpdateThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieUpdateVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_UPDATE']);
    }

    public function testDeleteThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieDeleteVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_DELETE']);
    }

    public function testValidateThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieValidateVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_VALIDATE']);
    }

    public function testCancelThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieAnnulationVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_CANCEL']);
    }

    public function testContactThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieContactVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_CONTACT_PARTICIPANTS']);
    }

    public function testDuplicateThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new DuplicateSortieVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_DUPLICATE']);
    }

    public function testFicheThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new FicheSortieVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['FICHE_SORTIE']);
    }

    public function testLegalValidationThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new SortieLegalValidationVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_LEGAL_VALIDATION']);
    }
}
