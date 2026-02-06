<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Entity\UserAttr;
use App\Security\SecurityConstants;
use App\Security\Voter\AdminVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminVoterTest extends TestCase
{
    private function getToken($user, array $attributes = []): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $token->method('getAttribute')->willReturnCallback(static fn ($key) => $attributes[$key] ?? null);
        $token->method('hasAttribute')->willReturnCallback(static fn ($key) => isset($attributes[$key]));

        return $token;
    }

    private function createVoterWithSession(?string $sessionRole = null): AdminVoter
    {
        $requestStack = $this->createMock(RequestStack::class);

        if (null !== $sessionRole) {
            $session = $this->createMock(SessionInterface::class);
            $session->method('get')->with(SecurityConstants::SESSION_USER_ROLE_KEY)->willReturn($sessionRole);

            $request = $this->createMock(Request::class);
            $request->method('hasSession')->willReturn(true);
            $request->method('getSession')->willReturn($session);
            $request->attributes = new \Symfony\Component\HttpFoundation\ParameterBag();

            $requestStack->method('getMainRequest')->willReturn($request);
        } else {
            $requestStack->method('getMainRequest')->willReturn(null);
        }

        return new AdminVoter($requestStack);
    }

    public function testDeniesWhenNotUser(): void
    {
        $voter = $this->createVoterWithSession();
        $token = $this->getToken(null);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_ADMIN]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenTokenHasAdminRole(): void
    {
        $voter = $this->createVoterWithSession();
        $user = $this->createMock(User::class);
        $token = $this->getToken($user, [SecurityConstants::SESSION_USER_ROLE_KEY => SecurityConstants::ROLE_ADMIN]);

        $res = $voter->vote($token, $user, [SecurityConstants::ROLE_ADMIN]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsSwitchUserForDeveloppeur(): void
    {
        $voter = $this->createVoterWithSession();
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->with(UserAttr::DEVELOPPEUR)->willReturn(true);
        $token = $this->getToken($user);

        $res = $voter->vote($token, $user, ['ROLE_ALLOWED_TO_SWITCH']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesSwitchUserForNonDeveloppeur(): void
    {
        $voter = $this->createVoterWithSession();
        $user = $this->createMock(User::class);
        $user->method('hasAttribute')->with(UserAttr::DEVELOPPEUR)->willReturn(false);
        $token = $this->getToken($user);

        $res = $voter->vote($token, $user, ['ROLE_ALLOWED_TO_SWITCH']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenSessionHasAdminRole(): void
    {
        $voter = $this->createVoterWithSession(SecurityConstants::ROLE_ADMIN);
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, $user, [SecurityConstants::ROLE_ADMIN]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRequest(): void
    {
        $voter = $this->createVoterWithSession();
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, $user, [SecurityConstants::ROLE_ADMIN]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = $this->createVoterWithSession();
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, $user, ['SOME_OTHER_ROLE']);
        $this->assertSame(Voter::ACCESS_ABSTAIN, $res);
    }
}
