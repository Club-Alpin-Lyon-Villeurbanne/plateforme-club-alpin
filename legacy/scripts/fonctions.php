<?php

use App\Legacy\LegacyContainer;

/*
    Récupération des articles publiés d'un utilisateur
    ET affichage
*/

function display_articles($id_user, $limit = 10, $title = '')
{
    $req = '
        SELECT SQL_CALC_FOUND_ROWS a.*, m.filename
        FROM caf_article as a
        LEFT JOIN media_upload m ON a.media_upload_id = m.id
        WHERE status_article=1
        AND user_article = ' . $id_user
        . ' ORDER BY a.`updated_at` DESC
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
