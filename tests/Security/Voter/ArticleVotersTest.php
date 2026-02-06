<?php

namespace App\Tests\Security\Voter;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Security\Voter\ArticleCreateVoter;
use App\Security\Voter\ArticleDeleteVoter;
use App\Security\Voter\ArticleManageVoter;
use App\Security\Voter\ArticleUnpublishVoter;
use App\Security\Voter\ArticleUpdateVoter;
use App\Security\Voter\ArticleViewVoter;
use App\UserRights;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleVotersTest extends TestCase
{
    private function getToken($user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
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

    // ==========================================
    // ArticleCreateVoter
    // ==========================================

    public function testCreateDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleCreateVoter($userRights);

        $res = $voter->vote($this->getToken(null), null, ['ARTICLE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testCreateGrantsWhenRightAllowed(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_create')->willReturn(true);
        $voter = new ArticleCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['ARTICLE_CREATE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testCreateDeniesWhenRightNotAllowed(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_create')->willReturn(false);
        $voter = new ArticleCreateVoter($userRights);

        $user = $this->createMock(User::class);
        $res = $voter->vote($this->getToken($user), null, ['ARTICLE_CREATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    // ==========================================
    // ArticleUpdateVoter
    // ==========================================

    public function testUpdateDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleUpdateVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_UPDATE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUpdateGrantsWhenOwnerWithRight(): void
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

    public function testUpdateGrantsWhenNotOwnerButCommissionRight(): void
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

    public function testUpdateUsesEvtCommissionWhenArticleHasNoCommission(): void
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

    public function testUpdateDeniesWhenOwnerWithoutRightAndNoCommissionRight(): void
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

    public function testUpdateThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleUpdateVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_UPDATE']);
    }

    // ==========================================
    // ArticleViewVoter
    // ==========================================

    public function testViewGrantsWhenOwner(): void
    {
        $user = $this->createMock(User::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($user);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testViewGrantsWhenCommissionReadAllowed(): void
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

    public function testViewGrantsViaArticleReadRight(): void
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

    public function testViewDeniesWhenNotPublicAndNoValidateRight(): void
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

    public function testViewDeniesWhenNoRights(): void
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

    public function testViewThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleViewVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_VIEW']);
    }

    public function testViewGrantsForAnonymousWhenPublicWithReadRight(): void
    {
        $owner = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'article_read' === $code);
        $voter = new ArticleViewVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testViewUsesEvtCommissionFallback(): void
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

    public function testViewGrantsViaCommissionValidateRight(): void
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

    public function testViewGrantsViaValidateAllRight(): void
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

    public function testViewGrantsWhenNotPublicButHasValidateRight(): void
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

    // ==========================================
    // ArticleDeleteVoter
    // ==========================================

    public function testDeleteDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleDeleteVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteGrantsWhenOwnerWithRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_delete')->willReturn(true);
        $voter = new ArticleDeleteVoter($userRights);

        $article = $this->createArticle($user, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_DELETE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeleteGrantsWhenNotOwnerButCommissionRight(): void
    {
        $owner = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_delete_notmine', $commission)->willReturn(true);
        $voter = new ArticleDeleteVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($currentUser), $article, ['ARTICLE_DELETE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeleteDeniesWhenNoCommissionAndNotOwnerWithRight(): void
    {
        $owner = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new ArticleDeleteVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken($currentUser), $article, ['ARTICLE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteDeniesWhenOwnerWithoutRight(): void
    {
        $user = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new ArticleDeleteVoter($userRights);

        $article = $this->createArticle($user, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_DELETE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testDeleteUsesEvtCommissionFallback(): void
    {
        $owner = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $evt = $this->createMock(Evt::class);
        $evt->method('getCommission')->willReturn($commission);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_delete_notmine', $commission)->willReturn(true);
        $voter = new ArticleDeleteVoter($userRights);

        $article = $this->createArticle($owner, null, $evt);
        $res = $voter->vote($this->getToken($currentUser), $article, ['ARTICLE_DELETE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testDeleteThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleDeleteVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_DELETE']);
    }

    // ==========================================
    // ArticleManageVoter
    // ==========================================

    public function testManageDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testManageGrantsWhenCommissionValidateRight(): void
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

    public function testManageGrantsWhenValidateAllRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->with('article_validate_all')->willReturn(true);
        $voter = new ArticleManageVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_MANAGE']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testManageDeniesWhenNoRights(): void
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

    public function testManageUsesEvtCommissionFallback(): void
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

    public function testManageThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleManageVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_MANAGE']);
    }

    // ==========================================
    // ArticleUnpublishVoter
    // ==========================================

    public function testUnpublishDeniesWhenAnonymous(): void
    {
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle();
        $res = $voter->vote($this->getToken(null), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUnpublishGrantsWhenOwnerWithEditRight(): void
    {
        $user = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'article_edit' === $code);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle($user);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUnpublishGrantsWhenCommissionValidateRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_validate', $commission)->willReturn(true);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUnpublishGrantsWhenValidateAllRight(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturnCallback(static fn ($code) => 'article_validate_all' === $code);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUnpublishDeniesWhenNoCommissionAndNotOwner(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle($owner);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUnpublishDeniesWhenNoRights(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->willReturn(false);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle($owner, $commission);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_DENIED, $res);
    }

    public function testUnpublishUsesEvtCommissionFallback(): void
    {
        $user = $this->createMock(User::class);
        $owner = $this->createMock(User::class);
        $commission = $this->createMock(Commission::class);
        $evt = $this->createMock(Evt::class);
        $evt->method('getCommission')->willReturn($commission);

        $userRights = $this->createMock(UserRights::class);
        $userRights->method('allowed')->willReturn(false);
        $userRights->method('allowedOnCommission')->with('article_validate', $commission)->willReturn(true);
        $voter = new ArticleUnpublishVoter($userRights);

        $article = $this->createArticle($owner, null, $evt);
        $res = $voter->vote($this->getToken($user), $article, ['ARTICLE_UNPUBLISH']);
        $this->assertSame(Voter::ACCESS_GRANTED, $res);
    }

    public function testUnpublishThrowsOnInvalidSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $userRights = $this->createMock(UserRights::class);
        $voter = new ArticleUnpublishVoter($userRights);

        $user = $this->createMock(User::class);
        $voter->vote($this->getToken($user), new \stdClass(), ['ARTICLE_UNPUBLISH']);
    }
}
