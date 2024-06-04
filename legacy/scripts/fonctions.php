<?php

use App\Legacy\LegacyContainer;

/*
    Récupération des sorties d'un utilisateur
    ET affichage
*/
function display_sorties($id_user, $limit = 10, $title = '')
{
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
        // jointure avec la table participation
        . 'AND evt_evt_join = id_evt
        AND status_evt_join = 1
        AND user_evt_join = ' . $id_user
        // de la plus récente a la plus ancienne
        . ' ORDER BY  `tsp_evt` DESC
        LIMIT ' . $limit;
    // echo $req;
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    // calcul du total grace à SQL_CALC_FOUND_ROWS
    $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

    // compte :
    if ($total > 0) {
        echo '<h2 id="user-sorties">' . $title . ' :</h2>';
        echo '<p class="mini">' . $total . ' sortie(s) en tout</p>';
        echo '<div style="width:620px">';
        // liste
        echo '<table id="agenda">';
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $evt = $handle;

            echo '<tr>'
                    . '<td class="agenda-gauche">' . date('d/m/Y', $evt['tsp_evt']) . '</td>'
                    . '<td>';
            require __DIR__ . '/../includes/agenda-evt-debut.php';
            echo '</td>'
                . '</tr>';
        }
        echo '</table>';
        echo '</div><br style="clear:both" />';
    }
}
/*
    Récupération des articles publiés d'un utilisateur
    ET affichage
*/

function display_articles($id_user, $limit = 10, $title = '')
{
    $req = '
        SELECT SQL_CALC_FOUND_ROWS *
        FROM caf_article
        WHERE status_article=1
        AND user_article = ' . $id_user
        . ' ORDER BY  `tsp_article` DESC
        LIMIT ' . $limit;

    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    // calcul tu total gr�ce � SQL_CALC_FOUND_ROWS
    $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));

    // compte :
    if ($total > 0) {
        echo '<h2 id="user-articles">' . $title . ' :</h2>';
        echo '<p class="mini">' . $total . ' articles en tout</p>';
        echo '<div style="width:490px">';
        // liste
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $article = $handle;
            require __DIR__ . '/../includes/article-lien-small.php';
        }
        echo '</div>';
    }
}

/*
    Récupération des notes, par commission, selon les droits utilisateur
*/
function get_niveaux($id_user, $editable = false)
{
    $notes = [];

    // A t'on les droits d'ecriture ?
    if (true == $editable && LegacyContainer::get('legacy_user_rights')->allowed('user_note_comm_edit')) {
        $ids_comms = $title_comms = [];

        $req = 'SELECT `id_commission`, `title_commission` FROM `caf_commission` ';

        $comms = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('user_note_comm_edit');

        $req .= 'WHERE code_commission IN (\'';
        $req .= implode("', '", $comms);
        $req .= '\') ';

        $req .= ' LIMIT 500;';
        $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);

        // tableau des commissions � traiter
        while ($row = $results->fetch_assoc()) {
            $ids_comms[] = $row['id_commission'];
            $title_comms[$row['id_commission']] = $row['title_commission'];
        }

        if (!empty($ids_comms)) {
            // On r�cup�re les notes saisies
            $req = 'SELECT N.id as niveau_id, N.id_commission, C.title_commission, niveau_technique, niveau_physique, commentaire
            FROM `caf_user_niveau` AS N, `caf_commission` AS C
            WHERE id_user = ' . $id_user . ' AND N.id_commission IN (' . implode(',', $ids_comms) . ') AND N.id_commission = C.id_commission;';

            $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);

            while ($note = $results->fetch_assoc()) {
                $notes['n_' . $note['niveau_id']] = $note;
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
    }

    // A t'on les droits de lecture ou les informations nous concernent-elles personnellement ?
    if (false == $editable && (LegacyContainer::get('legacy_user_rights')->allowed('user_note_comm_read') || (user() && $id_user == (string) getUser()->getId()))) {
        $req = 'SELECT `id_commission` FROM `caf_commission` ';
        $comms = LegacyContainer::get('legacy_user_rights')->getCommissionListForRight('user_note_comm_read');

        if ($comms) {
            $req .= 'WHERE code_commission IN (\'';
            $req .= implode("', '", $comms);
            $req .= '\') ';
        }

        $req .= ' LIMIT 500;';
        $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $ids_comms = [];
        while ($row = $results->fetch_assoc()) {
            $ids_comms[] = $row['id_commission'];
        }

        if (!empty($ids_comms)) {
            $req = 'SELECT N.id as niveau_id, N.id_commission, C.title_commission, niveau_technique, niveau_physique, commentaire FROM `caf_user_niveau` AS N, `caf_commission` AS C WHERE id_user = ' . $id_user . ' AND N.id_commission IN (' . implode(',', $ids_comms) . ') AND N.id_commission = C.id_commission;';

            $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($note = $results->fetch_assoc()) {
                $notes['n_' . $note['niveau_id']] = $note;
            }
        }
    }

    return count($notes) ? $notes : false;
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
            <?php $n = 0;
            foreach ($niveaux as $niveau) { ?>
            <div class="niveau editable">
                <div class="picto">
                    <img src="<?php echo comPicto($niveau['id_commission'], 'medium'); ?>" alt="" title="" class="picto-medium" />
                </div>
                <div class="droite">
                    <b><?php echo $niveau['title_commission']; ?></b>
                </div>
                <?php if (isset($niveau['niveau_id'])) {
                    $clef = 'niveau[' . $niveau['niveau_id'] . ']'; ?>
                <input type="hidden" name="<?php echo $clef; ?>[id]" value="<?php echo $niveau['niveau_id'] ?? ''; ?>">
                <?php
                } else {
                    $clef = 'new_niveau[' . $n . ']'; ?>
                <input type="hidden" name="<?php echo $clef; ?>" value="<?php echo $niveau['niveau_id'] ?? ''; ?>">
                <input type="hidden" name="<?php echo $clef; ?>[id_commission]" value="<?php echo $niveau['id_commission'] ?? ''; ?>">
                <input type="hidden" name="<?php echo $clef; ?>[id_user]" value="<?php echo $niveau['id_user'] ?? ''; ?>">
                <?php
                } ?>
                <div class="input">
                    <label>Niveau technique</label>
                    <input type="text" name="<?php echo $clef; ?>[niveau_technique]" value="<?php echo $niveau['niveau_technique'] ?? ''; ?>">
                    <label>Niveau physique</label>
                    <input type="text" name="<?php echo $clef; ?>[niveau_physique]" value="<?php echo $niveau['niveau_physique'] ?? ''; ?>">
                </div>
                <div class="input textarea">
                    <label>Commentaire</label>
                    <textarea name="<?php echo $clef; ?>[commentaire]"><?php echo $niveau['commentaire'] ?? ''; ?></textarea>
                </div>
            </div>
            <?php ++$n;
            } ?>
            <?php
            break;
        case 'lecture':
            ?>
            <?php foreach ($niveaux as $niveau) { ?>
            <?php if ((is_array($deja_displayed) && !isset($deja_displayed['n_' . $niveau['niveau_id']])) || !$deja_displayed) { ?>
            <?php if ((isset($niveau['niveau_technique']) && $niveau['niveau_technique']) || (isset($niveau['niveau_physique']) && $niveau['niveau_physique']) || (isset($niveau['commentaire']) && null !== $niveau['commentaire'])) { ?>
                <div class="niveau" data-commission="<?php echo $niveau['id_commission'] ?? ''; ?>">
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
            <?php }
            }
            } ?>
        <?php
            break;
        default: break;
    }
}

function get_groupes($id_commission, $force_valid = false)
{
    $groupes = [];

    if (null == $id_commission) {
        return $groupes;
    }

    $req = 'SELECT * FROM `caf_groupe` WHERE `id_commission` = ' . $id_commission;
    if ($force_valid) {
        $req .= ' AND actif = 1 ';
    }
    $req .= ' ORDER BY `actif` DESC, `nom` ASC';
    $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $results->fetch_assoc()) {
        $groupes[$row['id']] = $row;
    }

    return $groupes;
}

function get_groupe($id_groupe)
{
    if (!$id_groupe || '' === trim($id_groupe)) {
        return false;
    }

    $groupe = false;

    $req = 'SELECT * FROM `caf_groupe` WHERE `id` = ' . $id_groupe;
    $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $groupe = $row;
        }
    }

    return $groupe;
}

function get_evt($id_evt)
{
    $evt = false;

    $req = 'SELECT * FROM `caf_evt` WHERE `id_evt` = ' . $id_evt;
    $results = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $results->fetch_assoc()) {
        $evt = $row;
    }

    return $evt;
}

function empietement_sortie($id_user, $evt)
{
    $sorties = [];
    if (!is_array($evt)) {
        $evt = get_evt($evt);
    }

    if (!$evt['tsp_evt'] || !$evt['tsp_end_evt']) {
        return $sorties;
    }

    // on recherche une inscription à une sortie qui empiète sur la sortie en cours
    $req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt, status_evt_join, role_evt_join
            FROM caf_evt, caf_evt_join
            WHERE evt_evt_join = id_evt
            AND id_evt != ' . (int) $evt['id_evt'] . '
            AND user_evt_join = ' . (int) $id_user
            . ' AND status_evt != 2 '
            // timing :
            . ' AND ( '
                // commence pendant l'evt en cours
                . ' (tsp_evt >= ' . $evt['tsp_evt'] . ' AND tsp_evt <= ' . $evt['tsp_end_evt'] . ') '
                // ou finit pendant l'evt en cours
                . ' OR (tsp_end_evt >= ' . $evt['tsp_evt'] . ' AND tsp_end_evt <= ' . $evt['tsp_end_evt'] . ') '
                // ou "encadre" l'evt en cours
                . ' OR (tsp_evt <= ' . $evt['tsp_evt'] . ' AND tsp_end_evt >= ' . $evt['tsp_end_evt'] . ') '
            . ' )
            ORDER BY tsp_evt ASC
            LIMIT 1000';

    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if ($result) {
        while ($tmpJoin = $result->fetch_assoc()) {
            $sorties[] = $tmpJoin;
        }
    }

    return $sorties;
}

function get_sortie($id_evt, $type = 'full')
{
    $sortie = false;
    $data = '';

    switch ($type) {
        case 'full':
            $data = 'id_evt, code_evt, status_evt, status_legal_evt, user_evt, commission_evt, tsp_evt, tsp_end_evt, tsp_crea_evt, tsp_edit_evt, place_evt, rdv_evt,titre_evt, massif_evt, tarif_evt, cycle_master_evt, cycle_parent_evt, child_version_from_evt
				, cancelled_evt, cancelled_who_evt, cancelled_when_evt, description_evt, denivele_evt, difficulte_evt, matos_evt, need_benevoles_evt
				, lat_evt, long_evt
				, join_start_evt
				, ngens_max_evt, join_max_evt
				, nickname_user
				, title_commission, code_commission';
            break;
        case 'commission':
            $data = 'id_evt, title_commission, code_commission';
            break;
    }

    $req = "SELECT $data
		FROM caf_evt, caf_user, caf_commission
		WHERE id_evt=$id_evt
            AND id_user = user_evt
            AND commission_evt=id_commission
                LIMIT 1;";

    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sortie = $row;
        }
    }

    return $sortie;
}

function get_encadrants($id_evt, $only_ids = false)
{
    $users = [];
    $req = "SELECT id_user, civ_user,  cafnum_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user, birthday_user
                            , role_evt_join, is_covoiturage
                    FROM caf_evt_join, caf_user
                    WHERE evt_evt_join = $id_evt
                    AND user_evt_join = id_user
                    AND status_evt_join = 1
                    AND
                        (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'stagiaire' OR role_evt_join LIKE 'coencadrant')
                    LIMIT 300";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($only_ids) {
                $users[] = $row['id_user'] ?? [];
            } else {
                $row['sortie'] = get_sortie($id_evt);
                $users[] = $row;
            }
        }
    }

    return count($users) ? $users : false;
}

function mon_inscription($id_evt)
{
    $my_choices = false;

    if (user()) {
        $req = "SELECT * FROM `caf_evt_join` WHERE `evt_evt_join` = $id_evt AND `user_evt_join` = " . getUser()->getId() . ' LIMIT 1;';
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($row = $result->fetch_assoc()) {
            $my_choices = $row;
        }
    }

    return $my_choices;
}

function get_user($id_user, $valid = true, $simple = true)
{
    $user = null;
    $id_user = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) $id_user);
    $req = 'SELECT ';
    if ($simple) {
        $req .= ' id_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user, birthday_user, civ_user ';
    } else {
        $req .= ' * ';
    }
    $req .= " FROM caf_user WHERE id_user = $id_user";
    if ($valid) {
        $req .= ' AND valid_user = 1 ';
    }
    $req .= ' LIMIT 1';

    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $user = $row;
    }

    return $user;
}

function get_lieu($id_lieu)
{
    $lieu = null;
    $id_lieu = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) $id_lieu);
    $req = 'SELECT * FROM `caf_lieu` WHERE id = ' . $id_lieu . ' LIMIT 1';

    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($row = $result->fetch_assoc()) {
        $lieu = $row;
    }

    return $lieu;
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
        return '<iframe width="' . $w . '" height="' . $h . '" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"  src="' . $src . '" allowfullscreen></iframe>';
    }

    return null;
}

function display_new_lieu()
{
    global $_POST;

    return '<div style="float:left; width:50%" class="lieu_map">' .
            '<label for="lieu">Lieu :</label>' .
            '<input type="text" name="lieu[nom]"  id="lieu" class="type2" style="width:95%" value="' . inputVal('lieu|nom', '') . '" placeholder="ex: La dent du chat, Parking de Casino, 15 route de la soie, ...">' .
            '</div>' .
            '<div style="float:left; width:45%; padding:0 20px 0 0;">' .
            'Précisez sur la carte :<br />' .
            '<input type="button" name="codeAddress" class="type2" style="border-radius:5px; cursor:pointer;" value="Positionner" />' .
            '<input type="hidden" name="lieu[lat]" id="lieuLat" value="' . inputVal('lieu|lat', '') . '" />' .
            '<input type="hidden" name="lieu[lng]" id="lieuLng" value="' . inputVal('lieu|lng', '') . '" />' .
            '</div>' .
            '<br style="clear:both" />' .
            '<div id="place_finder_error" class="erreur" style="display:none"></div>' .
            '<div id="map-creersortie"></div>' .
            '<br>'/*.
            '<label for="ign">Extrait IGN : <small>Insérez le code de partage fourni par <a href="https://www.geoportail.gouv.fr/" target="_blank">GeoPortail</a>.</small></label>'.
            '<textarea name="lieu[ign]" id="ign" style="width:95%;height:80px;" class="type2">'.inputVal('lieu|ign', '').'</textarea>'*/;
}

function display_new_lieu_complexe($name = null, $reset = false)
{
    $arg = $val = null;
    if ($name) {
        $arg = "[$name]";
    }
    if ($reset) {
        $val = '|custom';
    } else {
        $val = '|' . $name;
    }

    return '<div class="lieu_map" id="lieu_' . $name . '"><div>' .
    '<label for="lieu' . $name . '">Lieu :</label>' .
    '<input type="text" name="lieu' . $arg . '[nom]"  id="lieu-lieu_' . $name . '" class="type2" style="width:95%" value="' . inputVal('lieu' . $val . '|nom', '') . '" placeholder="ex: La dent du chat, Parking de Casino, 15 route de la soie, ...">' .
    '</div>' .
    '<div>' .
    'Précisez sur la carte :<br />' .
    '<input type="button" name="codeAddress-lieu_' . $name . '" class="type2" style="border-radius:5px; cursor:pointer;" value="Positionner" />' .
    '<input type="hidden" name="lieu' . $arg . '[lat]" class="lieuLat" value="' . inputVal('lieu' . $val . '|lat', '') . '" />' .
    '<input type="hidden" name="lieu' . $arg . '[lng]" class="lieuLng" value="' . inputVal('lieu' . $val . '|lng', '') . '" />' .
    '</div>' .
    '<br style="clear:both" />' .
    '<div class="place_finder_error" class="erreur" style="display:none"></div>' .
    '<div class="map-creersortie" id="map-creersortie-lieu_' . $name . '"></div>' .
    '<br>'/*.
    '<label for="ign'.$name.'">Extrait IGN : <small>Insérez le code de partage fourni par <a href="https://www.geoportail.gouv.fr/" target="_blank">GeoPortail</a>.</small></label>'.
    '<textarea name="lieu'.$arg.'[ign]" id="ign'.$name.'" style="width:95%;height:80px;" class="type2">'.inputVal('lieu'.$val.'|ign', '').'</textarea>'*/ . '</div>';
}

function display_edit_lieu_link($id_lieu, $nom)
{
    return false;

    return '<a href="" class="todo edit rght mr10" title="Modifier le lieu : ' . $nom . '"></a>';
}

function display_dateTime($datetime)
{
    if (!$datetime) {
        return null;
    }
    $oDate = new DateTime($datetime);
    $sDate = $oDate->format('d/m/Y');
    $sHeure = $oDate->format("H\hi");

    return 'le ' . $sDate . ' à ' . $sHeure;
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

    return jour($oDate->format('N'), 'short') . $oDate->format(' d ') . mois($oDate->format('m')) . $oDate->format(' Y ');
}
