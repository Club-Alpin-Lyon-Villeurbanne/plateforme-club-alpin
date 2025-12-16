<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commission;
use App\Repository\ArticleRepository;
use App\Trait\PaginationControllerTrait;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{
    use PaginationControllerTrait;

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
        $total = $articleRepository->getArticlesByCommissionCount($commission);
        $paginationParams = $this->getPaginationParams($request, $total, $limit);

        $articles = $articleRepository->getArticles($paginationParams['first'], $paginationParams['per_page'], $commission);
        $sliderArticles = $articleRepository->findBy(['une' => true, 'status' => Article::STATUS_PUBLISHED], ['updatedAt' => 'DESC'], 5);

        return array_merge([
            'slider_articles' => $sliderArticles,
            'articles' => $articles,
            'sitename' => $this->getParameter('sitename'),
            'current_commission' => $commission,
            'current_url' => $this->generateUrl(
                $request->attributes->get('_route'),
                $commission ? ['code' => $commission->getCode()] : [],
            ),
        ], $paginationParams);
    }
}
