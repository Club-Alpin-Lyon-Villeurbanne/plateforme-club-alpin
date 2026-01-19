<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commission;
use App\Mailer\Mailer;
use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Trait\PaginationControllerTrait;
use App\UserRights;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ArticleManagementController extends AbstractController
{
    use PaginationControllerTrait;

    public function __construct(
        protected UserRights $userRights,
        protected CommissionRepository $commissionRepository,
        protected ArticleRepository $articleRepository,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(path: '/gestion-des-articles.html', name: 'manage_articles', methods: ['GET'], priority: 10)]
    #[Template('article/gestion-articles.html.twig')]
    public function index(Request $request): array
    {
        $validateAll = $this->userRights->allowed('article_validate_all');
        $validate = $this->userRights->allowed('article_validate');

        if (!$validate && !$validateAll) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        // commissions pour lesquelles on a des droits
        $commissions = [];
        if ($validate && !$validateAll) {
            $commissionCodes = $this->userRights->getCommissionListForRight('article_validate');
            $commissions = $this->commissionRepository->findBy(['code' => $commissionCodes]);
        }

        $total = $this->articleRepository->getUnvalidatedArticlesCount($commissions);
        $limit = $this->getParameter('max_articles_validation');
        $paginationParams = $this->getPaginationParams($request, $total, $limit);

        return array_merge([
            'articles' => $this->articleRepository->getUnvalidatedArticles($commissions, $paginationParams['first'], $paginationParams['per_page']),
        ], $paginationParams);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/article/{id}/publier', name: 'article_validate', requirements: ['id' => '\d+'], methods: ['POST'], priority: '10')]
    public function approve(Request $request, Article $article, EntityManagerInterface $em, Mailer $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('article_validate', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('ARTICLE_MANAGE', $article)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $article
            ->setStatus(Article::STATUS_PUBLISHED)
            ->setStatusWho($this->getUser())
            ->setValidationDate(new \DateTimeImmutable())
        ;
        $em->flush();

        $mailer->send($article->getUser(), 'transactional/article-valide', [
            'article_name' => $article->getTitre(),
            'article_url' => $this->generateUrl('article_view', ['code' => $article->getCode(), 'id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $this->addFlash('info', 'L\'article est publié');

        return $this->redirectToRoute('manage_articles');
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/article/{id}/refuser', name: 'article_refuse', requirements: ['id' => '\d+'], methods: ['POST'], priority: 10)]
    public function refuse(Request $request, Article $article, EntityManagerInterface $em, Mailer $mailer): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('article_refuse', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        if (!$this->isGranted('ARTICLE_MANAGE', $article)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à cela.');
        }

        $article
            ->setStatus(Article::STATUS_REFUSED)
            ->setStatusWho($this->getUser())
            ->setValidationDate(null)       // forcer au cas où il y avait une date
        ;
        $em->flush();

        $mailer->send($article->getUser(), 'transactional/article-refuse', [
            'message' => $request->request->get('msg', '...'),
            'article_name' => $article->getTitre(),
            'article_url' => $this->generateUrl('article_view', ['code' => $article->getCode(), 'id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $this->addFlash('info', 'L\'article est refusé');

        return $this->redirectToRoute('manage_articles');
    }

    protected function getArticleCommission(Article $article): ?Commission
    {
        $commission = $article->getCommission();
        if (!$commission instanceof Commission) {
            $commission = $article->getEvt()->getCommission();
        }

        return $commission;
    }
}
