<?php

    include SCRIPTS.'connect_mysqli.php';
    $id_page = (int) ($_POST['id_page']);

    // Récupération des sous-pages de cette page
    $req = 'SELECT * FROM `'.$pbd."page` WHERE parent_page=$id_page OR id_page=$id_page LIMIT 1000";

    $handleSql = $mysqli->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $code_page = $handle['code_page'];
        $id_page = $handle['id_page'];

        $req = 'SELECT COUNT(*) FROM `'.$pbd."page` WHERE `id_page`=$id_page AND `lock_page`!=1 AND `admin_page`!=1 LIMIT 1";

        $handleSql2 = $mysqli->query($req);
        if (!getArrayFirstValue($handleSql2->fetch_array(\MYSQLI_NUM))) {
            $errTab[] = "Il semble qu'aucune page ne soit autorisée à être supprimée ave cet id.";
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            // del page
            $req = 'DELETE FROM `'.$pbd.'page` WHERE `'.$pbd."page`.`id_page` = $id_page LIMIT 1;";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur BDD<br />'.$req;
            }

            // del contenus liés
            $req = 'DELETE FROM `'.$pbd."content_inline` WHERE `code_content_inline` LIKE 'meta-title-".$code_page."' OR `code_content_inline` LIKE 'mainmenu-".$code_page."'";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur BDD<br />'.$req;
            }
        }
        echo '<hr />';

        // LOG
        if (!isset($errTab) || 0 === count($errTab)) {
            mylog('page-delete', "Suppression de la page $code_page", false);
        }
    }

    $mysqli->close();
