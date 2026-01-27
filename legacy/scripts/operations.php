<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$errTab = [];
$operationsDir = __DIR__ . '/operations/';

/* -------------------------- * */
/* OPERATIONS SPECIFIQUES CAF * */
/* -------------------------- * */

$operation = $_POST['operation'] ?? null;

if (user()) {
    // COMMISSIONS : ACTIVER / DESACTIVER
    if ('commission_majvis' == $operation) {
        require $operationsDir . 'operations.commission_majvis.php';
    }

    // COMMISSIONS : REORGANISER
    elseif ('commission_reorder' == $operation) {
        require $operationsDir . 'operations.commission_reorder.php';
    }

    // COMMISSIONS : CREATE
    elseif ('commission_add' == $operation) {
        require $operationsDir . 'operations.commission_add.php';
    }

    // COMMISSIONS : EDIT
    elseif ('commission_edit' == $operation) {
        require $operationsDir . 'operations.commission_edit.php';
        require $operationsDir . 'operations.groupe_edit.php';
    }

    // ARTICLE : REMONTER EN TETE
    elseif ('renew_date_article' == $operation) {
        require $operationsDir . 'operations.renew_date_article.php';
    }

    // ARTICLES : SUPPRIMER UN COMMENTAIRE
    elseif ('comment_hide' == $operation) {
        require $operationsDir . 'operations.comment_hide.php';
    }
}

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    // ADMIN: modification de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
    if ('pagelibre_edit' == $operation) {
        require $operationsDir . 'operations.pagelibre_edit.php';
    }

    // ADMIN: ajout de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
    elseif ('pagelibre_add' == $operation) {
        require $operationsDir . 'operations.pagelibre_add.php';
    }

    // ADMIN: suppression de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
    elseif ('pagelibre_del' == $operation) {
        require $operationsDir . 'operations.pagelibre_del.php';
    }

    // BASE: add groupe de contenu
    elseif ('addContentGroup' == $operation) {
        require $operationsDir . 'operations.addContentGroup.php';
    }

    // BASE: add contenu inline
    elseif ('addContentInline' == $operation) {
        require $operationsDir . 'operations.addContentInline.php';
    }

    // GENERIQUE: maj page libre
    elseif ('majBd' == $operation) {
        $table = LegacyContainer::get('legacy_mysqli_handler')->escapeString($_POST['table']);
        $champ = LegacyContainer::get('legacy_mysqli_handler')->escapeString($_POST['champ']);
        $val = LegacyContainer::get('legacy_mysqli_handler')->escapeString(stripslashes($_POST['val']));
        $id = (int) $_POST['id'];

        if (!$table) {
            $errTab[] = 'Table manquante';
        }
        if (!$champ) {
            $errTab[] = 'Champ manquant';
        }
        if (!$id) {
            $errTab[] = 'ID manquant';
        }

        if (0 === count($errTab)) {
            $req = 'UPDATE `caf_' . $table . "` SET `$champ` = '$val' WHERE `caf_" . $table . '`.`id_' . $table . "` =$id LIMIT 1 ;";
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $erreur = 'Erreur BDD<br />' . $req;
            }
        }
    }
}

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    // BASE: page add
    if ('page_add' == $operation) {
        require $operationsDir . 'operations.page_add.php';
    }
    // BASE: page del
    elseif ('page_del' == $operation) {
        require $operationsDir . 'operations.page_del.php';
    }
}
