<?php

use App\Entity\UserAttr;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

?>
<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            // vérification de l'ID de commission
            $id_commission = filter_input(INPUT_GET, 'id_commission', FILTER_VALIDATE_INT) ?: 0;

$commissionTmp = false;
if ($id_commission) {
	$req = 'SELECT * FROM caf_commission WHERE ';
    $req .= " id_commission = $id_commission ";
	$req .= ' LIMIT 1';

	$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
	while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
		$commissionTmp = $handle;
	}
}

if (!$commissionTmp) {
    echo '<p class="erreur"> ID invalide</p>';
} else {
    $code_commission = $commissionTmp['code_commission'];
    if (!(isGranted(SecurityConstants::ROLE_CONTENT_MANAGER) || allowed('comm_edit') || (user() && getUser()->hasAttribute(UserAttr::RESPONSABLE_COMMISSION, $code_commission)))) {
        echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour afficher cette page</p>';
    } else {
        echo "<h1>Fiche de la commission '" . $commissionTmp['title_commission'] . "'</h1><hr />";

        // ENCADRANTS
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
						AND params_user_attr LIKE 'commission:" . $commissionTmp['code_commission'] . "'
						ORDER BY code_usertype DESC, lastname_user, firstname_user
						";
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $benvoles_emails = [];
        echo '<h1>ENCADREMENT</h1>';
        echo '<table class="big-lines-table"><tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
								<td style="text-align:center; width:60px;"><img src="' . userImg($row['id_user'], 'pic') . '" alt="" title="" style="max-height:40px; max-width:60px;" /></td>
								<td>' . userlink($row['id_user'], $row['firstname_user'] . ' ' . $row['lastname_user']);
            if ($row['doit_renouveler_user'] > 0) {
                echo '&nbsp;<img src="/img/base/delete.png" title="licence expirée" style="margin-bottom:-4px">';
            }
            echo '</td>
								<td><a href="mailto:' . $row['email_user'] . '">' . $row['email_user'] . '</a></td>
								<td>' . $row['title_usertype'] . '</td>
							</tr>';
            $benvoles_emails[] = $row['email_user'];
        }
        echo '</tbody></table>';

        echo '<h1>LISTE DES E-MAILS</h1>';
        echo '<textarea id="emailsaddresses" rows="10" cols="70">' . implode(',', $benvoles_emails) . '</textarea>';
    }
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