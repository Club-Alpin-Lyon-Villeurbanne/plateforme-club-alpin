<?php
// GESTION DES DROITS D'AFFICHAGE
$display = false;
if (admin() ||
    (
        user() &&
        (
            $evt['user_evt'] == $_SESSION['user']['id_user'] ||
            allowed('evt_validate_all') ||
            allowed('evt_join_doall') ||
            'encadrant' == $monStatut || 'coencadrant' == $monStatut ||
            allowed('evt_validate', 'commission:'.$evt['code_commission'])
        ) ||
        (
            $_SESSION['user']['status'] &&
            in_array('Salarié', $_SESSION['user']['status'], true)
        ) ||
        (
            (
                allowed('evt_join_notme') || allowed('evt_unjoin_notme') ||
                allowed('evt_joining_accept') || allowed('evt_joining_refuse')
            ) && (
                $_SESSION['user']['status'] &&
                in_array('Resp. de commission, '.$evt['code_commission'], $_SESSION['user']['status'], true)
            )
        )
    )
) {
    $display = true;
}

if (!$display) {
    include __DIR__.'/404.php';
    exit;
}

if ('0' == $evt['status_evt']) {
    //pas validee
    echo '<div class="alerte"><b>Note : Cette sortie n\'est pas publiée sur le site</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br /></div>';
} elseif ('2' == $evt['status_evt']) {
    //refuse
    $messageDiv = true;
    echo '<div class="alerte"><b>Note : Cette sortie a été refusée</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br /><br /></div>';
} elseif ('1' == $evt['cancelled_evt']) {
    echo '<div class="erreur"><img src="img/base/cross.png" alt="" title="" style="float:left; padding:2px 6px 0 0;" /> <b>Sortie annulée :</b><br /> Cette sortie a été annulée le '.date('d/m/Y à H:i').', par '.userlink($evt['cancelled_who_evt']['id_user'], $evt['cancelled_who_evt']['nickname_user']).'.<br /></div>';
}

$nAccepteesCalc = count($evt['joins']['encadrant']) + count($evt['joins']['coencadrant']) + count($evt['joins']['benevole']) + count($evt['joins']['inscrit']) + count($evt['joins']['manuel']);

presidence();
?>

<!doctype html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="utf-8">
    <title>Feuille de sortie-<?php echo html_utf8($evt['titre_evt']); ?>-<?php echo date('d.m.Y', $evt['tsp_evt']); ?></title>

    <link rel="stylesheet" href="css/style1.css" type="text/css" />
    <link rel="stylesheet" href="fonts/stylesheet.css" type="text/css" />
    <link rel="stylesheet" href="css/base.css" type="text/css"  />

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
                    <th colspan="3" style="text-align:center; font-size:17px"><small>FEUILLE DE SORTIE</small><br><?php echo html_utf8($evt['titre_evt']); ?><?php if ($destination) {
                        echo '<br><small>Destination : ['.$destination['nom'].']</small>';
                    } ?></th>
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
                                        echo html_utf8($p['tel_user']).'<br>';
                                    } else {
                                        if (!empty($p['tel2_user'])) {
                                            echo html_utf8($p['tel2_user']);
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
                                        echo html_utf8($vp['tel_user']).'<br>';
                                    } else {
                                        if (!empty($vp['tel2_user'])) {
                                            echo html_utf8($vp['tel2_user']);
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
                    <td><?php echo html_utf8($evt['tel_user']); ?></td>
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
                        <td><?php echo $tmp['tel_user']; ?></td>
                    </tr>
                <?php
                }
                foreach ($evt['joins']['coencadrant'] as $tmp) {
                    ?>
                    <tr>
                        <td><?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?></td>
                        <th>TEL</th>
                        <td><?php echo $tmp['tel_user']; ?></td>
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
        <th width='15%'>DATE :</th>
        <td width='25%'><?php echo date('d.m.Y', $evt['tsp_evt']); ?></td>
        <th width='20%'>COURSE, LIEU : </th>
        <td width='30%'><?php echo html_utf8($evt['titre_evt']); ?><?php if (count($evt['groupe']) > 0) {
                    echo ' - '.$evt['groupe']['nom'];
                } ?></td>
    </tr>
    <tr>
        <th>DISTANCE : </th>
        <td><?php echo $evt['distance_evt'] ? html_utf8($evt['distance_evt']) : '...'; ?> km</td>
        <th>DENIVELE POSITIF :</th>
        <td><?php echo $evt['denivele_evt'] ? html_utf8($evt['denivele_evt']) : '...'; ?> m</td>
    </tr>
    <tr>
        <th>NIVEAU :</th>
        <td><?php echo $evt['difficulte_evt'] ? html_utf8($evt['difficulte_evt']) : '...'; ?></td>
        <th>NB DE PARTICIPANTS :</th>
        <td><?php echo html_utf8($nAccepteesCalc); ?></td>
    </tr>
    <?php if ($evt['matos_evt']) { ?>
        <tr>
            <th>MATERIEL : </th>
            <td colspan="3"><?php echo html_utf8($evt['matos_evt']); ?></td>
        </tr>
    <?php } ?>
    <?php if ($evt['itineraire']) { ?>
        <tr>
            <th>ITINERAIRE PREVU : </th>
            <td colspan="3"><?php echo html_utf8($evt['itineraire']); ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<?php if ($destination) {
                    $b = 1;
                    echo '<ul><img src="img/bus.png" title="bus" class="lft mr10" />';
                    foreach ($destination['bus'] as $id_bus => $bus) {
                        echo '<li class="lft mr20">'.$bus['intitule'].'<ul>';
                        foreach ($bus['ramassage'] as $id_ramassage => $point) {
                            if (count($point['utilisateurs']) > 0) {
                                $busses[$id_ramassage] = ['short' => '['.$b++.']', 'long' => $point['nom'].' à '.display_time($point['date'])];
                                echo '<li><b>'.$busses[$id_ramassage]['short'].'</b> '.$busses[$id_ramassage]['long'].'</li>';
                            }
                        }
                        echo '</ul></li>';
                    }
                    echo '</ul><br class="clear"> ';
                } ?>

<table>
    <thead>
    <tr>
        <th></th>
        <th>PARTICIPANTS (NOM, PRÉNOM)</th>
        <th>N°ADHERENT</th>
        <th>AGE</th>
        <th>TÉL. PERSONNEL</th>
        <th>TÉL. <abbr title="En cas d'urgence">I.C.E</abbr></th>
        <?php if ('1' == $evt['cb_evt']) { ?><th><abbr title="Paiement en ligne"><img src="img/base/cb-oui.png"/></abbr></th><?php } ?>
        <?php if ('1' == $evt['repas_restaurant']) { ?><th><abbr title="Restaurant"><img src="img/base/resto-oui.png"/></abbr></th><?php } ?>
        <?php if ($destination) { ?><th><abbr title="Transport"><img src="img/bus.png" title="bus" width="14px"  />&nbsp;<img src="img/voiture.png" title="covoiturage" width="14px" /></abbr></th><?php } ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $number = 1;
    // constitution de la liste complete des participants
    $joinsParticipants = array_merge(
        $evt['joins']['encadrant'],
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
                <td><?php echo html_utf8($tmp['tel_user']); ?></td>
                <td><?php echo html_utf8($tmp['tel2_user']); ?></td>
                <?php if ('1' == $evt['cb_evt']) { ?><td><?php if ('1' == $tmp['is_cb']) {
                echo 'OUI';
            } elseif ('0' == $tmp['is_cb']) {
                echo '-';
            } else {
                echo '<small>NSP</small>';
            } ?></td><?php } ?>
                <?php if ('1' == $evt['repas_restaurant']) { ?><td><?php if ('1' == $tmp['is_restaurant']) {
                echo 'OUI';
            } elseif ('0' == $tmp['is_restaurant']) {
                echo '-';
            } else {
                echo '<small>NSP</small>';
            } ?></td><?php } ?>
                <?php if ($destination) { ?><td><?php if (null == $tmp['is_covoiturage']) {
                echo '<img src="img/base/error.png" title="Non renseigné !" width="12px" />';
            } elseif ('1' == $tmp['is_covoiturage']) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="img/voiture.png" title="covoiturage" width="12px" />';
            } elseif ($tmp['id_bus_lieu_destination'] > 0) {
                echo '<img src="img/bus.png" title="bus" width="12px" /> '.$busses[$tmp['id_bus_lieu_destination']]['short'];
            } else {
                echo '<small>NSP</small>';
            } ?></td><?php } ?>
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
                <?php if ('1' == $evt['cb_evt']) { ?><td></td><?php } ?>
                <?php if ('1' == $evt['repas_restaurant']) { ?><td></td><?php } ?>
            </tr>
        <?php
        }
    }
    ?>
    </tbody>
</table>
Imprimé le <?php echo html_utf8(date('d.m.Y à H:i')); ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<small>[<a href="<?php echo $_GET['hide_blank'] ? $versCettePage : $versCettePage.'?hide_blank=y'; ?>"><?php echo $_GET['hide_blank'] ? 'Afficher' : 'Masquer'; ?> les lignes vides</a>]</small>
</body>
</html>
