<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            // vérification de l'ID de commission
            $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

            $id_commission = (int) ($_GET['id_commission']);
            $code_commission = $mysqli->real_escape_string($_GET['code_commission']);

            if (!(admin() || allowed('comm_edit') || in_array('Resp. de commission, '.$code_commission, $_SESSION['user']['status'], true))) {
                echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour afficher cette page</p>';
            } else {
                $commissionTmp = false;
                $req = 'SELECT * FROM caf_commission WHERE ';
                if ($id_commission) {
                    $req .= " id_commission = $id_commission ";
                } elseif ($code_commission) {
                    $req .= " code_commission = '$code_commission' ";
                }
                $req .= ' LIMIT 1';

                $handleSql = $mysqli->query($req);
                while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
                    $commissionTmp = $handle;
                }

                if (!$commissionTmp) {
                    echo '<p class="erreur"> ID invalide</p>';
                } else {
                    echo "<h1>Fiche de la commission '".$commissionTmp['title_commission']."'</h1><hr />";

                    //print_r ($commissionTmp);

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
								|| code_usertype LIKE 'coencadrant'
							)
						AND usertype_user_attr = id_usertype
						AND user_user_attr = id_user
						AND params_user_attr LIKE 'commission:".$commissionTmp['code_commission']."'
						ORDER BY code_usertype DESC, lastname_user, firstname_user
						";
                    $result = $mysqli->query($req);
                    $benvoles_emails = [];
                    echo '<h1>BENEVOLES</h1>';
                    echo '<table class="big-lines-table"><tbody>';
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>
								<td style="text-align:center; width:60px;"><img src="'.userImg($row['id_user'], 'pic').'" alt="" title="" style="max-height:40px; max-width:60px;" /></td>
								<td>'.userlink($row['id_user'], $row['firstname_user'].' '.$row['lastname_user']);
                        if ($row['doit_renouveler_user'] > 0) {
                            echo '&nbsp;<img src="/img/base/delete.png" title="licence expirée" style="margin-bottom:-4px">';
                        }
                        echo '</td>
								<td><a href="mailto:'.$row['email_user'].'">'.$row['email_user'].'</a></td>
								<td>'.$row['title_usertype'].'</td>
							</tr>';
                        $benvoles_emails[] = $row['email_user'];
                    }
                    echo '</tbody></table>';

                    echo '<h1>LISTE DES E-MAILS</h1>';
                    echo '<textarea id="emailsaddresses" rows="10" cols="70">'.implode(',', $benvoles_emails).'</textarea>';

                    $mysqli->close();
                }
            }
            ?>
		</div>
	</div>

	<!-- partie droite -->
	<?php
    include __DIR__.'/../includes/right-type-agenda.php';
    ?>

	<br style="clear:both" />
</div>