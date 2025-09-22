<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\SecurityConstants;
use App\Security\Voter\EventJoiningAddVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventJoiningAddVoterTest extends TestCase
{
    private function makeToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        // non connecté
        $token = $this->makeToken(null);
        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled'])->getMock();
        $event->method('getCancelled')->willReturn(false);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsForAdmin(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(SecurityConstants::ROLE_ADMIN)->willReturn(true);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $token = $this->makeToken($user);
        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled'])->getMock();
        $event->method('getCancelled')->willReturn(false);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenEventCancelled(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $token = $this->makeToken($user);
        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled'])->getMock();
        $event->method('getCancelled')->willReturn(true);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenOwner(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $token = $this->makeToken($user);

        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled', 'getUser'])->getMock();
        $event->method('getCancelled')->willReturn(false);
        $event->method('getUser')->willReturn($user);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenSalarie(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->with(UserAttr::SALARIE)->willReturn(true);
        $token = $this->makeToken($user);

        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled', 'getUser'])->getMock();
        $event->method('getCancelled')->willReturn(false);
        $event->method('getUser')->willReturn($this->createMock(User::class));

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenEncadrantOfEvent(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $token = $this->makeToken($user);

        $participation = new class($user) {
            public function __construct(private $u)
            {
            }

            public function getUser()
            {
                return $this->u;
            }
        };

        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled', 'getUser', 'getEncadrants'])->getMock();
        $event->method('getCancelled')->willReturn(false);
        $event->method('getUser')->willReturn($this->createMock(User::class));
        $event->method('getEncadrants')->willReturn([$participation]);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenHasJoinDoAllRight(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('evt_join_doall')->willReturn(true);
        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $token = $this->makeToken($user);
        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled', 'getUser', 'getEncadrants'])->getMock();
        $event->method('getCancelled')->willReturn(false);
        $event->method('getUser')->willReturn($this->createMock(User::class));
        $event->method('getEncadrants')->willReturn([]);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenResponsableWithJoinNotMeRight(): void
    {
        $commission = $this->createMock(Commission::class);
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(function ($code) {
            return 'evt_join_notme' === $code;
        });
        $userRights->method('allowedOnCommission')->with('evt_unjoin_notme', $commission)->willReturn(false);

        $security = $this->createMock(Security::class);
        $voter = new EventJoiningAddVoter($userRights, $security);

        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->willReturnCallback(function ($attr, $param = null) use ($commission) {
            if (UserAttr::RESPONSABLE_COMMISSION === $attr && $param === $commission) {
                return true;
            }

            return false;
        });
        $token = $this->makeToken($user);

        $participation = new class($user) {
            public function __construct(private $u)
            {
            }

            public function getUser()
            {
                return $this->u;
            }
        };

        $event = $this->getMockBuilder('App\\Entity\\Evt')->disableOriginalConstructor()->onlyMethods(['getCancelled', 'getUser', 'getCommission', 'getEncadrants'])->getMock();
        $event->method('getCancelled')->willReturn(false);
        $event->method('getUser')->willReturn($this->createMock(User::class));
        $event->method('getCommission')->willReturn($commission);
        $event->method('getEncadrants')->willReturn([$participation]);

        $res = $voter->vote($token, $event, ['EVENT_JOINING_ADD']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }
}
