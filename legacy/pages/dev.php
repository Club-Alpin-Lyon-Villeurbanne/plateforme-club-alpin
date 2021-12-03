<div id="main" role="main">

	<?php

    // ATTRIBUTION DES DROITS AUX USERS
    if (true) {
        ?>
		<br /><br /><br /><br /><hr /><br /><br /><br /><br />
		<h3>Attribution des statuts à l'utilisateur : <?php echo html_utf8(getUser()->getNicknameUser()); ?></h3>
		<?php
        // req sql : trouver les attributs liés à cet user
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

        $req = 'SELECT title_usertype, code_usertype, params_user_attr, id_user_attr
		FROM caf_usertype, caf_user_attr
		WHERE usertype_user_attr = id_usertype
		AND user_user_attr = '.getUser()->getIdUser().'
		ORDER BY id_usertype DESC';

        $result = $mysqli->query($req);
        echo '<ul>';
        while ($row = $result->fetch_assoc()) {
            echo '<li>'
                        .'<b>'.html_utf8($row['title_usertype']).'</b>'.($row['params_user_attr'] ? ', '.str_replace(':', ' ', $row['params_user_attr']) : '')
                        .('adherent' != $row['code_usertype'] ?
                            '<form action="'.$versCettePage.'" method="post" onsubmit="return(confirm(\'Vraiment supprimer cet attribut ?\n Cet utilisateur ne sera plus '.addslashes(html_utf8($row['title_usertype'])).'\'))" style="display:inline;">
								<input type="hidden" name="operation" value="user_attr_del" />
								<input type="hidden" name="id_user_attr" value="'.$row['id_user_attr'].'" />
								<input type="image" src="/img/base/x.png" alt="DEL" title="Supprimer cet attribut" class="upfade" />
							</form>'
                        : '')
                    .'</li>';
        }
        echo '</ul>';

        // AJOUTER UN ATTRIBUT
        ?>
		<form action="<?php echo $versCettePage; ?>" method="post">
			<input type="hidden" name="operation" value="user_attr_add" />
			<input type="hidden" name="id_user" value="<?php echo getUser()->getIdUser(); ?>" />

			<h3>Ajouter un attribut à cet adhérent :</h3>
			<?php
            // message
            if ('user_attr_add' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
            }
        if ('user_attr_add' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
            echo '<div class="info">Mise à jour effectuée à '.date('H:i:s', time()).'.</div>';
        }

        // liste des types :
        $req = "SELECT * FROM caf_usertype WHERE code_usertype NOT LIKE 'visiteur' AND code_usertype NOT LIKE 'adherent' ";
        $result = $mysqli->query($req);
        echo '<select name="id_usertype"><option></option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="'.(int) ($row['id_usertype']).'" class="precise-comm-'.(int) ($row['limited_to_comm_usertype']).'">'.html_utf8($row['title_usertype']).'</option>';
        }
        echo '</select>';

        // liste des commissions
        $req = 'SELECT * FROM caf_commission ORDER BY ordre_commission ASC ';
        $result = $mysqli->query($req);

        echo '<div id="commissions-pick" class="nice-checkboxes">';
        while ($row = $result->fetch_assoc()) {
            echo '<label for="commissions-pick-'.$row['id_commission'].'"><input type="checkbox" name="commission[]" value="commission:'.html_utf8($row['code_commission']).'" id="commissions-pick-'.$row['id_commission'].'" /> ['.$row['img_commission'].'] '.$row['title_commission'].' </label> ';
        }
        echo '</div>';

        // script d'ergonomie
            ?>
			<br />
			<br />
			<input type="submit" value="Appliquer cet attribut" class="nice" />

			<script type="text/javascript">
				$().ready(function(){

					// affichage des checkbox "commission" si besoin
					$('#commissions-pick').hide();
					$('select[name=id_usertype]').bind('change focus', function(){
						if($(this).find('option:selected').hasClass('precise-comm-1'))
							$('#commissions-pick').slideDown({queue:false, duration:500});
						else
							$('#commissions-pick').slideUp({queue:false, duration:500});
					});



				});
			</script>
		</form>
		<?php
    }

    // FONCTION allowed : AIS-JE LE DROIT
    if (true) {
        echo '<br /><br /><br /><br /><hr /><br /><br /><br /><br />';

        // FONCTION "SUIS-JE-AUTORISE"
        if (true) {
            $devCommission = 'vtt';
            if (allowed('article_create', 'commission:'.$devCommission)) {
                echo "J'ai le droit de créer un article pour la commission $devCommission<hr />";
            } else {
                echo "Vous n'avez pas les droits nécessaire pour créer un article pour la commission $devCommission<hr />";
            }

            $devCommission = 'alpinisme';
            if (allowed('article_create', 'commission:'.$devCommission)) {
                echo "J'ai le droit de créer un article pour la commission $devCommission<hr />";
            } else {
                echo "Vous n'avez pas les droits nécessaire pour créer un article pour la commission $devCommission<hr />";
            }

            $devCommission = 'splitboard';
            if (allowed('article_create', 'commission:'.$devCommission)) {
                echo "J'ai le droit de créer un article pour la commission $devCommission<hr />";
            } else {
                echo "Vous n'avez pas les droits nécessaire pour créer un article pour la commission $devCommission<hr />";
            }

            $devCommission = 'speedriding';
            if (allowed('article_create', 'commission:'.$devCommission)) {
                echo "J'ai le droit de créer un article pour la commission $devCommission<hr />";
            } else {
                echo "Vous n'avez pas les droits nécessaire pour créer un article pour la commission $devCommission<hr />";
            }
        }
    }

    // LISTAGE DES DROITS
    if (true) {
        echo '<br /><br /><br /><br /><hr /><br /><br /><br /><br />';

        // ************************
        // OPERATIONS

        // ************************
        // AFFICHAGE
        $typeTab = [];
        $rightTab = [];
        $attrTab = [];
        $tmp = '';

        echo '<h3>Liste des types de adhérents et de leurs droits</h3>';
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

        // tous les types
        $req = '
		SELECT id_usertype, code_usertype, title_usertype
		FROM caf_usertype
		ORDER BY hierarchie_usertype
		';
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $typeTab[] = $handle;
        }

        // tous les droits
        $req = '
		SELECT id_userright, code_userright, title_userright, parent_userright
		FROM caf_userright
		ORDER BY ordre_userright
		';
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $rightTab[] = $handle;
        }

        // toutes les attributions de droits aux types
        $req = '
		SELECT type_usertype_attr, right_usertype_attr
		FROM caf_usertype_attr
		';
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $attrTab[] = $handle['type_usertype_attr'].'-'.$handle['right_usertype_attr'];
        } ?>

		<form action="<?php echo $versCettePage; ?>" method="post" onsubmit="return(confirm('Ces valeurs vont remplacer les valeurs existantes, OK ?'))">
			<input type="hidden" name="operation" value="usertype_attr_edit" />

			<?php
            // on fait courir le tableau
            echo '<table class="user-right-edit-table"><thead><tr><th></th>';
        foreach ($typeTab as $usertype) {
            echo '<th>'.html_utf8($usertype['title_usertype']).'</th>';
        } // types (abscisses)
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ($rightTab as $userright) { // types (ordonnées)
            if ($tmp != $userright['parent_userright']) {
                $tmp = $userright['parent_userright'];
                echo '<tr><td colspan="'.(count($typeTab) + 1).'"><b>'.html_utf8($userright['parent_userright']).'</b></td></tr>';
            }
            echo '<tr class="rightline"><td class="left"><span>'.html_utf8($userright['title_userright']).'</span><input type="text" value="'.html_utf8($userright['code_userright']).'" /></td>';
            for ($i = 0; $i < count($typeTab); ++$i) {
                // right>type : autorisé ou pas ? valeur true || false
                $on = (in_array($typeTab[$i]['id_usertype'].'-'.$userright['id_userright'], $attrTab, true) ? true : false);
                echo '<td class="toggle '.($on ? 'true' : 'false').'">'
                        .'<input '.($on ? '' : 'disabled="disabled"').' type="hidden" name="usertype_attr[]" value="'.$typeTab[$i]['id_usertype'].'-'.$userright['id_userright'].'" />' // Paire d'ID
                        .'<span class="clair">'.html_utf8($typeTab[$i]['title_usertype']).'</span>'
                        .'</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>'; ?>

			<input type="submit" value="Enregistrer la matrice dans cet état" class="nice" />

			<script type="text/javascript">
			$().ready(function(){
				// afficher/selectionner le code d'un droit
				$('.user-right-edit-table td.left').click(function(){
					$(this).addClass('selected').find('input:visible').focus().bind('blur', function(){
						$(this).parents('td').removeClass('selected');
					});
				});
				// change attribution
				$('.user-right-edit-table td.toggle').click(function(){
					$(this)	.toggleClass('edited')
							.toggleClass('false')
							.toggleClass('true');

					if($(this).hasClass('true'))	$(this).find('input').removeAttr('disabled');
					else							$(this).find('input').attr('disabled', 'disabled');
				});
			});
			</script>
		</form>

		<?php
        echo '<br /><br /><br /><br />';
    }
    ?>


</div>
