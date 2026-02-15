<?php

namespace App\Tests\Security\Voter;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\ArticleManageVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleManageVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenCommissionValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_validate', $commission)->willReturn(true);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle(null, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenValidateAllRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_validate_all')->willReturn(true);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNoRights(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle(null, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUsesEvtCommissionFallback(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $evt = $this->createMock(Evt::class);
        $evt->method('getCommission')->willReturn($commission);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_validate', $commission)->willReturn(true);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle(null, null, $evt);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleManageVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_MANAGE']);
    }

    private function createArticle(?User $author = null, ?Commission $commission = null, ?Evt $evt = null): Article
    {
        $article = $this->createMock(Article::class);
        $article->method('getUser')->willReturn($author);
        $article->method('getCommission')->willReturn($commission);
        $article->method('getEvt')->willReturn($evt);
        $article->method('isPublic')->willReturn(true);

        return $article;
    }
}
