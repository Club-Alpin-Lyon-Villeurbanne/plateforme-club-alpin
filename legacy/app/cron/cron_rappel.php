<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$MAX_TIMESTAMP_FOR_LEGAL_VALIDATION = strtotime(LegacyContainer::getParameter('legacy_env_MAX_TIMESTAMP_FOR_LEGAL_VALIDATION'));
$DATE_BUTOIRES = LegacyContainer::getParameter('legacy_env_CRON_DATE_BUTOIRES');

/**
 * Cette page a pour fonction d'envoyer les emails de rappels :
 * - RAPPEL D'eVeNEMENTS UTILISATEURS 1 : à X (=4) jours avant evt
 * - RAPPEL D'eVeNEMENTS UTILISATEURS 2 : à X (=2) jours avant evt
 * - CONTACT DES RESPONSABLES DE COMMISSION POUR VALIDER LES SORTIES EN ATTENTE
 * - CONTACT DES PRESIDENTS - VICE-PRES POUR VALIDER LEGALEMENT LES SORTIES EN ATTENTE.
 */
require __DIR__ . '/../../app/includes.php';

function cron_email($datas)
{
    if (!$datas['parent']) {
        throw new InvalidArgumentException('Missing parent');
    }
    if (!$datas['code']) {
        throw new InvalidArgumentException('Missing code');
    }

    // Verification que ce code n'existe pas deja :
    // Chaque operation ne doit être effectuee qu'une fois (un seul e-mail envoye)
    $req = "SELECT COUNT(id_chron_operation) FROM caf_chron_operation WHERE code_chron_operation = '" . $datas['code'] . "'";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    if (getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM))) {
        error_log('Envoi de mail ignore car deja envoye : ' . $datas['code'] . '');

        return;
    }

    LegacyContainer::get('legacy_mailer')->send($datas['to'], $datas['template'], $datas['context']);

    $req = "INSERT INTO caf_chron_operation(tsp_chron_operation, code_chron_operation, parent_chron_operation)
                                VALUES ('" . time() . "',  '" . $datas['code'] . "',  '" . $datas['parent'] . "');";

    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        error_log('Erreur sauvegarde SQL ');
    }
}

// heure actuelle
$h = date('h');

// timestamp auquel le dernier envoi aurait du être envoye: Par defaut c'est la veille a la dernière heure
$minTsp = mktime(end($DATE_BUTOIRES), 0, 0, date('m', strtotime('-1 day')), date('d', strtotime('-1 day')), date('Y', strtotime('-1 day')));
// Si aujourd'hui on a deja depasse une heure de lancement alors le dernier envoie aurait du être ajourd'hui a cette heure
foreach ($DATE_BUTOIRES as $tmpH) {
    if (date('H') > $tmpH) {
        $minTsp = mktime($tmpH, 0, 0, date('m'), date('d'), date('Y'));
    }
}

// ****** FAUT IL REQUETER ? ********
// dernier envoi en BD date de... :
$req = 'SELECT `tsp_chron_launch` FROM `caf_chron_launch` ORDER BY  `tsp_chron_launch` DESC LIMIT 0 , 1';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$lastTsp = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));

// envoi necessaire ? Alors go !
if ($lastTsp >= $minTsp) {
    return;
}
// sauvegarde de ce launch
error_log('Envoi necessaire ! Enregistrement de la nouvelle date en BDD');
if (!LegacyContainer::get('legacy_mysqli_handler')->query("INSERT INTO caf_chron_launch(tsp_chron_launch) VALUES('" . time() . "');")) {
    throw new RuntimeException("Erreur d'enregistrement du launch");
}
$id_chron_launch = LegacyContainer::get('legacy_mysqli_handler')->insertId();

// *******************************
// CONTACT DES RESPONSABLES DE COMMISSION POUR VALIDER LES SORTIES EN ATTENTE
error_log('---------------------------- please_validate_evt ----------------------------');
$nEltsToValidate = 0;
// c'est un tableau associatif qui va contenir en cle les emails des responsables, et en valeur la liste des sorties liees
// on peut alors creer un e-mail unqiue pour chaque user, avec la liste de ses sorties a valider
$datasTab = [];

// Pour chaque sortie non validee
$req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt
            , id_commission, code_commission
        FROM caf_evt, caf_commission
        WHERE status_evt = 0
        AND is_draft = 0
        AND tsp_evt IS NOT NULL
        AND commission_evt = id_commission '
        . 'ORDER BY tsp_crea_evt ASC ';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($evt = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    ++$nEltsToValidate;

    // on recupère les responsable de la commission liee...
    $req = " SELECT
            id_user, civ_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user
        FROM
            caf_user
            , caf_usertype
            , caf_user_attr
        WHERE code_usertype LIKE 'responsable-commission'
        AND usertype_user_attr = id_usertype
        AND user_user_attr = id_user
        AND params_user_attr LIKE 'commission:" . $evt['code_commission'] . "'
        ;
        ";

    // et on ajoute leur e-mail en cle au tableau, et l'evt a ce tableau
    $found = false;
    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($user = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
        $found = true;
        $datasTab[$user['email_user']][] = array_merge($evt, $user);
    }
    if (!$found) {
        error_log('<hr />>>>>>>>>>>>>ERREUR<<<<<<<<<<< il ne semble pas y avoir de responsable de commission ' . $evt['code_commission'] . '<hr />');
    }
}
error_log('' . $nEltsToValidate . ' evenements a valider, ' . count($datasTab) . ' responsables a contacter.');

// chaque var datas contient toutes les infos des sorties
foreach ($datasTab as $email => $evtdatas) {
    // echo '<pre>'; echo '********'.$email."\n"; print_r($datas); echo '</pre>';

    error_log('- Envoi necessaire : ' . $email);

    // liste des ID des evenements / pour generer un code d'email unqiue
    $ids_evt = '';
    foreach ($evtdatas as $tmp) {
        $ids_evt .= ($ids_evt ? '-' : '') . $tmp['id_evt'];
    }

    // liste des evts
    $evtList = [];
    foreach ($evtdatas as $tmp) {
        $evtList[] = [
            'name' => date('d/m/Y', $tmp['tsp_evt']) . ' - ' . $tmp['titre_evt'],
            'url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $tmp['code_evt'] . '-' . $tmp['id_evt'] . '.html?forceshow=true',
        ];
    }

    // vars d'envoi
    $datas = [];
    $datas['parent'] = $id_chron_launch;
    $datas['code'] = sha1('please_validate_evt;id_user=' . $evtdatas[0]['id_user'] . ';ids_evt=' . $ids_evt); // ce code, unique, evite de renvoyer ce mail a chaque appel du chron
    $datas['to'] = [$email => $evtdatas[0]['firstname_user'] . ' ' . $evtdatas[0]['lastname_user']];
    $datas['template'] = 'transactional/rappel-sortie-a-valider-resp-commission';
    $datas['context'] = [
        'sorties' => $evtList,
    ];
    cron_email($datas);
}

// *******************************
// CONTACT DES PRESIDENTS - VICE-PRES POUR VALIDER LEGALEMENT LES SORTIES EN ATTENTE
error_log('---------------------------- please_validate_legal_evt ----------------------------');
$nEltsToValidate = 0;
// c'est un tableau associatif qui va contenir en cle les emails des responsables, et en valeur la liste des sorties liees
// on peut alors creer un e-mail unqiue pour chaque user, avec la liste de ses sorties a valider
$datasTab = [];

// Pour chaque sortie non validee dans le timing demande, et publiee
$req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt
            , id_commission, code_commission
        FROM caf_evt, caf_commission
        WHERE status_legal_evt = 0
        AND commission_evt = id_commission
        AND tsp_evt > ' . time() . '
        AND tsp_evt < ' . $MAX_TIMESTAMP_FOR_LEGAL_VALIDATION . '
        AND status_evt = 1
        AND is_draft = 0
        ORDER BY tsp_evt ASC ';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
while ($evt = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    ++$nEltsToValidate;

    // on recupère les responsable : president ou vice pre
    $req = " SELECT
            id_user, civ_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user
        FROM
            caf_user
            , caf_usertype
            , caf_user_attr
        WHERE (
                code_usertype LIKE 'president' OR code_usertype LIKE 'vice-president'
            )
        AND usertype_user_attr = id_usertype
        AND user_user_attr = id_user
        ";

    // et on ajoute leur e-mail en cle au tableau, et l'evt a ce tableau
    $found = false;
    $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($user = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
        $found = true;
        $datasTab[$user['email_user']][] = array_merge($evt, $user);
    }
    if (!$found) {
        error_log('<hr />>>>>>>>>>>>>ERREUR<<<<<<<<<<< il ne semble pas y avoir de president ou vice president<hr />');
    }
}
error_log('' . $nEltsToValidate . ' evenements a valider, ' . count($datasTab) . ' responsables a contacter.');

// chaque var datas contient toutes les infos des sorties
foreach ($datasTab as $email => $evtdatas) {
    // echo '<pre>'; echo '********'.$email."\n"; print_r($datas); echo '</pre>';

    error_log('- Envoi necessaire : ' . $email);

    // liste des ID des evenements / pour generer un code d'email unqiue
    $ids_evt = '';
    foreach ($evtdatas as $tmp) {
        $ids_evt .= ($ids_evt ? '-' : '') . $tmp['id_evt'];
    }

    // liste des evts
    $evtList = [];
    foreach ($evtdatas as $tmp) {
        $evtList[] = [
            'name' => date('d/m/Y', $tmp['tsp_evt']) . ' - ' . $tmp['titre_evt'],
            'url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'sortie/' . $tmp['code_evt'] . '-' . $tmp['id_evt'] . '.html',
        ];
    }

    // vars d'envoi
    $datas = [];
    $datas['parent'] = $id_chron_launch;
    $datas['code'] = sha1('please_validate_evt;id_user=' . $evtdatas[0]['id_user'] . ';ids_evt=' . $ids_evt); // ce code, unique, evite de renvoyer ce mail a chaque appel du chron
    $datas['to'] = [$email => $evtdatas[0]['firstname_user'] . ' ' . $evtdatas[0]['lastname_user']];
    $datas['template'] = 'transactional/rappel-sortie-a-valider-president';
    $datas['context'] = [
        'sorties' => $evtList,
    ];
    cron_email($datas);
}
