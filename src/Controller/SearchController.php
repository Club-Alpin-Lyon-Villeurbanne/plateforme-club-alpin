<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\Repository\PageRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/recherche.html', name: 'search_result', methods: ['POST'], priority: 10)]
    public function index(
        Request $request,
        CommissionRepository $commissionRepository,
        ArticleRepository $articleRepository,
        EvtRepository $eventRepository,
        PageRepository $pageRepository,
    ): Response {
        $articlesTab = [];
        $evtTab = [];
        $freePagesTab = [];
        $errTab = [];
        $safeStr = '';
        $totalArticles = 0;
        $totalEvt = 0;
        $totalFreePages = 0;
        $commission = null;

        if (!$this->isCsrfTokenValid('search', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $searchResultsPerPage = $this->getParameter('search_results_per_page');
        $searchParam = trim($request->request->get('str'));

        // vérification des caractères
        if (empty($searchParam) || mb_strlen($searchParam) < 3) {
            $errTab[] = 'Votre recherche doit comporter au moins 3 caractères.';
        }

        if (empty($errTab)) {
            $safeStr = mb_substr($searchParam, 0, 80);
            $commissionCode = $request->request->get('commission');
            $commission = $commissionRepository->findVisibleCommission($commissionCode);

            // RECH ARTICLES
            $articlesTab = $articleRepository->searchArticles($safeStr, $searchResultsPerPage, $commission);
            $totalArticles = $articleRepository->getSearchArticlesCount($safeStr, $commission);

            // RECH SORTIES
            $evtTab = $eventRepository->searchEvents($safeStr, $searchResultsPerPage, $commission);
            $totalEvt = $eventRepository->getSearchEventsCount($safeStr, $commission);

            // RECH PAGES LIBRES
            $freePagesTab = $pageRepository->searchPages($safeStr, $searchResultsPerPage);
            $totalFreePages = $pageRepository->getSearchPagesCount($safeStr);
        }

        return $this->render('search/index.html.twig', [
            'safeStr' => $safeStr,
            'articlesTab' => $articlesTab,
            'evtTab' => $evtTab,
            'freePagesTab' => $freePagesTab,
            'errTab' => $errTab,
            'totalArticles' => $totalArticles,
            'totalEvt' => $totalEvt,
            'totalFreePages' => $totalFreePages,
            'commission' => $commission,
        ]);
    }
}
