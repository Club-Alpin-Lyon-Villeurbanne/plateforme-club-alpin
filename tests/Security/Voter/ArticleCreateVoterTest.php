<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\Voter\ArticleCreateVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleCreateVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleCreateVoter($userRights);

        $res = $voter->vote($this->getToken(null), null, ['ARTICLE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenRightAllowed(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_create')->willReturn(true);
        $voter = new ArticleCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['ARTICLE_CREATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenRightNotAllowed(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_create')->willReturn(false);
        $voter = new ArticleCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['ARTICLE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }
}
