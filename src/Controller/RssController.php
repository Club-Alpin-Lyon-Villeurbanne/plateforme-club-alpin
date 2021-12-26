<?php

namespace App\Controller;

use App\Legacy\LegacyContainer;
use App\Legacy\Rss\FeedWriter;
use App\Repository\ArticleRepository;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
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

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            CommissionRepository::class,
            ArticleRepository::class,
            EvtRepository::class,
        ]);
    }

    /**
     * @Route(
     *     name="rss",
     *     path="/rss.xml",
     *     methods={"GET"}
     * )
     */
    public function rssAction(Request $request)
    {
        $comTab = [];

        $rss_limit = 30;
        $entryTab = [];
        $current_commission = $rss_datas = null;

        $mode = $request->query->get('mode');

        if (preg_match('#^articles#', $mode)) {
            $rss_datas['description'] = sprintf('Articles du %s', $this->sitename);
            $commission = null;

            if (preg_match('#^articles-[a-zA-Z-]+$#', $mode)) {
                $commissionCode = strtolower(substr(strstr($mode, '-'), 1));
                $commission = $this->get(CommissionRepository::class)->findVisibleCommission($commissionCode);
                if ($commission) {
                    $rss_datas['title'] = $this->sitename.', articles «'.$commission->getTitle().'»';
                }
            }

            if (!$commission) {
                $rss_datas['title'] = 'Articles du '.$this->sitename;
            }

            foreach ($this->get(ArticleRepository::class)->getArticles($rss_limit, $commission) as $article) {
                $entry['title'] = $article->getTitre();
                $entry['link'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'article/'.$article->getCode().'-'.$article->getId().'.html';
                $entry['description'] = $article->getCont();
                $entry['timestamp'] = $article->getTsp();

                if (is_file(__DIR__.'/../public/ftp/articles/'.$article->getId().'/wide-figure.jpg')) {
                    $entry['img'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'ftp/articles/'.$article->getId().'/wide-figure.jpg';
                }

                $entryTab[] = $entry;
            }
        } elseif (preg_match('#^sorties#', $mode)) {
            $rss_datas['description'] = sprintf('Sorties du %s', $this->sitename);
            $commission = null;

            if (preg_match('#^sorties-[a-zA-Z-]+$#', $mode)) {
                $commissionCode = strtolower(substr(strstr($mode, '-'), 1));
                $commission = $this->get(CommissionRepository::class)->findVisibleCommission($commissionCode);
                if ($commission) {
                    $rss_datas['title'] = $this->sitename.', sorties «'.$commission->getTitle().'»';
                }
            }

            if (!$commission) {
                $rss_datas['title'] = sprintf('Sorties du %s', $this->sitename);
            }

            foreach ($this->get(EvtRepository::class)->getEvents($rss_limit, $commission) as $event) {
                $entry['title'] = $event->getTitre();
                $entry['link'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$event->getCode().'-'.$event->getId().'.html';
                $entry['description'] = '';
                if ($event->getCommission()) {
                    $entry['description'] .= 'Commission '.$event->getCommission()->getTitle();
                }
                if ($event->getMassif()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '').'massif : '.$event->getMassif();
                }
                if ($event->getTarif()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '').'tarif : '.$event->getTarif();
                }
                if ($event->getDifficulte()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '').'difficulté : '.$event->getDifficulte();
                }
                if ($event->getNeedBenevoles()) {
                    $entry['description'] .= ('' !== $entry['description'] ? ' | ' : '').'bénévoles appréciés';
                }
                $entry['timestamp'] = $event->getTsp();
                $entry['img'] = false;

                $entryTab[] = $entry;
            }
        }

        $CafFeed = new FeedWriter(FeedWriter::RSS2);

        $CafFeed->setTitle($rss_datas['title']);
        $CafFeed->setLink(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $CafFeed->setDescription($rss_datas['description']);

        $CafFeed->setChannelElement('language', 'fr-fr');
        $CafFeed->setChannelElement('pubDate', date(\DATE_RSS, time()));

        foreach ($entryTab as $entry) {
            $entry['description'] = str_replace('href="/', 'href="'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL), $entry['description']);
            $entry['description'] = str_replace('"ftp/', '"'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'ftp/', $entry['description']);
            $entry['description'] = str_replace('"IMG/', '"'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'IMG/', $entry['description']);

            $newItem = $CafFeed->createNewItem();

            $newItem->setTitle($entry['title']);
            $newItem->setLink($entry['link']);

            $newItem->setDate($entry['timestamp'] ?: time());
            $newItem->setDescription($entry['description']);
            $newItem->addElement('guid', $entry['link'], ['isPermaLink' => 'true']);

            $CafFeed->addItem($newItem);
        }

        return new Response($CafFeed->generateFeed(), 200, [
            'Cache-Control' => 'max-age=10',
            'Content-Type' => 'text/xml',
        ]);
    }
}
