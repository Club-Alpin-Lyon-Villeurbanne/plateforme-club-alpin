<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$id_evt = (int) ($_POST['id_evt']);
$id_user = getUser()->getId();

$evtDate = $evtTarif = $encEmail = $encName = null;

if (!$id_user || !$id_evt) {
    $errTab[] = 'Erreur de données';
}

if (!isset($errTab) || 0 === count($errTab)) {
    // Informations sur l'événement
    $req = "SELECT id_evt, titre_evt, tsp_evt, tarif_evt, code_evt FROM caf_evt WHERE id_evt=$id_evt LIMIT 1";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $row = $result->fetch_row();
    if ($row) {
        $evtId = html_utf8($row[0]);
        $evtCode = html_utf8($row[4]);
        $evtName = html_utf8($row[1]);
        $evtDate = html_utf8(date('d-m-Y', $row[2]));
        $evtTarif = html_utf8($row[3]);
        $evtUrl = html_utf8(LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.stripslashes($evtCode).'-'.$evtId.'.html');
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

    // récupération du statut de l'inscription : si elle est valide, l'orga recoit un e-mail
    $req = "SELECT status_evt_join, is_cb FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user ORDER BY tsp_evt_join DESC LIMIT 1 ";
    $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    $status_evt_join = 0;
    $is_cb = 0;
    while ($row = $result->fetch_assoc()) {
        $status_evt_join = $row['status_evt_join'];
        $is_cb = $row['is_cb'];
    }

    if (1 == $status_evt_join || 0 == $status_evt_join) {
        // envoi du mail à l'orga
        // recup de son email & nom
        $toMail = '';
        $toName = '';
        $req = "SELECT email_user, firstname_user, lastname_user FROM caf_user, caf_evt WHERE id_evt=$id_evt AND user_evt=id_user LIMIT 1";
        $result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($row = $result->fetch_assoc()) {
            $toMail = $row['email_user'];
            $toName = $row['firstname_user'].' '.$row['lastname_user'];
        }
        if (!isMail($toMail)) {
            $errTab[] = 'Les coordonnées du contact sont erronées';
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            // si pas de pb, suppression de l'inscription
            $req = "DELETE FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user";
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                $errTab[] = 'Erreur SQL';
            }

            $tmpUserName = (getUser()->getFirstname().' '.getUser()->getLastname());
            $evtName = $_POST['titre_evt'];
            $evtUrl = LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'sortie/'.stripslashes($_POST['code_evt']).'-'.$_POST['id_evt'].'.html';

            LegacyContainer::get('legacy_mailer')->send($toMail, 'transactional/sortie-desinscription', [
                'username' => $tmpUserName,
                'event_url' => $evtUrl,
                'event_name' => $evtName,
                'user' => getUser(),
            ], [], null, getUser()->getEmail());

            // paiement en ligne
            // envoi d'un email pour indiquer la désinscription d'un participant avec paiement en ligne
            if (1 == $is_cb) {
                $toNameFull = getUser()->getFirstname().' '.getUser()->getLastname();
                $toCafNum = getUser()->getCafnum();

                LegacyContainer::get('legacy_mailer')->send('comptabilite@clubalpinlyon.fr', 'transactional/sortie-desinscription-paiement-ligne', [
                    'event_name' => $evtName,
                    'event_url' => $evtUrl,
                    'event_date' => $evtDate,
                    'adherent' => $toNameFull,
                    'event_tarif' => $evtTarif,
                    'caf_num' => $toCafNum,
                    'encadrant_name' => $encName,
                    'encadrant_email' => $encEmail,
                ]);
            }
        }
    }
}
