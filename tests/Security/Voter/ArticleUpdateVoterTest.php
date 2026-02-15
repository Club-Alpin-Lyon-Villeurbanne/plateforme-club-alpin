<?php

namespace App\Tests\Security\Voter;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\ArticleUpdateVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleUpdateVoterTest extends TestCase
{
    use VoterTestHelperTrait;

    public function testDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleUpdateVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_UPDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testGrantsWhenOwnerWithRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_edit')->willReturn(true);
        $voter = new ArticleUpdateVoter($userRights);

        $article = $this->createArticle($user, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UPDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testGrantsWhenNotOwnerButCommissionRight(): void
    {
        $owner = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_edit_notmine', $commission)->willReturn(true);
        $voter = new ArticleUpdateVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($currentUser), $article, ['ARTICLE_UPDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUsesEvtCommissionWhenArticleHasNoCommission(): void
    {
        $owner = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $evt = $this->createMock(Evt::class);
        $evt->method('getCommission')->willReturn($commission);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_edit_notmine', $commission)->willReturn(true);
        $voter = new ArticleUpdateVoter($userRights);

        $article = $this->createArticle($owner, null, $evt);
        $res = $voter->vote($this->getToken($currentUser), $article, ['ARTICLE_UPDATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeniesWhenOwnerWithoutRightAndNoCommissionRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new ArticleUpdateVoter($userRights);

        $article = $this->createArticle($user, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UPDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleUpdateVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_UPDATE']);
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
