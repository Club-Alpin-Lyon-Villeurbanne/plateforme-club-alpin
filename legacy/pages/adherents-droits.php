<?php

use App\Legacy\LegacyContainer;

if (!allowed('user_giveright_1') && !allowed('user_giveright_2') && !allowed('user_givepresidence')) {
    echo 'Vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_user = (int) $_GET['id_user'];
    if (!$id_user) {
        echo 'Erreur : id invalide';
        exit;
    } ?>

    <h1>Attribution de responsabilités à l'adhérent : <?php echo html_utf8(stripslashes($_GET['nom'])); ?></h1>
    <?php
    // req sql : trouver les attributs liés à cet user
    $req = 'SELECT title_usertype, code_usertype, params_user_attr, id_user_attr, description_user_attr
    FROM caf_usertype, caf_user_attr
    WHERE usertype_user_attr = id_usertype
    AND user_user_attr = ' . $id_user . '
    ORDER BY hierarchie_usertype DESC';

    $statsTab = [];
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $statsTab[] = $row;
    }

    if (count($statsTab)) {
        echo '<h2>Responsabilités actuelles :</h2>'
            . '<ul>';
        foreach ($statsTab as $row) {
            echo '<li>'
                        . '- <b>' . html_utf8($row['title_usertype']) . '</b>' . ($row['params_user_attr'] ? ', ' . str_replace(':', ' ', $row['params_user_attr']) : '');

            // SUPPRESSION D'UN STATUT
            // ALORS, MANUELLEMENT : CHAQUE STATUT NE PEUT ÊTRE SUPPRIMÉ QUE SI L'USER COURANT A DES DROITS PARTICULIERS (allowed) DONC :
            $deleteRight = false;

            // user_giveright_1 : dépend de la commission, pasée ici dans $row['params_user_attr']
            if ('benevole_encadrement' == $row['code_usertype'] && allowed('user_giveright_1', $row['params_user_attr'])) {
                $deleteRight = true;
            }
            if ('encadrant' == $row['code_usertype'] && allowed('comm_delier_encadrant', $row['params_user_attr'])) {
                $deleteRight = true;
            }
            if ('stagiaire' == $row['code_usertype'] && allowed('comm_delier_encadrant', $row['params_user_attr'])) {
                $deleteRight = true;
            }
            if ('coencadrant' == $row['code_usertype'] && allowed('user_giveright_1', $row['params_user_attr'])) {
                $deleteRight = true;
            }
            if ('redacteur' == $row['code_usertype'] && allowed('user_giveright_1', $row['params_user_attr'])) {
                $deleteRight = true;
            }

            // user_giveright_2
            if ('salarie' == $row['code_usertype'] && allowed('user_giveright_2')) {
                $deleteRight = true;
            }

            // user_givepresidence
            if ('president' == $row['code_usertype'] && allowed('user_givepresidence')) {
                $deleteRight = true;
            }
            if ('vice-president' == $row['code_usertype'] && allowed('user_givepresidence')) {
                $deleteRight = true;
            }

            // comm_delier_responsable
            if ('responsable-commission' == $row['code_usertype'] && allowed('comm_delier_responsable')) {
                $deleteRight = true;
            }

            if ($deleteRight) {
                echo '
                                <form action="' . $versCettePage . '" method="post" onsubmit="return(confirm(\'Vraiment supprimer cette responsabilité ?\n Cet utilisateur ne sera plus ' . addslashes(html_utf8($row['title_usertype'])) . '\'))" style="display:inline;">
                                    <input type="hidden" name="operation" value="user_attr_del" />
                                    <input type="hidden" name="id_user_attr" value="' . $row['id_user_attr'] . '" />
                                    <input type="image" src="/img/base/x.png" alt="DEL" title="Supprimer cet attribut" class="upfade" />
                                </form>'
                ;
            }

            echo strlen($row['description_user_attr']) > 0 ?
                ('<em>(' . addslashes(html_utf8($row['description_user_attr'])) . ')</em>')
            : '';

            echo '</li>';
        }
        echo '</ul><br /><br />';
    }

    // AJOUTER UN ATTRIBUT
    ?>
        <form action="<?php echo $versCettePage; ?>" method="post">
            <input type="hidden" name="operation" value="user_attr_add" />
            <input type="hidden" name="id_user" value="<?php echo $id_user; ?>" />

            <h2>Ajouter une responsabilité à cet adhérent :</h2>
            <?php
        // message
        if (isset($_POST['operation']) && 'user_attr_add' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
        }
    if (isset($_POST['operation']) && 'user_attr_add' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<div class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '.</div>';
    }

    // liste des types :
    $req = "SELECT * FROM caf_usertype WHERE code_usertype NOT LIKE 'visiteur' AND code_usertype NOT LIKE 'adherent' ORDER BY hierarchie_usertype";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    echo '<select name="id_usertype"><option></option>';
    while ($row = $result->fetch_assoc()) {
        // ALORS, MANUELLEMENT : CHAQUE STATUT NE PEUT ÊTRE ATTRIBUÉ QUE SI L'USER COURANT A DES DROITS PARTICULIERS (allowed) DONC :
        $afficher = false;

        // user_giveright_1
        if ('benevole_encadrement' == $row['code_usertype'] && allowed('user_giveright_1')) {
            $afficher = true;
        }
        if ('coencadrant' == $row['code_usertype'] && allowed('user_giveright_1')) {
            $afficher = true;
        }
        if ('redacteur' == $row['code_usertype'] && allowed('user_giveright_1')) {
            $afficher = true;
        }

        // comm_lier_encadrant
        if ('encadrant' == $row['code_usertype'] && allowed('comm_lier_encadrant')) {
            $afficher = true;
        }
        if ('stagiaire' == $row['code_usertype'] && allowed('comm_lier_encadrant')) {
            $afficher = true;
        }

        // user_giveright_2
        if ('salarie' == $row['code_usertype'] && allowed('user_giveright_2')) {
            $afficher = true;
        }

        // user_giveright_3
        if ('responsable-commission' == $row['code_usertype'] && allowed('user_giveright_3')) {
            $afficher = true;
        }

        // user_givepresidence
        if ('president' == $row['code_usertype'] && allowed('user_givepresidence')) {
            $afficher = true;
        }
        if ('vice-president' == $row['code_usertype'] && allowed('user_givepresidence')) {
            $afficher = true;
        }

        if ($afficher) {
            echo '<option value="' . (int) $row['id_usertype'] . '" class="precise-comm-' . (int) $row['limited_to_comm_usertype'] . '">' . html_utf8($row['title_usertype']) . '</option>';
        }
    }
    echo '</select>';

    // liste des commissions
    $req = 'SELECT * FROM caf_commission WHERE vis_commission = 1 ORDER BY ordre_commission ASC ';
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    echo '<div id="commissions-pick" class="nice-checkboxes">';
    while ($row = $result->fetch_assoc()) {
        // on ne peut atribuer ce genre de droits qu'à une commission pour laquelle on est responsable
        if (allowed('user_giveright_1', 'commission:' . $row['code_commission'])) {
            echo '<label for="commissions-pick-' . $row['id_commission'] . '"><input type="checkbox" name="commission[]" value="commission:' . html_utf8($row['code_commission']) . '" id="commissions-pick-' . $row['id_commission'] . '" /> ' . $row['title_commission'] . ' </label> ';
        }
    }
    echo '</div>';

    // description de l'assignation
    echo '<br /><br />Description / commentaire :<br /><textarea style="width:50%;height:60px;" name="description_user_attr" id="description_user_attr" rows="2" cols="100" maxlength="200"></textarea>';
    ?>
        <br />
        <br />
        <input type="submit" value="Appliquer" class="nice" />

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
    <br><br>

    <div class="explain">
        <h3>Lorsque vous attribuez ou retirez des responsabilités aux adhérents, des notifications automatiques sont envoyées :</h3>
        <ul>
            <li>un adhérent reçoit ou perd des responsabilités : l'adhérent reçoit un e-mail</li>
            <li>s'il s'agit de responsabilité "encadrant", "co-enacadrant", "initiateur stagiaire" ou "responsable de commission" : les responsables (actuels) de la commission reçoivent un e-mail, ainsi que le président</li>
        </ul>
    </div>
	<?php
}
