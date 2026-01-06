<?php

use App\Entity\UserAttr;
use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    echo 'Session expirée';
} else {
    // LISTAGE DES DROITS

    // ************************
    // OPERATIONS

    // ************************
    // AFFICHAGE
    $typeTab = [];
    $rightTab = [];
    $attrTab = [];
    $tmp = '';
    $isDev = getUser()->hasAttribute(UserAttr::DEVELOPPEUR);

    // tous les types
    $req = '
	SELECT id_usertype, code_usertype, title_usertype
	FROM caf_usertype
	ORDER BY hierarchie_usertype
	';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $typeTab[] = $handle;
    }

    // tous les droits
    $req = '
	SELECT id_userright, code_userright, title_userright, parent_userright
	FROM caf_userright
	ORDER BY parent_userright, ordre_userright
	';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $rightTab[] = $handle;
    }

    // toutes les attributions de droits aux types
    $req = '
	SELECT type_usertype_attr, right_usertype_attr
	FROM caf_usertype_attr
	';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $attrTab[] = $handle['type_usertype_attr'] . '-' . $handle['right_usertype_attr'];
    } ?>
	<h1>Matrice des droits</h1>
	<p>
		Définit quel type d'adhérent est autorisé à quelles actions. Cliquer sur les intitulés à gauche pour afficher le code correspondant.
	</p>
	<br />
    <style>
        code {
            background-color: #f9f2f4;
            border-radius: 4px;
            color: #c7254e;
            padding: 2px 4px;
            font-family: Menlo,Monaco,Consolas,Lucida Console,monospace;
        }
    </style>
	<form style="background:white; padding:10px; max-width:1600px;  " action="<?php echo $versCettePage; ?>" method="post" onsubmit="return(confirm('Ces valeurs vont remplacer les valeurs existantes, OK ?'))">
		<input type="hidden" name="operation" value="usertype_attr_edit" />

		<?php

        if (isset($_POST['operation']) && 'usertype_attr_edit' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
        }
    if (isset($_POST['operation']) && 'usertype_attr_edit' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '.</div>';
    }

    // on fait courir le tableau
    echo '<table class="user-right-edit-table"><thead><tr><th></th>';
    foreach ($typeTab as $usertype) {
        echo '<th>';
        echo HtmlHelper::escape($usertype['title_usertype']);
        if ($isDev) {
            echo '<br/><code>' . $usertype['code_usertype'] . '</code>';
        }
        echo '</th>';
    } // types (abscisses)
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ($rightTab as $userright) { // types (ordonnées)
        if ($tmp != $userright['parent_userright']) {
            $tmp = $userright['parent_userright'];
            echo '<tr><td colspan="' . (count($typeTab) + 1) . '"><b>' . HtmlHelper::escape($userright['parent_userright']) . '</b></td></tr>';
        }
        echo '<tr class="rightline">
                <td class="left">';
        echo '<span>' . HtmlHelper::escape($userright['title_userright']) . '</span>';
        if ($isDev) {
            echo '<br/><code>' . $userright['code_userright'] . '</code>';
        }
        echo '<input type="text" value="' . HtmlHelper::escape($userright['code_userright']) . '" />';
        echo '</td>';
        for ($i = 0; $i < count($typeTab); ++$i) {
            // right>type : autorisé ou pas ? valeur true || false
            $on = (in_array($typeTab[$i]['id_usertype'] . '-' . $userright['id_userright'], $attrTab, true) ? true : false);
            echo '<td class="toggle ' . ($on ? 'true' : 'false') . '">'
                    . '<input ' . ($on ? '' : 'disabled="disabled"') . ' type="hidden" name="usertype_attr[]" value="' . $typeTab[$i]['id_usertype'] . '-' . $userright['id_userright'] . '" />' // Paire d'ID
                    . '<span class="clair">' . HtmlHelper::escape($typeTab[$i]['title_usertype']) . '</span>'
                    . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>'; ?>
		<br />
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
