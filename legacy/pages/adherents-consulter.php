<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

if (!isGranted(SecurityConstants::ROLE_ADMIN) && !allowed('user_edit_notme')) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) $_GET['id_user'];
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit;
    }

    if (empty($userTab)) {
        $id_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString($id_user);
        $req = "SELECT * FROM caf_user WHERE id_user='" . $id_user . "' LIMIT 1";
        $userTab = [];
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $userTab = $result->fetch_assoc();
        if ($userTab) {
            foreach ($userTab as $key => $val) {
                $userTab[$key] = inputVal($key, $userTab[$key]);
            }
        } else {
            echo 'Erreur : id invalide';
            exit;
        }

        // NOMBRE DE SORTIES
        $req = "
					SELECT id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, start_date, end_date, caf_evt.created_at, caf_evt.updated_at, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, join_max_evt, join_start_date
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
                    . 'ORDER BY  `start_date` DESC
					LIMIT 200';
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $userTab['sorties'] = [];
        if ($result->num_rows > 0) {
            while ($tmpArray = $result->fetch_assoc()) {
                $userTab['sorties'][] = $tmpArray;
            }
        }

        // ROLES
        // ( code_usertype LIKE 'responsable-commission' || code_usertype LIKE 'encadrant' || code_usertype LIKE 'coencadrant' )

        $req = 'SELECT title_usertype, params_user_attr, description_user_attr
        FROM caf_usertype, caf_user_attr
        WHERE usertype_user_attr = id_usertype
        AND user_user_attr = ' . $id_user . '
        ORDER BY title_usertype, params_user_attr';
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $userTab['roles'] = [];
        if ($result->num_rows > 0) {
            while ($tmpArray = $result->fetch_assoc()) {
                $userTab['roles'][] = $tmpArray;
            }
        }

        // NOMBRE ARTICLES
        $req = "SELECT id_article, code_article, titre_article, created_at FROM caf_article WHERE user_article='" . $id_user . "' AND status_article=1 ORDER BY id_article DESC";
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $userTab['articles'] = [];
        if ($result->num_rows > 0) {
            while ($tmpArray = $result->fetch_assoc()) {
                $userTab['articles'][] = $tmpArray;
            }
        }

        // FILIATION CHEF DE FAMILLE ?
        if ('' != $userTab['cafnum_parent_user']) {
            $req = LegacyContainer::get('legacy_user_repository')->findOneByLicenseNumber($userTab['cafnum_parent_user'], 'HYDRATE_LEGACY');
            $userTab['cafnum_parent_user'] = $req;
        }

        // FILIATION ENFANTS ?
        if ('' !== $userTab['cafnum_user']) {
            $req = "SELECT id_user, firstname_user, lastname_user FROM caf_user WHERE cafnum_parent_user='" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($userTab['cafnum_user']) . "'";
            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);

            $userTab['enfants'] = [];
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
				<td width='35%' valign='top'>" . $header . '</td>
				<td>' . $value . '</td>
			</tr>
			<tr>
				<td colspan=2><hr /></td>
			</tr>
		';
    } ?>

	<h1>Fiche adhérent : <?php echo ucfirst($userTab['firstname_user']) . ' ' . strtoupper($userTab['lastname_user']); ?></h1>

	<hr />



		<br />

		<table width='100%'>

			<?php
                printTableRow('<img src="' . userImg($userTab['id_user'], 'pic') . '" alt="" title="" style="max-width:100%" />', '<h1>' . ucfirst($userTab['firstname_user']) . ' ' . strtoupper($userTab['lastname_user']) . '</h1>');

    $rowValue = '<a href="' . LegacyContainer::get('legacy_router')->generate('user_full', ['id' => $userTab['id_user']]) . '" title="Fiche profil" target="_top">' . $userTab['nickname_user'] . '</a>';
    // possibilite de supprimer le user si pas de sortie ni articles
    if (isGranted(SecurityConstants::ROLE_ADMIN) && !is_array($userTab['sorties']) && !is_array($userTab['articles'])) {
        $rowValue .= '&nbsp;&nbsp;&nbsp;<a href="/includer.php?p=pages/adherents-supprimer.php&amp;id_user=' . (int) $userTab['id_user'] . '&amp;nom=' . urlencode(ucfirst($userTab['firstname_user']) . ' ' . strtoupper($userTab['lastname_user'])) . '" title="Supprimer le compte de cet utilisateur"><img src="/img/base/user_delete.png" alt="SUPPRIMER" title=""></a> ';
    }
    printTableRow('Pseudo :', $rowValue);

    $rowValue = $userTab['cafnum_user'];
    printTableRow('Numéro de licence :', $rowValue);

    $rowValue = '';
    if ($userTab['cafnum_parent_user']) {
        $rowValue = '<a href="/includer.php?p=pages/adherents-consulter.php&amp;id_user=' . (int) $userTab['cafnum_parent_user']['id_user'] . '">' . ucfirst($userTab['cafnum_parent_user']['firstname_user']) . ' ' . strtoupper($userTab['cafnum_parent_user']['lastname_user']) . '</a>';
        printTableRow('Parent (chef de famille) :', $rowValue);
    }

    $rowValue = [];
    if (is_array($userTab['enfants'])) {
        foreach ($userTab['enfants'] as $enfant) {
            $rowValue[] = '<a href="/includer.php?p=pages/adherents-consulter.php&amp;id_user=' . (int) $enfant['id_user'] . '">' . ucfirst($enfant['firstname_user']) . ' ' . strtoupper($enfant['lastname_user']) . '</a>';
        }

        printTableRow('Adhérents affiliés : ', implode('<br />', $rowValue));
    }

    $rowValue = 'NC';

    if (!empty($userTab['join_date'])) {
        $rowValue = (new DateTimeImmutable($userTab['join_date']))?->format('d/m/Y');
    }

    if ($userTab['alerte_renouveler_user'] || $userTab['doit_renouveler_user']) {
        // $rowValue = '<span class="alerte">'.$rowValue.'</span>';
        if ($userTab['doit_renouveler_user']) {
            // $rowValue .= '   (expirée)';
            $rowValue .= '&nbsp;&nbsp;&nbsp;<img src="/img/base/delete.png">';
        }
    } elseif (!empty($userTab['join_date'])) {
        $rowValue .= '&nbsp;&nbsp;&nbsp;<img src="/img/base/tick2.png">';
    }

    printTableRow('Date d\'adhésion (renouvellement) :', $rowValue);

    if ($userTab['birthdate']) {
        $birthdate = new DateTimeImmutable($userTab['birthdate']);
        $age = $birthdate->diff(new DateTime())->y;
        printTableRow('Date de naissance :', $birthdate->format('d/m/Y') . '&nbsp;&nbsp;&nbsp;(' . $age > 0 ? $age . ' ans' : '?)');
    }
    if ($userTab['email_user']) {
        printTableRow('E-mail :', '<a href="mailto:' . $userTab['email_user'] . '">' . $userTab['email_user'] . '</a>');
    }
    if ($userTab['tel_user']) {
        printTableRow('Numéro de téléphone personnel :', $userTab['tel_user']);
    }
    if ($userTab['tel2_user']) {
        printTableRow('Numéro de téléphone de sécurité :', $userTab['tel2_user']);
    }
    printTableRow('Adresse :', $userTab['adresse_user'] . '<br />' . $userTab['cp_user'] . ' ' . $userTab['ville_user'] . ' ' . $userTab['pays_user']);

    if ($userTab['manuel_user']) {
        printTableRow('Créé manuellement :', 'OUI&nbsp;&nbsp;&nbsp;<img src="/img/base/user_manuel.png">');
    }
    if ($userTab['nomade_user']) {
        printTableRow('Nomade :', 'OUI&nbsp;&nbsp;&nbsp;<img src="/img/base/nomade_user.png">');
    }
    if ($userTab['created_at']) {
        printTableRow('Insertion en base :', (new DateTime($userTab['created_at']))?->format('d/m/Y'));
    }
    if ($userTab['updated_at']) {
        printTableRow('Mise à jour en base :', (new DateTime($userTab['updated_at']))?->format('d/m/Y'));
    }
    if (is_array($userTab['articles'])) {
        $rowValue = [];
        foreach ($userTab['articles'] as $article) {
            $rowValue[] = '<a href="' . LegacyContainer::get('legacy_router')->generate('article_view', ['code' => $article['code_article'], 'id' => (int) $article['id_article'], 'forceshow' => 'true'], UrlGeneratorInterface::ABSOLUTE_URL) . '" target="_blank">' . (new DateTime($article['created_at']))?->format('d/m/Y') . ' - ' . HtmlHelper::escape($article['titre_article']) . '</a>';
        }
        printTableRow('Articles :', '<font size="-1" >' . implode('<br />', $rowValue) . '</font>');
    }

    if (is_array($userTab['sorties'])) {
        $rowValue = [];
        $rowRoleEvtTab = [];
        $rowRoleEvtValue = '';
        $rowValueHeader = [];

        foreach ($userTab['sorties'] as $evt) {
            if (!isset($rowValueHeader[$evt['role_evt_join']])) {
                $rowValueHeader[$evt['role_evt_join']] = 0;
            }
            ++$rowValueHeader[$evt['role_evt_join']];

            $row = '<a target="_blank" href="' . LegacyContainer::get('legacy_router')->generate('sortie', ['code' => $evt['code_evt'], 'id' => (int) $evt['id_evt']], UrlGeneratorInterface::ABSOLUTE_URL);
            if (allowed('evt_validate') && 1 != $evt['status_evt']) {
                $row .= '&forceshow=true';
            }
            $row .= '" title="">' . (new DateTimeImmutable($evt['start_date']))?->format('d/m/Y') . ' - ' . HtmlHelper::escape($evt['title_commission']) . ' - ' . HtmlHelper::escape($evt['titre_evt']) . '</a>';
            $rowValue[] = $row;
        }
        arsort($rowValueHeader);
        foreach ($rowValueHeader as $role => $nbRole) {
            $rowRoleEvtValue .= "&nbsp;&nbsp;&nbsp;- $role : $nbRole<br />";
        }
        printTableRow('Sorties :<br /><font size="-1" >' . $rowRoleEvtValue . '</font>', '<font size="-1" >' . implode('<br />', $rowValue) . '</font>');
    }

    if (is_array($userTab['roles'])) {
        $rowValue = [];
        foreach ($userTab['roles'] as $role) {
            if ($role['description_user_attr']) {
                $role['description_user_attr'] = ' <em>(' . $role['description_user_attr'] . ')</em>';
            }
            $rowValue[] = implode(' ', $role);
        }
        printTableRow('Rôles spécifiques :', implode('<br />', $rowValue));
    }
    printTableRow('N° ID en base :', $userTab['id_user']); ?>


		</table>

<?php
unset($userTab);
}
