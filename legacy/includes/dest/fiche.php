<div id="fiche-sortie">

    <?php
    // affichage complete d'une destination.
    // var necessaire : $destination

    if (isset($errTab) && count($errTab) > 0 && (in_array($_POST['operation'], ['dest_validate', 'dest_lock', 'dest_annuler', 'dest_mailer'], true))) {
        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
    }

    if (!$destination || empty($destination)) {
        echo '<p class="erreur">Erreur : destination non définie</p>';
    } else { ?>

        <?php $nInscritDestination = 0; ?>
        <?php if (!isset($destination['bus'])) {
        echo '<p class="erreur">pas de bus définit</p>';
    } else {
        foreach ($destination['bus'] as $id_bus => $bus) { ?>
            <?php if (!isset($bus['ramassage'])) {
            echo '<p class="erreur">pas de point de rammassage pour le bus</p>';
        } else {
            foreach ($bus['ramassage'] as $id_point => $point) {  ?>
                <?php if ($point['utilisateurs']['valide']) {
                $nInscritDestination += count($point['utilisateurs']['valide']);
                $destination['bus'][$id_bus]['countUtilisateurs'] += count($point['utilisateurs']['valide']);
            } ?>
            <?php }
        } ?>
        <?php }
    } ?>
        <?php if (!isset($destination['sorties'])) {
        echo '<p class="erreur">pas de sortie associée à la destination</p>';
    } else {
        foreach ($destination['sorties'] as $e => $evt) { ?>
        <?php if (1 == $evt['status_evt']) { ?>
            <?php
            $depose[$evt['destination']['id_lieu_depose']][$evt['destination']['date_depose']][] = 'Gp'.$e;
            $reprise[$evt['destination']['id_lieu_reprise']][$evt['destination']['date_reprise']][] = 'Gp'.$e;
            ?>
            <?php } ?>
        <?php }
    } ?>

        <?php include __DIR__.'/../../includes/dest/admin_status.php'; ?>

        <?php /* envoyer les emails de cloture */
        if (allowed('destination_mailer')
            || $destination['id_user_who_create'] == $_SESSION['user']['id_user']
            || $destination['id_user_responsable'] == $_SESSION['user']['id_user']
            || $destination['id_user_adjoint'] == $_SESSION['user']['id_user'] // je suis l'un des co/encadrant de l'une des sorties
        ) {
            ?>

            <?php if (0 == $destination['mail']) { ?>
                <?php if (date('Y-m-d H:i:s') > $destination['inscription_fin']) { ?>
                <div class="note mr10">
                    <form action="<?php echo $versCettePage; ?>" method="post">
                        <input type="hidden" name="operation" value="dest_mailer"/>
                        <input type="hidden" name="id_destination" value="<?php echo $destination['id']; ?>"/>

                        <?php if ($p_transporteurs && count($p_transporteurs)) { ?>
                        <label for="transporteur"><b>Tranporteur :</b></label><br>
                        <select name="transporteur" class="type1" style="width:97%">
                            <option value="">- Sélectionner un transporteur</option>
                        <?php foreach ($p_transporteurs as $code => $transporteur) { ?>
                            <option value="<?php echo $code; ?>"><?php echo $transporteur['nom']; ?> - <?php echo $transporteur['email']; ?></option>
                        <?php } ?>
                            <option value="-1">- Je gère moi même la réservation d'un transport, je ne sélectionne pas de transporteur.</option>
                        </select><br><br>
                        <?php } ?>

                        <label for="content_mail"><b>Contenu du mail :</b></label><br>
                        <textarea class="type1" name="content_mail" style="width:95%;height:400px;" ><?php
                            echo "Bonjour,\n\n";
                            echo "Le $p_sitename organise des sorties le ".display_date($destination['date'])." et vous sollicite pour le transport de ses adhérents.\n";
                            echo "\n# ".$destination['nom']."\nSecteur : ".$destination['lieu']['nom'].' [GPS] ('.substr($destination['lieu']['lat'], 0, 8).', '.substr($destination['lieu']['lng'], 0, 8).')'."\n";
                            echo "\n# Nombre total de participants transportés : $nInscritDestination\n";
                            echo "\n# Lieux et horaires de ramasse : \n";
                        ?><?php
                            $b = 1;
                            foreach ($destination['bus'] as $bus) {
                                if ($bus['ramassage']) {
                                    if ($bus['countUtilisateurs']) {
                                        echo "\n".$bus['intitule'].' - '.$bus['countUtilisateurs'].' personne(s)'."\n";
                                    }
                                    foreach ($bus['ramassage'] as $point) {
                                        $cpuv = count($point['utilisateurs']['valide']);
                                        $tmpUsers = [];
                                        if ($point['utilisateurs']['valide']) { ?>[<?php echo $b++; ?>]  : <?php echo $point['nom']; ?>, à <?php echo display_time($point['date']); ?> : <?php echo $cpuv; ?> personne(s)  [GPS] (<?php echo substr($point['lat'], 0, 8); ?>, <?php echo substr($point['lng'], 0, 8); ?>)<?php echo "\n";
                                         }
                                    }
                                }
                            }
                            echo "\n# Destinations : \n";
                            $lieux = [];

                            echo "\n- Lieux de dépose : \n";
                            foreach ($depose as $id_d => $hours) {
                                foreach ($hours as $hour => $sorties) {
                                    if (!isset($lieux[$id_d])) {
                                        $lieu = get_lieu($id_d);
                                        $lieux[$id_d] = $lieu;
                                    } else {
                                        $lieu = $lieux[$id_d];
                                    }
                                    echo $lieu['nom'];
                                    echo ', à '.display_time($hour).' [GPS] ('.substr($lieu['lat'], 0, 8).', '.substr($lieu['lng'], 0, 8).")\n";
                                }
                            }
                            echo "\n- Lieux de reprise : \n";
                            foreach ($reprise as $id_d => $hours) {
                                foreach ($hours as $hour => $sorties) {
                                    if (!isset($lieux[$id_d])) {
                                        $lieu = get_lieu($id_d);
                                        $lieux[$id_d] = $lieu;
                                    } else {
                                        $lieu = $lieux[$id_d];
                                    }
                                    echo $lieu['nom'];
                                    echo ', à '.display_time($hour).' [GPS] ('.substr($lieu['lat'], 0, 8).', '.substr($lieu['lng'], 0, 8).")\n";
                                }
                            }
                            echo "\n# Coordonnées du responsable de destination : \n";
                            if (is_array($destination['responsable'])) {
                                $um = $destination['responsable'];
                            } elseif (is_array($destination['co-responsable'])) {
                                $um = $destination['responsable'];
                            } else {
                                $um = $destination['createur'];
                            }
                            echo $um['ci_user'].' '.$um['lastname_user'].' '.$um['firstname_user'].' - '.$um['email_user'].' - '.$um['tel_user'].' - '.$um['tel2_user']."\n";
                            echo "\nEnvoyé par : $p_sitename \n";
                        ?></textarea>

                        <div class="check-nice">
                            <br>
                            <label for="responsables" style="width:100%"><input type="checkbox" name="responsables" id="responsables" checked="checked"> Envoyer un rappel aux responsables de sorties</label>
                        </div>
                        <br class="clear">
                        <br class="clear">

                        <div class="rght">
                            <a class="biglink" href="javascript:void(0)" title="Envoyer" onclick="$(this).parents('form').submit()">
                                <span class="bleucaf">&gt;</span>
                                    ENVOYER
                            </a>
                        </div>
                        <br class="clear">

                    </form>
                    <pre><?php unset($destination['description']); ?></pre>
                </div><br>
                <?php } ?>
            <?php } else { ?>
                    <div class="note mr10">
                    Les emails de clôture ont déjà été envoyés.
                    </div><br>
            <?php } ?>


        <?php
        } ?>

        <?php
            // titre
            echo '<h1>
                    <span class="bleucaf">&gt;</span> '.html_utf8($destination['nom']);
            echo '<span class="date"> / '.display_jour($destination['date']).'</span>
                </h1>';
        ?>

        <?php /* imprimer la fiche de destination */
        if (allowed('destination_print')
            || $destination['id_user_who_create'] == $_SESSION['user']['id_user']
            || $destination['id_user_responsable'] == $_SESSION['user']['id_user']
            || $destination['id_user_adjoint'] == $_SESSION['user']['id_user']
            || in_array($_SESSION['user']['id_user'], get_all_encadrants_destination($destination['id']), true) // je suis l'un des co/encadrant de l'une des sorties
        ) {
            ?>
            <a href="<?php echo 'feuille-de-sortie/dest-'.(int) ($destination['id']).'.html'; ?>" title="Ouvrir une nouvelle page avec la fiche complète des participants" class="nice2">
                <img src="/img/base/print.png" alt="PRINT" title="" style="height:20px" />
                Imprimer la fiche de destination
            </a><br />
        <?php
        } ?>

        <?php
            $p_date = date('Y-m-d', $p_time);
            $nowTime = new DateTime($p_date);
            $expDate = explode(' ', $destination['date']);
            $destTime = new DateTime($expDate[0]);
            $interval = $nowTime->diff($destTime);

            if ($destination['id_user_responsable'] == $_SESSION['user']['id_user'] || $destination['id_user_adjoint'] == $_SESSION['user']['id_user']) {
                // avant l'evt
                if (0 == $interval->invert) {
                    echo '<p class="info"><img src="/img/inscrit-encadrant.png" alt="" title="" style="float:left" /> <br />Vous êtes &laquo; '.
                        ($destination['id_user_responsable'] == $_SESSION['user']['id_user'] ? '' : 'co-').'responsable &raquo; de l\'organisation de cette destination.<br />&nbsp;
                    </p><br />';
                }
                // apres l'evt
                else {
                    echo '<p class="info"><img src="/img/inscrit-encadrant.png" alt="" title="" style="float:left" /> <br />Vous avez organisé cette destination en temps que &laquo; '.
                        ($destination['id_user_responsable'] == $_SESSION['user']['id_user'] ? '' : 'co-').'responsable &raquo;.<br />&nbsp;
                    </p><br />';
                }
            }
        ?>

        <?php include __DIR__.'/../../includes/dest/display.php'; ?>

        <?php if (user_in_destination($_SESSION['user']['id_user'], $destination['id'])
            || $destination[id_user_who_create] == $_SESSION['id_user']
            || $destination[id_user_responsable] == $_SESSION['id_user']
            || $destination[id_user_adjoint] == $_SESSION['id_user']) { ?>

            <div id="organisation_covoiturage">

                <hr class="clear">
                <ul class="nice-list">
                    <li class="wide"><b>NOMBRE DE PERSONNES TRANPORTEES EN BUS</b> : <?php echo $nInscritDestination; ?></li>
                </ul><br>

                <?php $b = 1; foreach ($destination['bus'] as $bus) { ?>
                    <?php if ($bus['ramassage'] && $bus['countUtilisateurs']) { ?>
                        <div class="note mr20 bbox">
                            <ul class="transport ">
                            <?php foreach ($bus['ramassage'] as $point) { ?>
                                <?php $tmpUsers = []; ?>
                                <?php if ($point['utilisateurs']['valide']) { ?>
                                <li class="wide bbox">
                                    <div class="presentation">
                                        <b><?php echo $bus['intitule']; ?></b> : <?php echo $point['nom']; ?>, à <?php echo display_time($point['date']); ?>
                                        <br>
                                    </div>
                                    <div class="utilisateurs">
                                        <?php $cpuv = count($point['utilisateurs']['valide']); ?>
                                        <br><p class="small">
                                            <b>Organisez-vous ! </b><br>
                                            <b class="bleucaf"><?php echo $cpuv; ?> personne<?php echo $cpuv > 1 ? 's' : ''; ?></b>
                                            <?php echo $cpuv > 1 ? 'peuvent' : 'peut'; ?> être intéressée<?php echo $cpuv > 1 ? 's' : ''; ?>
                                            pour covoiturer jusqu'à ce point de ramassage.
                                        </p>
                                        <?php
                                        echo '<table class="big-lines-table" style="">';
                                        foreach ($point['utilisateurs']['valide'] as $id_user) {
                                            $tmpUser = get_user($id_user, false);
                                            $tmpUser['sortie'] = user_sortie_in_dest($id_user, $destination['id']);
                                            $tmpUsers[$tmpUser['lastname_user'].$tmpUser['firstname_user'].$tmpUser['id_user']] = $tmpUser;
                                        }
                                        ksort($tmpUsers);
                                        foreach ($tmpUsers as $tmpUser) {
                                            echo '<tr><td>'
                                                .(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? '<p class="mini">'.strtoupper(html_utf8($tmpUser['lastname_user'])).', '.ucfirst(strtolower(html_utf8($tmpUser['firstname_user']))).'</p>' : '')
                                                .userlink($tmpUser['id_user'], $tmpUser['nickname_user'])
                                                .'</td>
                                                <td class="small">'.(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? $tmpUser['tel_user'] : '').'</td>
                                                <td class="small">'.(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? $tmpUser['tel2_user'] : '').'</td>
                                                <td class="small">'.(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? '<a href="mailto:'.$tmpUser['email_user'].'">'.$tmpUser['email_user'].'</a>' : '').'</td>'.
                                                (allowed('user_read_limited') ? '<td class="small">'.($tmpUser['is_covoiturage'] ? '<img src="/img/voiture.png" title="Covoiturage" width="16px">' : '').'</td>' : '')
                                                .'</tr>';
                                        }
                                        echo '</table>';
                                        ?>
                                    </div>
                                    <hr>
                                </li>
                                <?php } ?>
                            <?php } ?>
                            </ul>
                            <span class="rght"><b><span  class="bleucaf"><?php echo $bus['countUtilisateurs']; ?></span> personnes</b> prendront ce bus</span><br>
                        </div>
                        <br>
                    <?php } ?>
                <?php } ?>


                <?php $covoiturage = covoiturage_sorties_destination($destination['id']); ?>
                <hr class="clear">
                <ul class="nice-list">
                    <li class="wide"><b>SE RENDENT AUX SORTIES PAR LEURS PROPRES MOYENS</b> : <?php echo $covoiturage['total']; ?></li>
                </ul><br>

                <?php if ($covoiturage['total']) { ?>
                <div class="note mr20 bbox">
                    <ul class="nice-list">
                        <?php foreach ($covoiturage['covoiturage']['sortie'] as $id_sortie => $personnes) {
                                            $current = false; ?>
                            <?php foreach ($destination['sorties'] as $sortie) { ?>
                                <?php if ($sortie['id_evt'] == $id_sortie) {
                                                $current = $sortie;
                                            } ?>
                            <?php } ?>
                            <?php $tmpUsers = []; ?>
                            <li class="wide">
                                <span class="bleucaf"><?php echo html_utf8($current['titre_evt']); ?></span> <?php echo $current['groupe'] ? ' <small>('.$current['groupe']['nom'].')</small>' : ''; ?>
                                <?php
                                    echo '<table class="big-lines-table" style="">';
                                            foreach ($personnes as $id_user) {
                                                $tmpUser = get_user($id_user, false);
                                                $tmpUser['sortie'] = user_sortie_in_dest($id_user, $destination['id']);
                                                $tmpUsers[$tmpUser['lastname_user'].$tmpUser['firstname_user'].$tmpUser['id_user']] = $tmpUser;
                                            }
                                            ksort($tmpUsers);
                                            foreach ($tmpUsers as $tmpUser) {
                                                echo '<tr><td>'
                                            .(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? '<p class="mini">'.strtoupper(html_utf8($tmpUser['lastname_user'])).', '.ucfirst(strtolower(html_utf8($tmpUser['firstname_user']))).'</p>' : '')
                                            .userlink($tmpUser['id_user'], $tmpUser['nickname_user'])
                                            .'</td>
                                                    <td class="small">'.(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? $tmpUser['tel_user'] : '').'</td>
                                                    <td class="small">'.(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? $tmpUser['tel2_user'] : '').'</td>
                                                    <td class="small">'.(allowed('user_read_private', 'commission:'.$tmpUser['sortie']['code_commission']) ? '<a href="mailto:'.$tmpUser['email_user'].'">'.$tmpUser['email_user'].'</a>' : '').'</td>'.
                                            (allowed('user_read_limited') ? '<td class="small">'.($tmpUser['is_covoiturage'] ? '<img src="/img/voiture.png" title="Covoiturage" width="16px">' : '').'</td>' : '')
                                            .'</tr>';
                                            }
                                            echo '</table>'; ?>
                            </li>
                        <?php
                                        } ?>
                    </ul>
                </div>
                <?php } ?>
            </div>

            <pre>
                <?php /* print_r($covoiturage); ?>
                <?php print_r($userAllowedTo); ?>
                <?php print_r($destination); */ ?>
            </pre>
        <?php } ?>
    <?php } ?>
</div>

