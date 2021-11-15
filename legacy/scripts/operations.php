<?php

// quitter la session admin
if ($_GET['quitadmin']) {
    admin_stop();
}
// quitter la session user
if ($_GET['user_logout']) {
    user_logout();
    header('Location: accueil.html');
}

// if($_POST['operation'])	$errTab=array();
$errTab = [];
$operationsDir = __DIR__.'/operations/';

/* -------------------------- **/
/* OPERATIONS SPECIFIQUES CAF **/
/* -------------------------- **/

// SPECIAL : REINIT MDP : seconde étape (confirmation depuis le lien dans l'email
if ('mot-de-passe-perdu' == $p1 && $p2) {
    include $operationsDir.'operations.mot-de-passe-perdu.php';
}

// SPECIAL : REINIT EMAIL : seconde étape (confirmation depuis le lien dans l'email
if ('email-change' == $p1 && $p2) {
    include $operationsDir.'operations.email-change.php';
}

// SPECIAL : VALIDATION DE COMPTE USER
if ('user-confirm' == $p1) {
    include $operationsDir.'operations.user-confirm.php';
}

// GOTO
if ('goto' == $p1 && $p2 && $p3) {
    include $operationsDir.'operations.goto.php';
}

// COMMISSIONS : ACTIVER / DESACTIVER
if ('commission_majvis' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.commission_majvis.php';
}

// COMMISSIONS : REORGANISER
if ('commission_reorder' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.commission_reorder.php';
}

// COMMISSIONS : CREATE
if ('commission_add' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.commission_add.php';
}

// COMMISSIONS : EDIT
if ('commission_edit' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.commission_edit.php';
    include $operationsDir.'operations.groupe_edit.php';
}

// JOINS : USER / SORTIE : changement en bloc des statuts par l'organisateur
if ('user_join_update_status' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.user_join_update_status.php';
}

// JOINS : USER / SORTIE : annulation
if ('user_join_del' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.user_join_del.php';
}

// JOINS : USER / SORTIE : pré inscription + pré iscription affiliés
if ('user_join' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.user_join.php';
}

// JOINS : USER / SORTIE : inscription manuelle de la part de l'organisateur de l'événemeent
if ('user_join_manuel' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.user_join_manuel.php';
}

// JOINS : USER / SORTIE : inscription de nomade + création s'il n'existe pas deja
if ('user_join_nomade' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.user_join_nomade.php';
}

// SORTIE : suppression
if ('evt_del' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_del.php';
}

// SORTIE : reactivation
if ('evt_uncancel' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_uncancel.php';
}

// SORTIE : annulation
if ('evt_cancel' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_cancel.php';
}

// SORTIE : publication OU refus
if ('evt_validate' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_validate.php';
}

// SORTIE : modification : remet le status à 0
if ('evt_update' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_update.php';
}

// SORTIE : création
if ('evt_create' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_create.php';
}

// SORTIE : validation légale ou refus
if ('evt_legal_update' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_legal_update.php';
}
// SORTIE : contacter les inscrits
if ('evt_user_contact' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.evt_user_contact.php';
}

// DESTINATION : création
if ('dest_create' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.dest_create.php';
}

// DESTINATION : update
if ('dest_update' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.dest_update.php';
}

// DESTINATION : validation rapide / changement d'état
if (in_array($_POST['operation'], ['dest_validate', 'dest_lock', 'dest_annuler'], true) && user()) {
    include $operationsDir.'operations.dest_quick_update.php';
}

// DESTINATION : annulation
if ('dest_cancel' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.dest_cancel.php';
}

// DESTINATION : enoiv emails cloture
if ('dest_mailer' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.dest_mailer.php';
}

// BUS : update
if ('bus_update' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.bus_update.php';
}

// ARTICLE : publication OU refus
if ('article_validate' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.article_validate.php';
}

// ARTICLE : SUPPRIMER
if ('article_del' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.article_del.php';
}

// ARTICLE : DÉPUBLIER
if ('article_depublier' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.article_depublier.php';
}

// ARTICLE : MODIFIER
if ('article_update' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.article_update.php';
}

// ARTICLE : CRÉER
if ('article_create' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.article_create.php';
}

// ARTICLE : REMONTER EN TETE
if ('renew_date_article' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.renew_date_article.php';
}

// ARTICLES : COMMENTER
if ('comment' == $_POST['operation']) {
    include $operationsDir.'operations.comment.php';
}

// ARTICLES : SUPPRIMER UN COMMENTAIRE
if ('comment_hide' == $_POST['operation']) {
    include $operationsDir.'operations.comment_hide.php';
}

// PARTENAIRE : EDIT or ADD
if ('partenaire_edit' == $_POST['operation'] || 'partenaire_add' == $_POST['operation']) {
    include $operationsDir.'operations.partenaire_edit.php';
}

// PARTENAIRE : DELETE
if ('partenaire_delete' == $_POST['operation']) {
    include $operationsDir.'operations.partenaire_delete.php';
}

// USER : DELETE PROFIL IMG
if ('user_profil_img_delete' == $_POST['operation'] && user()) {
    include $operationsDir.'operations.user_profil_img_delete.php';
}

// USER : UPDATE PROFILE
if ('user_update' == $_POST['operation']) {
    include $operationsDir.'operations.user_update.php';
}

// USER : UPDATE NIVEAU SPORTIF par commission
if ('user_update' == $_POST['operation'] || 'niveau_update' == $_POST['operation']) {
    include $operationsDir.'operations.user_niveau_update.php';
}

// USER : TENTATIVE D'INSCRIPTION
if ('user_subscribe' == $_POST['operation']) {
    include $operationsDir.'operations.user_subscribe.php';
}

// USER : LOGIN
if ('user_login' == $_POST['operation']) {
    include $operationsDir.'operations.user_login.php';
}

// USER : ajout de l'attribut à l'user (type salarié, encadrant etc...)
if ('user_attr_add' == $_POST['operation']) {
    include $operationsDir.'operations.user_attr_add.php';
}

// USER : supression d'attribut
if ('user_attr_del' == $_POST['operation']) {
    $errTab[] = 'tooddo';
    $id_user_attr = (int) ($_POST['id_user_attr']);
    if (!$id_user_attr) {
        $errTab[] = 'No id';
    } else {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $req = 'DELETE FROM '.$pbd."user_attr WHERE id_user_attr = $id_user_attr LIMIT 1;";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
        $mysqli->close();
    }
}
// USER : CREATE (manuel)
if ('user_create' == $_POST['operation']) {
    include $operationsDir.'operations.user_create.php';
}

// USER : EDIT (manuel)
if ('user_edit' == $_POST['operation']) {
    include $operationsDir.'operations.user_edit.php';
}

// USER : SUPPRIMER
if ('user_delete' == $_POST['operation']) {
    $id_user = (int) ($_POST['id_user']);
    if (!$id_user) {
        $errTab[] = 'No id';
    } elseif (!admin() || !allowed('user_delete')) {
        $errTab[] = "Vous n'avez pas les droits necessaires";
    } else {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        // suppression participations aux sorties
        $req = "DELETE FROM caf_evt_join WHERE caf_evt_join.user_evt_join=$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        // modification des articles de ce user (articles orphelins...)
        $req = "UPDATE caf_article SET user_article=0 WHERE caf_article.user_article=$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        // suppression des droits
        $req = "DELETE FROM caf_user_attr WHERE caf_user_attr.user_user_attr=$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        // suppression du user
        $req = "DELETE FROM `caf_user` WHERE  `caf_user`.`id_user`=$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        mylog('user_delete', "Suppression definitive user $id_user", false);

        $mysqli->close();
    }
}

// USER : DESACTIVER
if ('user_desactiver' == $_POST['operation']) {
    $id_user = (int) ($_POST['id_user']);
    if (!$id_user) {
        $errTab[] = 'No id';
    } elseif (!allowed('user_desactivate_any')) {
        $errTab[] = "Vous n'avez pas les droits necessaires";
    } else {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $req = "UPDATE `caf_user` SET  `valid_user` =  '2' WHERE  `caf_user`.`id_user` =$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        mylog('user_desactiver', "desactivation user $id_user", false);

        $mysqli->close();
    }
}
// USER : REACTIVER
if ('user_reactiver' == $_POST['operation']) {
    $id_user = (int) ($_POST['id_user']);
    if (!$id_user) {
        $errTab[] = 'No id';
    } elseif (!allowed('user_reactivate')) {
        $errTab[] = "Vous n'avez pas les droits necessaires";
    } else {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $req = "UPDATE `caf_user` SET  `valid_user` =  '1' WHERE  `caf_user`.`id_user` =$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        mylog('user_reactiver', "reactivation user $id_user", false);

        $mysqli->close();
    }
}
// USER : RESET
if ('user_reset' == $_POST['operation']) {
    $id_user = (int) ($_POST['id_user']);
    if (!$id_user) {
        $errTab[] = 'No id';
    } elseif (!allowed('user_reset')) {
        $errTab[] = "Vous n'avez pas les droits necessaires";
    } else {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $req = "UPDATE caf_user
				SET valid_user =  '0',
				email_user =  '',
				mdp_user =  ''
				WHERE caf_user.id_user =$id_user";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }

        mylog('user_reset', "reset user $id_user", false);

        $mysqli->close();
    }
}

// USER (OU PAS) : CONTACT
if ('user_contact' == $_POST['operation']) {
    include $operationsDir.'operations.user_contact.php';
}

// MISE À JOUR DES FICHIERS ADHÉRENTS
if ('fichier_adherents_maj' == $_POST['operation']) {
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
                if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], __DIR__.'/../../public/ftp/fichiers-proteges/'.$_FILES['file']['name'][$i])) {
                    $errTab[] = 'Erreur de déplacement du fichier '.$_FILES['file']['name'][$i];
                }
                // $errTab[]="Erreur de déplacement du fichier ".$_FILES['file']['name'][$i]." vers ".'ftp/fichiers-proteges/'.$_FILES['file']['name'][$i];
            }
        }

        if (!$oneGood) {
            $errTab[] = 'Aucun fichier reçu ne correspond, opération ignorée';
        }
    }
}

// USER : RESET MDP
if ('user_mdp_reinit' == $_POST['operation']) {
    include $operationsDir.'operations.user_mdp_reinit.php';
}

// ADMIN : ajout de l'attribut à l'user (type admin, rédacteur etc...)
if ('user_attr_add_admin' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.user_attr_add_admin.php';
}

// ADMIN : supression d'attribut
if ('user_attr_del_admin' == $_POST['operation'] && admin()) {
    $id_user_attr = (int) ($_POST['id_user_attr']);
    if (!$id_user_attr) {
        $errTab[] = 'No id';
    } else {
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $req = 'DELETE FROM '.$pbd."user_attr WHERE id_user_attr = $id_user_attr LIMIT 1;";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
        $mysqli->close();
    }

    // log admin
    if (0 === count($errTab)) {
        mylog($_POST['operation'], "Suppression d'un droit à un user (id=$id_user_attr)");
    }
}

// ADMIN : écrasement et renouvellement de la matrice des droits
if ('usertype_attr_edit' == $_POST['operation'] && admin()) {
    /* ◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊[ BACKUP EXISTANT A FAIRE - ou pas ]◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊◊ */
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

    // supression des valeurs existantes
    if (!$mysqli->query('TRUNCATE '.$pbd.'usertype_attr')) {
        $errTab[] = 'Erreur à la réinitialisation de la table';
    }
    if (0 === count($errTab)) {
        foreach ($_POST['usertype_attr'] as $pair) {
            $tab = explode('-', $pair);
            $type_usertype_attr = (int) ($tab[0]);
            $right_usertype_attr = (int) ($tab[1]);
            if (!$mysqli->query('INSERT INTO '.$pbd."usertype_attr (id_usertype_attr, type_usertype_attr, right_usertype_attr, details_usertype_attr)
															VALUES (NULL , '$type_usertype_attr', '$right_usertype_attr', '$p_time');")) {
                $errTab[] = "Erreur de setting ($type_usertype_attr - $right_usertype_attr)";
            }
        }
    }
    $mysqli->close();
}

// ADMIN: modification de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
if ('pagelibre_edit' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.pagelibre_edit.php';
}

// ADMIN: ajout de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
if ('pagelibre_add' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.pagelibre_add.php';
}

// ADMIN: suppression de page libre // NOTE : PAS DE MULTILANGUE POUR LE MOMENT
if ('pagelibre_del' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.pagelibre_del.php';
}

// ADMIN : VOL DE SESSION (variables GET pour ce coup là)
if ('steal_session' == $_GET['operation'] && admin()) {
    $email_user = $_GET['email_user'];
    mylog('steal_session', "infiltration d'un user ($email_user)");
    user_login($email_user);
}

/* -------------------------- **/
/* OPERATIONS BASE      **/
/* -------------------------- **/

// BASE: page add
if ('page_add' == $_POST['operation'] && superadmin()) {
    include $operationsDir.'operations.page_add.php';
}
// BASE: page del
if ('page_del' == $_POST['operation'] && superadmin()) {
    include $operationsDir.'operations.page_del.php';
}

// BASE: add groupe de contenu
if ('addContentGroup' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.addContentGroup.php';
}

// BASE: add contenu inline
if ('addContentInline' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.addContentInline.php';
}

// GENERIQUE: maj
if ('majBd' == $_POST['operation'] && admin()) {
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    $table = $mysqli->real_escape_string($_POST['table']);
    $champ = $mysqli->real_escape_string($_POST['champ']);
    $val = $mysqli->real_escape_string(stripslashes($_POST['val']));
    $id = (int) ($_POST['id']);

    if (!$table) {
        $errTab[] = 'Table manquante';
    }
    if (!$champ) {
        $errTab[] = 'Champ manquant';
    }
    // if(!$val) $errTab[]="Val manquante";
    if (!$id) {
        $errTab[] = 'ID manquant';
    }

    if (0 === count($errTab)) {
        $req = 'UPDATE `'.$pbd.$table."` SET `$champ` = '$val' WHERE `".$pbd.$table.'`.`id_'.$table."` =$id LIMIT 1 ;";
        if (!$mysqli->query($req)) {
            $erreur = 'Erreur BDD<br />'.$req;
        }
    }
    $mysqli->close();
}

// GENERIQUE: sup
if ('supBd' == $_POST['operation'] && admin()) {
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    $table = $mysqli->real_escape_string($_POST['table']);
    $id = (int) ($_POST['id']);

    $req = 'DELETE FROM `'.$pbd.$table.'` WHERE `'.$pbd.$table.'`.`id_'.$table."` = $id LIMIT 1;";
    if (!$mysqli->query($req)) {
        $erreur = 'Erreur BDD<br />'.$req;
    }
    $mysqli->close();
}

// ADMIN : MISE A JOUR DES CONTENUS
if ('majConts' == $_POST['operation'] && admin()) {
    $langueCont = $_POST['langueCont'];
    if (!file_exists("contenus/$langueCont.txt")) {
        $erreur = 'Fichier de langue introuvable';
    } else {
        $contenu = '';
        // pour chaque var de contenu
        foreach ($_POST as $key => $val) {
            if ('contenu-' == substr($key, 0, 8)) {
                $contenu .= '
#'.substr($key, 8).'
'.stripslashes($val);
            }
        }

        if (!$contenu) {
            $erreur = 'Aucun contenu reçu';
        } else {
            // echo $contenu;
            if ($handle = fopen('contenus/'.$langueCont.'.txt', 'w')) {
                fwrite($handle, $contenu);
                fclose($handle);
            } else {
                $erreur = 'Ecriture impossible';
            }
        }
    }
}
// ADMIN : NOUVELLE PAGE
if ('page_new' == $_POST['operation'] && admin()) {
    include $operationsDir.'operations.page_new.php';
}

// CONTACT
if ('contact' == $_POST['operation']) {
    include $operationsDir.'operations.contact.php';
}
