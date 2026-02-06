<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\SecurityConstants;
use App\Security\Voter\SortieInscriptionsModificationVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SortieInscriptionsModificationVoterTest extends TestCase
{
    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function createVoter(bool $isAdmin = false, array $allowedRights = [], array $commissionRights = []): SortieInscriptionsModificationVoter
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(SecurityConstants::ROLE_ADMIN)->willReturn($isAdmin);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(fn ($right) => \in_array($right, $allowedRights, true));
        $userRights->method('allowedOnCommission')->willReturnCallback(
            fn ($right, $commission) => \in_array($right, $commissionRights, true)
        );

        return new SortieInscriptionsModificationVoter($userRights, $security);
    }

    private function createEvent(?User $organizer = null, bool $cancelled = false, array $encadrants = [], ?Commission $commission = null): Evt
    {
        $event = $this->createMock(Evt::class);
        $event->method('getUser')->willReturn($organizer);
        $event->method('getCancelled')->willReturn($cancelled);
        $event->method('getCommission')->willReturn($commission);

        $participations = [];
        foreach ($encadrants as $encadrant) {
            $participation = $this->createMock(EventParticipation::class);
            $participation->method('getUser')->willReturn($encadrant);
            $participations[] = $participation;
        }
        $event->method('getEncadrants')->willReturn($participations);

        return $event;
    }

    public function testDeniesWhenAnonymous(): void
    {
        $voter = $this->createVoter();
        $event = $this->createEvent();

        $res = $voter->vote($this->getToken(null), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenAdmin(): void
    {
        $voter = $this->createVoter(isAdmin: true);
        $user = $this->createMock(User::class);
        $event = $this->createEvent();

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenCancelled(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $event = $this->createEvent(organizer: $user, cancelled: true);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenOrganizer(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $event = $this->createEvent(organizer: $user);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenSalarie(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturnCallback(fn ($attr) => UserAttr::SALARIE === $attr);
        $event = $this->createEvent();

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenEncadrant(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $event = $this->createEvent(encadrants: [$user]);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenEvtJoinDoall(): void
    {
        $voter = $this->createVoter(allowedRights: ['evt_join_doall']);
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $event = $this->createEvent();

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenResponsableCommissionWithRights(): void
    {
        $commission = $this->createMock(Commission::class);

        $voter = $this->createVoter(allowedRights: ['evt_join_notme']);
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturnCallback(
            fn ($attr, $com = null) => UserAttr::RESPONSABLE_COMMISSION === $attr && $com === $commission
        );
        $event = $this->createEvent(commission: $commission);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoMatchingCondition(): void
    {
        $commission = $this->createMock(Commission::class);
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturn(false);
        $event = $this->createEvent(commission: $commission);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testAbstainsForNonEvtSubject(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);

        $res = $voter->vote($this->getToken($user), new \stdClass(), ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_ABSTAIN, $res);
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $event = $this->createMock(Evt::class);

        $res = $voter->vote($this->getToken($user), $event, ['SOME_OTHER_ATTRIBUTE']);
        $this->assertSame(Voter::ACCESS_ABSTAIN, $res);
    }

    public function testGrantsWhenResponsableWithCommissionUnjoinRight(): void
    {
        $commission = $this->createMock(Commission::class);

        $voter = $this->createVoter(commissionRights: ['evt_unjoin_notme']);
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturnCallback(
            fn ($attr, $com = null) => UserAttr::RESPONSABLE_COMMISSION === $attr && $com === $commission
        );
        $event = $this->createEvent(commission: $commission);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenResponsableWithCommissionJoiningAcceptRight(): void
    {
        $commission = $this->createMock(Commission::class);

        $voter = $this->createVoter(commissionRights: ['evt_joining_accept']);
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturnCallback(
            fn ($attr, $com = null) => UserAttr::RESPONSABLE_COMMISSION === $attr && $com === $commission
        );
        $event = $this->createEvent(commission: $commission);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenResponsableWithCommissionJoiningRefuseRight(): void
    {
        $commission = $this->createMock(Commission::class);

        $voter = $this->createVoter(commissionRights: ['evt_joining_refuse']);
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturnCallback(
            fn ($attr, $com = null) => UserAttr::RESPONSABLE_COMMISSION === $attr && $com === $commission
        );
        $event = $this->createEvent(commission: $commission);

        $res = $voter->vote($this->getToken($user), $event, ['SORTIE_INSCRIPTIONS_MODIFICATION']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }
}
