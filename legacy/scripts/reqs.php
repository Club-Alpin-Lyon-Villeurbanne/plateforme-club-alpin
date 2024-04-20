<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$MAX_TIMESTAMP_FOR_LEGAL_VALIDATION = strtotime(LegacyContainer::getParameter('legacy_env_MAX_TIMESTAMP_FOR_LEGAL_VALIDATION'));
$MAX_ARTICLES_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_ARTICLES_VALIDATION');
$MAX_SORTIES_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_SORTIES_VALIDATION');
$MAX_ARTICLES_ADHERENT = LegacyContainer::getParameter('legacy_env_MAX_ARTICLES_ADHERENT');
$MAX_ARTICLES_ACCUEIL = LegacyContainer::getParameter('legacy_env_MAX_ARTICLES_ACCUEIL');

// vars de notification
$notif_validerunarticle = 0;
$notif_validerunesortie = 0;
$notif_validerunesortie_president = 0;

// commission courante sur cette page
$current_commission = false;

// LISTE DES COMMISSIONS PUBLIQUES
$req = 'SELECT * FROM caf_commission WHERE vis_commission=1 ORDER BY ordre_commission ASC';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$comTab = [];
$comCodeTab = [];
while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
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

// NOTIFICATIONS EVTS
if (allowed('evt_validate_all')) { // pouvoir de valider toutes les sorties de ttes commission confondues
    // compte des sorties à valider
    $req = 'SELECT COUNT(id_evt)
	FROM caf_evt, caf_user
	WHERE status_evt=0
    AND tsp_evt IS NOT NULL
	AND id_user=user_evt '
    .'ORDER BY tsp_crea_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
} elseif (allowed('evt_validate')) { // pouvoir de valider les sorties d'un nombre N de commissions dont nous sommes ersponsable
    // recuperation des commissions sous notre joug
    $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('evt_validate');

    // compte des sorties à valider, selon la (les) commission dont nous sommes responsables
    $req = "SELECT COUNT(id_evt) FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND id_user=user_evt
		AND commission_evt=id_commission
		AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."') " // condition OR pour toutes les commissions autorisées
        .'ORDER BY tsp_crea_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
}

if (allowed('evt_legal_accept')) { // pouvoir de valider "legalement" une sortie comme sortie du caf
    // Pour chaque sortie non validee dans le timing demandé, et publiée
    $req = 'SELECT COUNT(id_evt)
			FROM caf_evt, caf_commission
			WHERE status_legal_evt = 0
			AND status_evt = 1
			AND commission_evt = id_commission
			AND tsp_evt > '.time().'
			AND tsp_evt < '.$MAX_TIMESTAMP_FOR_LEGAL_VALIDATION.'
			ORDER BY tsp_evt ASC ';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunesortie_president = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
}

// NOTIFICATIONS ARTICLES
if (allowed('article_validate_all')) { // pouvoir de valider les articles
    $req = 'SELECT COUNT(id_article) FROM caf_article WHERE status_article=0 AND topubly_article=1';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunarticle = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
} elseif (allowed('article_validate')) { // pouvoir de valider les articles
    // recuperation des commissions sous notre joug
    $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('article_validate');

    $req = "SELECT COUNT(id_article)
	FROM caf_article, caf_commission
	WHERE status_article=0
	AND topubly_article=1
	AND commission_article=id_commission
	AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."')"; // condition OR pour toutes les commissions autorisées

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunarticle = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
}

// PROFIL : infos generales, d'avantage d'info que dans la session seule
if ('profil' == $p1 && 'infos' == $p2 && getUser()) {
    $tmpUser = false;
    $req = 'SELECT * FROM caf_user WHERE id_user='.getUser()->getId().' LIMIT 1';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // filiation : ais-je des "enfants"
        if ('' !== trim($handle['cafnum_user'])) {
            $handle['enfants'] = [];
            $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, birthday_user, email_user, tel_user, cafnum_user FROM caf_user WHERE cafnum_parent_user = '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($handle['cafnum_user'])."' LIMIT 100";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['enfants'][] = $handle2;
            }
        }

        // filiation : ais-je un parent
        if (isset($handle['cafnum_parent_user']) && '' !== trim($handle['cafnum_parent_user'])) {
            $handle['parent'] = [];
            $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, birthday_user, email_user, tel_user, cafnum_user FROM caf_user WHERE cafnum_user = '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($handle['cafnum_parent_user'])."' LIMIT 1";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['parent'] = $handle2;
            }
        }

        $tmpUser = $handle;
    }
}

// MES ARTICLES
elseif ('profil' == $p1 && 'articles' == $p2 && getUser()) {
    // pagination
    $limite = $MAX_ARTICLES_ADHERENT; // nombre d'elements affiches
    $pagenum = (int) ($_GET['pagenum'] ?? 0);
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1

    $articleTab = [];
    $req = 'SELECT SQL_CALC_FOUND_ROWS * FROM caf_article
			WHERE user_article = '.getUser()->getId().'
			ORDER BY tsp_crea_article DESC LIMIT '.($limite * ($pagenum - 1)).", $limite";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    // calcul du total grâce à SQL_CALC_FOUND_ROWS
    $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));
    $nbrPages = ceil($total / $limite);

    // boucle
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // info de la commission liée
        if ($handle['commission_article'] > 0) {
            $req = 'SELECT * FROM caf_commission
				WHERE id_commission = '.(int) $handle['commission_article'].'
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['commission'] = $handle2;
            }
        }

        // info de la sortie liée
        if ($handle['evt_article'] > 0) {
            $req = 'SELECT code_evt, id_evt, titre_evt FROM caf_evt
				WHERE id_evt = '.(int) $handle['evt_article'].'
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['evt'] = $handle2;
            }
        }

        $articleTab[] = $handle;
    }
}
// PAGE ARTICLE
elseif ('article' == $p1) {
    $article = false;
    $errPage = false; // message d'erreur spécifique à la page courante si besoin
    $id_article = (int) substr(strrchr($p2, '-'), 1);

    // sélection complète, non conditionnelle par rapport au status
    $req = "SELECT *
		FROM caf_article
		WHERE id_article=$id_article
		LIMIT 1";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // on a le droit de voir cet article ?
        if (1 == $handle['status_article'] // publié
            || ((allowed('article_validate_all') || allowed('article_validate')) && isset($_GET['forceshow']) && $_GET['forceshow']) // ou mode validateur
            || (user() && $handle['user_article'] == (string) getUser()->getId()) // ou j'en suis l'auteur
        ) {
            // auteur :
            $req = 'SELECT id_user, nickname_user
				FROM caf_user
				WHERE id_user='.(int) $handle['user_article'].'
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['auteur'] = $handle2;
            }

            // info de la sortie liée
            if ($handle['evt_article'] > 0) {
                $req = 'SELECT code_evt, id_evt, titre_evt FROM caf_evt
					WHERE id_evt = '.(int) $handle['evt_article'].'
					LIMIT 1';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['evt'] = $handle2;
                }
            }

            // commentaires
            $commentsTab = [];
            $req = "SELECT SQL_CALC_FOUND_ROWS *
				FROM caf_comment
				WHERE parent_type_comment='article'
				AND   parent_comment=$id_article
				AND   status_comment=1
				ORDER BY tsp_comment DESC
				LIMIT 50";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);

            // calcul du total grâce à SQL_CALC_FOUND_ROWS
            $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
            $totalComments = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                // infos user
                $req = 'SELECT nickname_user FROM caf_user WHERE id_user='.(int) $handle2['user_comment'].' LIMIT 1';
                $handleSql3 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle3 = $handleSql3->fetch_array(\MYSQLI_ASSOC)) {
                    $handle2['nickname_user'] = $handle3['nickname_user'];
                }

                // il est possible que l'user ait été supprimé. Dans ce cas :
                if ($handle2['user_comment'] > 0 && !$handle2['nickname_user']) {
                    // on le traite comme un etranger
                    $handle2['user_comment'] = 0;
                }

                $commentsTab[] = $handle2;
            }

            // MOdification des METAS de la page
            $meta_title = $handle['titre_article'].' | '.$p_sitename;
            $meta_description = limiterTexte(strip_tags($handle['cont_article']), 200).'...';
            // opengraphe : image pour les partages
            if (is_file(__DIR__.'/../../public/ftp/articles/'.(int) $handle['id_article'].'/wide-figure.jpg')) {
                $ogImage = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'ftp/articles/'.(int) $handle['id_article'].'/wide-figure.jpg';
            }

            // maj nb vues
            if (!admin()) {
                $req = "UPDATE caf_article SET nb_vues_article=nb_vues_article+1 WHERE id_article=$id_article AND status_article=1 LIMIT 1";
                LegacyContainer::get('legacy_mysqli_handler')->query($req);
            }

            // go
            $article = $handle;
        } else {
            $errPage = 'Accès non autorisé';
        }
    }
}
// GESTION DES ARTICLES
elseif ('gestion-des-articles' == $p1 && (allowed('article_validate_all') || allowed('article_validate'))) {
    // articles à valider (pagination)
    $limite = $MAX_ARTICLES_VALIDATION;

    if (allowed('article_validate_all')) {
        // compte nb total articles
        $req = 'SELECT COUNT(id_article) FROM caf_article WHERE status_article=0';
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $compte = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM)); // nombre total d'evts à valider, défini plus haut

        // page ?
        $pagenum = (int) $p2;
        if ($pagenum < 1) {
            $pagenum = 1;
        } // les pages commencent à 1
        $nbrPages = ceil($compte / $limite);

        // articles à valider, selon la (les) commission dont nous sommes responsables
        $req = 'SELECT `id_article` ,  `status_article` ,  `topubly_article` ,  `tsp_crea_article` ,  `tsp_article` ,  `user_article` ,  `titre_article` ,  `code_article` ,  `commission_article` ,  `evt_article` ,  `une_article`
					, id_user, nickname_user, lastname_user, firstname_user, code_commission, title_commission
		FROM caf_article
		LEFT JOIN caf_commission ON (caf_commission.id_commission = caf_article.commission_article)
		LEFT JOIN caf_user ON (caf_user.id_user = caf_article.user_article)
		WHERE status_article=0
		AND id_user = user_article
		ORDER BY topubly_article desc,  tsp_validate_article ASC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    } elseif (allowed('article_validate')) { // commission non précisée ici = autorisation passée
        // recuperation des commissions sous notre joug
        $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('article_validate');

        // compte nb total articles
        $req = "SELECT COUNT(id_article)
		FROM caf_article, caf_commission
		WHERE status_article=0
		AND commission_article=id_commission
		AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."') "; // condition OR pour toutes les commissions autorisées

        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $compte = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM)); // nombre total d'evts à valider, défini plus haut

        // articles à valider (pagination)

        // page ?
        $pagenum = (int) $p2;
        if ($pagenum < 1) {
            $pagenum = 1;
        } // les pages commencent à 1
        $nbrPages = ceil($compte / $limite);

        // articles à valider, selon la (les) commission dont nous sommes responsables
        $req = "SELECT `id_article` ,  `status_article` ,  `topubly_article` ,  `tsp_crea_article` ,  `tsp_article` ,  `user_article` ,  `titre_article` ,  `code_article` ,  `commission_article` ,  `evt_article` ,  `une_article`
					, id_user, nickname_user, lastname_user, firstname_user, code_commission, title_commission
		FROM caf_article
		LEFT JOIN caf_commission ON (caf_commission.id_commission = caf_article.commission_article)
		LEFT JOIN caf_user ON (caf_user.id_user = caf_article.user_article)
		WHERE status_article=0
		AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."') " // condition OR pour toutes les commissions autorisées
        .'AND id_user = user_article
		ORDER BY topubly_article desc,  tsp_validate_article ASC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    }
    $articleStandby = $articleStandbyRedac = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // ajout au tableau
        if (1 == $handle['topubly_article']) {
            $articleStandby[] = $handle;
        } else {
            $articleStandbyRedac[] = $handle;
        }
    }
}
// ARTICLES EN HOME PAGE
elseif ('accueil' == $p1) {
    $sliderTab = [];
    $articlesTab = [];

    // *******************
    // articles dans le slider
    $req = 'SELECT `id_article` , `tsp_crea_article` ,  `tsp_article` ,  `user_article` ,  `titre_article` ,  `code_article` ,  `commission_article`
		FROM  `caf_article`
		WHERE  `status_article` =1
		AND  `une_article` =1
		ORDER BY  `tsp_validate_article` DESC
		LIMIT 0 , 5';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $sliderTab[] = $handle;
    }

    // *******************
    // articles dans la page
    $limite = $MAX_ARTICLES_ACCUEIL; // nombre d'elements affiches
    $pagenum = (int) ($_GET['pagenum'] ?? 0);
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1

    // premiere requete : défaut, ne considère pas les articles liés à une sortie, liée à la commission courante
    $select = 'id_article , status_article ,  status_who_article ,  tsp_article ,  user_article ,  titre_article ,  code_article ,  commission_article ,  evt_article ,  une_article ,  cont_article ';
    $req = 'SELECT SQL_CALC_FOUND_ROWS '.$select.'
		FROM  caf_article
		WHERE  status_article =1
		';

    if ($current_commission) {
        // .($current_commission?" AND (commission_article = ".intval($comTab[$current_commission]['id_commission'])." OR commission_article = 0) ":'')
        $req .= ' AND ((commission_article = 0 AND DATEDIFF(NOW(), tsp_lastedit)<30)
				OR
				(commission_article = '.(int) $comTab[$current_commission]['id_commission'].')
			) ';
    }
    // commission donnée : filtre (mais on inclut les actus club, commission=0)
    $req .= ' ORDER BY  tsp_validate_article DESC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    // calcul du total grâce à SQL_CALC_FOUND_ROWS
    $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));
    $nbrPages = ceil($total / $limite);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // info de la commission liée
        if ($handle['commission_article'] > 0) {
            $req = 'SELECT * FROM caf_commission
				WHERE id_commission = '.(int) $handle['commission_article'].'
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['commission'] = $handle2;
            }
        }

        // info de la sortie liée && de la commission liée à la sortie liée (simple ou pas ?)
        if ($handle['evt_article'] > 0) {
            $req = 'SELECT
					code_evt, id_evt, titre_evt
					, code_commission
				FROM caf_evt, caf_commission
				WHERE id_evt = '.(int) $handle['evt_article'].'
				AND id_commission = commission_evt
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['evt'] = $handle2;
            }
        }

        $articlesTab[] = $handle;
    }
}

// PAGE AGENDA : LISTE DES SORTIES D'UN MOIS DONNÉ
elseif ('agenda' == $p1) {

    // default values if nothing provided
    if (isset($_GET['year']) && $_GET['year'] > 2000) {
        $year = (int) $_GET['year'];
    } else {
        $year = (int) date('Y');
    }

    if (isset($_GET['month']) && $_GET['month'] > 0 && $_GET['month'] < 13) {
        $month = (int) $_GET['month'];
    } else {
        $month = (int) date('m');
    }

    // nombre de jours dans ce mois (!! réutilisé dans la page !!)
    $nDays = date('t', strtotime("$year-$month-10"));

    // timestamp minimal et maximal
    $start_tsp = mktime(0, 0, 0, $month, 1, $year); // premiere seconde du premier jour du mois
    $end_tsp = mktime(23, 59, 59, $month, $nDays, $year); // derniere seconde du dernier jour

    // echo 'start_tsp='.$start_tsp.'<hr />end_tsp='.$end_tsp.'<hr /><hr />';

    // le tableau couvre tous les jours du mois
    $agendaTab = [];
    for ($i = 1; $i <= $nDays; ++$i) {
        $agendaTab[$i] = ['debut' => [], 'courant' => []];
    }

    // infos statistiques
    $nEvts = 0; // nombre d'events démarrant de mois ci

    $req = 'SELECT  id_evt, cancelled_evt, code_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, commission_evt, titre_evt, massif_evt, difficulte_evt, cycle_master_evt, cycle_parent_evt
				, cycle_parent_evt, child_version_from_evt, join_max_evt, join_start_evt, id_groupe
				, title_commission, code_commission
		FROM caf_evt, caf_commission
		WHERE id_commission = commission_evt
		AND status_evt = 1 '
        //  " AND cancelled_evt != 1 " // les sorties annulées y figurent ausssi
        .($p2 ? " AND code_commission = '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2)."' " : '')
        // truc des dates :
        .' AND ( '
            // la fin de l'événement est comprise dans ce mois
            ." ( tsp_end_evt > $start_tsp AND tsp_end_evt < $end_tsp ) "
            // OU le début de l'événement est compris dans ce mois
            ." OR ( tsp_evt > $start_tsp AND tsp_evt < $end_tsp ) "
            // OU l'événement comprend l'intégralité du mois
            ." OR ( tsp_evt < $start_tsp AND tsp_end_evt > $end_tsp ) "
        .' ) '
        .' ORDER BY cancelled_evt ASC , tsp_evt ASC';

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    // pour chaque event
    while ($handleSql && $handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $handle['groupe'] = get_groupe($handle['id_groupe']);

        // dates utiles pour ranger cet evenemtn dans le tableau
        $tmpStartD = date('d', $handle['tsp_evt']); // jour de cet evt de 1 à 28-30-31
        $tmpStartM = date('m', $handle['tsp_evt']); // mois de cet evt
        $tmpStartY = date('Y', $handle['tsp_evt']); // annee de cet evt
        $tmpEndD = date('d', $handle['tsp_end_evt']); // Jour de fin
        $tmpEndM = date('m', $handle['tsp_end_evt']); // Mois de fin
        $tmpEndY = date('Y', $handle['tsp_end_evt']); // annee de fin

        $handle['jourN'] = false; // compte des jours à afficher ?

        // s'il court sur plusieurs jours on initialise le compte des jours
        if ($tmpStartD.$tmpStartM != $tmpEndD.$tmpEndM) {
            $handle['jourN'] = 1;
        }

        // si cet événement débute ce mois
        if ($tmpStartM == $month) {
            // echo 'ADD '.$handle['id_evt'].' on '.$tmpStartD.'<hr />';
            // info statistique
            ++$nEvts;

            // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
            require __DIR__.'/../includes/evt-temoin-reqs.php';

            // on l'ajoute au bon jour, colonne 'debut'
            $agendaTab[(int) $tmpStartD]['debut'][] = $handle;
        }
        // s'il court sur plusieurs jours (on inclut les evts qui commencent avant ce mois)
        if ($tmpStartD.$tmpStartM != $tmpEndD.$tmpEndM) {
            // on l'ajoute sur chaque jour ou il court sauf le premier, deja inqiqué colonne 'courant'
            $bool = true;
            // jour auquel commencer
            if ($tmpStartM != $month) {
                $i = 1;
            } // si l'evt a commencé avant le mois en cours, on commence à ajouter les lignes à 1 (premier jour)
            else {
                $i = $tmpStartD + 1;
            } // sinon, on commence à ajouter les lignes au jour du mois

            while ($bool) {
                // Nième jour de cet event :
                $tmpDay = mktime(23, 59, 59, $month, $i, $year); // jour ciblé ici
                $handle['jourN'] = ceil(($tmpDay - $handle['tsp_evt']) / 86400); // nombre de jours d'ecart

                // si ce jour dépasse le nombre de jours du mois, on s'arrête là
                if ($i > $nDays) {
                    $bool = false;
                }
                // si ce jour est supérieur au jour de fin dans le bon mois, on s'arrête là
                if ($tmpEndM == $month && $i > $tmpEndD) {
                    $bool = false;
                }

                if ($bool || 1 == $i) {
                    // jour N si l'event est sur plusieur jours
                    $agendaTab[$i]['courant'][] = $handle;
                }
                ++$i; // incrémenation d'un jour
            }
        }
    }
}

// CREER UNE SORTIE : VARS UTILES
elseif ('creer-une-sortie' == $p1) {
    if (!user()) {
        header('Location: /login');
        exit;
    }

    if ($p2) {
        // CREER UNE SORTIE : même page utilisée pour modifier une sortie, gérée ici si on passe un paramètre en "p3"
        $id_evt_to_update = false; // variable pour annoncer au formulaire qu'il s'agit d'un update et non d'une créa. Par defaut, créa : false
        $update_status = false;

        // LSITE DES ENCADRANTS AUTORISÉS À ASSOCIER À LA COMMISSION COURANTE
        // encadrants
        $encadrantsTab = [];
        $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
            FROM caf_user, caf_user_attr, caf_usertype
            WHERE doit_renouveler_user=0
            AND id_user =user_user_attr
            AND usertype_user_attr=id_usertype
            AND code_usertype='encadrant'
            AND params_user_attr='commission:$com'
            ORDER BY  lastname_user ASC";
        // CRI - 29/08/2015
        // Correctif car la commission du jeudi compte plus de 50 encadrants
        // LIMIT 0 , 50";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $encadrantsTab[] = $handle;
        }

        $stagiairesTab = [];
        $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
            FROM caf_user, caf_user_attr, caf_usertype
            WHERE doit_renouveler_user=0
            AND id_user =user_user_attr
            AND usertype_user_attr=id_usertype
            AND code_usertype='stagiaire'
            AND params_user_attr='commission:$com'
            ORDER BY  lastname_user ASC";
        // CRI - 29/08/2015
        // Correctif car la commission du jeudi compte plus de 50 encadrants
        // LIMIT 0 , 50";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $stagiairesTab[] = $handle;
        }

        // coencadrants
        $coencadrantsTab = [];
        $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
            FROM caf_user, caf_user_attr, caf_usertype
            WHERE doit_renouveler_user=0
            AND id_user =user_user_attr
            AND usertype_user_attr=id_usertype
            AND code_usertype='coencadrant'
            AND params_user_attr='commission:$com'
            ORDER BY  lastname_user ASC";
        // CRI - 29/08/2015
        // Correctif car la commission du jeudi compte plus de 50 encadrants
        // LIMIT 0 , 50";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $coencadrantsTab[] = $handle;
        }

        // benevoles
        $benevolesTab = [];
        $com = LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2);
        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
            FROM caf_user, caf_user_attr, caf_usertype
            WHERE doit_renouveler_user=0
            AND id_user =user_user_attr
            AND usertype_user_attr=id_usertype
            AND code_usertype='benevole'
            AND params_user_attr='commission:$com'
            ORDER BY  lastname_user ASC
            LIMIT 0 , 50";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $benevolesTab[] = $handle;
        }

        // sorties creees par moi, et premières d'un cycle, dans la commission courante
        $parentEvents = [];
        $req = 'SELECT  id_evt, code_evt, tsp_evt, tsp_crea_evt, titre_evt, massif_evt, cycle_master_evt, cycle_parent_evt
                    , title_commission, code_commission
            FROM caf_evt, caf_commission
            WHERE user_evt = '.getUser()->getId()."
            AND cycle_master_evt=1
            AND id_commission = commission_evt
            AND code_commission = '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($p2)."'
            ORDER BY tsp_evt DESC
            LIMIT 200";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // compte de sorties enfant
            $req = 'SELECT COUNT(id_evt) FROM caf_evt WHERE cycle_parent_evt='.$handle['id_evt'];
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            $handle['nchildren'] = getArrayFirstValue($handleSql2->fetch_array(\MYSQLI_NUM));

            $parentEvents[] = $handle;
        }

        // MISE A JOUR
        if ($p3 && 'update-' == substr($p3, 0, 7)) {
            // un ID de sortie est vise, il s'agit d'une modif et non d'une creation
            $id_evt = (int) substr(strrchr($p3, '-'), 1);

            $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
                    , denivele_evt, distance_evt, matos_evt, difficulte_evt, description_evt, lat_evt, long_evt
                    , ngens_max_evt
                    , join_start_evt, join_max_evt, id_groupe, tarif_detail, need_benevoles_evt, itineraire
                    , nickname_user
                    , title_commission, code_commission
            FROM caf_evt, caf_user, caf_commission as commission
            WHERE id_evt=$id_evt
            AND id_user = user_evt
            AND commission_evt=commission.id_commission
            LIMIT 1";

            $handleTab = [];
            $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

            while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
                // variable pour annoncer au formulaire qu'il s'agit d'un update et non d'une creation
                $id_evt_to_update = $id_evt;
                $update_status = $handle['status_evt'];

                // Recup' encadrants,coencadrants,benevoles
                $encadrants = [];
                $stagiaires = [];
                $coencadrants = [];
                $benevoles = [];
                $req = "SELECT * FROM caf_evt_join WHERE evt_evt_join=$id_evt LIMIT 300";
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    if ('encadrant' == $handle2['role_evt_join']) {
                        $encadrants[] = $handle2['user_evt_join'];
                    }
                    if ('stagiaire' == $handle2['role_evt_join']) {
                        $stagiaires[] = $handle2['user_evt_join'];
                    }
                    if ('coencadrant' == $handle2['role_evt_join']) {
                        $coencadrants[] = $handle2['user_evt_join'];
                    }
                    if ('benevole' == $handle2['role_evt_join']) {
                        $benevoles[] = $handle2['user_evt_join'];
                    }
                }

                // benevoles
                $benevolesTab = [];
                if (count($benevoles) > 0) {
                    $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, civ_user
            FROM caf_user
            WHERE id_user IN ('.implode(',', $benevoles).')
            ORDER BY  lastname_user ASC
            LIMIT 0 , 50';
                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $benevolesTab[] = $handle2;
                    }
                }

                // méthode "sale & rapide" : on remplace les valeurs POST par défaut, par celles issues de la BDD
                $_POST['commission_evt'] = $handle['commission_evt'];
                $_POST['titre_evt'] = $handle['titre_evt'];
                $_POST['encadrants'] = $encadrants;
                $_POST['stagiaires'] = $stagiaires;
                $_POST['coencadrants'] = $coencadrants;
                $_POST['benevoles'] = $benevoles;
                $_POST['tarif_evt'] = $handle['tarif_evt'];
                $_POST['tarif_detail'] = $handle['tarif_detail'];
                $_POST['massif_evt'] = $handle['massif_evt'];
                $_POST['cycle_master_evt'] = $handle['cycle_master_evt'];
                $_POST['cycle_parent_evt'] = $handle['cycle_parent_evt'];
                $_POST['id_groupe'] = $handle['id_groupe'];
                $_POST['itineraire'] = $handle['itineraire'];
                $_POST['rdv_evt'] = $handle['rdv_evt'];
                $_POST['lat_evt'] = $handle['lat_evt'];
                $_POST['long_evt'] = $handle['long_evt'];
                $_POST['tsp_evt_day'] = $handle['tsp_evt'] ? date('d/m/Y', $handle['tsp_evt']) : '';
                $_POST['tsp_evt_hour'] = $handle['tsp_evt'] ? date('H:i', $handle['tsp_evt']) : '';
                $_POST['tsp_end_evt_day'] = $handle['tsp_end_evt'] ? date('d/m/Y', $handle['tsp_end_evt']) : '';
                $_POST['tsp_end_evt_hour'] = $handle['tsp_end_evt'] ? date('H:i', $handle['tsp_end_evt']) : '';
                $_POST['denivele_evt'] = $handle['denivele_evt'];
                $_POST['ngens_max_evt'] = $handle['ngens_max_evt'];
                $_POST['distance_evt'] = $handle['distance_evt'];
                $_POST['matos_evt'] = $handle['matos_evt'];
                $_POST['difficulte_evt'] = $handle['difficulte_evt'];
                $_POST['description_evt'] = $handle['description_evt'];
                $_POST['join_max_evt'] = $handle['join_max_evt'];
                $_POST['need_benevoles_evt'] = $handle['need_benevoles_evt'];
                // special : tsp to days. le timestamp enregistré commence à minuit pile
                $_POST['join_start_evt_days'] = floor(($handle['tsp_evt'] - $handle['join_start_evt']) / 86400);

                // c'est une sortie enfant, recup du parent si sortie creee par un tiers
                if ($handle['cycle_parent_evt'] > 0) {
                    $_POST['cycle'] = 'child';

                    $req = 'SELECT id_evt, code_evt, tsp_evt, tsp_crea_evt, titre_evt, massif_evt, cycle_master_evt, cycle_parent_evt
            FROM caf_evt, caf_commission
            WHERE id_evt='.$handle['cycle_parent_evt'].'
            AND user_evt != '.getUser()->getId().'
            AND cycle_master_evt=1
            ORDER BY tsp_evt DESC
            LIMIT 1';
                    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    $parentEvents[] = $handleSql->fetch_array(\MYSQLI_ASSOC);
                }
            }
        }
    }
}
// PAGE SORTIE
elseif ('sortie' == $p1 || 'feuille-de-sortie' == $p1) {
    $evt = false;
    $id_evt = null;
    $errPage = false; // message d'erreur spécifique à la page courante si besoin

    if ('feuille-de-sortie' == $p1) {
        $type = strstr($p2, '-', true);
        switch ($type) {
            case 'evt':
                $id_evt = (int) substr(strrchr($p2, '-'), 1);
                break;
            default:
                break;
        }
    } elseif ('sortie' == $p1) {
        $id_evt = (int) substr(strrchr($p2, '-'), 1);
    }

    if ($id_evt) {
        // selection complete, non conditionnelle par rapport au statut
        $req = "SELECT
                id_evt, code_evt, status_evt, status_legal_evt, status_who_evt, status_legal_who_evt,
                    user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt,
                    rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt,
                    cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt,
                    matos_evt, need_benevoles_evt, lat_evt, long_evt, join_start_evt, ngens_max_evt, join_max_evt,
                    id_groupe, tarif_detail, distance_evt, itineraire,
                nickname_user, civ_user, firstname_user, lastname_user, tel_user,
                title_commission, code_commission
            FROM caf_evt as evt, caf_user as user, caf_commission as commission
            WHERE id_evt=$id_evt
                AND id_user = user_evt
                AND commission_evt=commission.id_commission
                LIMIT 1";

        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $on_peut_voir = true;

            // on a le droit de voir cette page ?
            if (
                ($on_peut_voir && (1 == $handle['status_evt'])) // publiée
                || (allowed('evt_validate') && isset($_GET['forceshow']) && $_GET['forceshow']) // ou mode validateur
                || (allowed('evt_validate_all') && isset($_GET['forceshow']) &&  $_GET['forceshow']) // ou mode validateur
                || (user() && $handle['user_evt'] == (string) getUser()->getId()) // ou j'en suis l'auteur ? QUID de l'encadrant ?
            ) {
                $current_commission = $handle['code_commission'];

                // Groupe de niveau
                $handle['groupe'] = [];
                if (null != $handle['id_groupe']) {
                    $req = 'SELECT * FROM `caf_groupe` WHERE `id` = '.$handle['id_groupe'];
                    $handleGroupe = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($groupe = $handleGroupe->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['groupe'] = $groupe;
                    }
                }

                // participants integres a la sortie
                $handle['joins'] = ['inscrit' => [], 'manuel' => [], 'encadrant' => [], 'stagiaire' => [], 'coencadrant' => [], 'benevole' => [], 'enattente' => []];

                if ($handle['cycle_parent_evt']) {
                    // cette sortie fait partie d'un cycle, alors on ajoute un lien vers son parent
                    $req = '
                        SELECT id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, join_max_evt, join_start_evt
                            , nickname_user, civ_user
                            , title_commission, code_commission
                        FROM caf_evt
                            , caf_user
                            , caf_commission
                        WHERE id_user = user_evt
                        AND id_evt='.(int) $handle['cycle_parent_evt'].'
                        AND id_commission = commission_evt
                        ORDER BY  `tsp_crea_evt` DESC
                        LIMIT 1';

                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['cycleparent'] = $handle2;
                    }
                } elseif ($handle['cycle_master_evt']) {
                    // cette sortie est la premiere d'un cycle, on recupere les infos des sorties suivantes
                    $req = '
                        SELECT id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, title_commission, code_commission, tsp_evt, titre_evt, cycle_parent_evt
                        FROM caf_evt
                            , caf_commission
                        WHERE cycle_parent_evt='.(int) $id_evt.'
                        AND id_commission = commission_evt
                        ORDER BY `tsp_crea_evt` ASC
                        LIMIT 30';
                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['cyclechildren'][] = $handle2;
                    }
                }

                // participants "speciaux" avec droits :
                $req = "SELECT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join = $id_evt
                    AND user_evt_join = id_user
                    AND status_evt_join = 1
                    AND
                        (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'stagiaire' OR role_evt_join LIKE 'coencadrant' OR role_evt_join LIKE 'benevole')
                    LIMIT 300";
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins'][$handle2['role_evt_join']][] = $handle2;
                }

                // participants "enattente" :
                $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join , is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join  = '.(int) ($handle['cycle_parent_evt'] ?: $id_evt).'
                    AND user_evt_join = id_user
                    AND status_evt_join = 0
                    LIMIT 300';

                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins']['enattente'][] = $handle2;
                }

                // participants "normaux" : inscrit en ligne : leur role est à "inscrit"
                $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join  = '.(int) ($handle['cycle_parent_evt'] ?: $id_evt)."
                    AND user_evt_join = id_user
                    AND role_evt_join LIKE 'inscrit'
                    AND status_evt_join = 1
                    LIMIT 300";
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins']['inscrit'][] = $handle2;
                }

                // participants "manuel" : inscrit par l'orga : leur role est à "manuel"
                $req = 'SELECT DISTINCT id_user, cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user, civ_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join  = '.(int) ($handle['cycle_parent_evt'] ?: $id_evt)."
                    AND user_evt_join = id_user
                    AND role_evt_join LIKE 'manuel'
                    AND status_evt_join = 1
                    LIMIT 300";
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['joins']['manuel'][] = $handle2;
                }

                // mon rapport à cette sortie
                $monStatut = 'neutre';

                if (user()) {
                    $req = "SELECT * FROM caf_evt_join
                        WHERE evt_evt_join=$id_evt
                        AND user_evt_join=".getUser()->getId().'
                        ORDER BY tsp_evt_join DESC
                        LIMIT 1';
                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        // si je suis pas encore validé
                        if (0 == $handle2['status_evt_join']) {
                            $monStatut = 'en attente';
                        }
                        // si je suis inscrit, "monStatut" prend la valeur de mon role
                        if (1 == $handle2['status_evt_join']) {
                            $monStatut = $handle2['role_evt_join'];
                        }
                        // si je suis refusé
                        if (2 == $handle2['status_evt_join']) {
                            $monStatut = 'refusé';
                        }
                    }
                }

                if ('sortie' == $p1) {
                    // AUTRES INFOS, PAS NECESSAIRE POUR LA FICHE DE SORTIE

                    // si la sortie est annulée, on recupère les details de "WHO" : qui l'a annulée
                    if ('1' == $handle['cancelled_evt']) {
                        $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, nomade_user, civ_user
                            FROM caf_user
                            WHERE id_user='.(int) $handle['cancelled_who_evt'].'
                            LIMIT 300';
                        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                            $handle['cancelled_who_evt'] = $handle2;
                        }
                    }

                    // si un compte rendu existe ?
                    $handle['cr'] = false;
                    $req = "SELECT id_article, titre_article, code_article
                        FROM caf_article
                        WHERE evt_article = $id_evt
                        AND status_article = 1
                        ORDER BY tsp_validate_article DESC
                        LIMIT 1";
                    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                    while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                        $handle['cr'] = $handle2;
                    }

                    // Modification des METAS de la page
                    $meta_title = $handle['titre_evt'].' | '.$p_sitename;
                    $meta_description = limiterTexte(strip_tags($handle['description_evt']), 200).'...';

                    // si je suis chef de famille (filiations) je rajoute la liste de mes "enfants" pour les inscrire
                    $filiations = [];
                    if (user() && getUser()->getCafnum()) {
                        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, birthday_user, civ_user, email_user, tel_user, cafnum_user FROM caf_user WHERE cafnum_parent_user LIKE '".LegacyContainer::get('legacy_mysqli_handler')->escapeString(getUser()->getCafnum())."' LIMIT 15";
                        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                            $filiations[] = $handle2;
                        }
                    }
                }

                // go
                $evt = $handle;
            } else {
                $errPage = 'Accès non autorisé';
            }
        }
    }
}
// PAGE ANNULER UNE SORTIE
elseif ('annuler-une-sortie' == $p1) {
    $evt = false;
    $errPage = false; // message d'erreur spécifique à la page courante si besoin

    $id_evt = (int) substr(strrchr($p2, '-'), 1);

    // sélection complète, non conditionnelle par rapport au status
    $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt,
              tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
                , cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt, matos_evt, need_benevoles_evt
                , lat_evt, long_evt
                , join_start_evt
                , ngens_max_evt, join_max_evt
                , nickname_user
                , title_commission, code_commission
        FROM caf_evt, caf_user, caf_commission
        WHERE id_evt=$id_evt
        AND id_user = user_evt
        AND commission_evt=id_commission
        LIMIT 1";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // on a le droit de supprimer cette page ?
        if (allowed('evt_cancel', 'commission:'.$handle['code_commission'])) {
            // participants:
            // si la sortie est enfant d'un cycle, on cherche les participants à la sortie parente
            if ($handle['cycle_parent_evt']) {
                $id_evt_forjoins = $handle['cycle_parent_evt'];
            } else {
                $id_evt_forjoins = $handle['id_evt'];
            }

            $handle['joins'] = [];
            $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, nomade_user
                    , role_evt_join
                FROM caf_evt_join, caf_user
                WHERE evt_evt_join = $id_evt_forjoins
                AND user_evt_join = id_user
                LIMIT 300";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['joins'][] = $handle2;
            }

            // si la sortie est annulée, on recupère les details de "WHO" : qui l'a annulée
            if ('1' == $handle['cancelled_evt']) {
                $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user
                    FROM caf_user
                    WHERE id_user='.(int) $handle['cancelled_who_evt'].'
                    LIMIT 300';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['cancelled_who_evt'] = $handle2;
                }
            }

            $evt = $handle;
        } else {
            $errPage = 'Accès non autorisé';
        }
    }
}
// PAGE SUPPRIMER UNE SORTIE
elseif ('supprimer-une-sortie' == $p1) {
    $evt = false;
    $errPage = false; // message d'erreur spécifique à la page courante si besoin
    $id_evt = (int) substr(strrchr($p2, '-'), 1);

    // sélection complète, non conditionnelle par rapport au status
    $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
				, cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt, matos_evt, need_benevoles_evt
				, lat_evt, long_evt
				, join_start_evt
				, ngens_max_evt, join_max_evt
				, nickname_user
				, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE id_evt=$id_evt
		AND id_user = user_evt
		AND commission_evt=id_commission
		LIMIT 1";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // on a le droit de supprimer cette page ?
        if (allowed('evt_cancel', 'commission:'.$handle['code_commission'])) {
            // participants:
            $handle['joins'] = [];
            $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, nomade_user
					, role_evt_join
				FROM caf_evt_join, caf_user
				WHERE evt_evt_join ='.(int) $handle['id_evt'].'
				AND user_evt_join = id_user
				LIMIT 300';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['joins'][] = $handle2;
            }

            // si la sortie est annulée, on recupère les details de "WHO" : qui l'a annulée
            if ('1' == $handle['cancelled_evt']) {
                $req = 'SELECT id_user, firstname_user, lastname_user, nickname_user
					FROM caf_user
					WHERE id_user='.(int) $handle['cancelled_who_evt'].'
					LIMIT 300';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['cancelled_who_evt'] = $handle2;
                }
            }

            $evt = $handle;
        } else {
            $errPage = 'Accès non autorisé';
        }
    }
}
// GESTION DES SORTIES
elseif ('gestion-des-sorties' == $p1 && (allowed('evt_validate_all') || allowed('evt_validate'))) {
    // sorties à valider (pagination)
    // compte
    $limite = $MAX_SORTIES_VALIDATION;
    $compte = $notif_validerunesortie; // nombre total d'evts à valider, défini plus haut
    // page ?
    $pagenum = (int) $p2;
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1
    $nbrPages = ceil($compte / $limite);

    // requetes pour les sorties en attente de validation de cet user POUR TOUTES LES COMMISSIONS
    if (allowed('evt_validate_all')) {
        $req = 'SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
					, join_start_evt, cycle_master_evt, cycle_parent_evt
					, nickname_user
					, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND id_user = user_evt
		AND commission_evt=id_commission '
        .'ORDER BY tsp_evt ASC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    }

    // requetes pour SEULEMENT les sorties DES COMMISSION que nous sommes autorisées à administrer
    elseif (allowed('evt_validate')) { // commission non précisée ici = autorisation passée
        // recuperation des commissions sous notre joug
        $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('evt_validate');

        // sorties à valider, selon la (les) commission dont nous sommes responsables
        $req = "SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
					, join_start_evt, cycle_master_evt, cycle_parent_evt
					, nickname_user
					, title_commission, code_commission
		FROM caf_evt, caf_user, caf_commission
		WHERE status_evt=0
        AND tsp_evt IS NOT NULL
		AND id_user=user_evt
		AND commission_evt=id_commission
		AND (code_commission LIKE '".implode("' OR code_commission LIKE '", $tab)."') " // condition OR pour toutes les commissions autorisées
        .'ORDER BY tsp_crea_evt ASC
		LIMIT '.($limite * ($pagenum - 1)).", $limite";
    }

    $evtStandby = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
        require __DIR__.'/../includes/evt-temoin-reqs.php';

        // ajout au tableau
        $evtStandby[] = $handle;
    }
}
// VALIDATION PRESIDENT DES SORTIES
elseif ('validation-des-sorties' == $p1 && allowed('evt_legal_accept')) {
    // sorties à valider (pagination)
    // compte
    $limite = $MAX_SORTIES_VALIDATION;
    $compte = $notif_validerunesortie_president; // nombre total d'evts à valider, défini plus haut
    // page ?
    $pagenum = (int) $p2;
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1
    $nbrPages = ceil($compte / $limite);

    // requetes pour les sorties en attente de validation par le president
    $req = 'SELECT  id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
				, join_start_evt, cycle_master_evt, cycle_parent_evt
				, nickname_user
				, title_commission, code_commission
	FROM caf_evt, caf_user, caf_commission
	WHERE status_evt=1
	AND status_legal_evt=0
	AND tsp_evt > '.time().'
	AND tsp_evt < '.$MAX_TIMESTAMP_FOR_LEGAL_VALIDATION.'

	AND id_user = user_evt
	AND commission_evt=id_commission
	ORDER BY tsp_evt ASC
	LIMIT '.($limite * ($pagenum - 1)).", $limite";

    $evtStandby = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // compte plpaces totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
        require __DIR__.'/../includes/evt-temoin-reqs.php';

        // ajout au tableau
        $evtStandby[] = $handle;
    }
}
// LISTE DES USERS / ADHERENTS
elseif (('adherents' == $p1 && allowed('user_see_all')) || ('admin-users' == $p1 && admin())) {
    $userTab = [];
    $show = 'valid';
    // fonctions disponibles
    if (isset($_GET['show']) && in_array($_GET['show'], ['all', 'manual', 'notvalid', 'nomade', 'dels', 'expired', 'valid-expired'], true)) {
        $show = $_GET['show'];
    }
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);

    $req = 'SELECT id_user , email_user , cafnum_user , firstname_user , lastname_user , nickname_user , created_user , birthday_user , tel_user , tel2_user , adresse_user, cp_user ,  ville_user ,  civ_user , valid_user , manuel_user, nomade_user, date_adhesion_user, doit_renouveler_user
		FROM  `caf_user` '
        .('dels' == $show ? ' WHERE valid_user=2 ' : '')
        .('manual' == $show ? ' WHERE manuel_user=1 ' : '')
        .('nomade' == $show ? ' WHERE nomade_user=1 ' : '')
        .('valid' == $show ? ' WHERE valid_user=1 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        .('notvalid' == $show ? ' WHERE valid_user=0 AND doit_renouveler_user=0 AND nomade_user=0 ' : '')
        .('expired' == $show ? ' WHERE valid_user=0 AND doit_renouveler_user=1 ' : '')
        .('valid-expired' == $show ? ' WHERE valid_user=1 AND doit_renouveler_user=1 ' : '')
        .' ORDER BY lastname_user ASC, lastname_user ASC
		LIMIT 9000';			// , pays_user

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $handleSql->fetch_assoc()) {
        if ('0' == $row['birthday_user'] || '1' == $row['birthday_user'] || '' == $row['birthday_user']) {
            // dans ces cas, bug très probable
            $row['birthday_user'] = 0;
        } else { // la date de naissance est remplacée par l'age (avec zéros inutiles, pour tri de la colonne)
            $row['birthday_user'] = sprintf('%03d', getYearsSinceDate($row['birthday_user']));
        }

        $userTab[] = $row;
    }
}

// GESTION PARTENAIRES
elseif ('admin-partenaires' == $p1 && admin()) {
    $partenairesTab = [];
    $show = 'all';
    // fonctions disponibles
    if (isset($_GET['show']) && in_array($_GET['show'], ['all', 'public', 'private', 'enabled', 'disabled'], true)) {
        $show = $_GET['show'];
    }
    $show = LegacyContainer::get('legacy_mysqli_handler')->escapeString($show);

    $req = 'SELECT part_id, part_name, part_url, part_desc, part_image, part_type, part_enable, part_order, part_click
		FROM caf_partenaires '
        .('private' == $show ? ' WHERE part_type=1 ' : '')
        .('public' == $show ? ' WHERE part_type=2 ' : '')
        .('enabled' == $show ? ' WHERE part_enable=1 ' : '')
        .('disabled' == $show ? ' WHERE part_enable != 1' : '')
        .' ORDER BY part_order, part_type, part_name ASC
		LIMIT 1000';

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $handleSql->fetch_assoc()) {
        $partenairesTab[] = $row;
    }

//	print_r($partenairesTab);exit;
}

// FICHE USER
elseif ('user-full' == $p1) {
    // id du profil
    $id_user = (int) $p2;
    $tmpUser = false;

    $req = "SELECT * FROM caf_user WHERE id_user = $id_user LIMIT 1";
    // AND valid_user = 1
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // liste des statuts
        $row['statuts'] = [];

        $req = 'SELECT title_usertype, params_user_attr
			FROM caf_user_attr, caf_usertype
			WHERE user_user_attr='.$id_user.'
			AND id_usertype=usertype_user_attr
			ORDER BY hierarchie_usertype DESC
			LIMIT 50';
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($row2 = $handleSql2->fetch_assoc()) {
            $commission = substr(strrchr($row2['params_user_attr'], ':'), 1);
            $row['statuts'][] = $row2['title_usertype'].($commission ? ', '.$commission : '');
        }

        $tmpUser = $row;
    }
}

// RECHERCHE
elseif ('recherche' == $p1 && isset($_GET['str']) && strlen($_GET['str'])) {
    // vérification des caractères
    $safeStr = substr(html_utf8(stripslashes($_GET['str'])), 0, 80);
    $safeStrSql = LegacyContainer::get('legacy_mysqli_handler')->escapeString(substr(stripslashes($_GET['str']), 0, 80));

    if (strlen($safeStr) < 3) {
        $errTab[] = 'Votre recherche doit comporter au moins 3 caractères.';
    }

    if (!isset($errTab) || 0 === count($errTab)) {
        // *******
        // RECH ARTICLES - permet la recherche par pseudo de l'auteur
        $articlesTab = [];
        $req = 'SELECT
				SQL_CALC_FOUND_ROWS
				`id_article` ,  `tsp_article` ,  `user_article` ,  `status_article` ,  `titre_article` ,  `code_article` ,  `commission_article` ,  `une_article` ,  `cont_article`
				, nickname_user, id_user
			FROM caf_article, caf_user
			WHERE  `status_article` =1
			AND user_article = id_user
			AND status_article = 1
			'
            // commission donnée : filtre (mais on inclut les actus club, commission=0)
            .($current_commission ? ' AND (commission_article = '.(int) $comTab[$current_commission]['id_commission'].' OR commission_article = 0) ' : '')
            // RECHERCHE
            ." AND (
						titre_article LIKE  '%$safeStrSql%'
					OR	cont_article LIKE  '%$safeStrSql%'
					OR	nickname_user LIKE  '%$safeStrSql%'
			) "

            .' ORDER BY  `tsp_validate_article` DESC
			LIMIT 10';
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // calcul du total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $totalArticles = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // info de la commission liée
            if ($handle['commission_article'] > 0) {
                $req = 'SELECT * FROM caf_commission
					WHERE id_commission = '.(int) $handle['commission_article'].'
					LIMIT 1';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['commission'] = $handle2;
                }
            }
            $articlesTab[] = $handle;
        }

        // *******
        // RECH SORTIES
        $evtTab = [];

        $req = 'SELECT
				SQL_CALC_FOUND_ROWS
				id_evt, code_evt, tsp_evt, tsp_crea_evt, titre_evt, massif_evt, cycle_master_evt, cycle_parent_evt
				, title_commission, code_commission
			FROM caf_evt, caf_commission, caf_user
			WHERE id_commission = commission_evt
			AND id_user = user_evt
			AND status_evt = 1
			'
            // si une comm est sélectionnée, filtre
            .($current_commission ? " AND code_commission LIKE '".LegacyContainer::get('legacy_mysqli_handler')->escapeString($current_commission)."' " : '')
            // RECHERCHE
            ." AND (
						titre_evt LIKE '%$safeStrSql%'
					OR	massif_evt LIKE '%$safeStrSql%'
					OR	rdv_evt LIKE '%$safeStrSql%'
					OR	description_evt LIKE '%$safeStrSql%'
					OR	nickname_user LIKE '%$safeStrSql%'
			) "
            .' ORDER BY tsp_evt DESC
			LIMIT 10';

        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // calcul du total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $totalEvt = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $evtTab[] = $handle;
        }
    }
}
