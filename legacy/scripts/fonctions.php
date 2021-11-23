<?php

global $_POST;
global $userAllowedTo, $pbd;

/*
    Récupération des sorties d'un utilisateur
    ET affichage
*/
function display_sorties($id_user, $limit = 10, $title = '')
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    $req = '
        SELECT SQL_CALC_FOUND_ROWS
            id_evt, code_evt, status_evt, status_legal_evt, cancelled_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, join_max_evt, join_start_evt
            , nickname_user
            , title_commission, code_commission
            , role_evt_join
        FROM caf_evt
            , caf_user
            , caf_commission
            , caf_evt_join
        WHERE status_evt=1
        AND id_user = user_evt
        AND id_commission = commission_evt '
        //	AND tsp_end_evt <= $p_time
        // jointure avec la table participation
        .'AND evt_evt_join = id_evt
        AND status_evt_join = 1
        AND user_evt_join = '.$id_user
        // de la plus récente a la plus ancienne
        .' ORDER BY  `tsp_evt` DESC
        LIMIT '.$limit;
    // echo $req;
    $handleSql = $mysqli->query($req);
    // calcul du total grace à SQL_CALC_FOUND_ROWS
    $totalSql = $mysqli->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

    // compte :
    if ($total > 0) {
        echo '<h2 id="user-sorties">'.$title.' :</h2>';
        echo '<p class="mini">'.$total.' sortie(s) en tout</p>';
        echo '<div style="width:490px">';
        // liste
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $evt = $handle;
            include __DIR__.'/../includes/agenda-evt-debut.php';
        }
        echo '</div><hr />';
    }
    $mysqli->close();
}
/*
    Récupération des articles publiés d'un utilisateur
    ET affichage
*/

function display_articles($id_user, $limit = 10, $title = '')
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    $req = '
        SELECT SQL_CALC_FOUND_ROWS *
        FROM caf_article
        WHERE status_article=1
        AND user_article = '.$id_user
        .' ORDER BY  `tsp_article` DESC
        LIMIT '.$limit;

    $handleSql = $mysqli->query($req);
    // calcul tu total gr�ce � SQL_CALC_FOUND_ROWS
    $totalSql = $mysqli->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

    // compte :
    if ($total > 0) {
        echo '<h2 id="user-articles">'.$title.' :</h2>';
        echo '<p class="mini">'.$total.' articles en tout</p>';
        echo '<div style="width:490px">';
        // liste
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $article = $handle;
            include __DIR__.'/../includes/article-lien-small.php';
        }
        echo '</div>';
    }
    $mysqli->close();
}

/*
    Récupération des notes, par commission, selon les droits utilisateur
*/
function get_niveaux($id_user, $editable = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $notes = false;

    // A t'on les droits d'ecriture ?
    if (true == $editable && $userAllowedTo['user_note_comm_edit']) {
        $note_comm_edit = $userAllowedTo['user_note_comm_edit'];
        $ids_comms = $title_comms = [];

        // ON r�cup�re les identifiants et titres commission en �criture
        $req = 'SELECT `id_commission`, `title_commission` FROM `'.$pbd.'commission` ';
        if ('true' === $note_comm_edit) {
        } else {
            $tab = explode('|', $note_comm_edit);
            $comms = [];
            foreach ($tab as $elt) {
                $comm = explode(':', $elt);
                if ('commission' == $comm[0]) {
                    $comms[] = $comm[1];
                }
            }
            if ($comms) {
                $req .= 'WHERE code_commission IN (\'';
                $req .= implode("', '", $comms);
                $req .= '\') ';
            }
        }
        $req .= ' LIMIT 500;';
        $results = $mysqli->query($req);

        // tableau des commissions � traiter
        while ($row = $results->fetch_assoc()) {
            $ids_comms[] = $row['id_commission'];
            $title_comms[$row['id_commission']] = $row['title_commission'];
        }

        // On r�cup�re les notes saisies
        $req = 'SELECT N.id as niveau_id, N.id_commission, C.title_commission, niveau_technique, niveau_physique, commentaire FROM `'.$pbd.'user_niveau` AS N, `'.$pbd.'commission` AS C WHERE id_user = '.$id_user.' AND N.id_commission IN ('.implode(',', $ids_comms).') AND N.id_commission = C.id_commission;';
        $results = $mysqli->query($req);
        while ($note = $results->fetch_assoc()) {
            $notes['n_'.$note['niveau_id']] = $note;
            // On vide le tableau des commissions � traiter si on avait une note
            if (($key = array_search($note['id_commission'], $ids_comms, true)) !== false) {
                unset($ids_comms[$key]);
            }
        }

        // Pour toutes les commissions non not�es, on initialise un tableau de notation
        foreach ($ids_comms as $id_comm) {
            $notes[] = [
                'id_commission' => $id_comm,
                'id_user' => $id_user,
                'title_commission' => $title_comms[$id_comm],
            ];
        }
    }

    // A t'on les droits de lecture ou les informations nous concernent-elles personnellement ?
    if (false == $editable && ($userAllowedTo['user_note_comm_read'] || $id_user == $_SESSION['user']['id_user'])) {
        $note_comm_read = $userAllowedTo['user_note_comm_read'];

        // ON r�cup�re les identifiants commission en lecture
        $req = 'SELECT `id_commission` FROM `'.$pbd.'commission` ';
        if ('true' === $note_comm_read || $id_user == $_SESSION['user']['id_user']) {
        } else {
            $tab = explode('|', $note_comm_read);
            $comms = [];
            foreach ($tab as $elt) {
                $comm = explode(':', $elt);
                if ('commission' == $comm[0]) {
                    $comms[] = $comm[1];
                }
            }
            if ($comms) {
                $req .= 'WHERE code_commission IN (\'';
                $req .= implode("', '", $comms);
                $req .= '\') ';
            }
        }
        $req .= ' LIMIT 500;';
        $results = $mysqli->query($req);
        $ids_comms = [];
        while ($row = $results->fetch_assoc()) {
            $ids_comms[] = $row['id_commission'];
        }

        $req = 'SELECT N.id as niveau_id, N.id_commission, C.title_commission, niveau_technique, niveau_physique, commentaire FROM `'.$pbd.'user_niveau` AS N, `'.$pbd.'commission` AS C WHERE id_user = '.$id_user.' AND N.id_commission IN ('.implode(',', $ids_comms).') AND N.id_commission = C.id_commission;';

        $results = $mysqli->query($req);
        while ($note = $results->fetch_assoc()) {
            $notes['n_'.$note['niveau_id']] = $note;
        }
    }

    return $notes;
    $mysqli->close();
}

/*
    Affichage des information de niveau :
    - sélection $type : ecriture//lecture
    - $deja_displayed : passer le tableau des niveaux en droit d'écriture de façon à ne pas afficher des doublons écriture ET lecture.

    @return : champs de formulaire (ecriture) ou affichage simple (lecture)
*/
function display_niveaux($niveaux, $type = 'lecture', $deja_displayed = false)
{
    switch ($type) {
        case 'ecriture':
            ?>
            <?php $n = 0; foreach ($niveaux as $niveau) { ?>
            <div class="niveau editable">
                <div class="picto">
                    <img src="<?php echo comPicto($niveau['id_commission'], 'medium'); ?>" alt="" title="" class="picto-medium" />
                </div>
                <div class="droite">
                    <b><?php echo $niveau['title_commission']; ?></b>
                </div>
                <?php if (isset($niveau['niveau_id'])) {
                $clef = 'niveau['.$niveau['niveau_id'].']'; ?>
                <input type="hidden" name="<?php echo $clef; ?>[id]" value="<?php echo $niveau['niveau_id']; ?>">
                <?php
            } else {
                $clef = 'new_niveau['.$n.']'; ?>
                <input type="hidden" name="<?php echo $clef; ?>" value="<?php echo $niveau['niveau_id']; ?>">
                <input type="hidden" name="<?php echo $clef; ?>[id_commission]" value="<?php echo $niveau['id_commission']; ?>">
                <input type="hidden" name="<?php echo $clef; ?>[id_user]" value="<?php echo $niveau['id_user']; ?>">
                <?php
            } ?>
                <div class="input">
                    <label>Niveau technique</label>
                    <input type="text" name="<?php echo $clef; ?>[niveau_technique]" value="<?php echo $niveau['niveau_technique']; ?>">
                    <label>Niveau physique</label>
                    <input type="text" name="<?php echo $clef; ?>[niveau_physique]" value="<?php echo $niveau['niveau_physique']; ?>">
                </div>
                <div class="input textarea">
                    <label>Commentaire</label>
                    <textarea name="<?php echo $clef; ?>[commentaire]"><?php echo $niveau['commentaire']; ?></textarea>
                </div>
            </div>
            <?php ++$n; } ?>
            <?php
            break;
        case 'lecture':
        ?>
            <?php foreach ($niveaux as $niveau) { ?>
            <?php if ((is_array($deja_displayed) && !isset($deja_displayed['n_'.$niveau['niveau_id']])) || !$deja_displayed) { ?>
            <?php if ($niveau['niveau_technique'] || $niveau['niveau_physique'] || null !== $niveau['commentaire']) { ?>
                <div class="niveau" data-commission="<?php echo $niveau['id_commission']; ?>">
                    <div class="picto">
                        <img src="<?php echo comPicto($niveau['id_commission'], 'medium'); ?>" alt="" title="" class="picto-medium" />
                    </div>
                    <div class="droite">
                        <b><?php echo $niveau['title_commission']; ?></b>
                    </div>
                    <div class="input">
                    <?php if ($niveau['niveau_technique'] || $niveau['niveau_physique']) { ?>
                        <?php if ($niveau['niveau_technique']) { ?><p style="float:left;width:50%;">Niveau technique : <?php echo $niveau['niveau_technique']; ?></p><?php } ?><?php if ($niveau['niveau_physique']) { ?><p style="float:left;width:50%;">Niveau physique : <?php echo $niveau['niveau_physique']; ?></p><?php } ?><br style="clear:both;">
                    <?php } ?>
                        <quote ><?php echo $niveau['commentaire']; ?></quote>
                    </div>
                </div>
            <?php }}} ?>
        <?php
            break;
        default: break;
    }
}

function get_groupes($id_commission, $force_valid = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $groupes = [];

    $req = 'SELECT * FROM `'.$pbd.'groupe` WHERE `id_commission` = '.$id_commission;
    if ($force_valid) {
        $req .= ' AND actif = 1 ';
    }
    $req .= ' ORDER BY `actif` DESC, `nom` ASC';
    $results = $mysqli->query($req);
    while ($row = $results->fetch_assoc()) {
        $groupes[$row['id']] = $row;
    }

    return $groupes;
}

function get_groupe($id_groupe)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $groupe = false;

    $req = 'SELECT * FROM `'.$pbd.'groupe` WHERE `id` = '.$id_groupe;
    $results = $mysqli->query($req);
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $groupe = $row;
        }
    }

    return $groupe;
}

function get_evt($id_evt)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $evt = false;

    $req = 'SELECT * FROM `'.$pbd.'evt` WHERE `id_evt` = '.$id_evt;
    $results = $mysqli->query($req);
    while ($row = $results->fetch_assoc()) {
        $evt = $row;
    }

    $mysqli->close();

    return $evt;
}

function empietement_sortie($id_user, $evt)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $sorties = [];
    if (!is_array($evt)) {
        $evt = get_evt($evt);
    }

    // on recherche une inscription à une sortie qui empiète sur la sortie en cours
    $req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt, status_evt_join, role_evt_join, is_cb, is_restaurant
            FROM caf_evt, caf_evt_join
            WHERE evt_evt_join = id_evt
            AND id_evt != '.(int) ($evt['id_evt']).'
            AND user_evt_join = '.(int) $id_user
            .' AND status_evt != 2 '
            // timing :
            .' AND ( '
                // commence pendant l'evt en cours
                .' (tsp_evt >= '.$evt['tsp_evt'].' AND tsp_evt <= '.$evt['tsp_end_evt'].') '
                // ou finit pendant l'evt en cours
                .' OR (tsp_end_evt >= '.$evt['tsp_evt'].' AND tsp_end_evt <= '.$evt['tsp_end_evt'].') '
                // ou "encadre" l'evt en cours
                .' OR (tsp_evt <= '.$evt['tsp_evt'].' AND tsp_end_evt >= '.$evt['tsp_end_evt'].') '
            .' )
            ORDER BY tsp_evt ASC
            LIMIT 1000';

    $result = $mysqli->query($req);
    while ($tmpJoin = $result->fetch_assoc()) {
        $sorties[] = $tmpJoin;
    }

    $mysqli->close();

    return $sorties;
}

function user_in_destination($id_user, $id_destination, $valid = true)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $is_in = false;

    $req = 'SELECT * FROM `'.$pbd."evt_join`
            WHERE `user_evt_join` = $id_user
                AND `id_destination` = $id_destination "
        .(true === $valid ? ' AND `status_evt_join` = 1 ' : '')
        .' ';

    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $is_in = $row['evt_evt_join'];
        }
    }
    $mysqli->close();

    return $is_in;
}

function user_in_cb($id_user, $valid = true)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $is_cb = false;

    $req = 'SELECT * FROM `'.$pbd."evt_join`
            WHERE `user_evt_join` = $id_user "
        .(true === $valid ? ' AND `status_evt_join` = 1 ' : '')
        .' ';

    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $is_cb = $row['is_cb'];
        }
    }
    $mysqli->close();

    return $is_cb;
}

function user_in_destination_repas($id_user, $id_destination, $valid = true)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $is_repas = false;

    $req = 'SELECT * FROM `'.$pbd."evt_join`
            WHERE `user_evt_join` = $id_user
                AND `id_destination` = $id_destination "
        .(true === $valid ? ' AND `status_evt_join` = 1 ' : '')
        .' ';

    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $is_repas = $row['is_restaurant'];
        }
    }
    $mysqli->close();

    return $is_repas;
}

function user_sortie_in_dest($id_user, $id_destination, $valid = true)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $sortie = false;

    $req = 'SELECT evt_evt_join FROM `'.$pbd."evt_join`
            WHERE `user_evt_join` = $id_user
                AND `id_destination` = $id_destination "
        .(true === $valid ? ' AND `status_evt_join` = 1 ' : '')
        .' ORDER BY `id_bus_lieu_destination` ASC';

    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sortie = get_sortie($row['evt_evt_join'], 'commission');
        }
    }
    $mysqli->close();

    return $sortie;
}

function covoiturage_sorties_destination($id_destination)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $personnes = false;
    $count = 0;

    $req = "SELECT * FROM `caf_evt_join` WHERE `id_destination` = $id_destination AND `is_covoiturage` = 1 AND status_evt_join = 1 ORDER BY `evt_evt_join` ASC";
    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $personnes['sortie'][$row['evt_evt_join']][] = $row['user_evt_join'];
            ++$count;
        }
    }

    $mysqli->close();

    return ['total' => $count, 'covoiturage' => $personnes];
}

function get_sortie($id_evt, $type = 'full')
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $sortie = false;
    $data = '';

    switch ($type) {
        case 'full':
            $data = 'id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cb_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt, repas_restaurant
				, cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt, matos_evt, need_benevoles_evt
				, lat_evt, long_evt
				, join_start_evt
				, ngens_max_evt, join_max_evt
				, nickname_user
				, title_commission, code_commission';
            break;
        case 'commission':
            $data = 'id_evt, title_commission, code_commission, repas_restaurant';
            break;
    }

    $req = "SELECT  $data
		FROM ".$pbd.'evt, '.$pbd.'user, '.$pbd."commission
		WHERE id_evt=$id_evt
            AND id_user = user_evt
            AND commission_evt=id_commission
                LIMIT 1;";

    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sortie = $row;
        }
    }
    $mysqli->close();

    return $sortie;
}

function get_encadrants($id_evt, $only_ids = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $users = false;
    $req = 'SELECT id_user, civ_user,  cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user
                            , role_evt_join, is_cb, is_restaurant, is_covoiturage, id_destination, id_bus_lieu_destination
                    FROM '.$pbd.'evt_join, '.$pbd."user
                    WHERE evt_evt_join = $id_evt
                    AND user_evt_join = id_user
                    AND status_evt_join = 1
                    AND
                        (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'coencadrant')
                    LIMIT 300";
    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($only_ids) {
                $users[] = $row['id_user'];
            } else {
                $row['sortie'] = get_sortie($id_evt);
                $users[] = $row;
            }
        }
    }
    $mysqli->close();

    return $users;
}

function get_all_encadrants_destination($id_destination, $only_ids = true)
{
    $users = [];

    $sorties = get_sorties_for_destination($id_destination);
    if ($sorties) {
        foreach ($sorties as $sortie) {
            $tmp = get_encadrants($sortie['id_evt']);
            foreach ($tmp as $u) {
                if ($only_ids) {
                    $users[] = $u['id_user'];
                } else {
                    $users[] = $u;
                }
            }
        }
    }
    if ($users) {
        $users = array_unique($users);
    }

    return $users;
}

/*
 * entrée : bus or id_bus
 */
function nb_places_restantes_bus($bus)
{
    $id_bus = null;

    if (is_array($bus)) {
        if (isset($bus['places_disponibles'])) {
            return $bus['places_disponibles'];
        }
        if (isset($bus['id'])) {
            $id_bus = $bus['id'];
        }
    } else {
        $id_bus = $bus;
    }
    $bus = get_bus($id_bus, ['pts']);

    return $bus['places_disponibles'];
}

function get_info_bus_lieu_destination($id_bus_lieu_destination)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $infos = [];
    $req = 'SELECT id_lieu, date FROM `'.$pbd."bus_lieu_destination` WHERE `id` = $id_bus_lieu_destination LIMIT 1";
    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $id_lieu = $row['id_lieu'];
        $req_lieu = 'SELECT * FROM `'.$pbd.'lieu` WHERE `id` = '.$id_lieu;
        $lieu = $mysqli->query($req_lieu);
        while ($rowLieu = $lieu->fetch_assoc()) {
            $infos['lieu'] = $rowLieu;
        }
        $infos['date'] = $row['date'];
    }

    $mysqli->close();

    return $infos;
}

function ramassage_appartient_quel_bus($id_bus_lieu_destination)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $bus_id = false;

    $req = 'SELECT id_bus FROM `'.$pbd."bus_lieu_destination` WHERE `id` = $id_bus_lieu_destination LIMIT 1";

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $bus_id = $row['id_bus'];
    }
    $mysqli->close();

    return $bus_id;
}

function nb_places_restante_bus_ramassage($id_bus_lieu_destination)
{
    $id_bus = ramassage_appartient_quel_bus($id_bus_lieu_destination);

    return nb_places_restantes_bus($id_bus);
}

function get_bus($id_bus, $params = ['dest', 'pts'])
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $id_bus = $mysqli->real_escape_string((int) $id_bus);
    $req = 'SELECT * FROM '.$pbd."bus WHERE id = $id_bus LIMIT 1";
    $bus = null;

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $row['places_disponibles'] = $row['places_max'];

        if (in_array('dest', $params, true)) {
            $destination = get_destination($row['id_destination']);
            if (is_array($destination)) {
                $row['destination'] = $destination;
            }
        }

        if (in_array('pts', $params, true)) {
            $pts_ramassage = get_points_ramassage($id_bus, $row['id_destination']);
            if (is_array($pts_ramassage)) {
                $row['ramassage'] = $pts_ramassage;
                if ($pts_ramassage) {
                    foreach ($pts_ramassage as $point) {
                        if (isset($point['utilisateurs']['valide'])) {
                            $row['places_disponibles'] -= count($point['utilisateurs']['valide']);
                        }
                    }
                }
            }
        }

        $bus = $row;
    }
    $mysqli->close();

    return $bus;
}

function get_points_ramassage($id_bus, $id_destination)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $points = null;
    $id_bus = $mysqli->real_escape_string((int) $id_bus);
    $id_destination = $mysqli->real_escape_string((int) $id_destination);

    $req = 'SELECT
              BLD.id as bdl_id, id_bus, id_destination, id_lieu, type_lieu, date,
              L.id as l_id, nom, description, ign, lat, lng
            FROM    `'.$pbd.'bus_lieu_destination` AS BLD,
                    `'.$pbd."lieu` AS L
            WHERE   `id_bus` = $id_bus
                    AND `id_destination` = $id_destination
                    AND L.`id` = BLD.`id_lieu`
            ORDER BY    BLD.`date` ASC;";

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $users = [];
        $req2 = 'SELECT * FROM `'.$pbd.'evt_join` WHERE `id_bus_lieu_destination` = '.$row['bdl_id']." AND `id_destination` = $id_destination AND status_evt_join = 1";
        $result2 = $mysqli->query($req2);
        while ($row2 = $result2->fetch_assoc()) {
            if (1 == $row2['status_evt_join']) {
                $users['valide'][] = $row2['user_evt_join'];
            }
        }
        if ($users['valide']) {
            $users['valide'] = array_unique($users['valide']);
        }
        $row['utilisateurs'] = $users;

        $points[$row['bdl_id']] = $row;
    }
    $mysqli->close();

    return $points;
}

function mon_inscription($id_evt)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;
    $my_choices = false;

    $req = 'SELECT * FROM `'.$pbd."evt_join` WHERE `evt_evt_join` = $id_evt AND `user_evt_join` = ".$_SESSION['user']['id_user'].' LIMIT 1;';
    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $my_choices = $row;
    }

    $mysqli->close();

    return $my_choices;
}

function get_destination($id_dest, $full = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $destination = null;
    $id_dest = $mysqli->real_escape_string((int) $id_dest);
    $req = 'SELECT * FROM '.$pbd."destination WHERE id = $id_dest LIMIT 1";

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $destination = $row;
        if (true == $full) {
            // Lieu
            $destination['lieu'] = get_lieu($destination['id_lieu']);
            unset($destination['id_lieu']);
            // Bus
            $destination['bus'] = get_bus_destination($id_dest);
            // Users
            $destination['responsable'] = get_user($destination['id_user_responsable']);
            $destination['co-responsable'] = get_user($destination['id_user_adjoint']);
            $destination['createur'] = get_user($destination['id_user_who_create']);
            // Sorties

            $destination['sorties'] = get_sorties_for_destination($id_dest);
        }
    }
    $mysqli->close();

    return $destination;
}

/* recupere les information de liaison evt/destination (lieux et horaires de depose reprise */
function get_sortie_destination($id_dest, $id_evt, $lieux = true)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;
    $sortie = false;

    $id_dest = $mysqli->real_escape_string((int) $id_dest);
    $req = 'SELECT * FROM `'.$pbd.'evt_destination` WHERE ';
    if (0 != $id_dest) {
        $req .= " id_destination = $id_dest AND ";
    }
    $req .= " id_evt = $id_evt LIMIT 1";

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        if ($lieux) {
            $row['lieu']['depose'] = get_lieu($row['id_lieu_depose']);
            unset($row['id_lieu_depose']);
            $row['lieu']['reprise'] = get_lieu($row['id_lieu_reprise']);
            unset($row['id_lieu_reprise']);
            $row['lieu']['depose']['date_depose'] = $row['date_depose'];
            unset($row['date_depose']);
            $row['lieu']['reprise']['date_reprise'] = $row['date_reprise'];
            unset($row['date_reprise']);
        }
        $sortie = $row;
    }

    return $sortie;
}

function get_sorties_for_destination($id_dest, $ids_only = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $sorties = false;
    $ids = $full = [];

    $id_dest = $mysqli->real_escape_string((int) $id_dest);
    $req = 'SELECT * FROM `'.$pbd."evt_destination` WHERE id_destination = $id_dest";
    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $ids[$row['id_evt']] = $row['id_evt'];
        $full[$row['id_evt']] = $row;
    }

    if ($ids && !$ids_only) {
        $req = 'SELECT * from `'.$pbd.'evt`, `'.$pbd.'commission` AS commission ';
        $req .= ' WHERE ';
        $req .= ' commission_evt=commission.id_commission AND ';
        $req .= ' id_evt IN (\'';
        $req .= implode("', '", $ids);
        $req .= '\') ';

        // Groupes
        $result = $mysqli->query($req);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['destination'] = $full[$row['id_evt']];
                if ($row['id_groupe']) {
                    $row['groupe'] = get_groupe($row['id_groupe']);
                }
                unset($row['id_groupe']);
                $sorties[] = $row;
            }
        }

        return $sorties;
    }

    return $ids;
}

function get_user($id_user, $valid = true, $simple = true)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $user = null;
    $id_user = $mysqli->real_escape_string((int) $id_user);
    $req = 'SELECT ';
    if ($simple) {
        $req .= ' id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, birthday_user, civ_user ';
    } else {
        $req .= ' * ';
    }
    $req .= ' FROM '.$pbd."user WHERE id_user = $id_user";
    if ($valid) {
        $req .= ' AND valid_user = 1 ';
    }
    $req .= ' LIMIT 1';

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $user = $row;
    }
    $mysqli->close();

    return $user;
}

function get_bus_destination($id_destination)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $busses = [];
    // Bus
    $req = 'SELECT * FROM `'.$pbd.'bus` WHERE `id_destination` = '.$id_destination;
    $handleSql = $mysqli->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $busses[$handle['id']] = get_bus($handle['id'], ['pts']);
    }

    $mysqli->close();

    return $busses;
}

function get_lieu($id_lieu)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $lieu = null;
    $id_lieu = $mysqli->real_escape_string((int) $id_lieu);
    $req = 'SELECT * FROM `'.$pbd.'lieu` WHERE id = '.$id_lieu.' LIMIT 1';

    $result = $mysqli->query($req);
    while ($row = $result->fetch_assoc()) {
        $lieu = $row;
    }
    $mysqli->close();

    return $lieu;
}

function get_future_destinations($can_modify = false, $for_event_creation = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $destinations = [];

    $mon_id = $mysqli->real_escape_string($_SESSION['user']['id_user']);
    $req = 'SELECT * FROM `'.$pbd."destination`
	        WHERE `date` > '".date('Y-m-d H:i:s')."' ";
    if ($for_event_creation) {
        $req .= ' AND publie = 0 AND annule != 1';
    }
    if ($can_modify) {
        if (allowed('destination_supprimer') || allowed('destination_modifier') || allowed('destination_activer_desactiver')) {
            $req .= '';
        } else {
            $req .= ' AND (id_user_who_create = '.$mon_id.'  OR id_user_responsable = '.$mon_id.' OR id_user_adjoint = '.$mon_id.')';
        }
    }
    $req .= ' ORDER BY date ASC';
    $handleSql = $mysqli->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $handle['sorties'] = get_sorties_for_destination($handle['id']);
        $destinations[] = $handle;
    }
    $mysqli->close();

    return $destinations;
}

function get_iframe_src($field = null)
{
    $src = null;
    if (null !== $field) {
        $dom = new DOMDocument();
        @$dom->loadHTML(stripslashes($field));
        foreach ($dom->getElementsByTagName('iframe') as $node) {
            $src = $node->getAttribute('src');
        }
    }

    return $src;
}

function display_frame_geoportail($src, $w = 620, $h = 350)
{
    if (!empty($src)) {
        return '<iframe width="'.$w.'" height="'.$h.'" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"  src="'.$src.'" allowfullscreen></iframe>';
    }

    return null;
}

function display_new_lieu()
{
    global $_POST;

    return '<div style="float:left; width:50%" class="lieu_map">'.
            '<label for="lieu">Lieu :</label>'.
            '<input type="text" name="lieu[nom]"  id="lieu" class="type2" style="width:95%" value="'.inputVal('lieu|nom', '').'" placeholder="ex: La dent du chat, Parking de Casino, 15 route de la soie, ...">'.
            '</div>'.
            '<div style="float:left; width:45%; padding:0 20px 0 0;">'.
            'Précisez sur la carte :<br />'.
            '<input type="button" name="codeAddress" class="type2" style="border-radius:5px; cursor:pointer;" value="Positionner" />'.
            '<input type="hidden" name="lieu[lat]" id="lieuLat" value="'.inputVal('lieu|lat', '').'" />'.
            '<input type="hidden" name="lieu[lng]" id="lieuLng" value="'.inputVal('lieu|lng', '').'" />'.
            '</div>'.
            '<br style="clear:both" />'.
            '<div id="place_finder_error" class="erreur" style="display:none"></div>'.
            '<div id="map-creersortie"></div>'.
            '<br>'/*.
            '<label for="ign">Extrait IGN : <small>Insérez le code de partage fourni par <a href="https://www.geoportail.gouv.fr/" target="_blank">GeoPortail</a>.</small></label>'.
            '<textarea name="lieu[ign]" id="ign" style="width:95%;height:80px;" class="type2">'.inputVal('lieu|ign', '').'</textarea>'*/;
}

function display_previous_lieux($name = null, $id_destination)
{
    $previous_lieux_destination = get_lieux_depose_reprise_destination($id_destination);
    $chain = null;
    if ($previous_lieux_destination) {
        $chain .= '<select name="lieu['.$name.'][use_existant]" class="type2" style="width:95%;">';
        $chain .= '<option value=""> - Utiliser un lieu de cette destination</option>';
        foreach ($previous_lieux_destination as $previous_dest) {
            $chain .= '<option value="'.$previous_dest['id'].'"';
            if (isset($_POST['lieu'][$name]['use_existant']) && $_POST['lieu'][$name]['use_existant'] == $previous_dest['id']) {
                $chain .= ' selected="selected" ';
            }
            $chain .= '>'.html_utf8($previous_dest['nom']).'</option>';
        }
        $chain .= '</select><br><b>OU</b> créer un nouveau lieu :<br><br>';
    } else {
        $chain = false;
    }

    return $chain;
}

function get_lieux_depose_reprise_destination($id_destination)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $ids = $lieux = false;

    $id_destination = $mysqli->real_escape_string((int) $id_destination);
    $req = 'SELECT id_lieu_depose, id_lieu_reprise FROM `'.$pbd."evt_destination` WHERE `id_destination` = $id_destination";
    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id_lieu_depose'];
            $ids[] = $row['id_lieu_reprise'];
        }
    }
    if ($ids) {
        $ids = array_unique($ids);
        foreach ($ids as $id) {
            $lieux[$id] = get_lieu($id);
        }
    }

    return $lieux;
}

function get_lieux_destination($id_destination, $type = null)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $ids = $lieux = false;

    $id_destination = $mysqli->real_escape_string((int) $id_destination);
    $req = "SELECT id_lieu_$type, date_$type FROM `".$pbd."evt_destination` WHERE `id_destination` = $id_destination ORDER BY date_$type ASC";
    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ids[$row['id_lieu_'.$type]] = $row['date_'.$type];
        }
    }
    if ($ids) {
        foreach ($ids as $id => $heure) {
            $lieux[$id] = get_lieu($id);
            $lieux[$id]['date'] = $heure;
        }
    }

    return $lieux;
}

function display_new_lieu_complexe($name = null, $reset = false)
{
    global $_POST;
    $arg = $val = null;
    if ($name) {
        $arg = "[$name]";
    }
    if ($reset) {
        $val = '|custom';
    } else {
        $val = '|'.$name;
    }

    return '<div class="lieu_map" id="lieu_'.$name.'"><div>'.
    '<label for="lieu'.$name.'">Lieu :</label>'.
    '<input type="text" name="lieu'.$arg.'[nom]"  id="lieu-lieu_'.$name.'" class="type2" style="width:95%" value="'.inputVal('lieu'.$val.'|nom', '').'" placeholder="ex: La dent du chat, Parking de Casino, 15 route de la soie, ...">'.
    '</div>'.
    '<div>'.
    'Précisez sur la carte :<br />'.
    '<input type="button" name="codeAddress-lieu_'.$name.'" class="type2" style="border-radius:5px; cursor:pointer;" value="Positionner" />'.
    '<input type="hidden" name="lieu'.$arg.'[lat]" class="lieuLat" value="'.inputVal('lieu'.$val.'|lat', '').'" />'.
    '<input type="hidden" name="lieu'.$arg.'[lng]" class="lieuLng" value="'.inputVal('lieu'.$val.'|lng', '').'" />'.
    '</div>'.
    '<br style="clear:both" />'.
    '<div class="place_finder_error" class="erreur" style="display:none"></div>'.
    '<div class="map-creersortie" id="map-creersortie-lieu_'.$name.'"></div>'.
    '<br>'/*.
    '<label for="ign'.$name.'">Extrait IGN : <small>Insérez le code de partage fourni par <a href="https://www.geoportail.gouv.fr/" target="_blank">GeoPortail</a>.</small></label>'.
    '<textarea name="lieu'.$arg.'[ign]" id="ign'.$name.'" style="width:95%;height:80px;" class="type2">'.inputVal('lieu'.$val.'|ign', '').'</textarea>'*/.'</div>';
}

function display_edit_lieu_link($id_lieu, $nom)
{
    return false;

    return '<a href="" class="todo edit rght mr10" title="Modifier le lieu : '.$nom.'"></a>';
}

function display_dateTime($datetime)
{
    if (!$datetime) {
        return null;
    }
    $oDate = new DateTime($datetime);
    $sDate = $oDate->format('d/m/Y');
    $sHeure = $oDate->format("H\hi");

    return 'le '.$sDate.' à '.$sHeure;
}
function display_date($datetime)
{
    if (!$datetime) {
        return null;
    }
    $oDate = new DateTime($datetime);

    return $oDate->format('d/m/Y');
}
function display_time($datetime)
{
    if (!$datetime) {
        return null;
    }
    $oDate = new DateTime($datetime);

    return $oDate->format("H\hi");
}
function display_jour($datetime)
{
    if (!$datetime) {
        return null;
    }
    $oDate = new DateTime($datetime);

    return jour($oDate->format('N'), 'short').$oDate->format(' d ').mois($oDate->format('m')).$oDate->format(' Y ');
}

/* transmettre le trableau destination OU l'identifiant */
function inscriptions_status_destination($destination)
{
    if (!is_array($destination)) {
        $destination = get_destination($destination);
    }
    $status = $msg = null;
    if (is_array($destination)) {
        $today = new DateTime(date('Y-m-d H:i:s'));
        $ouverture = new DateTime($destination['inscription_ouverture']);
        $fermeture = new DateTime($destination['inscription_fin']);

        if (1 == $destination['inscription_locked']) {
            $msg = 'Les inscriptions ont été bloquées pour le moment. Merci de réessayer plus tard.';
        } else {
            if ($today < $ouverture) {
                $msg = 'Les inscriptions ne sont pas encore possibles. Elles le seront à partir de '.display_jour($destination['inscription_ouverture']).' à '.display_time($destination['inscription_ouverture']);
            } elseif ($today > $fermeture) {
                $msg = 'Les inscriptions sont terminées.';
            } else {
                $status = true;
                $msg = 'Les inscriptions sont possibles jusqu\'à '.display_jour($destination['inscription_fin']).' à '.display_time($destination['inscription_fin']);
            }
        }
    }

    return ['status' => $status, 'message' => $msg];
}

/* transmettre le trableau destination OU l'identifiant */
function is_destination_status($destination, $param = false)
{
    $status = false;
    if (!is_array($destination)) {
        $destination = get_destination($destination, false);
    }
    if (is_array($destination)) {
        switch ($param) {
            case false:
                break;
            case 'publie':
                if ('1' == $destination[$param]) {
                    $status = true;
                }
                break;
            case 'annule':
                if ('1' == $destination[$param]) {
                    $status = true;
                }
                break;
        }
    }

    return $status;
}

function is_sortie_in_destination($id_evt)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $destination = false;

    $req = 'SELECT * FROM `'.$pbd.'evt_destination` WHERE `id_evt` = '.$id_evt;
    $handleSql = $mysqli->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $destination = $handle['id_destination'];
    }
    $mysqli->close();

    return $destination;
}

function select_lieux_ramasse_connus($id_current_dest = false, $full = true, $exlude = false)
{
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    global $userAllowedTo, $pbd;

    $ids = false;
    $lieux = false;

    $req = 'SELECT id_lieu FROM `'.$pbd."bus_lieu_destination` WHERE `type_lieu` LIKE 'ramasse' ";
    if ($id_current_dest) {
        $req .= " AND id_destination = $id_current_dest ";
    }
    if ($exlude) {
        $req .= " AND id_bus != $exlude ";
    }
    $req .= ' GROUP BY id_lieu';
    $result = $mysqli->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id_lieu'];
        }
    }

    if ($ids && $full) {
        $req = 'SELECT * FROM `'.$pbd.'lieu` WHERE `id` IN ('.implode(',', $ids).')';
        $result = $mysqli->query($req);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $lieux[] = $row;
            }
        }

        return $lieux;
    }

    $mysqli->close();

    return $ids;
}
