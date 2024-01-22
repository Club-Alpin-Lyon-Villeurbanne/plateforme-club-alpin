<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

header('Cache-Control: max-age=10');
header('Content-Type: text/xml');

require __DIR__.'/app/includes.php';

// _________________________________________________ RSS
require_once __DIR__.'/includes/FeedWriter.php';
require_once __DIR__.'/includes/FeedItem.php';

// LISTE DES COMMISSIONS PUBLIQUES
$req = 'SELECT * FROM caf_commission WHERE vis_commission=1 ORDER BY ordre_commission ASC';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$comTab = [];
$comCodeTab = [];
while ($handle = $handleSql->fetch_assoc()) {
    // v2 :
    $comTab[$handle['code_commission']] = $handle;

    // définition de la variable de page 'current_commission' si elle est précisée dans l'URL
    if ($p2 == $handle['code_commission']) {
        $current_commission = $p2;
    }
    // variable de commission si elle est passée "en force" dans les vars GET
    elseif (($_GET['commission'] ?? null) == $handle['code_commission']) {
        $current_commission = $_GET['commission'];
    }
}

// _________________________________________________ PARAMS XML
$rss_limit = 30;
$entryTab = [];
$current_commission = $rss_datas = null;

// CONSTRUCTION DES REQUETES

// *** ARTICLES, par defaut
if (!array_key_exists('mode', $_GET) || preg_match('#^articles#', $_GET['mode'])) {
    $rss_datas['description'] = 'Articles du '.$p_sitename;

    // COMMISSION PRECISEE
    if (array_key_exists('mode', $_GET) && preg_match('#^articles-[a-z-]*$#', $_GET['mode'])) {
        $givencom = strtolower(substr(strstr($_GET['mode'], '-'), 1));
        if ($comTab[$givencom]) {
            $current_commission = $givencom;
            $rss_datas['title'] = $p_sitename.', articles «'.$current_commission.'»';
        }
    }

    // TOUTES COMMISSIONS CONFONDUES
    if (!$current_commission) {
        $rss_datas['title'] = 'Articles du '.$p_sitename;
    }

    // REQ
    $req = 'SELECT *
		FROM  `caf_article`
		WHERE  `status_article` =1 '
        // commission donnée : filtre (mais on inclut les actus club, commission=0)
        .($current_commission ? ' AND (commission_article = '.(int) $comTab[$current_commission]['id_commission'].' OR commission_article = 0) ' : '')
        ."ORDER BY  `tsp_validate_article` DESC
		LIMIT $rss_limit";

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_assoc()) {
        // info de la commission liée
        if ($handle['commission_article'] > 0) {
            $req = 'SELECT * FROM caf_commission
				WHERE id_commission = '.(int) $handle['commission_article'].'
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_assoc()) {
                $handle['commission'] = $handle2;
            }
        }

        $entry['title'] = $handle['titre_article'];
        $entry['link'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'article/'.$handle['code_article'].'-'.$handle['id_article'].'.html';
        $entry['description'] = $handle['cont_article'];
        $entry['timestamp'] = $handle['tsp_article'];

        // check image
        if (is_file(__DIR__.'/../public/ftp/articles/'.(int) $handle['id_article'].'/wide-figure.jpg')) {
            $entry['img'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'ftp/articles/'.(int) $handle['id_article'].'/wide-figure.jpg';
        }

        $entryTab[] = $entry;
    }
}

// *** SORTIES
if (array_key_exists('mode', $_GET) && preg_match('#^sorties#', $_GET['mode'])) {
    $rss_datas['description'] = 'Sorties du '.$p_sitename;

    // COMMISSION PRECISEE
    if (preg_match('#^sorties-[a-z-]*$#', $_GET['mode'])) {
        $givencom = strtolower(substr(strstr($_GET['mode'], '-'), 1));
        if ($comTab[$givencom]) {
            $current_commission = $givencom;
            $rss_datas['title'] = $p_sitename.', sorties «'.$current_commission.'»';
        }
    }

    // TOUTES COMMISSIONS CONFONDUES
    if (!$current_commission) {
        $rss_datas['title'] = 'Sorties du '.$p_sitename;
    }

    // REQ
    $req = 'SELECT *
		FROM  `caf_evt`
		WHERE  `status_evt` =1
		AND tsp_evt > '.time().'
		'
        // commission donnée : filtre
        .($current_commission ? ' AND commission_evt = '.(int) $comTab[$current_commission]['id_commission'].' ' : '')
        ."ORDER BY  `tsp_evt` ASC
		LIMIT $rss_limit";

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_assoc()) {
        // info de la commission liée
        if ($handle['commission_evt'] > 0) {
            $req = 'SELECT * FROM caf_commission
				WHERE id_commission = '.(int) $handle['commission_evt'].'
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_assoc()) {
                $handle['commission'] = $handle2;
            }
        }

        $entry['title'] = $handle['titre_evt'];
        $entry['link'] = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].'.html';
        $entry['description'] = '';
        if ($current_commission) {
            $entry['description'] .= ($entry['description'] ? ' | ' : '').'Commission '.$current_commission;
        }
        if ($handle['massif_evt']) {
            $entry['description'] .= ($entry['description'] ? ' | ' : '').'massif : '.$handle['massif_evt'];
        }
        if ($handle['tarif_evt']) {
            $entry['description'] .= ($entry['description'] ? ' | ' : '').'tarif : '.$handle['tarif_evt'];
        }
        if ($handle['difficulte_evt']) {
            $entry['description'] .= ($entry['description'] ? ' | ' : '').'difficulté : '.$handle['difficulte_evt'];
        }
        if ($handle['need_benevoles_evt']) {
            $entry['description'] .= ($entry['description'] ? ' | ' : '').'bénévoles appréciés';
        }
        $entry['timestamp'] = $handle['tsp_evt'];

        $entry['img'] = false;

        $entryTab[] = $entry;
    }
}

// Creating an instance of FeedWriter class.
// The constant RSS2 is passed to mention the version
$CafFeed = new FeedWriter(RSS2);

// Setting the channel elements
// Use wrapper functions for common channel elements
$CafFeed->setTitle(!empty($rss_datas) ? $rss_datas['title'] : '');
$CafFeed->setLink(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL));
$CafFeed->setDescription(!empty($rss_datas) ? $rss_datas['description'] : '');

// Image title and link must match with the 'title' and 'link' channel elements for RSS 2.0
// $CafFeed->setImage('Testing the RSS writer class','https://www.ajaxray.com/projects/rss','https://www.rightbrainsolution.com/images/logo.gif');

// Use core setChannelElement() function for other optional channels
$CafFeed->setChannelElement('language', 'fr-fr');
$CafFeed->setChannelElement('pubDate', date(\DATE_RSS, time()));

// Adding a feed. Genarally this portion will be in a loop and add all feeds.

foreach ($entryTab as $entry) {
    $entry['description'] = str_replace('href="/', 'href="'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL), $entry['description']);
    $entry['description'] = str_replace('"ftp/', '"'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'ftp/', $entry['description']);
    $entry['description'] = str_replace('"IMG/', '"'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'IMG/', $entry['description']);

    // Create an empty FeedItem
    $newItem = $CafFeed->createNewItem();

    // Add elements to the feed item
    // Use wrapper functions to add common feed elements
    $newItem->setTitle($entry['title']);
    $newItem->setLink($entry['link']);
    // The parameter is a timestamp for setDate() function
    $newItem->setDate($entry['timestamp'] ?: time());
    $newItem->setDescription($entry['description']);
    $newItem->addElement('guid', $entry['link'], ['isPermaLink' => 'true']);

    // Now add the feed item
    $CafFeed->addItem($newItem);
}
// OK. Everything is done. Now genarate the feed.
$CafFeed->generateFeed();
