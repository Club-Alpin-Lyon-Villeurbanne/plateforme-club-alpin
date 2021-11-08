<?php
if (!admin()) {
    echo 'Votre session administrateur a expiré ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) ($_GET['id_user']);
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit();
    } ?>
	<h2>Attribution des statuts à l'utilisateur : <?php echo html_utf8($_GET['nom']); ?></h2>
	<?php
    // req sql : trouver les attributs liés à cet user
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

    $req = 'SELECT title_usertype, code_usertype, params_user_attr, id_user_attr
	FROM caf_usertype, caf_user_attr
	WHERE usertype_user_attr = id_usertype
	AND user_user_attr = '.$id_user.'
	ORDER BY hierarchie_usertype DESC';

    $statsTab = [];
    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $statsTab[] = $row;
    }

    if (count($statsTab)) {
        echo '<h3>Statuts actuels :</h3>'
            .'<ul>';
        foreach ($statsTab as $row) {
            echo '<li>'
                    .'- <b>'.html_utf8($row['title_usertype']).'</b>'.($row['params_user_attr'] ? ', '.str_replace(':', ' ', $row['params_user_attr']) : '')
                    .('adherent' != $row['code_usertype'] ?
                        '<form action="'.$versCettePage.'" method="post" onsubmit="return(confirm(\'Vraiment supprimer cet attribut ?\n Cet utilisateur ne sera plus '.addslashes(html_utf8($row['title_usertype'])).'\'))" style="display:inline;">
							<input type="hidden" name="operation" value="user_attr_del_admin" />
							<input type="hidden" name="id_user_attr" value="'.$row['id_user_attr'].'" />
							<input type="image" src="/img/base/x.png" alt="DEL" title="Supprimer cet attribut" class="upfade" />
						</form>'
                    : '')
                .'</li>';
        }
        echo '</ul><br /><br />';
    }

    // AJOUTER UN ATTRIBUT
    ?>
	<form action="<?php echo $versCettePage; ?>" method="post">
		<input type="hidden" name="operation" value="user_attr_add_admin" />
		<input type="hidden" name="id_user" value="<?php echo $id_user; ?>" />

		<h3>Ajouter un attribut à cet adhérent :</h3>
		<?php
        // message
        if ('user_attr_add_admin' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
    if ('user_attr_add_admin' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info">Mise à jour effectuée à '.date('H:i:s', $p_time).'.</div>';
    }

    // liste des types :
    $req = "SELECT * FROM caf_usertype WHERE code_usertype NOT LIKE 'visiteur' AND code_usertype NOT LIKE 'adherent' ORDER BY hierarchie_usertype";
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
        echo '<label for="commissions-pick-'.$row['id_commission'].'"><input type="checkbox" name="commission[]" value="commission:'.html_utf8($row['code_commission']).'" id="commissions-pick-'.$row['id_commission'].'" /> '.$row['title_commission'].' </label> ';
    }
    echo '</div>';

    $mysqli->close();

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
?>
