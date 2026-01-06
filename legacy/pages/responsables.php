<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;

?>
<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            inclure($p1, 'vide');

// liste des commissions visibles par ordre alphabétique
ksort($comTab);

// creation des raccourcis vers les commissions
echo '<p>';
foreach ($comTab as $code => $data) {
    echo '<a class="lien-big" style="color:black;" href="/responsables.html#' . $data['code_commission'] . '">' . HtmlHelper::escape($data['title_commission']) . '</a>
				&nbsp;';
}
echo '<p>';

// la requete se fait ds la boucle
foreach ($comTab as $code => $data) {
    $dejaVus = []; // IDs des users déja mis en responsable dans cette commsision (evite les doublons pour qqn à la fois resp. de comm' et encadrant...)

    echo '<h2><a id="' . $data['code_commission'] . '">&gt; ' . HtmlHelper::escape($data['title_commission']) . '</a></h2>';
    $req = " SELECT
						id_user, civ_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, doit_renouveler_user
						, title_usertype
					FROM
						caf_user
						, caf_usertype
						, caf_user_attr
					WHERE
						(
							code_usertype LIKE 'responsable-commission'
							|| code_usertype LIKE 'encadrant'
							|| code_usertype LIKE 'stagiaire'
							|| code_usertype LIKE 'coencadrant'
							|| code_usertype LIKE 'benevole_encadrement'
						)
					AND usertype_user_attr = id_usertype
					AND user_user_attr = id_user
					AND doit_renouveler_user = 0
                    AND is_deleted = 0
					AND params_user_attr LIKE 'commission:" . $code . "'
					ORDER BY hierarchie_usertype DESC, firstname_user ASC
					";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    echo '<table class="big-lines-table"><tbody>';
    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['id_user'], $dejaVus, true)) {
            $userImg = '';
            if (user()) {
                $userImg = '<td style="text-align:center; width:60px;"><img src="' . userImg($row['id_user'], 'pic') . '" alt="" title="" style="max-height:40px; max-width:60px;" /></td>';
            }
            echo '<tr>
								' . $userImg . '
								<td>' . userlink($row['id_user'], $row['nickname_user']) . '</td>
								<td>' . $row['title_usertype'] . '</td>
							</tr>';
        }
        $dejaVus[] = $row['id_user'];
    }
    echo '</tbody></table><hr /><br />';
}
?>
		</div>
	</div>

	<!-- partie droite -->
	<?php
    require __DIR__ . '/../includes/right-type-agenda.php';
?>

	<br style="clear:both" />

</div>
