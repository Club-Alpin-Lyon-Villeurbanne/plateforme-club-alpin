<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$role_evt_join = $toNameFull = $toTel = $affiliant_user_join = $subject = $content_main = $evtDate = $evtTarif = $toCafNum = $encEmail = $encName = null;

// vars
$id_evt = (int) ($_POST['id_evt']);
$errTabMail = [];

$addAlert = []; // pour affichage de messages supplémentaires
// on recoit un tableau des ID de JOINTS
// et toutes les valeurs du statut présentées sous la forme : status_evt_join_ID_EVT_JOIN

// suis-je encadrant sur cette sortie ?
$suis_encadrant = false;
$req = "SELECT COUNT(id_evt_join)
FROM caf_evt_join
WHERE evt_evt_join=$id_evt
AND user_evt_join = ".getUser()->getId()."
AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'coencadrant')
LIMIT 1";
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_encadrant = true;
}

// suis-je l'auteur de cette sortie ?
$suis_auteur = false;
$req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND user_evt = ".getUser()->getId().' LIMIT 1';
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_auteur = true;
}

// Informations sur l'événement
$req = "SELECT id_evt, titre_evt, tsp_evt, tarif_evt FROM caf_evt WHERE id_evt=$id_evt LIMIT 1";
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$row = $result->fetch_row();
if ($row) {
    $evtId = html_utf8($row[0]);
    $evtTitre = html_utf8($row[1]);
    $evtDate = html_utf8(date('d-m-Y', $row[2]));
    $evtTarif = html_utf8($row[3]);
}

// Informations sur l'encadrant
$req = "SELECT B.firstname_user, B.lastname_user, B.email_user
        FROM caf_evt_join AS A
        LEFT JOIN caf_user AS B
            ON A.user_evt_join = B.id_user
        WHERE A.evt_evt_join=$id_evt
        AND (A.role_evt_join LIKE 'encadrant')
        LIMIT 1";
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$row = $result->fetch_row();
if ($row) {
    $encName = html_utf8($row[0].' '.$row[1]);
    $encEmail = html_utf8($row[2]);
}

if (!allowed('evt_join_doall') && !allowed('evt_join_notme') && !$suis_encadrant && !$suis_auteur) {
    $errTab[] = 'Opération interdite : Il semble que vous ne soyez pas autorisé à modifier les inscrits.';
}

if (isset($_POST['id_evt_join']) && (!isset($errTab) || 0 === count($errTab))) {
    //				print_r($_POST);

    foreach ($_POST['id_evt_join'] as $id_evt_join) {
        $send_mail = false;

        // securite :
        $id_evt_join = (int) $id_evt_join;

        if ($id_evt_join) {
            // nouvelles valeurs demandées
            $status_evt_join_new = '';
            if (array_key_exists('status_evt_join_'.$id_evt_join, $_POST)) {
                $status_evt_join_new = LegacyContainer::get('legacy_mysqli_handler')->escapeString((int) ($_POST['status_evt_join_'.$id_evt_join]));
                $send_mail = true;
            }
            $role_evt_join_new = '';
            if (array_key_exists('role_evt_join_'.$id_evt_join, $_POST)) {
                $role_evt_join_new = LegacyContainer::get('legacy_mysqli_handler')->escapeString($_POST['role_evt_join_'.$id_evt_join]);
            }

            if (0 == strlen($status_evt_join_new) && 0 == strlen($role_evt_join_new)) {
                continue;
            }

            if ($status_evt_join_new < 0) {
                $req = "DELETE FROM caf_evt_join WHERE id_evt_join=$id_evt_join";

                $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                    $errTab[] = 'Erreur SQL';
                }
                continue;
            }

            // récupération de la valeur actuelle, savoir si on la change ou pas
            $req = "SELECT status_evt_join, user_evt_join, affiliant_user_join, role_evt_join, evt_evt_join, is_cb FROM caf_evt_join WHERE id_evt_join=$id_evt_join ORDER BY tsp_evt_join DESC LIMIT 1 ";

            $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);

            $status_evt_join = 0;
            $user_evt_join = 0;
            $evt_evt_join = 0;
            $is_cb = 0;
            $isFiliation = false; // si cette inscription a été enregistrée par un parent : autre e-mail de contact (voir plus loin)

            if ($row = $result->fetch_assoc()) {
                $status_evt_join = (int) ($row['status_evt_join']);
                $user_evt_join = (int) ($row['user_evt_join']);
                $evt_evt_join = (int) ($row['evt_evt_join']);
                $affiliant_user_join = (int) ($row['affiliant_user_join']);
                $role_evt_join = $row['role_evt_join'];
                $is_cb = $row['is_cb'];

                if ($affiliant_user_join > 0) {
                    $isFiliation = true;
                }
            }

            if (('' !== $status_evt_join_new && ($status_evt_join_new != $status_evt_join)) || ('' !== $role_evt_join_new && (0 != strcmp($role_evt_join_new, $role_evt_join)))) {
                // check technique
                if (0 == $user_evt_join || 0 == $evt_evt_join) {
                }// $errTab[]="Erreur de données ($user_evt_join / $evt_evt_join). Mise à jour interrompue.";

                if (!isset($errTab) || 0 === count($errTab)) {
                    // update inscription
                    $req = 'UPDATE caf_evt_join
                        SET lastchange_when_evt_join = '.time().'
                        , lastchange_who_evt_join = '.getUser()->getId();

                    // s'il y a modification : update et envoi de mail
                    if ('' !== $status_evt_join_new && ($status_evt_join_new != $status_evt_join)) {
                        $req .= " , status_evt_join='".$status_evt_join_new."'";
                    }
                    if ('' !== $role_evt_join_new && (0 != strcmp($role_evt_join_new, $role_evt_join))) {
                        $req .= " , role_evt_join='".$role_evt_join_new."'";
                    }

                    $req .= " WHERE caf_evt_join.id_evt_join =$id_evt_join";

                    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                        $errTab[] = 'Erreur SQL';
                    }

                    // si la mise à jour s'est bien passée
                    else {
                        // si la nouvelle valeur est 1 ou 2
                        if ($send_mail && (1 == $status_evt_join_new || 2 == $status_evt_join_new)) {
                            // si la var pour empecher les mails n'est pas passée (dans le cas d'un événement deja passé)
                            // if(!$_POST['dontsendmail']){
                            if ('on' != $_POST['disablemails'] && !$_POST['dontsendmail']) {
                                // envoi du mail à l'inscrit (ou au désinscrit du coup)
                                // recup de son email & nom
                                $toMail = '';
                                $toName = '';
                                $isNomade = false;
                                $req = "SELECT email_user, firstname_user, lastname_user, civ_user, nomade_user, tel_user, tel2_user, cafnum_user FROM caf_user WHERE id_user=$user_evt_join LIMIT 1";
                                $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                                while ($row = $result->fetch_assoc()) {
                                    $toMail = $row['email_user'];
                                    $toName = $row['firstname_user'];
                                    $toNameFull = $row['firstname_user'].' '.$row['lastname_user'];
                                    $toTel = $row['tel_user'].' '.($row['tel2_user'] ? ' - '.$row['tel2_user'] : '');
                                    $isNomade = (int) ($row['nomade_user']);
                                    $toCafNum = $row['cafnum_user'];
                                }

                                // nomade ?
                                if ($isNomade) {
                                    $addAlert[] = "
                                        <b>$toNameFull</b> est un adhérent nomade. Il n'a pas d'email et doit être prévenu par téléphone de son nouveau statut : "
                                        .(0 == $status_evt_join_new ? '<b>En attente</b>' : '')
                                        .(1 == $status_evt_join_new ? '<b>Inscrit</b>' : '')
                                        .(2 == $status_evt_join_new ? '<b>Refusé</b>' : '')
                                        .'. <br />Tél : '.$toTel
                                        ;
                                }

                                // filiation ? Dans ce cas on change la valeurs du mail
                                if ($isFiliation) {
                                    $req = "SELECT email_user, firstname_user, lastname_user, civ_user, nomade_user, tel_user, tel2_user FROM caf_user WHERE id_user=$affiliant_user_join LIMIT 1";
                                    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
                                    while ($row = $result->fetch_assoc()) {
                                        $toMail = $row['email_user'];
                                    }
                                }

                                // PAS nomade : email
                                if (!$isNomade && ('' !== $toMail)) {
                                    if (!isset($errTab) || 0 === count($errTab)) {
                                        $evtName = $_POST['titre_evt'];
                                        $evtUrl = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.stripslashes($_POST['code_evt']).'-'.$_POST['id_evt'].'.html';

                                        switch ($role_evt_join) {
                                            case 'encadrant':
                                            case 'coencadrant':
                                            case 'benevole':
                                                $role = $role_evt_join.'(e)';
                                                break;
                                            default:
                                                $role = 'participant(e)';
                                        }

                                        $context = [
                                            'role' => $role,
                                            'event_url' => $evtUrl,
                                            'event_name' => $evtName,
                                        ];

                                        if (1 == $status_evt_join_new) {
                                            LegacyContainer::get('legacy_mailer')->send($toMail, 'transactional/sortie-participation-confirmee', $context);
                                        }
                                        if (2 == $status_evt_join_new) {
                                            LegacyContainer::get('legacy_mailer')->send($toMail, 'transactional/sortie-participation-declinee', $context);
                                        }

                                        if (1 == $is_cb) {
                                            $context = [
                                                'event_name' => $evtName,
                                                'event_url' => $evtUrl,
                                                'event_date' => $evtDate,
                                                'adherent' => $toNameFull,
                                                'event_tarif' => $evtTarif,
                                                'caf_num' => $toCafNum,
                                                'encadrant_name' => $encName,
                                                'encadrant_email' => $encEmail,
                                            ];

                                            if (1 == $status_evt_join_new) {
                                                LegacyContainer::get('legacy_mailer')->send('comptabilite@clubalpinlyon.fr', 'transactional/sortie-participation-confirmee-paiement-ligne', $context);
                                            }

                                            if (2 == $status_evt_join_new) {
                                                LegacyContainer::get('legacy_mailer')->send('comptabilite@clubalpinlyon.fr', 'transactional/sortie-participation-declinee-paiement-ligne', $context);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
$errTab = array_merge(isset($errTab) ? $errTab : [], $errTabMail);
