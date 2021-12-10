<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

if ('0' == $destination['publie']) {
    //pas validee
    echo '<div class="alerte"><b>Note : Cette destination n\'est pas publiée sur le site</b>. Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page. Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br /></div>';
} elseif ('1' == $destination['annule']) {
    echo '<div class="erreur"><img src="'.LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL).'img/base/cross.png" alt="" title="" style="float:left; padding:2px 6px 0 0;" /> <b>Destination annulée</b><br class="clear"></div>';
}

presidence();

?><!doctype html>
<html lang="<?php echo $lang; ?>">
    <head>
        <meta charset="utf-8">
        <title>Feuille de destination - <?php echo html_utf8($destination['nom']); ?> - le <?php echo display_date($destination['date']); ?> à <?php echo display_time($destination['date']); ?></title>

        <link rel="stylesheet" href="/css/style1.css" type="text/css" />
        <link rel="stylesheet" href="/fonts/stylesheet.css" type="text/css" />
        <link rel="stylesheet" href="/css/base.css" type="text/css"  />

    </head>
    <body id="feuille-de-sortie" <!-- onload="window.print() -->">



    <table style="border:0; padding:0; margin:0;">
        <tbody>
        <tr>
            <td style="border:0">
                <img src="<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>img/logo.png" alt="" title="" style="float:left" /><br><br><br><br><br>
                <div style="padding-left:45px;">
                    <?php
                    inclure('adresse-fiche-sortie', '');
                    ?>
                </div>
            </td>
            <td style="border:0">
                <table style='width:560px'>
                    <thead>
                    <tr>
                        <th colspan="3" style="text-align:center; font-size:17px"><small>FEUILLE DE DESTINATION</small><br><?php echo strtoupper($destination['nom']); ?> : <?php echo $destination['lieu']['nom']; ?></th>
                    </tr>
                    </thead>
                    <tbody>
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

                    <?php if (1 == $evt['status_legal_evt']) { ?>
                    <?php } ?>

                    <tr>
                        <th colspan="3">ORGANISATION : </th>
                    </tr>
                        <?php if ($destination['responsable']) { ?>
                            <tr>
                                <td><?php echo html_utf8(strtoupper($destination['responsable']['lastname_user']).', '.ucfirst(strtolower($destination['responsable']['firstname_user']))); ?></td>
                                <th>TEL</th>
                                <td><?php if (!empty($destination['responsable']['tel_user'])) {
                                            echo html_utf8($destination['responsable']['tel_user']);
                                        } else {
                                            if (!empty($destination['responsable']['tel2_user'])) {
                                                echo html_utf8($destination['responsable']['tel2_user']);
                                            }
                                        } ?></td>
                            </tr>
                        <?php } ?>
                        <?php if ($destination['co-responsable']) { ?>
                            <tr>
                                <td><?php echo html_utf8(strtoupper($destination['co-responsable']['lastname_user']).', '.ucfirst(strtolower($destination['co-responsable']['firstname_user']))); ?></td>
                                <th>TEL</th>
                                <td><?php if (!empty($destination['co-responsable']['tel_user'])) {
                                            echo html_utf8($destination['co-responsable']['tel_user']);
                                        } else {
                                            if (!empty($destination['co-responsable']['tel2_user'])) {
                                                echo html_utf8($destination['co-responsable']['tel2_user']);
                                            }
                                        } ?></td>
                            </tr>
                        <?php } ?>
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
            <th width='30%'>DEPART :</th>
            <td width='30%'><?php echo display_date($destination['date']); ?>, à <?php echo display_time($destination['date']); ?></td>
            <th width='20%'>RETOUR : </th>
            <td width='20%'><?php echo display_date($destination['date_fin']); ?></td>
        </tr>
        </tbody>
    </table>

    <table>
        <tbody>
        <tr>
            <?php foreach ($destination['bus'] as $bus) { ?>
                <?php if ($p && 0 == $p % 2) {
                                            echo '</tr><tr>';
                                        } ?>
                <th <?php echo count($destination['bus']) > 1 ? " width='15%' " : ''; ?> ><?php echo $bus['intitule']; ?></th>
                <td <?php echo count($destination['bus']) > 1 ? " width='5%' " : ''; ?>  >[<?php echo $bus['places_max'] - $bus['places_disponibles']; ?>p.]</td>
                <td <?php echo count($destination['bus']) > 1 ? " width='30%' " : ''; ?> >
                    <ul>
                        <?php foreach ($bus['ramassage'] as $point) { ?>
                            <?php $cpuv = count($point['utilisateurs']['valide']); ?>
                            <li>
                                <?php echo $point['nom']; ?> à <?php echo display_time($point['date']); ?>
                                <?php if ($cpuv > 0) { ?>&nbsp;&nbsp;[<?php echo $cpuv; ?> p.]<?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                </td>
            <?php ++$p; } ?>
        </tr>
        </tbody>
    </table>
    <small>Coût du transport, par personne : <b><?php echo $destination['cout_transport']; ?> €</b></small>

    <?php $depose = $reprise = []; ?>
    <table>
        <thead>
            <tr>
                <th width="10%">Groupe</th>
                <th width="30%">Encadrants</th>
                <th width="60%">Sortie</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($destination['sorties'] as $e => $evt) { ?>
            <?php if (1 == $evt['status_evt']) { ?>
            <?php
                $depose[$evt['destination']['id_lieu_depose']][$evt['destination']['date_depose']][] = 'Gp'.($e + 1);
                $reprise[$evt['destination']['id_lieu_reprise']][$evt['destination']['date_reprise']][] = 'Gp'.($e + 1);
                $groupes[$evt['id_evt']] = 'Gp'.($e + 1);
             ?>
            <tr <?php if (0 == $evt['status_legal_evt']) {
                 echo ' class="vis-off-light" ';
             }  ?> >
                <td><b><?php echo 'Gp'.($e + 1); ?></b> <?php if (count($evt['groupe']) > 0) {
                 echo ' : '.html_utf8($evt['groupe']['nom']);
             } ?></td>
                <td>
                    <?php $users = get_encadrants($evt['id_evt']); ?>
                    <ul>
                    <?php foreach ($users as $tmp) {  ?>
                        <li style="border-bottom:1px dotted #ccc;overflow:hidden;margin-bottom:5px;">
                            <?php if ('encadrant' == $tmp['role_evt_join']) {
                 echo '<b>';
             } ?>
                            <?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?>
                            <?php if ('encadrant' == $tmp['role_evt_join']) {
                 echo '</b>';
             } ?>
                            <span class="rght"><?php if (!empty($tmp['tel_user'])) {
                 echo html_utf8($tmp['tel_user']);
             } else {
                 if (!empty($tmp['tel2_user'])) {
                     echo html_utf8($tmp['tel2_user']);
                 }
             } ?></span>
                        </li>
                    <?php } ?>
                    </ul>
                </td>
                <td>
                    <?php if (0 == $evt['status_legal_evt']) { ?>
                    <b class="rght">ATTENTION : Sortie non validée par le <?php echo $p_sitename; ?></b><br>
                    <?php } ?>
                    <b><?php echo html_utf8($evt['titre_evt']); ?></b> : <?php echo html_utf8($evt['itineraire']); ?><br>
                    <?php if ($evt['distance_evt']) { ?> - Dist. : <?php echo html_utf8($evt['distance_evt']); ?> km<?php } ?>
                    <?php if ($evt['denivele_evt']) { ?> - Déniv. : <?php echo html_utf8($evt['denivele_evt']); ?> m<?php } ?>
                </td>
            </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>

    <?php $lieux = []; ?>
    <table>
        <tbody >
            <tr style="vertical-align:top;">
                <td style="padding:0;border:none;">
                    <table style="width:100%;margin:0;background-color:#fff;">
                        <thead>
                        <tr>
                            <th colspan="3">Depose</th>
                        </tr>
                        </thead>
                    <?php foreach ($depose as $id_d => $hours) { ?>
                        <?php foreach ($hours as $hour => $sorties) { ?>
                        <tr>
                            <?php if (!isset($lieux[$id_d])) {
                 $lieu = get_lieu($id_d);
                 $lieux[$id_d] = $lieu;
             } else {
                 $lieu = $lieux[$id_d];
             } ?>
                            <td width="25%"><?php echo $lieu['nom']; ?></td>
                            <td width="10%">
                                <?php echo display_time($hour); ?>
                            </td>
                            <td width="65%"><?php foreach ($sorties as $sortie) {
                 echo $sortie.', ';
             } ?></td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                    </table>
                </td>
                <td style="padding:0;border:none;">
                    <table style="width:100%;margin:0;background-color:#fff;">
                        <thead>
                        <tr>
                            <th colspan="3">Reprise</th>
                        </tr>
                        </thead>
                        <?php foreach ($reprise as $id_d => $hours) { ?>
                            <?php foreach ($hours as $hour => $sorties) { ?>
                                <tr>
                                    <?php if (!isset($lieux[$id_d])) {
                 $lieu = get_lieu($id_d);
                 $lieux[$id_d] = $lieu;
             } else {
                 $lieu = $lieux[$id_d];
             } ?>
                                    <td width="25%"><?php echo $lieu['nom']; ?></td>
                                    <td width="10%">
                                        <?php echo display_time($hour); ?>
                                    </td>
                                    <td width="65%"><?php foreach ($sorties as $sortie) {
                 echo $sortie.', ';
             } ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>
    <hr class="clear">


    <div id="organisation_covoiturage">

        <?php $nInscritDestination = 0; ?>
        <?php foreach ($destination['bus'] as $id_bus => $bus) { ?>
            <?php foreach ($bus['ramassage'] as $id_point => $point) {  ?>
                <?php if ($point['utilisateurs']['valide']) {
                 $nInscritDestination += count($point['utilisateurs']['valide']);
                 $destination['bus'][$id_bus]['countUtilisateurs'] += count($point['utilisateurs']['valide']);
             } ?>
            <?php } ?>
        <?php } ?>

        <ul class="nice-list">
            <li class="wide"><b>NOMBRE DE PERSONNES TRANPORTEES EN BUS</b> : <?php echo $nInscritDestination; ?></li>
        </ul>

        <?php
            $b = 1;
            $chain = null;
            $d_chain = '<ul><img src="/img/bus.png" title="bus" class="lft mr10" />';
            foreach ($destination['bus'] as $id_bus => $bus) {
                $displayBus = false;

                $arrets = null;
                foreach ($bus['ramassage'] as $id_ramassage => $point) {
                    if (count($point['utilisateurs']) > 0) {
                        $busses[$id_ramassage] = ['short' => '['.$b++.']', 'long' => $point['nom'].' à '.display_time($point['date'])];
                        $arrets .= '<li><b>'.$busses[$id_ramassage]['short'].'</b> '.$busses[$id_ramassage]['long'].'</li>';
                        $displayBus = true;
                    }
                }
                if ($displayBus) {
                    $chain .= '<li class="lft mr20">'.$bus['intitule'].'<ul>'.$arrets.'</ul></li>';
                }
            }
            $e_chain = '</ul><br class="clear"><br class="clear"> ';
        if (null !== $chain) {
            echo $d_chain.$chain.$e_chain;
        }
        ?>

        <?php $b = 1; foreach ($destination['bus'] as $bus) { ?>
            <?php if ($bus['ramassage']) { ?>
                        <?php foreach ($bus['ramassage'] as $point) { ?>
                            <?php $tmpUsers = []; ?>
                            <?php if ($point['utilisateurs']['valide']) { ?>
                                <?php $cpuv = count($point['utilisateurs']['valide']); ?>

                                    <div class="presentation">
                                        <b>[<?php echo $b; ?>] <?php echo $bus['intitule']; ?></b> : <?php echo $point['nom']; ?>, à <?php echo display_time($point['date']); ?> :
                                        <b><span  class="bleucaf"><?php echo $cpuv; ?></span> personne(s)</b>
                                        <br>
                                    </div>
                                    <div class="utilisateurs">


                                        <table>
                                            <thead>
                                            <tr>
                                                <th colspan="4"></th>
                                                <th>PARTICIPANTS (NOM, PRÉNOM)</th>
                                                <th>N°ADHERENT</th>
                                                <th>AGE</th>
                                                <th>TÉL. PERSONNEL</th>
                                                <th>TÉL. <abbr title="En cas d'urgence">I.C.E</abbr></th>
                                                <th><abbr title="Restaurant"><img src="/img/base/resto-oui.png"/></abbr></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $number = 1;
                                            foreach ($point['utilisateurs']['valide'] as $id_user) {
                                                $tmpUser = get_user($id_user, false);
                                                $tmpUser['is_cb'] = user_in_cb($id_user);
                                                $tmpUser['is_restaurant'] = user_in_destination_repas($id_user, $destination['id']);
                                                $tmpUser['sortie'] = user_sortie_in_dest($id_user, $destination['id']);
                                                $tmpUsers[$tmpUser['lastname_user'].$tmpUser['firstname_user'].$tmpUser['id_user']] = $tmpUser;
                                            }
                                            ksort($tmpUsers);
                                            /* for($i = 0; $i <= rand(10, 40); $i++) {
                                                $tmpUsers[] = array();
                                            } */
                                            foreach ($tmpUsers as $tmp) { ?>
                                                <tr>
                                                    <td><?php echo $number++; ?></td>
                                                    <td>&nbsp;<img src="/img/bus.png" width="10" /></td>
                                                    <td>&nbsp;€&nbsp;</td>
                                                    <td><b><?php echo $groupes[$tmp['sortie']['id_evt']]; ?></b></td>
                                                    <td><?php echo html_utf8($tmp['civ_user'].' '.strtoupper($tmp['lastname_user']).', '.ucfirst(mb_strtolower($tmp['firstname_user'], 'UTF-8'))); ?></td>
                                                    <td><?php echo html_utf8($tmp['cafnum_user']); ?></td>
                                                    <td><?php echo getYearsSinceDate($tmp['birthday_user']); ?></td>
                                                    <td><?php echo html_utf8($tmp['tel_user']); ?></td>
                                                    <td><?php echo html_utf8($tmp['tel2_user']); ?></td>
                                                    <td><?php if ('1' == $evt['cb_evt']) { ?><?php if ('1' == $tmp['is_cb']) {
                                                echo 'OUI';
                                            } elseif ('0' == $tmp['is_cb']) {
                                                echo '-';
                                            } else {
                                                echo '<small>NSP</small>';
                                            } ?><?php } else {
                                                echo '-';
                                            } ?></td>
                                                    <td><?php if ('1' == $evt['repas_restaurant']) { ?><?php if ('1' == $tmp['is_restaurant']) {
                                                echo 'OUI';
                                            } elseif ('0' == $tmp['is_restaurant']) {
                                                echo '-';
                                            } else {
                                                echo '<small>NSP</small>';
                                            } ?><?php } else {
                                                echo '-';
                                            } ?></td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>

                                    </div>

                            <?php } ?>
                        <?php ++$b; }  ?>

            <?php }  ?>
        <?php } ?>


        <?php $covoiturage = covoiturage_sorties_destination($destination['id']); ?>

        <?php if ($covoiturage['total']) { ?>

            <hr class="clear">
            <ul class="nice-list">
                <li class="wide"><b>SE RENDENT AUX SORTIES PAR LEURS PROPRES MOYENS</b> : <?php echo $covoiturage['total']; ?></li>
            </ul>

                <ul class="nice-list">
                    <table>
                        <thead>
                        <tr>
                            <th colspan="3"></th>
                            <th>PARTICIPANTS (NOM, PRÉNOM)</th>
                            <th>N°ADHERENT</th>
                            <th>AGE</th>
                            <th>TÉL. PERSONNEL</th>
                            <th>TÉL. <abbr title="En cas d'urgence">I.C.E</abbr></th>
                            <?php if ('1' == $evt['repas_restaurant']) { ?><th><abbr title="Restaurant"><img src="/img/base/resto-oui.png"/></abbr></th><?php } ?>
                        </tr>
                        </thead>
                        <tbody><?php
                        $number = 1; ?>
                    <?php foreach ($covoiturage['covoiturage']['sortie'] as $id_sortie => $personnes) {
                            $current = false; ?>
                        <?php foreach ($destination['sorties'] as $sortie) { ?>
                            <?php if ($sortie['id_evt'] == $id_sortie) {
                                $current = $sortie;
                            } ?>
                        <?php } ?>
                        <?php $tmpUsers = []; ?>
                            <?php
                            foreach ($personnes as $id_user) {
                                $tmpUser = get_user($id_user, false);
                                $tmpUser['sortie'] = user_sortie_in_dest($id_user, $destination['id']);
                                $tmpUsers[$tmpUser['lastname_user'].$tmpUser['firstname_user'].$tmpUser['id_user']] = $tmpUser;
                            }
                            ksort($tmpUsers);
                            foreach ($tmpUsers as $tmp) { ?>
                                <tr>
                                    <td><?php echo $number++; ?></td>
                                    <td>&nbsp;</td>
                                    <td><b><?php echo $groupes[$tmp['sortie']['id_evt']]; ?></b></td>
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
                                </tr>
                            <?php } ?>
                    <?php
                        } ?>
                        </tbody>
                    </table>
                </ul>

        <?php } ?>
    </div>



    <pre><?php // print_r($destination);?></pre>

    Imprimé le <?php echo html_utf8(date('d.m.Y à H:i')); ?>
    </body>
</html>
