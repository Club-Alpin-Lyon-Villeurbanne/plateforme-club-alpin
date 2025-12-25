<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$errTab = [];
$operationsDir = __DIR__ . '/operations/';

/* -------------------------- * */
/* OPERATIONS SPECIFIQUES CAF * */
/* -------------------------- * */

$operation = $_POST['operation'] ?? null;

// Protection : whitelist des opérations publiques autorisées
$publicOperations = ['user_subscribe'];
if ($operation && !in_array($operation, $publicOperations, true) && !user()) {
    http_response_code(403);
    exit('Forbidden');
}

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

    // ARTICLE : publication OU refus
    elseif ('article_validate' == $operation) {
        require $operationsDir . 'operations.article_validate.php';
    }

    // ARTICLE : SUPPRIMER
    elseif ('article_del' == $operation) {
        require $operationsDir . 'operations.article_del.php';
    }

    // ARTICLE : DÉPUBLIER
    elseif ('article_depublier' == $operation) {
        require $operationsDir . 'operations.article_depublier.php';
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

// USER : CREATE (manuel)
if ('user_create' == $operation) {
    require $operationsDir . 'operations.user_create.php';
}

// USER : EDIT (manuel)
elseif ('user_edit' == $operation) {
    require $operationsDir . 'operations.user_edit.php';
}

// USER : SUPPRIMER
elseif ('user_delete' == $operation) {
    $id_user = (int) $_POST['id_user'];
    if (!$id_user) {
        $errTab[] = 'No id';
    } elseif (!isGranted(SecurityConstants::ROLE_ADMIN) || !allowed('user_delete')) {
        $errTab[] = "Vous n'avez pas les droits necessaires";
    } else {
        // suppression participations aux sorties
        $req = "DELETE FROM caf_evt_join WHERE caf_evt_join.user_evt_join=$id_user";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        // modification des articles de ce user (articles orphelins...)
        $req = "UPDATE caf_article SET user_article=0 WHERE caf_article.user_article=$id_user";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        // suppression des droits
        $req = "DELETE FROM caf_user_attr WHERE caf_user_attr.user_user_attr=$id_user";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        // suppression du user
        $req = "DELETE FROM `caf_user` WHERE  `caf_user`.`id_user`=$id_user";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        mylog('user_delete', "Suppression definitive user $id_user", false);
    }
}

// USER (OU PAS) : CONTACT
elseif ('user_contact' == $operation) {
    require $operationsDir . 'operations.user_contact.php';
}

// MISE À JOUR DES FICHIERS ADHÉRENTS
elseif ('fichier_adherents_maj' == $operation) {
    if (!allowed('user_updatefiles')) {
        $errTab[] = 'Il semble que vous ne disposez pas des droits nécessaires';
    }

    $length = 0;
    if (0 === count($errTab)) {
        $length = count($_FILES['file']['name']);
        if ($length < 1) {
            $errTab[] = 'Aucunes données reçues';
        }
    }

    if (0 === count($errTab)) {
        $oneGood = false;
        for ($i = 0; $i < $length; ++$i) {
            if ('7300.txt' == $_FILES['file']['name'][$i] || '7480.txt' == $_FILES['file']['name'][$i]) {
                $oneGood = true;
                if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], __DIR__ . '/../../public/ftp/fichiers-proteges/' . $_FILES['file']['name'][$i])) {
                    $errTab[] = 'Erreur de déplacement du fichier ' . $_FILES['file']['name'][$i];
                }
            }
        }

        if (!$oneGood) {
            $errTab[] = 'Aucun fichier reçu ne correspond, opération ignorée';
        }
    }
}

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    // ADMIN : écrasement et renouvellement de la matrice des droits
    if ('usertype_attr_edit' == $operation) {
        /* ◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊[ BACKUP EXISTANT A FAIRE - ou pas ]◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊ */

        // supression des valeurs existantes
        if (!LegacyContainer::get('legacy_mysqli_handler')->query('TRUNCATE caf_usertype_attr')) {
            $errTab[] = 'Erreur à la réinitialisation de la table';
        }
        if (0 === count($errTab)) {
            foreach ($_POST['usertype_attr'] as $pair) {
                $tab = explode('-', $pair);
                $type_usertype_attr = (int) $tab[0];
                $right_usertype_attr = (int) $tab[1];
                if (!LegacyContainer::get('legacy_mysqli_handler')->query("INSERT INTO caf_usertype_attr (type_usertype_attr, right_usertype_attr, details_usertype_attr)
                                                                VALUES ('$type_usertype_attr', '$right_usertype_attr', '" . time() . "');")) {
                    $errTab[] = "Erreur de setting ($type_usertype_attr - $right_usertype_attr)";
                }
            }
        }
    }

    // ADMIN: modification de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
    elseif ('pagelibre_edit' == $operation) {
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
    // GENERIQUE: maj
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

    // GENERIQUE: sup
    elseif ('supBd' == $operation) {
        $table = LegacyContainer::get('legacy_mysqli_handler')->escapeString($_POST['table']);
        $id = (int) $_POST['id'];

        $req = 'DELETE FROM `caf_' . $table . '` WHERE `caf_' . $table . '`.`id_' . $table . "` = $id LIMIT 1;";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            $erreur = 'Erreur BDD<br />' . $req;
        }
    }

    // ADMIN : MISE A JOUR DES CONTENUS
    elseif ('majConts' == $operation) {
        $langueCont = $_POST['langueCont'];
        if (!file_exists("contenus/$langueCont.txt")) {
            $erreur = 'Fichier de langue introuvable';
        } else {
            $contenu = '';
            // pour chaque var de contenu
            foreach ($_POST as $key => $val) {
                if ('contenu-' == substr($key, 0, 8)) {
                    $contenu .= '
    #' . substr($key, 8) . '
    ' . stripslashes($val);
                }
            }

            if (!$contenu) {
                $erreur = 'Aucun contenu reçu';
            } else {
                // echo $contenu;
                if ($handle = fopen('contenus/' . $langueCont . '.txt', 'w')) {
                    fwrite($handle, $contenu);
                    fclose($handle);
                } else {
                    $erreur = 'Ecriture impossible';
                }
            }
        }
    }
    // ADMIN : NOUVELLE PAGE
    elseif ('page_new' == $operation) {
        require $operationsDir . 'operations.page_new.php';
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
