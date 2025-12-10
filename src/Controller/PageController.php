<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commission;
use App\Repository\ArticleRepository;
use App\Service\MetabaseService;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PageController extends AbstractController
{
    #[Route('/stats/metabase', name: 'stats_metabase')]
    #[IsGranted('ROLE_USER')]
    #[Template('pages/stats-metabase.html.twig')]
    public function metabase(MetabaseService $metabase): array
    {
        $iframeUrl = $metabase->generateDashboardUrl(6);

        return [
            'iframeUrl' => $iframeUrl,
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/', name: 'homepage', priority: 100)]
    #[Route('/accueil.html', name: 'homepage_full', priority: 100)]
    #[Route('/accueil/{code}.html', name: 'commission_homepage', priority: 100)]
    #[Template('pages/home.html.twig')]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        ?Commission $commission = null,
    ): array {
        $limit = $this->getParameter('max_home_articles');
        $page = max(1, $request->query->getInt('page', 1));
        $total = $articleRepository->getArticlesByCommissionCount($commission);
        $pages = (int) ceil($total / $limit);
        $first = $limit * ($page - 1);
        $page = min($page, max(1, $pages));

        $articles = $articleRepository->getArticles($commission, $first, $limit);
        $sliderArticles = $articleRepository->findBy(['une' => true, 'status' => Article::STATUS_PUBLISHED], ['updatedAt' => 'DESC'], 5);

        return [
            'slider_articles' => $sliderArticles,
            'articles' => $articles,
            'sitename' => $this->getParameter('sitename'),
            'current_commission' => $commission,
            'total' => $total,
            'per_page' => $limit,
            'pages' => $pages,
            'page' => $page,
            'current_url' => $this->generateUrl(
                $request->attributes->get('_route'),
                $commission ? ['code' => $commission->getCode()] : [],
            ),
        ];
    }
}
