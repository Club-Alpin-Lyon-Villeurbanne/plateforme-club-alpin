<?php

namespace App\Tests\Security\Voter;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\ArticleViewVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleViewVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testGrantsWhenOwner(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($user);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenCommissionReadAllowed(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturnCallback(static function ($code) {
            return 'article_read' === $code;
        });
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsViaArticleReadRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'article_read' === $code);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenNotPublicAndNoValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturn(false);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner, $commission, null, false);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeniesWhenNoRights(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturn(false);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleViewVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_VIEW']);
    }

    public function testGrantsForAnonymousWhenPublicWithReadRight(): void
    {
        $owner = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'article_read' === $code);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUsesEvtCommissionFallback(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $evt = $this->createMock(Evt::class);
        $evt->method('getCommission')->willReturn($commission);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturnCallback(
            static fn ($code) => 'article_read' === $code
        );
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner, null, $evt);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsViaCommissionValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturnCallback(
            static fn ($code) => 'article_validate' === $code
        );
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsViaValidateAllRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'article_validate_all' === $code);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenNotPublicButHasValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowedOnCommission')->willReturnCallback(
            static fn ($code) => 'article_validate' === $code
        );
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner, $commission, null, false);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    private function createArticle(?User $author = null, ?Commission $commission = null, ?Evt $evt = null, bool $public = true): Article
    {
        $article = $this->createMock(Article::class);
        $article->method('getUser')->willReturn($author);
        $article->method('getCommission')->willReturn($commission);
        $article->method('getEvt')->willReturn($evt);
        $article->method('isPublic')->willReturn($public);

        return $article;
    }
}
