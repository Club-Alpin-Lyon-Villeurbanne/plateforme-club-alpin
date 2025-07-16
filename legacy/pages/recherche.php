<?php

use App\Legacy\LegacyContainer;

if ('recherche' == $p1 && isset($_GET['str']) && strlen($_GET['str'])) {
    // vérification des caractères
    $safeStr = substr(html_utf8(stripslashes($_GET['str'])), 0, 80);
    $safeStrSql = LegacyContainer::get('legacy_mysqli_handler')->escapeString(substr(stripslashes($_GET['str']), 0, 80));
    $safeStrSqlWildCard = '%' . $safeStrSql . '%';

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
                , nickname_user, id_user, media_upload_id, m.filename
            FROM caf_article AS a
            LEFT JOIN caf_user as u  ON a.user_article = u.id_user
            LEFT JOIN media_upload m ON a.media_upload_id = m.id
            WHERE  status_article = 1';
        // commission donnée : filtre (mais on inclut les actus club, commission=0)
        if ($current_commission) {
            $req .= ' AND (commission_article = ? OR commission_article = 0) ';
        }
        // RECHERCHE
        $req .= ' AND (
                        titre_article LIKE  ?
                    OR	cont_article LIKE  ?
                    OR	nickname_user LIKE  ?
            ) '

        . ' ORDER BY  `tsp_validate_article` DESC
            LIMIT 10';
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($req);
        if ($current_commission) {
            $stmt->bind_param('isss', (int) $comTab[$current_commission]['id_commission'], $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard);
        } else {
            $stmt->bind_param('sss', $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard);
        }
        $stmt->execute();
        $handleSql = $stmt->get_result();

        // calcul du total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $totalArticles = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // info de la commission liée
            if ($handle['commission_article'] > 0) {
                $req = 'SELECT * FROM caf_commission
                    WHERE id_commission = ' . (int) $handle['commission_article'] . '
                    LIMIT 1';
                $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                    $handle['commission'] = $handle2;
                }
            }
            $articlesTab[] = $handle;
        }
        $stmt->close();

        // *******
        // RECH SORTIES
        $evtTab = [];

        $req = 'SELECT
                SQL_CALC_FOUND_ROWS
                id_evt, code_evt, commission_evt, tsp_evt, tsp_crea_evt, titre_evt, massif_evt, place_evt, join_start_evt, ngens_max_evt, cancelled_evt, is_draft
                , title_commission, code_commission
            FROM caf_evt, caf_commission, caf_user
            WHERE id_commission = commission_evt
            AND id_user = user_evt
            AND status_evt = 1';
        // si une comm est sélectionnée, filtre
        if ($current_commission) {
            $req .= ' AND commission_evt = ? ';
        }
        // RECHERCHE
        $req .= ' AND (
                        titre_evt LIKE ?
                    OR	massif_evt LIKE ?
                    OR	place_evt LIKE ?
                    OR	rdv_evt LIKE ?
                    OR	description_evt LIKE ?
                    OR	nickname_user LIKE ?
            ) '
        . ' ORDER BY tsp_evt DESC
            LIMIT 10';
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($req);
        if ($current_commission) {
            $stmt->bind_param('issssss', (int) $comTab[$current_commission]['id_commission'], $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard);
        } else {
            $stmt->bind_param('ssssss', $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard);
        }
        $stmt->execute();
        $handleSql = $stmt->get_result();

        // calcul du total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $totalEvt = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // compte places totales, données stockées dans $handle['temoin'] && $handle['temoin-title']
            require __DIR__ . '/../includes/evt-temoin-reqs.php';

            $evtTab[] = $handle;
        }
        $stmt->close();

        // *******
        // RECH PAGES LIBRES
        $freePagesTab = [];

        $req = 'SELECT
                SQL_CALC_FOUND_ROWS
                p.id_page, p.code_page, p.default_name_page, i.contenu_content_inline,
                CASE WHEN i.contenu_content_inline IS NOT NULL THEN i.contenu_content_inline ELSE p.default_name_page END as page_title
            FROM caf_page as p
            INNER JOIN caf_content_html as c ON (c.code_content_html = CONCAT(\'main-pagelibre-\', p.id_page) AND c.current_content_html = 1)
            LEFT JOIN caf_content_inline as i ON (i.code_content_inline = CONCAT(\'meta-title-\', p.code_page))
            WHERE p.pagelibre_page = 1
            AND p.vis_page = 1';
        // RECHERCHE
        $req .= ' AND (
                    p.default_name_page LIKE ?
                    OR c.contenu_content_html LIKE ?
                    OR i.contenu_content_inline LIKE ?
            )
            ORDER BY created_page DESC
            LIMIT 10';
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare($req);
        $stmt->bind_param('sss', $safeStrSqlWildCard, $safeStrSqlWildCard, $safeStrSqlWildCard);
        $stmt->execute();
        $handleSql = $stmt->get_result();

        // calcul du total grâce à SQL_CALC_FOUND_ROWS
        $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
        $totalFreePages = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $freePagesTab[] = $handle;
        }
        $stmt->close();
    }
}
?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            if (empty($_GET['str']) || !strlen($_GET['str'])) {
                echo '<h1>Recherche</h1>';
                inclure($p1, 'vide');
            } elseif (isset($safeStr)) {
                // TITRE
                echo '<h1>Votre recherche : &laquo;&nbsp;' . $safeStr . '&nbsp;&raquo;</h1>';

                // ERREURS
                if (isset($errTab) && count($errTab) > 0) {
                    echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                } else {
                    // RECHERCHE - ARTICLES
                    echo '<hr /><h2>Articles</h2>';

                    // compte
                    if (!count($articlesTab)) {
                        echo '<p class="alerte">Aucun article trouvé</p>';
                    } else {
                        echo '<p class="mini">' . $totalArticles . ' article' . ($totalArticles > 1 ? 's' : '') . ' trouvé' . ($totalArticles > 1 ? 's' : '') . '. ' . ($totalArticles != count($articlesTab) ? 'Voici les ' . count($articlesTab) . ' plus récents :' : '') . '</p>';
                    }

                    for ($i = 0; $i < count($articlesTab); ++$i) {
                        $article = $articlesTab[$i];
                        require __DIR__ . '/../includes/article-lien.php';
                    }

                    // RECHERCHE - SORTIES
                    echo '<br /><hr /><h2>Sorties</h2>';

                    // compte
                    if (!count($evtTab)) {
                        echo '<p class="alerte">Aucune sortie trouvée</p>';
                    } else {
                        echo '<p class="mini">' . $totalEvt . ' sortie' . ($totalEvt > 1 ? 's' : '') . ' trouvée' . ($totalEvt > 1 ? 's' : '') . '. ' . ($totalEvt != count($evtTab) ? 'Voici les ' . count($evtTab) . ' plus récentes :' : '') . '</p>';
                    }

                    echo '<br /><table id="agenda">';
                    for ($i = 0; $i < count($evtTab); ++$i) {
                        $evt = $evtTab[$i];

                        echo '<tr>'
                                . '<td class="agenda-gauche">' . date('d/m/Y', $evt['tsp_evt']) . '</td>'
                                . '<td>';
                        require __DIR__ . '/../includes/agenda-evt-debut.php';
                        echo '</td>'
                            . '</tr>';
                    }
                    echo '</table>';

                    // RECHERCHE - PAGES LIBRES
                    echo '<br /><hr /><h2>Pages</h2>';
                    if (!count($freePagesTab)) {
                        echo '<p class="alerte">Aucune page trouvée</p>';
                    } else {
                        echo '<p class="mini">' . $totalFreePages . ' page' . ($totalFreePages > 1 ? 's' : '') . ' trouvée' . ($totalFreePages > 1 ? 's' : '') . '. ' . ($totalFreePages != count($freePagesTab) ? 'Voici les ' . count($freePagesTab) . ' plus récentes :' : '') . '</p>';
                    }
                    echo '<br />';
                    foreach ($freePagesTab as $freePage) {
                        echo '<a href="/pages/' . $freePage['code_page'] . '.html">' . $freePage['page_title'] . '</a><br />';
                    }
                }
            }
// - Sorties

?>
			<br style="clear:both" />
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>

	<br style="clear:both" />
</div>
