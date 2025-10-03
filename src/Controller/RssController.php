<?php

namespace App\Controller;

use App\Legacy\LegacyContainer;
use App\Legacy\Rss\FeedWriter;
use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RssController extends AbstractController
{
    private string $sitename;

    public function __construct(string $sitename)
    {
        $this->sitename = $sitename;
    }

    #[Route(name: 'rss', path: '/rss.xml', methods: ['GET'])]
    public function rssAction(
        Request $request,
        CommissionRepository $commissionRepository,
        ArticleRepository $articleRepository,
        EvtRepository $evtRepository,
        CacheManager $cacheManager
    ) {
        $comTab = [];

        $rssLimit = 30;
        $entryTab = [];
        $currentCommission = $rssData = null;

        $mode = $request->query->get('mode');

        if (!$mode || preg_match('#^articles#', $mode)) {
            $rssData['description'] = sprintf('Articles du %s', $this->sitename);
            $commission = null;

            if (preg_match('#^articles-[a-zA-Z-]+$#', $mode)) {
                $commissionCode = strtolower(substr(strstr($mode, '-'), 1));
                $commission = $commissionRepository->findVisibleCommission($commissionCode);
                if ($commission) {
                    $rssData['title'] = $this->sitename . ', articles «' . $commission->getTitle() . '»';
                }
            }

            if (!$commission) {
                $rssData['title'] = 'Articles du ' . $this->sitename;
            }

            foreach ($articleRepository->getArticles($commission, ['limit' => $rssLimit]) as $article) {
                $entry['title'] = $article->getTitre();
                $entry['link'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'article/' . $article->getCode() . '-' . $article->getId() . '.html';
                $entry['description'] = $article->getCont();
                $entry['timestamp'] = $article->getCreatedAt()->getTimestamp();

                if ($article->getMediaUpload()) {
                    $entry['img'] = $cacheManager->getBrowserPath('uploads/files/' . $article->getMediaUpload()->getFilename(), 'wide_thumbnail');
                }

                $entryTab[] = $entry;
            }
        } elseif (preg_match('#^sorties#', $mode)) {
            $rssData['description'] = sprintf('Sorties du %s', $this->sitename);
            $commission = null;

            if (preg_match('#^sorties-[a-zA-Z-]+$#', $mode)) {
                $commissionCode = strtolower(substr(strstr($mode, '-'), 1));
                $commission = $commissionRepository->findVisibleCommission($commissionCode);
                if ($commission) {
                    $rssData['title'] = $this->sitename . ', sorties «' . $commission->getTitle() . '»';
                }
            }

            if (!$commission) {
                $rssData['title'] = sprintf('Sorties du %s', $this->sitename);
            }

            foreach ($evtRepository->getUpcomingEvents($commission, ['limit' => $rssLimit]) as $event) {
                $entry['title'] = $event->getTitre();
                $entry['link'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $event->getCode() . '-' . $event->getId() . '.html';
                $entry['description'] = '';
                if ($event->getCommission()) {
                    $entry['description'] .= 'Commission ' . $event->getCommission()->getTitle();
                }
                if ($event->getMassif()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '') . 'massif : ' . $event->getMassif();
                }
                if ($event->getPlace()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '') . 'lieu départ activité : ' . $event->getPlace();
                }
                if ($event->getTarif()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '') . 'tarif : ' . $event->getTarif();
                }
                if ($event->getDifficulte()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '') . 'difficulté : ' . $event->getDifficulte();
                }
                if ($event->getNeedBenevoles()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '') . 'bénévoles appréciés';
                }
                $entry['timestamp'] = $event->getEventStartDate()->getTimestamp();
                $entry['img'] = false;

                $entryTab[] = $entry;
            }
        }

        $cafFeed = new FeedWriter(FeedWriter::RSS2);

        $cafFeed->setTitle($rssData['title']);
        $cafFeed->setLink(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $cafFeed->setDescription($rssData['description']);

        $cafFeed->setChannelElement('language', 'fr-fr');
        $cafFeed->setChannelElement('pubDate', date(\DATE_RSS, time()));

        foreach ($entryTab as $entry) {
            $entry['description'] = str_replace('href="/', 'href="' . LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL), $entry['description']);
            $entry['description'] = str_replace('"ftp/', '"' . LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'ftp/', $entry['description']);
            $entry['description'] = str_replace('"IMG/', '"' . LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'IMG/', $entry['description']);

            $newItem = $cafFeed->createNewItem();

            $newItem->setTitle($entry['title']);
            $newItem->setLink($entry['link']);

            $newItem->setDate($entry['timestamp'] ?: time());
            $newItem->setDescription($entry['description']);
            $newItem->addElement('guid', $entry['link'], ['isPermaLink' => 'true']);

            $cafFeed->addItem($newItem);
        }

        return new Response($cafFeed->generateFeed(), 200, [
            'Cache-Control' => 'max-age=10',
            'Content-Type' => 'text/xml',
        ]);
    }
}
