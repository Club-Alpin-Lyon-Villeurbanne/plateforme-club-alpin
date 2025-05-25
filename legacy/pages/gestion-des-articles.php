<?php

use App\Legacy\LegacyContainer;

$MAX_ARTICLES_VALIDATION = LegacyContainer::getParameter('legacy_env_MAX_ARTICLES_VALIDATION');
$notif_validerunarticle = 0;

// NOTIFICATIONS ARTICLES
if (allowed('article_validate_all')) { // pouvoir de valider les articles
    $req = 'SELECT COUNT(id_article) FROM caf_article WHERE status_article=0 AND topubly_article=1';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunarticle = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
} elseif (allowed('article_validate')) { // pouvoir de valider les articles
    // recuperation des commissions sous notre joug
    $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('article_validate');

    $req = "SELECT COUNT(id_article)
	FROM caf_commission c, caf_article a
        LEFT JOIN caf_evt e ON (a.evt_article = e.id_evt)
        LEFT JOIN caf_commission ce ON e.commission_evt = ce.id_commission
	WHERE a.status_article=0
	AND a.topubly_article=1
	AND a.commission_article=c.id_commission
	AND (
	    c.code_commission IN ('" . implode("','", $tab) . "')
	    OR (a.commission_article = -1 AND e.id_evt IS NOT NULL AND ce.code_commission IN ('" . implode("','", $tab) . "'))
    )"; // condition OR pour toutes les commissions autorisées, et les compte-rendus de sorties (commission à -1) sur une commission ou j'ai acces

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $notif_validerunarticle = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
}

if (allowed('article_validate_all') || allowed('article_validate')) {
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
		LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";
    } elseif (allowed('article_validate')) { // commission non précisée ici = autorisation passée
        // recuperation des commissions sous notre joug
        $tab = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('article_validate');

        // compte nb total articles
        $req = "SELECT COUNT(id_article)
        FROM caf_commission c, caf_article a
            LEFT JOIN caf_evt e ON (a.evt_article = e.id_evt)
            LEFT JOIN caf_commission ce ON e.commission_evt = ce.id_commission
		WHERE a.status_article=0
		AND a.commission_article = c.id_commission
		AND (
            c.code_commission IN ('" . implode("','", $tab) . "')
            OR (a.commission_article = -1 AND e.id_evt IS NOT NULL AND ce.code_commission IN ('" . implode("','", $tab) . "'))
        ) "; // condition OR pour toutes les commissions autorisées

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
					, id_user, nickname_user, lastname_user, firstname_user, c.code_commission, c.title_commission
        FROM caf_commission c, caf_article a
		    LEFT JOIN caf_user u ON (u.id_user = a.user_article)
            LEFT JOIN caf_evt e ON (a.evt_article = e.id_evt)
            LEFT JOIN caf_commission ce ON e.commission_evt = ce.id_commission
		WHERE status_article=0
		AND a.commission_article = c.id_commission
		AND (
            c.code_commission IN ('" . implode("','", $tab) . "')
            OR (a.commission_article = -1 AND e.id_evt IS NOT NULL AND ce.code_commission IN ('" . implode("','", $tab) . "'))
        ) " // condition OR pour toutes les commissions autorisées
        . 'AND u.id_user = a.user_article
		ORDER BY topubly_article desc,  tsp_validate_article ASC
		LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";
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

?>


<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">

			<h1>Publication des articles</h1>
			<?php
            if (!allowed('article_validate') && !allowed('article_validate_all')) {
                echo '<p class="erreur">Droits insuffisants pour afficher cette page.</p>';
            } else {
                inclure($p1 . '-main', 'vide');

                if ($notif_validerunarticle > 0) {
                    echo '<br /><h2>' . $notif_validerunarticle . ' article' . ($notif_validerunarticle > 1 ? 's' : '') . ' proposé' . ($notif_validerunarticle > 1 ? 's' : '') . ' en attente de publication :</h2>';
                }

                // liste articles en attente de publication :
                if (!$notif_validerunarticle) {
                    echo '<p class="info">Aucun article n\'est en attente de publication pour l\'instant.</p>';
                } else {
                    // ************
                    // ** AFFICHAGE, on recupere le design de l'agenda
                    for ($i = 0; $i < count($articleStandby); ++$i) {
                        $article = $articleStandby[$i];

                        // check image
                        if (is_file(__DIR__ . '/../../public/ftp/articles/' . (int) $article['id_article'] . '/wide-figure.jpg')) {
                            $img = '/ftp/articles/' . (int) $article['id_article'] . '/wide-figure.jpg';
                        } else {
                            $img = '/ftp/articles/0/wide-figure.jpg';
                        }

                        // type d'article : lié à l'id de la commission en fait
                        if (0 == $article['commission_article']) {
                            $type = 'Actualité du club (toutes les commissions)';
                        } elseif (-1 == $article['commission_article']) {
                            $type = 'Compte rendu de sortie';
                        } else {
                            $type = 'Actualité « ' . $article['title_commission'] . ' »';
                        }

                        // Aff
                        echo '<hr />'
                        // Boutons
                        . '<div class="article-tools-valid">'

                            // apercu
                            . '<a class="nice2" href="/article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication" target="_blank">Aperçu</a> ';

                        // Moderation
                        echo '
							<form action="' . $versCettePage . '" method="post" style="display:inline" class="loading">
								<input type="hidden" name="operation" value="article_validate" />
								<input type="hidden" name="status_article" value="1" />
								<input type="hidden" name="id_article" value="' . ((int) $article['id_article']) . '" />
								<input type="submit" value="Autoriser &amp; publier" class="nice2 green" title="Autorise instantanément la publication de la sortie" />
							</form>

							<input type="button" value="Refuser" class="nice2 red" onclick="showModal($(this).next().html())" title="Ne pas autoriser la publication de cette sortie. Vous devrez ajouter un message au créateur de la sortie." />
							<div style="display:none" id="refuser-' . (int) $article['id_article'] . '">
								<form action="' . $versCettePage . '" method="post" class="loading">
									<input type="hidden" name="operation" value="article_validate" />
									<input type="hidden" name="status_article" value="2" />
									<input type="hidden" name="id_article" value="' . ((int) $article['id_article']) . '" />

									<p>Laissez un message à l\'auteur pour lui expliquer la raison du refus :</p>
									<input type="text" name="msg" class="type1" placeholder="ex : Décocher &laquo;A la Une&raquo;" />
									<input type="submit" value="Refuser la publication" class="nice2 red" />
									<input type="button" value="Annuler" class="nice2" onclick="closeModal()" />
								</form>
							</div>';
                        echo '</div>'

                            . '<div style="width:100px; float:left; padding:6px 10px 0 0;"><a href="/article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html?forceshow=true" target="_blank">'
                                // image liee
                                . '<img src="' . $img . '" alt="" title="" style="width:100%; " />'
                            . '</a></div>'
                            . '<div style="float:right; width:510px">'

                            // INFOS
                            . '<p style="padding:5px 5px; line-height:18px;">'
                                . '<b><a href="/article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html?forceshow=true" target="_blank">' . html_utf8($article['titre_article']) . '</a></b><br />'
                                . '<b>Type d\'article :</b> ' . $type . '<br />'
                                . '<span class="mini">Par ' . userlink($article['id_user'], $article['nickname_user']) . '</span> - '
                                . '<span class="mini">Le ' . jour(date('N', $article['tsp_article']), 'short') . ' ' . date('d', $article['tsp_article']) . ' ' . mois(date('m', $article['tsp_article'])) . ' ' . date('Y', $article['tsp_article']) . ' à ' . date('H:i', $article['tsp_article']) . '<br />'
                                . ($article['une_article'] ? '<span class="mini"><b><img src="/img/base/star.png" style="vertical-align:bottom; height:13px;" /> Article à la UNE</b> : cet article sera placé dans le slider de la page d\'accueil !</span>' : '')
                            . '</ul>'

                        . '</div>'
                        . '<br style="clear:both" />';
                    }
                }

                // liste articles cours de redaction :
                $notif_validerunarticle = count($articleStandbyRedac);
                if (0 == $notif_validerunarticle) {
                    echo '<p class="info">Aucun article n\'est en cours de rédaction pour l\'instant.</p>';
                } else {
                    echo '<br /><br /><h2>' . $notif_validerunarticle . ' article' . ($notif_validerunarticle > 1 ? 's' : '') . ' en cours de rédaction dont la publication n\'a pas été demandée :</h2>';

                    // ************
                    // ** AFFICHAGE, on recupere le design de l'agenda
                    for ($i = 0; $i < count($articleStandbyRedac); ++$i) {
                        $article = $articleStandbyRedac[$i];

                        // check image
                        if (is_file(__DIR__ . '/../../public/ftp/articles/' . (int) $article['id_article'] . '/wide-figure.jpg')) {
                            $img = '/ftp/articles/' . (int) $article['id_article'] . '/wide-figure.jpg';
                        } else {
                            $img = '/ftp/articles/0/wide-figure.jpg';
                        }

                        // type d'article : lié à l'id de la commission en fait
                        if (0 == $article['commission_article']) {
                            $type = 'Actualité du club (toutes les commissions)';
                        } elseif (-1 == $article['commission_article']) {
                            $type = 'Compte rendu de sortie';
                        } else {
                            $type = 'Actualité « ' . $article['title_commission'] . ' »';
                        }

                        // Aff
                        echo '<hr />'
                        // Boutons
                        . '<div class="article-tools-valid">'

                            // apercu
                            . '<a class="nice2" href="/article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html?forceshow=true" title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication" target="_blank">Aperçu</a> ';

                        // edition
                        if (allowed('article_edit_notmine') || allowed('article_edit', 'commission:' . $article['commission_article'])) {
                            echo '<a href="/article-edit/' . (int) $article['id_article'] . '.html" title="" class="nice2 orange">
									Modifier
								</a>';
                        }

                        // Suppression
                        if (allowed('article_delete_notmine') || allowed('article_delete', 'commission:' . $article['commission_article'])) {
                            echo '<a href="javascript:showModal($(\'#supprimer-form-' . $article['id_article'] . '\').html());" title="" class="nice2 red">
										Supprimer
									</a>';
                            echo '<div id="supprimer-form-' . (int) $article['id_article'] . '" style="display:none">
										<form action="' . $versCettePage . '" method="post" style="width:600px; text-align:left">
											<input type="hidden" name="operation" value="article_del" />
											<input type="hidden" name="id_article" value="' . $article['id_article'] . '" />
											<p>Voulez-vous vraiment supprimer définitivement cet article ? <br />Cette action est irréversible.</p>
											<input type="button" class="nice2" value="Annuler" onclick="closeModal();" />
											<input type="submit" class="nice2 red" value="Supprimer cet article" />
										</form>
									</div>';
                        }

                        echo '</div>';

                        echo '<div style="width:100px; float:left; padding:6px 10px 0 0;"><a href="/article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html?forceshow=true" target="_blank">'
                                // image liee
                                . '<img src="' . $img . '" alt="" title="" style="width:100%; " />'
                            . '</a></div>'
                            . '<div style="float:right; width:510px">'

                            // INFOS
                            . '<p style="padding:5px 5px; line-height:18px;">'
                                . '<b><a href="/article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html?forceshow=true" target="_blank">' . html_utf8($article['titre_article']) . '</a></b><br />'
                                . '<b>Type d\'article :</b> ' . $type . '<br />'
                                . '<span class="mini">Par ' . userlink($article['id_user'], $article['nickname_user']) . '</span> - '
                                . '<span class="mini">Le ' . jour(date('N', $article['tsp_article']), 'short') . ' ' . date('d', $article['tsp_article']) . ' ' . mois(date('m', $article['tsp_article'])) . ' ' . date('Y', $article['tsp_article']) . ' à ' . date('H:i', $article['tsp_article']) . '<br />'
                                . ($article['une_article'] ? '<span class="mini"><b><img src="/img/base/star.png" style="vertical-align:bottom; height:13px;" /> Article à la UNE</b> : cet article sera placé dans le slider de la page d\'accueil !</span>' : '')
                            . '</ul>'

                        . '</div>'
                        . '<br style="clear:both" />';
                    }
                }
                // PAGES
                if ($nbrPages > 1) {
                    echo '<nav class="pageSelect"><hr />';
                    for ($i = 1; $i <= $nbrPages; ++$i) {
                        echo '<a href="' . $p1 . '/' . $i . '.html" title="" class="' . ($pagenum == $i ? 'up' : '') . '">P' . $i . '</a> ' . ($i < $nbrPages ? '  ' : '');
                    }
                    echo '</nav>';
                }
            }
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