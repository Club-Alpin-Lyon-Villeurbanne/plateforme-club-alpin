<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\SecurityConstants;
use App\Security\Voter\ContentManagerVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContentManagerVoterTest extends TestCase
{
    private function getToken($user, array $attributes = []): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $token->method('getAttribute')->willReturnCallback(fn ($key) => $attributes[$key] ?? null);
        $token->method('hasAttribute')->willReturnCallback(fn ($key) => isset($attributes[$key]));

        return $token;
    }

    private function createVoter(bool $isAdmin = false, ?string $sessionRole = null, bool $hasRequest = true): ContentManagerVoter
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(SecurityConstants::ROLE_ADMIN)->willReturn($isAdmin);

        $requestStack = $this->createMock(RequestStack::class);

        if ($hasRequest) {
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

        return new ContentManagerVoter($security, $requestStack);
    }

    public function testDeniesWhenAnonymous(): void
    {
        $voter = $this->createVoter();
        $token = $this->getToken(null);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_CONTENT_MANAGER]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenAdmin(): void
    {
        $voter = $this->createVoter(isAdmin: true);
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_CONTENT_MANAGER]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenTokenHasContentManagerRole(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $token = $this->getToken($user, [SecurityConstants::SESSION_USER_ROLE_KEY => SecurityConstants::ROLE_CONTENT_MANAGER]);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_CONTENT_MANAGER]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenSessionHasContentManagerRole(): void
    {
        $voter = $this->createVoter(sessionRole: SecurityConstants::ROLE_CONTENT_MANAGER);
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_CONTENT_MANAGER]);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRequest(): void
    {
        $voter = $this->createVoter(hasRequest: false);
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_CONTENT_MANAGER]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenSessionHasOtherRole(): void
    {
        $voter = $this->createVoter(sessionRole: SecurityConstants::ROLE_ADMIN);
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, null, [SecurityConstants::ROLE_CONTENT_MANAGER]);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = $this->createVoter();
        $user = $this->createMock(User::class);
        $token = $this->getToken($user);

        $res = $voter->vote($token, null, ['SOME_OTHER_ROLE']);
        $this->assertSame(Voter::ACCESS_ABSTAIN, $res);
    }
}
