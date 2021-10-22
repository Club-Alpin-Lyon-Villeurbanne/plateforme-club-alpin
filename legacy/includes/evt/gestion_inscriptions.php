<?php
// GESTION DES INSCRIPTIONS

if ('1' != $evt['cancelled_evt']) {
    if (
        // j'en suis l'auteur
        (user() && $_SESSION['user']['id_user'] == $evt['user_evt'])
        // ou j'ai un droit spécial
        || allowed('evt_join_doall')
        // ou je participe à cette sortie en tant qu'encadrant / coencadrant
        || 'encadrant' == $monStatut
        || 'coencadrant' == $monStatut
        || ($_SESSION['user']['status'] && in_array('Salarié', $_SESSION['user']['status'], true))
        || ((allowed('evt_join_notme') || allowed('evt_unjoin_notme') || allowed('evt_joining_accept') || allowed('evt_joining_refuse')) && ($_SESSION['user']['status'] && in_array('Resp. de commission, '.$evt['code_commission'], $_SESSION['user']['status'], true)))
        ) {
        // droit de modification en fonction des conditions ci-dessus :
        $droitDeModif = false;
        // je suis salarie
        if ($_SESSION['user']['status'] && in_array('Salarié', $_SESSION['user']['status'], true)) {
            $droitDeModif = true;
        }
        // j'en suis l'auteur ou droits
        if ($_SESSION['user']['id_user'] == $evt['user_evt']) {
            $droitDeModif = true;
        }
        // ou j'ai un super droit
        if (admin() || allowed('evt_join_doall')) {
            $droitDeModif = true;
        }
        // ou je participe à cette sortie en tant qu'encadrant / coencadrant
        if ('encadrant' == $monStatut || 'coencadrant' == $monStatut) {
            $droitDeModif = true;
        }
        // ou je suis resp de comm pour la sortie
        if ((allowed('evt_join_notme') || allowed('evt_unjoin_notme') || allowed('evt_joining_accept') || allowed('evt_joining_refuse')) && ($_SESSION['user']['status'] && in_array('Resp. de commission, '.$evt['code_commission'], $_SESSION['user']['status'], true))) {
            $droitDeModif = true;
        } ?>
        <!-- Datatables -->
        <link rel="stylesheet" href="/tools/datatables/media/css/jquery.dataTables.sobre.css" type="text/css" media="screen" />
        <script type="text/javascript" src="/tools/datatables/media/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript">

        // jquery
            $(document).ready(function(){

                var expr = new RegExp('>[ \t\r\n\v\f]*<', 'g');
                var tbhtml = $('.datatable').html();
                $('.datatable').html(tbhtml.replace(expr, '><'));

                // datatables
                $('.datatable').dataTable( {
                    "aaSorting": [[ 1, "asc" ], [ 0, "asc" ]],
                    "iDisplayLength" : -1,
                    "bLengthChange": false,
                    "bFilter": false,
                    "bPaginate": false,
                    "bInfo": false
                    // "aLengthMenu": [[-1],  ["All"]]
                    // "bLengthChange": false
                    // "sDom": 'T<"clear">lfrtip'
                } );

                // format de couleur du changement de statut
                $('.joinlabels input').bind('change', function(){
                    // changment ligne
                    $(this).parents('tr').removeClass('status0 status1 status2 status3 status-1').addClass('status'+$(this).val());

                    // changement des stats
                    var nAcceptees=0;
                    var nRefusees=0;
                    $('.joinlabels input:checked[type=radio]').each(function(){
                        if($(this).val()==1) 	nAcceptees++;
                        if($(this).val()==2) 	nRefusees++;

                    });
                    $('#nAcceptees').html(nAcceptees);
                    $('#nRefusees').html(nRefusees);
                });

            });
        </script>

        <div id="inscription-gestion">
            <?php
            // message à afficher pour l'organsiateur, quand aux inscriptions, légèrement différent si la sortie est passée
            if ($evt['tsp_end_evt'] > $p_time) {
                inclure('formalites-gestion-des-inscrits', 'vide');
            } else {
                inclure('formalites-gestion-des-inscrits-evt-passe', 'vide');
            } ?>
            <br />

            <form action="<?php echo $versCettePage; ?>#inscription-gestion" method="post" class="loading">
                <input type="hidden" name="operation" value="user_join_update_status" />
                <input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
                <input type="hidden" name="titre_evt" value="<?php echo html_utf8($evt['titre_evt']); ?>" />
                <input type="hidden" name="code_evt" value="<?php echo html_utf8($evt['code_evt']); ?>" />
                <!-- si l'evt est passé on interdit l'envoi d'e-mails -->
                <?php if ($evt['tsp_end_evt'] < $p_time) { ?>
                    <input type="hidden" name="dontsendmail" value="true" />
                <?php } ?>

                <?php
                // TABLEAU erreurs
                if ('user_join_update_status' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                    echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                }
        if ('user_join_update_status' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
            echo '<p class="info">Mise à jour effectuée à '.date('H:i', $p_time).'.</p><br />';
        }

        // TABLEAU alertes
        if ('user_join_update_status' == $_POST['operation'] && $addAlert && count($addAlert) > 0) {
            echo '<div class="alerte">Attention : <ul><li>'.implode('</li><li>', $addAlert).'</li></ul></div><br />';
        } ?>

                <table class="datatable">
                    <thead>
                        <tr>
                            <th width='30%'>Nom / Pseudo</th>
                            <th width='15%'>Statut</th>
                            <th width='15%'>Rôle</th>
                            <th width='10%'>Date</th>
                            <?php if (1 == $evt['cb_evt'] && 1 == $evt['repas_restaurant']) { ?>
							<th width='20%'>Contact</th>
							<?php } elseif ((1 == $evt['cb_evt'] && 0 == $evt['repas_restaurant']) || (0 == $evt['cb_evt'] && 1 == $evt['repas_restaurant'])) { ?>
                            <th width='25%'>Contact</th>
                            <?php } else { ?>
                            <th width='30%'>Contact</th>
                            <?php } ?>
                            <?php if (1 == $evt['cb_evt']) { ?>
                            <th width='5%'><abbr title="Paiement en ligne">P.</abbr></th>
                            <?php } ?>
                            <?php if (1 == $evt['repas_restaurant']) { ?>
                            <th width='5%'><abbr title="Restaurant">R.</abbr></th>
                            <?php } ?>
                            <?php if ($destination) { ?>
                            <th><abbr title="Covoiturage">C.</abbr></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // vars pour les stats sous le tableau
                        $nDemandes = $nAcceptees = $nRefusees = $nAbsents = $nCb = $nCb_nsp = $nRestaurant = $nRestaurant_nsp = 0;

        // requete SQL in page
        $mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
        // participants non filtrés
        $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, nomade_user, tel_user, tel2_user, email_user
                                    , id_evt_join , role_evt_join, tsp_evt_join, status_evt_join, doit_renouveler_user, is_cb, is_restaurant, is_covoiturage, id_destination, id_bus_lieu_destination
                            FROM caf_evt_join, caf_user
                            WHERE evt_evt_join =$id_evt
                            AND user_evt_join = id_user
                            ORDER BY tsp_evt_join
                            LIMIT 300";
        $result = $mysqli->query($req);
        while ($row = $result->fetch_assoc()) {
            // STATS
            ++$nDemandes;
            if (1 == $row['status_evt_join']) {
                ++$nAcceptees;
                if (1 == $row['is_cb']) {
                    ++$nCb;
                }
                if (null === $row['is_cb']) {
                    ++$nCb_nsp;
                }
                if (1 == $row['is_restaurant']) {
                    ++$nRestaurant;
                }
                if (null === $row['is_restaurant']) {
                    ++$nRestaurant_nsp;
                }
            }
            if (2 == $row['status_evt_join']) {
                ++$nRefusees;
            }
            if (3 == $row['status_evt_join']) {
                ++$nAbsents;
            }

            // PARTICIPANTS - EMPIETEMENTS : VÉRIFICATION DE (PRÉ)INSCRIPTIONS À D'AUTRES SORTIES DANS LE MEME TIMING
            // création du tableau "empiètement" pour chaque user
            $row['empietements'] = empietement_sortie($row['id_user'], $evt);

            // AFFICHAGE DE LA LIGNE DE CET INSCRIT
            echo '<tr class="status'.($row['status_evt_join']).'" style="color:gray; font-size:10px;">';

            echo '<td>'
                                        .html_utf8(strtoupper($row['lastname_user'])).', '
                                        .html_utf8(ucfirst(strtolower($row['firstname_user']))).'<br />'
                                        .userlink($row['id_user'], $row['nickname_user']);
            // expiré
            if ($row['doit_renouveler_user']) {
                echo '&nbsp;&nbsp;&nbsp;<img src="img/base/delete.png" title="licence expirée" style="margin-bottom:-4px;">';
            }
            echo '</td>';

            echo '<td class="mini joinlabels status'.($row['status_evt_join']).'">
                                        ';

            $disable0 = true; //attente
                                        $disable1 = true; //accepte
                                        $disable2 = true; //refuse
                                        $disable3 = true; //absent
                                        $disable_1 = true; //desinscrire

                                        if (allowed('evt_joining_accept') || allowed('evt_join_doall')) {
                                            $disable0 = false; //attente
                                            $disable1 = false; //accepte
                                            $disable3 = false; //absent
                                        }
            if (allowed('evt_joining_refuse') || allowed('evt_join_doall')) {
                $disable2 = false; //refuse
            }
            if (allowed('evt_unjoin_notme') || allowed('evt_join_doall')) {
                $disable_1 = false; //desinscrire
            }

            // empiètements
            if (count($row['empietements'])) {
                echo '<div class="empietements">
                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Attention au timing :</b> ';
                foreach ($row['empietements'] as $tmpJoin) {
                    // préinscrit
                    if (0 == $tmpJoin['status_evt_join']) {
                        echo '<br />- Adhérent pré-inscrit sur <br /><a href="sortie/'.$tmpJoin['code_evt'].'-'.$tmpJoin['id_evt'].'.html" title="">'.html_utf8($tmpJoin['titre_evt']).'</a> ';
                    }
                    // inscrit confirmé
                    if (1 == $tmpJoin['status_evt_join']) {
                        echo '<br />- Adhérent <span style="color:red">confirmé</span> sur <br /> <a href="sortie/'.$tmpJoin['code_evt'].'-'.$tmpJoin['id_evt'].'.html" title="">'.html_utf8($tmpJoin['titre_evt']).'</a><br />';
                    }

                    // s'il est confirmé ailleurs, on bride l'outil de mise à jour
                                                // if($tmpJoin['status_evt_join'] == 1) $disable1 = true;
                                                // **** OPTION RETIREE : car une sortie du meme jour finit par défaut à minuit
                                                // et on veut pouvoir s'inscrire à la sortie barbecue après la sortie rando !
                }
                echo '</div>';
            }

            if ('encadrant' != $row['role_evt_join']) {// && $row['role_evt_join'] != 'coencadrant'
                if ($droitDeModif) {
                    echo '<span style="display:none">'.(int) ($row['status_evt_join']).'</span>
                                                <input type="hidden" name="id_evt_join[]" value="'.(int) ($row['id_evt_join']).'" />';

                    if (0 == $row['status_evt_join']) {
                        // EN ATTENTE
                        echo '<label for="join_'.(int) ($row['id_evt_join']).'_0">
                                                        <input '.(0 == $row['status_evt_join'] ? 'checked="checked"' : '').' '.($disable0 ? 'disabled="disabled"' : '').' type="radio" name="status_evt_join_'.(int) ($row['id_evt_join']).'" id="join_'.(int) ($row['id_evt_join']).'_0" value="0" />
                                                        En attente
                                                    </label>';
                    }

                    if (($p_time >= $evt['tsp_end_evt'] && 1 == $row['status_evt_join']) || $p_time < $evt['tsp_end_evt']) {
                        echo '<label for="join_'.(int) ($row['id_evt_join']).'_1">
                                                <input '.(1 == $row['status_evt_join'] ? 'checked="checked"' : '').' '.($disable1 ? 'disabled="disabled"' : '').' type="radio" name="status_evt_join_'.(int) ($row['id_evt_join']).'" id="join_'.(int) ($row['id_evt_join']).'_1" value="1" />
                                                Accepté
                                                </label>';
                    }

                    if ($p_time >= $evt['tsp_end_evt'] && 2 != $row['status_evt_join']) {
                        echo '<label for="join_'.(int) ($row['id_evt_join']).'_3">
                                                        <input '.(3 == $row['status_evt_join'] ? 'checked="checked"' : '').' '.($disable3 ? 'disabled="disabled"' : '').' type="radio" name="status_evt_join_'.(int) ($row['id_evt_join']).'" id="join_'.(int) ($row['id_evt_join']).'_3" value="3" />
                                                        Absent
                                                    </label>';
                    }

                    if (($p_time >= $evt['tsp_end_evt'] && 2 == $row['status_evt_join']) || $p_time < $evt['tsp_end_evt']) {
                        echo '<label for="join_'.(int) ($row['id_evt_join']).'_2">
                                                <input '.(2 == $row['status_evt_join'] ? 'checked="checked"' : '').' '.($disable2 ? 'disabled="disabled"' : '').' type="radio" name="status_evt_join_'.(int) ($row['id_evt_join']).'" id="join_'.(int) ($row['id_evt_join']).'_2" value="2" />
                                                Refusé
                                                </label>';
                    }

                    if ($p_time < $evt['tsp_end_evt']) {
                        echo '<label for="join_'.(int) ($row['id_evt_join']).'_-1">
                                                <input name="status_evt_join_'.(int) ($row['id_evt_join']).'" '.($disable_1 ? 'disabled="disabled"' : '').' type="radio" id="join_'.(int) ($row['id_evt_join']).'_-1" value="-1" />
                                                Désinscrire
                                                </label>';
                    }
                } else {
                    //echo '<input type="hidden" name="status_evt_join_'.intval($row['id_evt_join']).'" value="'.intval($row['status_evt_join']).'" />';
                    echo ''.(0 == $row['status_evt_join'] ? 'En attente' : '')
                                                .(1 == $row['status_evt_join'] ? 'Accepté' : '')
                                                .(2 == $row['status_evt_join'] ? 'Refusé' : '')
                                                .(3 == $row['status_evt_join'] ? 'Absent' : '')
                                                .(-1 == $row['status_evt_join'] ? 'Désinscrire' : '');
                }
            }

            echo '</td><td class="mini" > ';
            // .html_utf8($row['role_evt_join'])

            if ($row['nomade_user']) {
                echo '<br /><img src="img/base/bullet_error.png" alt="!" title="Attention. Ne reçoit pas d\'e-mail" style="vertical-align:top" /><br />';
            } elseif ($droitDeModif && (0 == strcmp($row['role_evt_join'], 'manuel') || 0 == strcmp($row['role_evt_join'], 'inscrit') || 0 == strcmp($row['role_evt_join'], 'benevole'))) {
                if (0 == strcmp($row['role_evt_join'], 'manuel')) {
                    //manuel
                    echo '<label style="display:block; white-space:nowrap;" for="role_join_'.(int) ($row['id_evt_join']).'_m">
                                                    <input  '.(0 == strcmp($row['role_evt_join'], 'manuel') ? 'checked="checked"' : '').' name="role_evt_join_'.(int) ($row['id_evt_join']).'" type="radio" id="role_join_'.(int) ($row['id_evt_join']).'_m" value="manuel" />
                                                    Manuel
                                                    </label>';
                }
                echo '<label style="display:block; white-space:nowrap;" for="role_join_'.(int) ($row['id_evt_join']).'_i">
                                                <input  '.(0 == strcmp($row['role_evt_join'], 'inscrit') ? 'checked="checked"' : '').' name="role_evt_join_'.(int) ($row['id_evt_join']).'" type="radio" id="role_join_'.(int) ($row['id_evt_join']).'_i" value="inscrit" />
                                                Inscrit
                                                </label>';
                echo '<label style="display:block; white-space:nowrap;" for="role_join_'.(int) ($row['id_evt_join']).'_b">
                                                <input '.(0 == strcmp($row['role_evt_join'], 'benevole') ? 'checked="checked"' : '').' name="role_evt_join_'.(int) ($row['id_evt_join']).'" type="radio" id="role_join_'.(int) ($row['id_evt_join']).'_b" value="benevole" />
                                                Bénévole
                                                </label>';
                //echo '<select name="role_evt_join_'.intval($row['id_evt_join']).'">';
                //echo '<option value="inscrit" '.((strcmp($row['role_evt_join'],'inscrit')==0)?'selected="selected"':'').'>Inscrit</option>';
                //echo '<option value="benevole" '.((strcmp($row['role_evt_join'],'benevole')==0)?'selected="selected"':'').'>Bénévole</option>';
                //echo '<option value="coencadrant" '.((strcmp($row['role_evt_join'],'coencadrant')==0)?'selected="selected"':'').'>Co-encadrant</option>';
                //echo '<option value="encadrant" '.((strcmp($row['role_evt_join'],'encadrant')==0)?'selected="selected"':'').'>Encadrant</option>';
                echo '</select>';
            } else {
                echo html_utf8($row['role_evt_join']);
            }

            echo '</td>
                                    <td style="white-space:nowrap">
                                        <span style="display:none">'.html_utf8($row['tsp_evt_join']).'</span>
                                        '.date('d/m', $row['tsp_evt_join']).'<br />
                                        '.date('à H:i', $row['tsp_evt_join']).'
                                    </td>
                                    <td>
                                        <a href="mailto:'.html_utf8($row['email_user']).'">'.substr(html_utf8($row['email_user']), 0, 15).'...</a><br />
                                        '.html_utf8($row['tel_user']).'<br />
                                        '.html_utf8($row['tel2_user']).'
                                    </td>';
            if (1 == $evt['cb_evt']) {
                echo '<td><img src="img/base/'.('1' == $row['is_cb'] ? 'cb-oui.png' : ('0' == $row['is_cb'] ? 'cb-non.png' : 'cb-nsp.png')).'" title="'.('1' == $row['is_cb'] ? 'Oui' : ('0' == $row['is_cb'] ? 'Non' : 'NSP')).'" /></td>';
            }
            if (1 == $evt['repas_restaurant']) {
                echo '<td><img src="img/base/'.('1' == $row['is_restaurant'] ? 'resto-oui.png' : ('0' == $row['is_restaurant'] ? 'resto-non.png' : 'resto-nsp.png')).'" title="'.('1' == $row['is_restaurant'] ? 'Oui' : ('0' == $row['is_restaurant'] ? 'Non' : 'NSP')).'" /></td>';
            }
            if ($destination) {
                echo '<td>'.((null === $row['is_covoiturage'] && null === $row['id_bus_lieu_destination']) ? '<img src="img/base/error.png" title="Mettre à jour les préférences" width="16px">' : ($row['is_covoiturage'] ? '<img src="img/voiture.png" title="Covoiturage" width="16px">' : '')).'</th>';
            }
            echo '</tr>';
        }
        $mysqli->close; ?>
                    </tbody>
                </table>

                <!-- stats affichées -->
                <p>Stats :</p>
                <ul>
                    <li>
                        <b id="nDemandes"><?php echo $nDemandes; ?></b> demande(s), dont
                        <b id="nAcceptees"><?php echo $nAcceptees; ?></b> acceptée(s), et
                        <b id="nRefusees"><?php echo $nRefusees; ?></b> refusée(s).
                    </li>
                    <li><b id="nRefusees"><?php echo $nAbsents; ?></b> absent(s).</li>
                    <?php if ('1' == $evt['cb_evt']) { ?>
                    <li>
                        <b id="nCb"><?php echo $nCb; ?></b> paiement en ligne confirmé<?php if ($nCb_nsp) { ?>,
                        <b id="nCb_nsp"><?php echo $nCb_nsp; ?></b> NSP <small>(inscriptions manuelles, nomades et organisateur)</small><?php } ?>.
                    </li><?php } ?>
                    <?php if ('1' == $evt['repas_restaurant']) { ?>
                    <li>
                        <b id="nRestaurant"><?php echo $nRestaurant; ?></b> restaurant confirmé<?php if ($nRestaurant_nsp) { ?>,
                        <b id="nRestaurant_nsp"><?php echo $nRestaurant_nsp; ?></b> NSP <small>(inscriptions manuelles, nomades et organisateur)</small><?php } ?>.
                    </li><?php } ?>
                </ul>

                <br style="clear:both"  />

                <?php
                if ($droitDeModif) {
                    ?>
                    <p class="mini">
                        <input type="checkbox" name="disablemails" <?php if ('on' == $_POST['disablemails']) {
                        echo 'checked="checked"';
                    } ?> />
                        Ne pas envoyer les e-mails lors de la mise à jour (déconseillé, sauf après l'événement).
                    </p>

                    <div style="text-align:center">
                        <br />
                        <a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
                            <span class="bleucaf">&gt;</span>
                            ENREGISTRER LES STATUTS CI-DESSUS
                        </a>
                    </div>
                    <?php
                } else {
                    echo '<p class="mini">'.$monStatut.' : Vous ne pouvez pas modifier les inscriptions</p>';
                } ?>
                <br />
                <br />
            </form>
            <hr />

        <?php
            if ($droitDeModif) {
                echo '<h2 id="autresoptions">Autres options :</h2>';

                if (allowed('evt_contact_all')) {
                    ?>
                    <!-- Contact par email des participants (orga uniquement) -->
                    <a class="nice2 blue" href="javascript:void(0)" onclick="$('#contact-inscrits').slideToggle()" title=""><img src="img/base/email.png" alt="" title="" /> Envoyer un e-mail groupé aux inscrits</a><br />

                    <form action="<?php echo $versCettePage; ?>#autresoptions" method="post" id="contact-inscrits" style="display: <?php if ('evt_user_contact' != $_POST['operation']) {
                        echo 'none';
                    } ?>; border : 1px solid #eaeaea; box-shadow:0 0 20px -10px gray;; padding:10px; margin:10px 0 20px 0;; border-radius:10px">
                        <input type="hidden" name="operation" value="evt_user_contact" />
                        <input type="hidden" name="id_evt" value="<?php echo (int) ($evt['id_evt']); ?>" />

                        <h2>Formulaire de contact</h2>
                        <?php
                        // MESSAGES A LA SOUMISSION
                        if ('evt_user_contact' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                        }
                    if ('evt_user_contact' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                        echo '<p class="info">Votre message a bien été envoyé.</p>';
                    } ?>

                        <input type="radio" name="status_sendmail" id="status_sendmail_1" value="1" <?php if ('evt_user_contact' != $_POST['operation'] || '1' == $_POST['status_sendmail']) {
                        echo 'checked="checked"';
                    } ?> /><label for="status_sendmail_1"> Envoyer uniquement aux adhérents confirmés pour la sortie</label><br />
                        <input type="radio" name="status_sendmail" id="status_sendmail_2" value="2" <?php if ('2' == $_POST['status_sendmail']) {
                        echo 'checked="checked"';
                    } ?> /><label for="status_sendmail_2"> Envoyer uniquement aux adhérents refusés/absents</label><br />
                        <input type="radio" name="status_sendmail" id="status_sendmail_0" value="0" <?php if ('0' == $_POST['status_sendmail']) {
                        echo 'checked="checked"';
                    } ?> /><label for="status_sendmail_0"> Envoyer uniquement aux adhérents en attente de confirmation</label><br />
                        <input type="radio" name="status_sendmail" id="status_sendmail_all" value="*" <?php if ('*' == $_POST['status_sendmail']) {
                        echo 'checked="checked"';
                    } ?> /><label for="status_sendmail_all"> Envoyer à toute la liste des inscrits, confirmés ou non</label><br />

                        <br />
                        Objet :<br />
                        <input type="text" name="objet" class="type1" style="width:95%" value="<?php echo html_utf8(stripslashes($_POST['objet'])); ?>" placeholder="Note importante pour la sortie du <?php echo date('d/m', $evt['tsp_evt']); ?>" /><br />
                        Message :<br />
                        <textarea name="message" class="type1" style="width:95%; height:150px"><?php echo html_utf8(stripslashes($_POST['message'])); ?></textarea>

                        <br /><br />
                        <input type="submit" class="nice" value="&gt; Envoyer mon message" onclick="$.fancybox.close()" />
                    </form>
        <?php
                }

                if (allowed('user_see_all') || allowed('evt_join_notme') || allowed('evt_join_doall')) {
                    // Ajout de adhérents manuellement
                    echo '<a class="nice2 blue fancyframe" href="includer.php?p=includes/join_manual.php&amp;id_evt='.$id_evt.'" title="">Inscrire manuellement des adhérents du club</a>';

                    // Ajout de adhérents nomades
                    echo '<a class="nice2 blue fancyframe" href="includer.php?p=includes/join_nomad.php&amp;id_evt='.$id_evt.'" title="">Ajouter un adhérent "Nomade"</a>';
                }
            } ?>
            <br style="clear:both"  /><br />

        </div>
        <br />
        <hr />
        <?php
    }
}
