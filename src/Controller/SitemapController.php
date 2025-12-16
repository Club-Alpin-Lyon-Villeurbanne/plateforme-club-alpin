<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Repository\PageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
class SitemapController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly CommissionRepository $commissionRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(path: '/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'https://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xsi', 'https://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xsi:schemaLocation', 'https://www.sitemaps.org/schemas/sitemap/0.9 https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

        $add = function (string $loc) use ($xml, $baseUrl): void {
            if (!str_starts_with($loc, 'http')) {
                $loc = rtrim($baseUrl, '/') . '/' . ltrim($loc, '/');
            }
            $xml->startElement('url');
            $xml->writeElement('loc', $loc);
            $xml->writeElement('changefreq', 'daily');
            $xml->endElement();
        };

        // Accueil
        $add('/');

        // Pages fixes (publiques, non admin) – uniquement celles présentes dans le menu principal
        foreach ($this->pageRepository->findBy(['vis' => true, 'admin' => false, 'superadmin' => false, 'menu' => true], ['ordreMenu' => 'ASC']) as $page) {
            $code = method_exists($page, 'getCode') ? $page->getCode() : null;
            if ($code) {
                $add('/pages/' . $code . '.html');
            }
        }

        // Pages fixes présentes dans le footer (whitelist explicite)
        foreach (['responsables', 'nos-partenaires-prives', 'nos-partenaires-publics', 'mentions-legales'] as $footerCode) {
            $add('/pages/' . $footerCode . '.html');
        }

        // Commissions visibles
        foreach ($this->commissionRepository->findVisible() as $commission) {
            $code = method_exists($commission, 'getCode') ? $commission->getCode() : null;
            if ($code) {
                $url = $this->urlGenerator->generate('commission_homepage', [
                    'code' => $code,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                $add($url);
            }
        }

        // Derniers articles publiés (limite raisonnable)
        foreach ($this->articleRepository->getArticles(0, 200) as $article) {
            if (method_exists($article, 'getCode') && method_exists($article, 'getId')) {
                $url = $this->urlGenerator->generate('article_view', [
                    'code' => $article->getCode(),
                    'id' => $article->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                $add($url);
            }
        }

        $xml->endElement(); // urlset
        $xml->endDocument();

        $content = $xml->outputMemory(true);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
