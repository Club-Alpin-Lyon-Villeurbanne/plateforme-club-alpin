<?php

use App\Entity\UserAttr;

// GESTION DES DROITS D'AFFICHAGE
$display = false;
if (admin() ||
    (
        user() &&
        (
            (user() && $evt['user_evt'] == (string) getUser()->getId()) ||
            allowed('evt_validate_all') ||
            allowed('evt_join_doall') ||
            'encadrant' == $monStatut ||
            'stagiaire' == $monStatut || 'coencadrant' == $monStatut ||
            allowed('evt_validate', 'commission:'.$evt['code_commission'])
        )
        || (user() && getUser()->hasAttribute(UserAttr::SALARIE)) ||
        (
            (
                allowed('evt_join_notme') || allowed('evt_unjoin_notme') ||
                allowed('evt_joining_accept') || allowed('evt_joining_refuse')
            ) && (
                user() && getUser()->hasAttribute(UserAttr::RESPONSABLE_COMMISSION, $evt['code_commission'])
            )
        )
    )
) {
    $display = true;
}

if (!$display) {
    require __DIR__.'/../../pages/404.php';
    exit;
}

if ('0' == $evt['status_evt']) {
    // pas validee
    echo '<div class="alerte"><b>Note : Cette sortie n\'est pas publiée sur le site</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br /></div>';
} elseif ('2' == $evt['status_evt']) {
    // refuse
    $messageDiv = true;
    echo '<div class="alerte"><b>Note : Cette sortie a été refusée</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br /><br /></div>';
} elseif ('1' == $evt['cancelled_evt']) {
    echo '<div class="erreur"><img src="/img/base/cross.png" alt="" title="" style="float:left; padding:2px 6px 0 0;" /> <b>Sortie annulée :</b><br /> Cette sortie a été annulée le '.date('d/m/Y à H:i').', par '.userlink($evt['cancelled_who_evt']['id_user'], $evt['cancelled_who_evt']['nickname_user']).'.<br /></div>';
}

$nAccepteesCalc = count($evt['joins']['encadrant']) + count($evt['joins']['stagiaire']) + count($evt['joins']['coencadrant']) + count($evt['joins']['benevole']) + count($evt['joins']['inscrit']) + count($evt['joins']['manuel']);

presidence();
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Feuille de sortie-<?php echo html_utf8($evt['titre_evt']); ?> - <?php echo date('d.m.Y', $evt['tsp_evt']); ?></title>

    <link rel="stylesheet" href="/css/style1.css" type="text/css" />
    <link rel="stylesheet" href="/fonts/stylesheet.css" type="text/css" />
    <link rel="stylesheet" href="/css/base.css" type="text/css"  />

</head>
<body id="feuille-de-sortie" <!-- onload="window.print() -->">

<table style="border:0; padding:0; margin:0;">
    <tbody>
    <tr>
        <td style="border:0">
            <?php if (1 == $evt['status_legal_evt']) { ?>
                <img src="/img/logo.png" alt="" title="" style="float:left" /><br><br><br><br><br>
                <div style="padding-left:45px;">
                    <?php
                    inclure('adresse-fiche-sortie', '');
                ?>
                </div>
            <?php } else { ?>
                <p class="alerte">Cette sortie n'a pas été validée légalement par les dirigeants de <?php echo $p_sitename; ?>.<br>La sortie se fait sous la responsabilité des organisateurs et des participants.</p>
            <?php } ?>
        </td>
        <td style="border:0">
            <table style='width:560px'>
                <thead>
                <tr>
                    <th colspan="3" style="text-align:center; font-size:17px"><small>FEUILLE DE SORTIE</small><br><?php echo html_utf8($evt['titre_evt']); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (1 == $evt['status_legal_evt']) { ?>
                    <?php if (isset($president) && !empty($president)) { ?>
                        <tr>
                            <th colspan="3">PRESIDENT : </th>
                        </tr>
                        <?php foreach ($president as $p) { ?>
                            <tr>
                                <td><?php echo html_utf8(strtoupper($p['lastname_user']).', '.ucfirst(strtolower($p['firstname_user']))); ?></td>
                                <th>TEL</th>
                                <td><?php
                                if (!empty($p['tel_user'])) {
                                    echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $p['tel_user'])).'<br>';
                                } else {
                                    if (!empty($p['tel2_user'])) {
                                        echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $p['tel2_user']));
                                    }
                                }
                            ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    <?php if (isset($vicepresident) && !empty($vicepresident)) { ?>
                        <tr>
                            <th colspan="3">VICE PRESIDENT : </th>
                        </tr>
                        <?php foreach ($vicepresident as $vp) { ?>
                            <tr>
                                <td><?php echo html_utf8(strtoupper($vp['lastname_user']).', '.ucfirst(strtolower($vp['firstname_user']))); ?></td>
                                <th>TEL</th>
                                <td><?php
                            if (!empty($vp['tel_user'])) {
                                echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $vp['tel_user'])).'<br>';
                            } else {
                                if (!empty($vp['tel2_user'])) {
                                    echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $vp['tel2_user']));
                                }
                            }
                            ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    <tr>
                        <th>COMMISSION : </th>
                        <td colspan="2"><?php echo html_utf8($evt['title_commission']); ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <th colspan="3">ORGANISATEUR : </th>
                </tr>
                <tr>
                    <td><?php echo html_utf8(strtoupper($evt['lastname_user']).', '.ucfirst(strtolower($evt['firstname_user']))); ?></td>
                    <th>TEL</th>
                    <td><?php echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $evt['tel_user'])); ?></td>
                </tr>
                <tr>
                    <th colspan="3">ENCADRANT(S) : </th>
                </tr>
                <?php
                foreach ($evt['joins']['encadrant'] as $tmp) {
                    ?>
                    <tr>
                        <td><b><?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?></b></td>
                        <th>TEL</th>
                        <td><?php echo preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $tmp['tel_user']); ?></td>
                    </tr>
                <?php
                }
                foreach ($evt['joins']['stagiaire'] as $tmp) {
                    ?>
                    <tr>
                        <td><b><?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?></b></td>
                        <th>TEL</th>
                        <td><?php echo preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $tmp['tel_user']); ?></td>
                    </tr>
                <?php
                }
                foreach ($evt['joins']['coencadrant'] as $tmp) {
                    ?>
                    <tr>
                        <td><?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?></td>
                        <th>TEL</th>
                        <td><?php echo preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $tmp['tel_user']); ?></td>
                    </tr>
                <?php
                }
?>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>

    <div style="width:900px;">
    <?php inclure('complement-contacts-specifiques-fiche', ''); ?>
    </div>

<table>
    <tbody>
    <tr>
        <th width='15%' style="width: fit-content">DATE :</th>
        <td width='25%'><?php echo date('d.m.Y', $evt['tsp_evt']); ?></td>
        <th width='20%'>COURSE, LIEU : </th>
        <td width='30%'><?php echo html_utf8($evt['titre_evt']); ?><?php if (count($evt['groupe']) > 0) {
                echo ' - '.$evt['groupe']['nom'];
            } ?></td>
    </tr>
    <tr>
        <th style="width: fit-content">DISTANCE : </th>
        <td><?php echo $evt['distance_evt'] ? html_utf8($evt['distance_evt']) : '...'; ?> km</td>
        <th>DENIVELE POSITIF :</th>
        <td><?php echo $evt['denivele_evt'] ? html_utf8($evt['denivele_evt']) : '...'; ?> m</td>
    </tr>
    <tr>
        <th style="width: fit-content">NIVEAU :</th>
        <td><?php echo $evt['difficulte_evt'] ? html_utf8($evt['difficulte_evt']) : '...'; ?></td>
        <th>NB DE PARTICIPANTS :</th>
        <td><?php echo html_utf8($nAccepteesCalc); ?></td>
    </tr>
    <?php if ($evt['matos_evt']) { ?>
        <tr>
            <th style="width: fit-content">MATERIEL : </th>
            <td colspan="3"><?php echo html_utf8($evt['matos_evt']); ?></td>
        </tr>
    <?php } ?>
    <?php if ($evt['itineraire']) { ?>
        <tr>
            <th style="width: fit-content">ITINERAIRE PREVU : </th>
            <td colspan="3"><?php echo html_utf8($evt['itineraire']); ?></td>
        </tr>
        <tr>
            <th style="width: fit-content">EN CAS D'ACCIDENT : </th>
            <td colspan="3">Contactez notre assurance Gras-Savoye au 09 72 72 22 43. <br> Contactez le président ou vice-président (numéro ci-dessus). </td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th></th>
        <th>PARTICIPANTS (NOM, PRÉNOM)</th>
        <th>N°ADHERENT</th>
        <th>AGE</th>
        <th>TÉL. PERSONNEL</th>
        <th>TÉL. <abbr title="En cas d'urgence">I.C.E</abbr></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $number = 1;
// constitution de la liste complete des participants
$joinsParticipants = array_merge(
    $evt['joins']['encadrant'],
    $evt['joins']['stagiaire'],
    $evt['joins']['coencadrant'],
    $evt['joins']['benevole'],
    $evt['joins']['inscrit'],
    $evt['joins']['manuel']);

if (is_array($joinsParticipants)) {
    foreach ($joinsParticipants as $it => $tmpUser) {
        $joinsParticipants[$tmpUser['lastname_user'].$tmpUser['firstname_user'].$tmpUser['id_user']] = $tmpUser;
        unset($joinsParticipants[$it]);
    }
    ksort($joinsParticipants);
    foreach ($joinsParticipants as $tmp) {
        ?>
            <tr>
                <td><?php echo $number++; ?></td>
                <td><?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?></td>
                <td><?php echo html_utf8($tmp['cafnum_user']); ?></td>
                <td><?php echo getYearsSinceDate($tmp['birthday_user']); ?></td>
                <td><?php echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $tmp['tel_user'])); ?></td>
                <td><?php echo html_utf8(preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $tmp['tel2_user'])); ?></td>
            </tr>
        <?php
    }
}
// lignes vides
if (!isset($_GET['hide_blank']) || 'y' != $_GET['hide_blank']) {
    for ($i = $number; $i <= (max($evt['ngens_max_evt'], $nAccepteesCalc) * 1); ++$i) {
        ?>
            <tr>
                <td><?php echo $number++; ?></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        <?php
    }
}
?>
    </tbody>
</table>
Imprimé le <?php echo html_utf8(date('d.m.Y à H:i')); ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small>[<a href="<?php echo isset($_GET['hide_blank']) && $_GET['hide_blank']  ? $versCettePage : $versCettePage.'?hide_blank=y'; ?>"><?php echo isset($_GET['hide_blank'] ) && $_GET['hide_blank'] ? 'Afficher' : 'Masquer'; ?> les lignes vides</a>]</small>
</body>
</html>
