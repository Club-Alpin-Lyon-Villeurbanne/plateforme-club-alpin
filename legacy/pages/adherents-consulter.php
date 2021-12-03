<?php
if (!admin() && !allowed('user_edit_notme')) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) ($_GET['id_user']);
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit();
    }

    if (0 == count($userTab)) {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

        $id_user = $mysqli->real_escape_string($id_user);
        $req = 'SELECT * FROM '.$pbd."user WHERE id_user='".$id_user."' LIMIT 1";
        $userTab = [];
        $result = $mysqli->query($req);
        $userTab = $result->fetch_assoc();
        if ($userTab) {
            foreach ($userTab as $key => $val) {
                $userTab[$key] = inputVal($key, $userTab[$key]);
            }
        } else {
            echo 'Erreur : id invalide';
            exit();
        }

        // NOMBRE DE SORTIES
        $req = "
					SELECT id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, join_max_evt, join_start_evt
						, nickname_user
						, title_commission, code_commission
						, role_evt_join
					FROM caf_evt
						, caf_user
						, caf_commission
						, caf_evt_join
					WHERE status_evt=1
					AND id_user = user_evt
					AND id_commission = commission_evt
					AND evt_evt_join = id_evt
					AND status_evt_join = 1
					AND user_evt_join = $id_user "
                    // de la plus récente a la plus ancienne
                    .'ORDER BY  `tsp_evt` DESC
					LIMIT 200';
        $result = $mysqli->query($req);
        if ($result->num_rows > 0) {
            while ($tmpArray = $result->fetch_assoc()) {
                $userTab['sorties'][] = $tmpArray;
            }
        }

        // NOMBRE ARTICLES
        $req = 'SELECT id_article, code_article, titre_article, tsp_validate_article FROM '.$pbd."article WHERE user_article='".$id_user."' AND status_article=1 ORDER BY id_article DESC";
        $result = $mysqli->query($req);
        if ($result->num_rows > 0) {
            while ($tmpArray = $result->fetch_assoc()) {
                $userTab['articles'][] = $tmpArray;
            }
        }

        // FILIATION CHEF DE FAMILLE ?
        if ('' !== $userTab['cafnum_parent_user']) {
            $req = 'SELECT id_user, firstname_user, lastname_user, cafnum_user FROM '.$pbd."user WHERE cafnum_user = '".$mysqli->real_escape_string($userTab['cafnum_parent_user'])."' LIMIT 1";
            $result = $mysqli->query($req);
            $userTab['cafnum_parent_user'] = $result->fetch_assoc();
        }

        // FILIATION ENFANTS ?
        if ('' !== $userTab['cafnum_user']) {
            $req = 'SELECT id_user, firstname_user, lastname_user FROM '.$pbd."user WHERE cafnum_parent_user='".$mysqli->real_escape_string($userTab['cafnum_user'])."'";
            $result = $mysqli->query($req);
            if ($result->num_rows > 0) {
                while ($tmpArray = $result->fetch_assoc()) {
                    $userTab['enfants'][] = $tmpArray;
                }
            }
        }
    }

    function printTableRow($header, $value)
    {
        echo "
			<tr>
				<td width='35%' valign='top'>".$header.'</td>
				<td>'.$value.'</td>
			</tr>
			<tr>
				<td colspan=2><hr /></td>
			</tr>
		';
    } ?>

	<h1>Fiche adhérent : <?php echo $userTab['firstname_user'].' '.$userTab['lastname_user']; ?></h1>

	<hr />



		<br />

		<table width='100%'>

			<?php
                printTableRow('<img src="'.userImg($userTab['id_user'], 'pic').'" alt="" title="" style="max-width:100%" />', '<h1>'.$userTab['civ_user'].' '.$userTab['firstname_user'].' '.$userTab['lastname_user'].'</h1>');

    $rowValue = '<a href="/user-full/'.$userTab['id_user'].'.html" title="Fiche profil" target="_top">'.$userTab['nickname_user'].'</a>';
    // possibilite de supprimer le user si pas de sortie ni articles
    if (admin() && !is_array($userTab['sorties']) && !is_array($userTab['articles'])) {
        $rowValue .= '&nbsp;&nbsp;&nbsp;<a href="includer.php?p=pages/adherents-supprimer.php&amp;id_user='.(int) ($userTab['id_user']).'&amp;nom='.urlencode($userTab['civ_user'].' '.$elt['firstname_user'].' '.$userTab['lastname_user']).'" title="Supprimer le compte de cet utilisateur"><img src="/img/base/user_delete.png" alt="SUPPRIMER" title=""></a> ';
    }
    printTableRow('Pseudo :', $rowValue);

    $rowValue = $userTab['cafnum_user'];
    if (0 == $userTab['valid_user'] && '' !== $userTab['cookietoken_user']) {
        $rowValue .= '<br />URL d\'activation du compte : ';
        if (admin()) {
            $rowValue .= '<a href="'.$p_racine.'user-confirm/'.$userTab['cookietoken_user'].'-'.$userTab['id_user'].'.html">';
        }
        $rowValue .= $p_racine.'user-confirm/'.$userTab['cookietoken_user'].'-'.$userTab['id_user'].'.html';
        if (admin()) {
            $rowValue .= '</a>';
        }
        $rowValue .= '<br />';
    }
    printTableRow('Numéro de licence :', $rowValue);

    $rowValue = '';
    if ($userTab['cafnum_parent_user']) {
        $rowValue = '<a href="includer.php?p=pages/adherents-consulter.php&amp;id_user='.(int) ($userTab['cafnum_parent_user']['id_user']).'">'.$userTab['cafnum_parent_user']['firstname_user'].' '.$userTab['cafnum_parent_user']['lastname_user'].'</a>';
        printTableRow('Parent (chef de famille) :', $rowValue);
    }

    $rowValue = '';
    if (is_array($userTab['enfants'])) {
        foreach ($userTab['enfants'] as $enfant) {
            $rowValue[] = '<a href="includer.php?p=pages/adherents-consulter.php&amp;id_user='.(int) ($enfant['id_user']).'">'.$enfant['firstname_user'].' '.$enfant['lastname_user'].'</a>';
        }

        printTableRow('Adhérents affiliés : ', implode('<br />', $rowValue));
    }

    $rowValue = 'NC';

    if ($userTab['date_adhesion_user'] > 0) {
        $rowValue = date('d/m/Y', $userTab['date_adhesion_user']);
    }

    if ($userTab['alerte_renouveler_user'] || $userTab['doit_renouveler_user']) {
        //$rowValue = '<span class="alerte">'.$rowValue.'</span>';
        if ($userTab['doit_renouveler_user']) {
            //$rowValue .= '   (expirée)';
            $rowValue .= '&nbsp;&nbsp;&nbsp;<img src="/img/base/delete.png">';
        }
    } elseif ($userTab['date_adhesion_user']) {
        $rowValue .= '&nbsp;&nbsp;&nbsp;<img src="/img/base/tick2.png">';
    }

    printTableRow('Date d\'adhésion (renouvellement) :', $rowValue);

    if ($userTab['birthday_user']) {
        printTableRow('Date de naissance :', date('d/m/Y', $userTab['birthday_user']).'&nbsp;&nbsp;&nbsp;('.getYearsSinceDate($userTab['birthday_user']).' ans)');
    }
    if ($userTab['email_user']) {
        printTableRow('E-mail :', '<a href="mailto:'.$userTab['email_user'].'">'.$userTab['email_user'].'</a>');
    }
    if ($userTab['tel_user']) {
        printTableRow('Numéro de téléphone personnel :', $userTab['tel_user']);
    }
    if ($userTab['tel2_user']) {
        printTableRow('Numéro de téléphone de sécurité :', $userTab['tel2_user']);
    }
    printTableRow('Adresse :', $userTab['adresse_user'].'<br />'.$userTab['cp_user'].' '.$userTab['ville_user'].' '.$userTab['pays_user']);

    $rowValue = '';
    switch ($userTab['auth_contact_user']) {
                            case 'all': $rowValue .= 'Tous les visiteurs du site';
                                break;
                            case 'users': $rowValue .= 'Tous les adhérents, inscrits et connectés sur ce site';
                                break;
                            case 'none': $rowValue .= 'Responsables du club uniquement';
                                break;
                        }
    printTableRow('Qui peut me contacter sur le site, via un formulaire de contact (adresse e-mail jamais dévoilée) ? :', $rowValue);
    if ($userTab['manuel_user']) {
        printTableRow('Créé manuellement :', 'OUI&nbsp;&nbsp;&nbsp;<img src="/img/base/user_manuel.png">');
    }
    if ($userTab['nomade_user']) {
        printTableRow('Nomade :', 'OUI&nbsp;&nbsp;&nbsp;<img src="/img/base/nomade_user.png">');
    }
    printTableRow('Statut compte internet :', ((1 == $userTab['valid_user']) ? 'ACTIF' : ((2 == $userTab['valid_user']) ? 'DESACTIVE' : 'NON ACTIF')));
    if ($userTab['created_user']) {
        printTableRow('Création du compte :', date('d/m/Y', $userTab['created_user']));
    }
    if ($userTab['ts_insert_user']) {
        printTableRow('Insertion en base :', date('d/m/Y', $userTab['ts_insert_user']));
    }
    if ($userTab['ts_update_user']) {
        printTableRow('Mise à jour en base :', date('d/m/Y', $userTab['ts_update_user']));
    }
    if (is_array($userTab['articles'])) {
        $rowValue = [];
        foreach ($userTab['articles'] as $article) {
            $rowValue[] = '<a href="article/'.html_utf8($article['code_article']).'-'.(int) ($article['id_article']).'.html?forceshow=true" target="_blank">'.date('d.m.Y', $article['tsp_validate_article']).' - '.$article['titre_article'].'</a>';
        }
        printTableRow('Articles :', '<font size="-1" >'.implode('<br />', $rowValue).'</font>');
    }

    if (is_array($userTab['sorties'])) {
        $rowValue = [];
        $rowRoleEvtTab = [];
        $rowRoleEvtValue = '';
        foreach ($userTab['sorties'] as $evt) {
            ++$rowValueHeader[$evt['role_evt_join']];
            $row = '<a target="_blank" href="sortie/'.html_utf8($evt['code_evt']).'-'.(int) ($evt['id_evt']).'.html?commission='.$evt['code_commission'];
            if (allowed('evt_validate') && 1 != $evt['status_evt']) {
                $row .= '&forceshow=true';
            }
            $row .= '" title="">'.date('d.m.Y', $evt['tsp_evt']).' - '.html_utf8($evt['title_commission']).' - '.html_utf8($evt['titre_evt']).'</a>';
            $rowValue[] = $row;
        }
        arsort($rowValueHeader);
        foreach ($rowValueHeader as $role => $nbRole) {
            $rowRoleEvtValue .= "&nbsp;&nbsp;&nbsp;- $role : $nbRole<br />";
        }
        printTableRow('Sorties :<br />'.'<font size="-1" >'.$rowRoleEvtValue.'</font>', '<font size="-1" >'.implode('<br />', $rowValue).'</font>');
    }
    printTableRow('N° ID en base :', $userTab['id_user']); ?>


		</table>

<?php
unset($userTab);
}
