<?php

header("Cache-Control: max-age=10");
header("Content-Type: text/xml");

//_________________________________________________ DEFINITION DES DOSSIERS
define ('DS', DIRECTORY_SEPARATOR );
define ('ROOT', dirname(__FILE__).DS);                          // Racine
include (ROOT.'app'.DS.'includes.php');

//_________________________________________________ RSS
include 'includes/FeedWriter.php';

// LISTE DES COMMISSIONS PUBLIQUES
include SCRIPTS.'connect_mysqli.php';
$req="SELECT * FROM caf_commission WHERE vis_commission=1 ORDER BY ordre_commission ASC";
$handleSql=$mysqli->query($req);
$comTab=array();
$comCodeTab=array();
while($handle=$handleSql->fetch_assoc()){
        // v2 :
        $comTab[$handle['code_commission']]=$handle;

        // définition de la variable de page 'current_commission' si elle est précisée dans l'URL
        if($p2 == $handle['code_commission']){
                // $_SESSION['current_commission=$p2;
                $current_commission=$p2;
        }
        // variable de commission si elle est passée "en force" dans les vars GET
        elseif($_GET['commission'] == $handle['code_commission']){
                $current_commission=$_GET['commission'];
        }
}

//_________________________________________________ PARAMS XML
$rss_limit=30;
$entryTab=array();

// CONSTRUCTION DES REQUETES

// *** SORTIES
if(preg_match("#^sorties#", $_GET['mode'])){

        $rss_datas['description']="Sorties du ".$p_sitename;

        // COMMISSION PRECISEE
        if(preg_match("#^sorties-[a-z-]*$#", $_GET['mode'])){
                $givencom = strtolower(substr(strchr($_GET['mode'], '-'), 1));
                if($comTab[$givencom]){
                        $current_commission = $givencom;
                        $rss_datas['title'] = $p_sitename.", sorties «".$current_commission."»";
                }
        }

        // TOUTES COMMISSIONS CONFONDUES
        if(!$current_commission){
                $rss_datas['title'] = "Sorties du ".$p_sitename;
        }

        // REQ
        $req="SELECT *
                FROM  `caf_evt`
                WHERE  `status_evt` =1
                AND tsp_evt > ".time()."
                "
                // commission donnée : filtre
                .($current_commission?" AND commission_evt = ".intval($comTab[$current_commission]['id_commission'])." ":'')
                ."ORDER BY  `tsp_evt` ASC
                LIMIT $rss_limit";

        error_log ("### req=[".$req."]");
        $handleSql = $mysqli->query($req);
        while($handle=$handleSql->fetch_assoc()){
                // info de la commission liée
                if($handle['commission_evt']>0){
                        $req="SELECT * FROM caf_commission
                                WHERE id_commission = ".intval($handle['commission_evt'])."
                                LIMIT 1";
                        $handleSql2 = $mysqli->query($req);
                        while($handle2=$handleSql2->fetch_assoc()){
                                $handle['commission'] = $handle2;
                        }
                }

                $entry['title'] = $handle['titre_evt'];
                $entry['link'] = $p_racine.'sortie/'.$handle['code_evt'].'-'.$handle['id_evt'].'.html';
                $entry['description'] = '';
                /**/if($current_commission)                     $entry['description'] .= ($entry['description']?' - ':'').$current_commission;
                /**/if($handle['massif_evt'])                   $entry['description'] .= ($entry['description']?' - ':'').$handle['massif_evt'];
                /**/if($handle['difficulte_evt'])               $entry['description'] .= ($entry['description']?' - ':'').$handle['difficulte_evt'];
                $entry['timestamp']=$handle['tsp_evt'];

                $entry['img'] = false;

                $entryTab[] = $entry;
        }
}


$mysqli->close();

//Creating an instance of FeedWriter class.
//The constant RSS2 is passed to mention the version
$CafFeed = new FeedWriter(RSS2);

//Setting the channel elements
//Use wrapper functions for common channel elements
$CafFeed->setTitle($rss_datas['title']);
$CafFeed->setLink($p_racine);
$CafFeed->setDescription($rss_datas['description']);

//Image title and link must match with the 'title' and 'link' channel elements for RSS 2.0
//$CafFeed->setImage('Testing the RSS writer class','http://www.ajaxray.com/projects/rss','http://www.rightbrainsolution.com/images/logo.gif');

//Use core setChannelElement() function for other optional channels
$CafFeed->setChannelElement('language', 'fr-fr');
$CafFeed->setChannelElement('pubDate', date(DATE_RSS, time()));

//Adding a feed. Genarally this portion will be in a loop and add all feeds.

foreach($entryTab as $entry){
        $entry['description'] = str_replace ('href="/', 'href="'.$p_racine, $entry['description']);
        $entry['description'] = str_replace ('"ftp/', '"'.$p_racine.'ftp/', $entry['description']);
        $entry['description'] = str_replace ('"IMG/', '"'.$p_racine.'IMG/', $entry['description']);

        //Create an empty FeedItem
        $newItem = $CafFeed->createNewItem();


        //Add elements to the feed item
        //Use wrapper functions to add common feed elements
        $newItem->setTitle($entry['title']);
        $newItem->setLink($entry['link']);
        //The parameter is a timestamp for setDate() function
// debut modif Daniel
        //$newItem->setDate($entry['timestamp']?$entry['timestamp']:time());
        //$newItem->addElement('pubDate', 'Sat, 01 Jan 2016');
        {
        static $jours = array (
                "Mon"=>"Lun", "Tue"=>"Mar", "Wed"=>"Mer", "Thu"=>"Jeu",
                "Fri"=>"Ven", "Sat"=>"Sam", "Sun"=>"Dim");

        static $mois = array (
                "Jan"=>"Jan", "Feb"=>"Fev", "Mar"=>"Mar", "Apr"=>"Avr",
                "May"=>"Mai", "Jun"=>"Jui", "Jul"=>"Jui", "Aug"=>"Aou",
                "Sep"=>"Sep", "Oct"=>"Oct", "Nov"=>"Nov", "Dec"=>"Dec");

        // date "Sat, 06 Feb 2016 06:30:00 +0100"
        $strDate = date(DATE_RSS, $entry['timestamp']?$entry['timestamp']:time());
        $tvDate="";
        if (preg_match("/(\w+), *(\d\d) *(\w+) (\d\d\d\d)/",
                $strDate,
                $matches)) {
                $tvDate=$jours[$matches[1]]." ".$matches[2]." ".$mois[$matches[3]]." ";
                $newItem->addElement('wfw:tvDate', $tvDate);
                }
        }
        $newItem->addElement('pubDate', $matches[1].", ".$matches[2]." ".$matches[3]." ".$matches[4]);
        error_log ("### strDate=[".$strDate."]");
        error_log ("### trDate=[".$tvDate."]");
        $newItem->setDescription($tvDate." ".$entry['description']);
// fin modif Daniel
        $newItem->addElement('guid', $entry['link'], array('isPermaLink'=>'true'));

        //Now add the feed item
        $CafFeed->addItem($newItem);

}
//OK. Everything is done. Now genarate the feed.
$CafFeed->genarateFeed();



?>

