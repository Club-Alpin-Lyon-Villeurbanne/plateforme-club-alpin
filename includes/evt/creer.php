<form action="<?php echo $versCettePage;?>" method="post" style="overflow:hidden">
    <input type="hidden" name="operation" value="<?php echo $id_evt_to_update?'evt_update':'evt_create'; ?>" />
    <input type="hidden" name="id_evt_to_update" value="<?php echo intval($id_evt_to_update); ?>" />

    <?php
    // masque certaines option si cet evt est une suite de cycle
    if($_POST['cycle_parent_evt'] && $_POST['cycle']=='child') $suiteDeCycle=true;


    // message d'erreur
    if($_POST['operation'] && sizeof($errTab)){
        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul>';
        if (!$destination) echo '<b>Attention :</b> Le marqueur rouge sur la carte a peut-être été déplacé !';
        echo '</div>';
    }
    // message d'info : si c'est une modification de sortie
    if($_POST['operation']=='evt_update' && !sizeof($errTab))	echo '<p class="info"><img src="img/base/tick.png" alt="" title="" /> Mise à jour effectuée à '.date("H:i:s", $p_time).'. <b>Important :</b> cette sortie doit à présent être validée par un responsable pour être publiée sur le site.<a href="profil/sorties/self.html" title="">&gt; Retourner à la liste de mes sorties</a></p>';
    ?>



    <?php
    // liens dans le cas de la creation d'une sortie
    if ($destination) {
        echo '<input type="hidden" name="id_destination" value="'.intval($destination['id']).'" />';
        echo '<h2 class="trigger-h2 '.($id_evt_to_update?'off':'').'"  title="Cliquer pour ouvrir ou fermer">DESTINATION : <span class="bleucaf">'.html_utf8($destination['nom']).'</span><span class="date"> / '.display_jour($destination['date']).'</span></h2>';
        //echo '<div id="destination-info" class="trigger-me">';
    } else {
        echo '<h2 class="trigger-h2" title="Cliquer pour ouvrir ou fermer">DESTINATION : </h2>';
        //echo '<div id="destination-info" class="trigger-me">';
    }

    if(!$id_evt_to_update){
        $destinations = get_future_destinations(false, true);
        ?>
        <?php
        if (!$destination) { echo '<p>Si vous avez besoin d\'un bus, vous pouvez lier cette sortie à une destination :</p>'; }
        else { echo '<p>Vous pouvez choisir une autre destination :</p>'; }
        ?>
        <div class="faux-select-wrapper" id="choix-destination">
            <div class="faux-select">
                <a href="<?php echo 'creer-une-sortie/'.html_utf8($p2).'.html'; ?>" <?php if (!$destination) echo ' class="up" '; ?>><i>Pas de destination</i></a>
                <?php
                foreach($destinations as $dest){
                    echo '<a href="creer-une-sortie/'.html_utf8($p2).'/destination-'.$dest['id'].'.html" title="" class="'.($destination['id']==$dest['id']?'up':'').'" style="text-align:right;padding-right:50px;">
                        <b>'.html_utf8($dest['nom']).'</b>
                        <span class="date"> / '.display_jour($dest['date']).'</span>
                    </a> ';
                }
                ?>
            </div>
        </div><br class="clear">
    <?php

    }
    echo '<div id="destination-info" class="trigger-me">';
    if ($destination) {
        include(INCLUDES.'dest'.DS.'display.php');
    }
    echo '</div>';

    ?><br class="clear">


    <!-- liste des commissions où poster l'evt -->
    <div style="float:left; padding:0 20px 10px 0">

        Sortie liée à la commission :<br />
        <?php
        // liens dans le cas de la creation d'une sortie
        if(!$id_evt_to_update){
            ?>
            <div class="faux-select-wrapper" id="choix-commission">
                <div class="faux-select">
                    <?php
                    foreach($comTab as $code=>$data){
                        if(allowed('evt_create', 'commission:'.$code))
                            echo '<a href="creer-une-sortie/'.html_utf8($code).($destination?'/'.$destination['code']:'').'.html" title="" class="'.($code==$current_commission?'up':'').'">'.html_utf8($data['title_commission']).'</a> ';
                    }
                    ?>
                </div>
            </div>
            <?php
            echo '<input type="hidden" name="commission_evt" value="'.intval($comTab[$current_commission]['id_commission']).'" />';
        }
        // juste l'info  et la variable dans le cas d'une modification de sortie existante
        else{
            echo '<b>'.$comTab[$current_commission]['title_commission'].'</b><input type="hidden" name="commission_evt" value="'.intval($_POST['commission_evt']).'" />';
        }
        ?>
    </div>

    <div style="float:right;margin-right:20px;" >
        Titre :<br />
        <input style="width:320px;" type="text" name="titre_evt" class="type1" value="<?php echo inputVal('titre_evt', '');?>" placeholder="ex : Escalade du Grand Som" />
    </div>

    <?php $groupes = get_groupes($comTab[$current_commission]['id_commission'], true); ?>

    <?php if (count($groupes) > 0) { ?>
        <select name="id_groupe" class="type1" style="width:95%">
            <option value="">- Précisez le groupe concerné par cette sortie (facultatif) :</option>
            <?php
            // articles liés aux commissions
            foreach($groupes as $code=>$groupe) {
                echo '<option value="'.$groupe['id'].'" '.($_POST['id_groupe']==$groupe['id']?'selected="selected"':'').'>Groupe : '.html_utf8($groupe['nom']).' &raquo;</option>';
            }
            ?>
        </select>
    <?php } else { ?>
        <input type="hidden" name="id_groupe" value="" >
    <?php } ?>
    <br class="clear">


    <div id="individus">
        <h2 class="trigger-h2">Encadrant(s) :</h2>
        <div class="trigger-me check-nice">
            <?php
            $encadrants=is_array($_POST['encadrants'])?$_POST['encadrants']:array();
            if(!sizeof($encadrantsTab))
                // echo '<p class="erreur">Erreur : aucun adhérent n\'est déclaré <b>encadrant</b> pour cette commission. Vous ne pourrez pas créer de sortie...</p>';
                echo '<p class="info">Aucun adhérent n\'est déclaré <b>encadrant</b> pour cette commission.</p>';
            foreach($encadrantsTab as $encadrant)
                echo '<label for="encadrant-'.$encadrant['id_user'].'">
									<input type="checkbox" '.(in_array($encadrant['id_user'], $encadrants)?'checked="checked"':'').' name="encadrants[]" value="'.$encadrant['id_user'].'" id="encadrant-'.$encadrant['id_user'].'" />
									'.$encadrant['firstname_user'].'
									'.$encadrant['lastname_user'].'
									<a class="fancyframe" href="includer.php?p=includes/fiche-profil.php&amp;id_user='.$encadrant['id_user'].'" title="Voir la fiche"><img src="img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
								</label>';
            ?>
            <br style="clear:both" />
        </div>

        <h2 class="trigger-h2">Co-Encadrant(s) :</h2>
        <div class="trigger-me check-nice">
            <?php
            $coencadrants=is_array($_POST['coencadrants'])?$_POST['coencadrants']:array();
            if(!sizeof($coencadrantsTab))
                // echo '<p class="info">Aucun adhérent n\'est déclaré <b>encadrant</b> pour cette commission.</p>';echo '<p class="erreur">Erreur : aucun adhérent n\'est déclaré <b>coencadrant</b> pour cette commission. Vous ne pourrez pas créer de sortie...</p>';
                echo '<p class="info">Aucun adhérent n\'est déclaré <b>co-encadrant</b> pour cette commission.</p>';
            foreach($coencadrantsTab as $coencadrant)
                echo '<label for="coencadrant-'.$coencadrant['id_user'].'">
									<input type="checkbox" '.(in_array($coencadrant['id_user'], $coencadrants)?'checked="checked"':'').' name="coencadrants[]" value="'.$coencadrant['id_user'].'" id="coencadrant-'.$coencadrant['id_user'].'" />
									'.$coencadrant['firstname_user'].'
									'.$coencadrant['lastname_user'].'
									<a class="fancyframe" href="includer.php?p=includes/fiche-profil.php&amp;id_user='.$coencadrant['id_user'].'" title="Voir la fiche"><img src="img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
								</label>';
            ?>
            <br style="clear:both" />
        </div>

        <h2 class="trigger-h2">Bénévoles <?php if ($id_evt_to_update) echo '(modifiable dans la gestion des inscrits) '; ?>:</h2>
        <div class="trigger-me check-nice">
            <?php
            // modification possible seulement en cas de creation d'une nouvelle sortie
            if(!$id_evt_to_update){
                $benevoles=is_array($_POST['benevoles'])?$_POST['benevoles']:array();
                if(!sizeof($benevolesTab)) {
                    echo '<p class="info">Aucun adhérent n\'est déclaré <b>bénévole</b> pour cette commission ou cette sortie.</p>';
                }
                foreach($benevolesTab as $benevole) {
                    echo '<label for="benevole-'.$benevole['id_user'].'">
									<input '.($id_evt_to_update?'disabled':'').' type="checkbox" '.(in_array($benevole['id_user'], $benevoles)?'checked="checked"':'').' name="benevoles[]" value="'.$benevole['id_user'].'" id="benevole-'.$benevole['id_user'].'" />
									'.$benevole['firstname_user'].'
									'.$benevole['lastname_user'].'
									<a class="fancyframe" href="includer.php?p=includes/fiche-profil.php&amp;id_user='.$benevole['id_user'].'" title="Voir la fiche"><img src="img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
								</label>';
                }
                echo '<br style="clear:both" />';
            }
            ?>

            <label for="need_benevoles_evt" style="margin-top:15px; display:block; float:none; width:93%; background-color:white; background-position:8px 5px; padding-left:10px; padding-top:5px; box-shadow:0 0 15px -8px black;">
                <input type="checkbox" class="custom" name="need_benevoles_evt" id="need_benevoles_evt" <?php if($_POST['need_benevoles_evt']==1 OR $_POST['need_benevoles_evt']=='on') echo 'checked="checked"';?> /> Afficher un encart &laquo;Nous aurions besoin de bénévoles&raquo; sur la page de la sortie ?
            </label>

            <br style="clear:both" />
        </div>
    </div>

    <h2 class="clear trigger-h2">Informations :</h2>
    <div class="trigger-me">

        <?php if (!$destination) { ?>
            <div>
                Massif :<br />
                <input style="width:95%;" type="text" name="massif_evt" class="type2" value="<?php echo inputVal('massif_evt', '');?>" placeholder="ex : Chartreuse" />
            </div>
        <?php } ?>

        <?php if (!$destination) { ?>
            <br />
            Cette sortie fait-elle partie d'un cycle de plusieurs sorties ?
            <?php inclure('infos-cycle', 'mini'); ?>

            <?php if (!$_POST['cycle_master_evt']) {
                // cette sortie n'est pas un debut de cycle
                // et si c'est une sortie de cycle, il n'y a pas de sortie associee pour le moment
                ?>

                <label class="biglabel" for="cycle_none">
                    <input type="radio" name="cycle" id="cycle_none" value="none" <?php if($_POST['cycle']=='none' or !$_POST['cycle']) echo 'checked="checked"'; ?> /> Non, c'est une sortie unique
                </label>
            <?php } ?>

            <?php if(!$_POST['cycle']) { ?>
                <label class="biglabel" for="cycle_parent">
                    <input type="radio" name="cycle" id="cycle_parent" value="parent" <?php if($_POST['cycle_master_evt']) echo 'checked="checked"';?> /> Oui, cette sortie est la première d'un cycle,
                    <?php
                    if($_POST['cycle_master_evt']){
                        echo '<b>des sorties sont dejà associées</b>';
                    } else{
                        echo 'd\'autres sorties vont suivre';
                    }
                    ?>
                </label>
            <?php } ?>

            <?php if(!($_POST['parent'] || $_POST['cycle_master_evt'])){ ?>
                <label class="biglabel" for="cycle_child">
                    <input type="radio" name="cycle" id="cycle_child" value="child" <?php if($_POST['cycle']=='child') echo 'checked="checked"'; ?> /> Oui, cette sortie est la suite d'une sortie précédente
                </label>

                <div id="cycle_parent_select" style="display:<?php /**/ //echo ($_POST['cycle']=='child'?'block':'none'); /**/ ?>; ">
                    <?php
                    // LISTE DES SORTIES MASTER DE CYCLES
                    if(!sizeof($parentEvents)) echo '<p class="alerte">Vous n\'avez pas encore créé de première sortie pour un cycle. Vous devez commencer par entrer la première sortie du cycle pour pouvoir y joindre d\'autres sorties ensuite.</p>';
                    else{
                        ?>
                        Merci de sélectionner la sortie parente (la première sortie du cycle) :<br />
                        <select name="cycle_parent_evt">
                            <?php
                            foreach($parentEvents as $tmpEvt)
                                echo '<option value="'.$tmpEvt['id_evt'].'" '.($_POST['cycle_parent_evt']==$tmpEvt['id_evt']?'selected="selected"':'').'>'.html_utf8($tmpEvt['titre_evt']).' - Le '.date('d/m/Y', $tmpEvt['tsp_evt']).' - '.$tmpEvt['nchildren'].' sorties liées</option>';
                            ?>
                        </select>
                    <?php
                    }
                    ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <input type="hidden" name="cycle" id="cycle_none" value="none">
        <?php } ?>
        <br />

        <?php if ($destination) { ?>

            <div class="lft half first">
                <b>Le bus vous déposera :</b><br><br>

                <?php $lds = get_lieux_destination($destination['id'], 'depose'); ?>
                <?php if ($lds) { ?>
                    <div class="small">
                    <p class="note">Dans la mesure du possible et pour limiter les trajets inutiles, <b>utiliser les lieux de dépose et horaires déjà convenus</b> pour cette destination :</p>
                    <ul>
                <?php foreach ($lds as $ld) { ?>
                    <li><?php echo $ld['nom']; ?>, à <?php echo display_time($ld['date']); ?></li>
                    <?php } ?>
                    </ul></div><hr>
                <?php } ?>

                <label for="date_depose">Horaire :</label>
                <input type="text" class="type2" name="lieu[depose][date_depose]" id="date_depose" value="<?php echo inputVal('lieu|depose|date_depose', '');?>" placeholder="aaaa-mm-jj hh:ii:ss"><br>
                <p><small>Cet horaire est important : il permet aux adhérents qui se rendent à la sortie par leurs propres moyens de vous y retrouver.</small></p>

                <?php if ($_POST['lieu']['depose']['id']) {  ?>

                    <br><input type="hidden" name="lieu[id_lieu_depose]" value="<?php echo $_POST['lieu']['depose']['id'];?>">

                    <div class="display_lieu" data-id_lieu="<?php echo $_POST['lieu']['depose']['id'];?>" style="position:relative;">
                        <?php echo display_edit_lieu_link($_POST['lieu']['depose']['id'], inputVal('lieu|depose|nom', '')); ?>
                        <b><?php echo inputVal('lieu|depose|nom', ''); ?></b><br>
                        <div class="map" data-lat="" data-lng=""></div>
                        <?php if ($_POST['lieu']['depose']['ign']) { ?>
                            <div class="ign"><?php echo display_frame_geoportail(inputVal('lieu|depose|ign', ''), 620, 350); ?></div>
                        <?php } ?>
                        <div class="description"><?php echo inputVal('lieu|depose|description', ''); ?></div>
                    </div>
                    <a href="" id="modify_lieu_depose" class="add">Changer de lieu</a>


                <?php } else { ?>
                    <a href="" id="modify_lieu_depose" class="add">Définir un lieu</a>
                <?php } ?>

                <div id="new_lieu_depose"></div>


            </div>
            <div class="lft half last ">
                <b>Le bus vous reprendra :</b><br><br>

                <?php $lds = get_lieux_destination($destination['id'], 'reprise'); ?>
                <?php if ($lds) { ?>
                    <div class="small">
                    <p class="note bbox">Dans la mesure du possible et pour limiter les trajets inutiles, <b>utiliser les lieux de reprise et horaires déjà convenus</b> pour cette destination :</p>
                    <ul>
                        <?php foreach ($lds as $ld) { ?>
                            <li><?php echo $ld['nom']; ?>, à <?php echo display_time($ld['date']); ?></li>
                        <?php } ?>
                    </ul></div><hr>
                <?php } ?>

                <label for="date_reprise">Horaire :</label>
                <input type="text" class="type2" name="lieu[reprise][date_reprise]" id="date_reprise" value="<?php echo inputVal('lieu|reprise|date_reprise', '');?>" placeholder="aaaa-mm-jj hh:ii:ss"><br>

                <p><small>Cet horaire est important : il permet d'éviter les retards au retour.</small></p>
                <?php if ($_POST['lieu']['reprise']['id']) { ?>

                    <br><input type="hidden" name="lieu[id_lieu_reprise]" value="<?php echo $_POST['lieu']['reprise']['id'];?>">

                    <div class="display_lieu" data-id_lieu="<?php echo $_POST['lieu']['reprise']['id'];?>" style="position:relative;">
                        <?php echo display_edit_lieu_link($_POST['lieu']['reprise']['id'], inputVal('lieu|reprise|nom', '')); ?>
                        <b><?php echo inputVal('lieu|reprise|nom', ''); ?></b><br>
                        <div class="map" data-lat="" data-lng=""></div>
                        <?php if ($_POST['lieu']['reprise']['ign']) { ?>
                            <div class="ign"><?php echo display_frame_geoportail(inputVal('lieu|reprise|ign', ''), 620, 350); ?></div>
                        <?php } ?>
                        <div class="description"><?php echo inputVal('lieu|reprise|description', ''); ?></div>
                    </div>
                    <div class="check-nice">
                        <label class="in_front">
                            <input type="checkbox" id="same_as_depose" name="lieu[reprise][same_as_depose]" > Utiliser même lieu que dépose
                        </label><br>
                    </div>
                    <span class="lft"> OU&nbsp;</span>&nbsp;<a href="" id="modify_lieu_reprise" class="add lft">Changer de lieu</a>


                <?php } else { ?>
                    <div class="check-nice">
                <label class="in_front">
                    <input type="checkbox" id="same_as_depose" name="lieu[reprise][same_as_depose]" <?php  if ($_POST['lieu']['reprise']['same_as_depose'] == 'on') echo ' checked="checked" ';  ?> > Utiliser même lieu que dépose
                </label><br>
                    </div>
                    <span class="lft"> OU&nbsp;</span>&nbsp;<a href="" id="modify_lieu_reprise" class="add lft">Autre lieu</a>

                <?php    //echo display_new_lieu_complexe('reprise');
                } ?>
                <div id="new_lieu_reprise"></div>

            </div>
            <br class="clear"><br>

            <?php if( $_POST['lieu']['depose']['id'] && $_POST['lieu']['reprise']['id'] ) { ?>
                <div id="map_dr"></div>
            <?php } ?>

        <?php } else { ?>


        <br />
        <div style="float:left; width:45%; padding:0 20px 5px 0;">
            Ville, et lieu de rendez-vous covoiturage :<br />
            <?php
            inclure('infos-lieu-de-rdv', 'mini');
            ?>
            <input type="text" name="rdv_evt" class="type2" style="width:95%" value="<?php echo inputVal('rdv_evt', '');?>" placeholder="ex : Pralognan la Vanoise, les fontanettes" />
        </div>

        <div style="float:left; width:45%; padding:0 20px 0 0;">
            Précisez sur la carte :<br />
            <?php
            inclure('infos-carte', 'mini');
            ?>
            <input type="button" name="codeAddress" class="type2" style="border-radius:5px; cursor:pointer;" value="Placer le point sur la carte" />
            <input type="hidden" name="lat_evt" value="<?php echo inputVal('lat_evt', '');?>" />
            <input type="hidden" name="long_evt" value="<?php echo inputVal('long_evt', '');?>" />

            <!--
            <input type="hidden" name="codeAddress" class="type2" style="border-radius:5px; cursor:pointer;" value="Placer le point sur la carte" />
            <input type="hidden" name="lat_evt" value="45.7337532" />
            <input type="hidden" name="long_evt" value="4.9092352" />
            -->
        </div>
        <br style="clear:both" />

        <div id="place_finder_error" class="erreur" style="display:none"></div>
        <div id="map-creersortie"></div>

        <br />
        <div style="width:45%; padding-right:3%; float:left">
            Date et heure de RDV / covoiturage :<br />
            <input type="text" name="tsp_evt_day" class="type2" style="width:45%; float:left;" value="<?php echo inputVal('tsp_evt_day', '');?>" placeholder="jj/mm/aaaa" />
            <input type="text" name="tsp_evt_hour" class="type2" style="width:45%" value="<?php echo inputVal('tsp_evt_hour', '');?>" placeholder="hh:ii" />
        </div>

        <div style="width:50%; float:left">
            Date de fin de la sortie :<br />
            <input type="text" name="tsp_end_evt_day" class="type2" style="width:45%; float:left;" value="<?php echo inputVal('tsp_end_evt_day', '');?>" placeholder="jj/mm/aaaa" />
            <!--
							<input type="text" name="tsp_end_evt_hour" class="type2" style="width:45%;" value="<?php echo inputVal('tsp_end_evt_hour', '');?>" placeholder="hh:ii" />
							-->
            <input type="button" value="même jour ?" class="nice" onclick="$('input[name=tsp_end_evt_day]').val($('input[name=tsp_evt_day]').val())" style="margin-top:7px" />
        </div>

        <?php } ?>

        <br style="clear:both" />
        <br />
    </div>


    <h2 class="trigger-h2">Tarif :</h2>
    <div class="trigger-me check-nice" style="padding-right:20px">

        <div style="float:left; padding:0 20px 5px 0;">
            Tarif :<br />
            <input type="text" name="tarif_evt" class="type2" value="<?php echo inputVal('tarif_evt', '');?>" placeholder="ex : 35.50 " />€
        </div>
        <br style="clear:both" />

        <div style="float:left; padding:0 20px 5px 0;">
            <input type="checkbox" name="cb_evt" <?php if($_POST['cb_evt']==1 OR $_POST['cb_evt']=='on') echo 'checked="checked"'; ?>/> paiement en ligne possible
        </div>
        <br style="clear:both" />

        <?php
        inclure('infos-tarifs', 'mini');
        ?>
        Détails des frais :
        <textarea name="tarif_detail" class="type2" style="width:95%; min-height:80px" placeholder="Ex : Remontées mécaniques 12€, Péage 11.50€, Car 7€, Vin chaud 5€ = somme 35.50"><?php echo inputVal('tarif_detail', '');?></textarea>
        <br>

        <?php
        inclure('infos-resto', 'mini');
        ?>
        <label><input type="checkbox" name="repas_restaurant" id="repas_restaurant" <?php if($_POST['repas_restaurant']==1 OR $_POST['repas_restaurant']=='on') echo 'checked="checked"'; ?> >&nbsp;Repas au restaurant possible</label>
        <div id="tarif_restaurant">
            Tarif du repas :<br />
            <input type="text" name="tarif_restaurant" class="type2" value="<?php echo inputVal('tarif_restaurant', '');?>" placeholder="ex : 55.90 " />€
        </div>

        <br />
    </div>

    <h2 class="trigger-h2">Inscriptions :</h2>
    <div class="trigger-me" style="padding-right:20px">

        <!-- si on rensigne une suite de cycle, cette section est blqoquée  -->
        <div id="inscriptions-on" style="display:<?php echo $suiteDeCycle?'none':'block';?>">


            Nombre maximum de personnes sur cette sortie (encadrement compris) :<br />
            <p class="mini">
                <input onblur="if($(this).val()) $(this).val(parseInt($(this).val()) -0);" type="text" name="ngens_max_evt" class="type2" style="width:40px; text-align:center" value="<?php echo inputVal('ngens_max_evt', '');?>" placeholder=" ex : 8" />
                personnes affichées. Ceci n'influence <u>pas</u> le nombre d'inscriptions possibles en ligne.
            </p>
            <br style="clear:both" />

            <?php if (!$destination) { ?>
            <div style="width:45%; padding-right:3%; float:left">
                Les inscriptions démarrent :<br />
                <input onblur="if($(this).val()) $(this).val(parseInt($(this).val()) -0);" type="text" name="join_start_evt_days" class="type2" style="width:40px; text-align:center" value="<?php echo inputVal('join_start_evt_days', '');?>" placeholder=" > 2" />
								<span class="mini">
									jours avant la sortie.
								</span>
            </div>
            <?php } ?>

            <div style="width:50%; float:left">
                Inscriptions maximum via le formulaire internet :<br />
                <input onblur="if($(this).val()) $(this).val(parseInt($(this).val()) -0);" type="text" name="join_max_evt" class="type2" style="width:40px; text-align:center" value="<?php echo inputVal('join_max_evt', '');?>" placeholder="ex : 5" />
                <span class="mini">
                    inscriptions en ligne max.
                </span>
            </div>

            <?php if ($destination) { ?>
                <div style="width:100%;clear:both;">
                <br><p><b>Dates</b> :</p>
                    <ul class="nice-list">
                        <li class="wide">Ouverture : <?php echo display_jour($destination['inscription_ouverture']).' à '. display_time($destination['inscription_ouverture']); ?></li>
                        <li class="wide">Fermeture : <?php echo display_jour($destination['inscription_fin']).' à '. display_time($destination['inscription_fin']); ?></li>
                    </ul>
                </div>
            <?php } ?>

        </div>

        <!-- message d'info -->
        <div id="inscriptions-off" style="display:<?php echo $suiteDeCycle?'block':'none';?>">
            <p class="alerte">Les inscriptions à cette sortie sont gérées sur la première sortie du cycle dont elle fait partie.</p>
        </div>

        <br style="clear:both" />
    </div>


    <h2 class="trigger-h2">Difficulté / matériel :</h2>
    <div class="trigger-me">

        Difficulté, niveau : 50 caractères max.<br />
        <input type="text" name="difficulte_evt" class="type2" value="<?php echo inputVal('difficulte_evt', '');?>" placeholder="ex : PD, 5d+, exposé..." />

        <br />
        Dénivelé positif :<br />
        <input type="text" name="denivele_evt" class="type2" value="<?php echo inputVal('denivele_evt', '');?>" placeholder="ex : 1200 (m)" />m.

        <br />
        Distance :<br />
        <input type="text" name="distance_evt" class="type2" value="<?php echo inputVal('distance_evt', '');?>" placeholder="ex : 13.50 (km)" />km.

        <br />
        <div style="float:right; padding-right:20px;">
            <select>
                <option value="">- Listes prédéfinies </option>
                <option value="Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos.  SANS OUBLIER : DVA, sonde, pelle (et raquettes) qui peuvent être prêtés par le CAF contre participation aux frais.">Ski alpinisme</option>
                <option value="Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos.  SANS OUBLIER : DVA, sonde, pelle (et raquettes) qui peuvent être prêtés par le CAF contre participation aux frais.">Rando raquettes</option>
                <option value="Cartes vitale, Mutuelle, assurance et CAF. Sac à dos adapté à la randonnée et suffisamment grand pour contenir les vêtements de l’activité extérieure : fourrure polaire, goretex ou équivalent, cape de pluie, sur-sac, gants, bonnet ou chapeau, pique-nique,  boisson, lunettes de soleil et crème solaire. Chaussures de montagne avec une semelle crantée, bâtons, chaussures de rechange pour la voiture (avec sac plastique). Espèces ou chèque pour les frais de covoiturage.">Randonnée Montagne</option>
                <option value="Sac de couchage, tapis de sol, lampe de poche, briquet, gamelles, repas, tente">Bivouac</option>
                <option value="Chaussures avec des semelles adhérentes, casque, baudrier, chaussons, longe de 8mm, 2 mousquetons à vis, un tube d'assurage, 2 machards, sac  dos petit ou moyen, coupe vent, 2l d'eau, vivres de courses, lampe de poche, téléphone portable chargé et allumé, lunettes de soleil. En hiver : gants, bonnet.">Grandes voies </option>
                <option value="Casque, baudrier, longe de via ferrata, gants de jardinage, vêtements de sport, petit sac à dos, 1-2 litres d'eau, pique nique">Via ferrata </option>
                <option value="Vêtements de sport sales, pull en laine, bottes ou chaussures de marche, gants Mappa, 1 litre d'eau, pique nique, 4 piles rondes type LR 6 (vous les récupérez à la fin de la sortie)">Spéléo </option>
                <option value="Sac de couchage (avec sac à viande), tapis de sol, popote (assiette + bol), gourde, couverts, lampe de poche (frontale c'est mieux), petit nécessaire de toilette">Camping </option>
                <option value="Baudrier, chaussons d'escalade, casque">Escalade en falaise </option>
                <option value="Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos">Affaires personnelles </option>
                <option value="Piolet, casque, baudrier, crampons avec anti-bottes (impérativement), 1 mousquetons à vis, 2 cordassons ou ficelous (pour auto-assurance), gourde, sac à dos (40-50 litres), chaussures à semelles rigides (cuirs ou coques), lampe frontale, lunettes de glacier. VETEMENTS : système 3 couches : veste, et pantalon gore-tex ou équivalent, maillot en carline, fourrure polaire, guêtres, gants (prévoir une paire de rechange), cagoule ou bonnet ou serre-tête. ">Alpinisme</option>
                <option value="Une paire de piolets techniques, une paire de crampons techniques, grosses chaussures à tiges rigides, 2 voire 3 paires de gants (dont imperméables), veste imperméable, vêtements chauds, bonnet, thé chaud...">Cascade de glace </option>
                <option value="Casque, gants et protections, chaussures, eau et nourriture de course, une chambre à air, une pompe, démonte-pneus, un multi-tool, une attache rapide de chaine, une patte de dérailleur, et un VTT en bon état de fonctionnement: freins, pneus, transmission, serrages... Et savoir réparer les petites pannes!">Vélo de Montagne</option>
            </select>
            <input type="button" value="appliquer" class="nice" id="predefinitions-matos-submit" />

            <!-- ****************** Listes de matériel -->
            <script type="text/javascript">
                // bind
                $().ready(function() {
                    $('#predefinitions-matos-submit').bind('click', function(){
                        var ta = $('textarea[name=matos_evt]');
                        var go = true;
                        // confirmer réécriture
                        if(ta.val() != '')	go= confirm("Ceci va effacer le contenu actuel du champ 'Matériel'. Continuer ?");
                        if(go){
                            ta.val($(this).siblings('select').val().replace(/\*/g, "\n"));
                        }
                    });
                });
            </script>
            <!-- ****************** -->

        </div>
        Matériel nécessaire :
        <textarea name="matos_evt" class="type2" style="width:95%; min-height:80px" placeholder="ex : 10 Dégaines, 1 Baudrier, 1 Maillot de bain..."><?php echo inputVal('matos_evt', '');?></textarea>
        <?php
        inclure('infos-matos', 'mini');
        ?>
    </div>


    <h2 class="trigger-h2">Itinéraire :</h2>
    <div class="trigger-me">
        <textarea name="itineraire" class="type2" style="width:95%; min-height:80px"><?php echo stripslashes($_POST['itineraire']);?></textarea>
    </div>


    <h2 class="trigger-h2">Description complète :</h2>
    <div class="trigger-me">
        <p>
            Entrez ci-dessous toutes les informations qui ne figurent pas dans le formulaire.
            N'hésitez pas à mettre un maximum de détails, cet élément formera le corps de la page dédiée à cette sortie.
        </p>
        <?php include (INCLUDES.'help'.DS.'tinymce.php'); ?>
        <textarea name="description_evt" style="width:99%"><?php echo stripslashes($_POST['description_evt']);?></textarea>
    </div>

    <br />
    <br />
    <div style="text-align:center">
        <a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
            <span class="bleucaf">&gt;</span>
            ENREGISTRER ET DEMANDER LA PUBLICATION
        </a>
    </div>
</form>


<br class="clear"/><br />
<br /><br />

<!-- ****************** -->
<!-- tinyMCE -->
<script language="javascript" type="text/javascript" src="tools/tinymce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.webkitresize.min.js"></script><!-- debug handles -->
<script language="javascript" type="text/javascript">
    tinyMCE.init({
        // debug handles
        init_instance_callback: function () { $(".mceIframeContainer iframe").webkitimageresize().webkittableresize().webkittdresize(); },

        height : 500,
        theme : "advanced",
        mode : "exact",
        language : "fr",
        elements : "description_evt",
        entity_encoding : "raw",
        plugins : "safari,spellchecker,style,layer,table,save,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
        remove_linebreaks : false,
        file_browser_callback : 'userfilebrowser',

        // forecolor,backcolor,|,
        theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,|,removeformat,cleanup,code",
        theme_advanced_buttons2 : "undo,redo,|,cut,copy,paste,pastetext,|,bullist,numlist,|,link,unlink,image,media,|,charmap,sub,sup",
        theme_advanced_buttons3 : "tablecontrols,|,hr,visualaid,|,fullscreen",

        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,

        document_base_url : '<?php echo $p_racine;?>',

        content_css : "<?php echo $p_racine;?>css/base.css,<?php echo $p_racine;?>css/style1.css,<?php echo $p_racine;?>fonts/stylesheet.css",
        body_id : "bodytinymce_user",
        body_class : "description_evt",
        theme_advanced_styles : "<?php echo $p_tiny_theme_advanced_styles; ?>",

        relative_urls : true,
        convert_urls : false,
        remove_script_host : false,
        theme_advanced_blockformats : "p,h3,h4,h5,ul,li",

        theme_advanced_resize_horizontal : false,
        theme_advanced_resizing : true,
        apply_source_formatting : true,
        spellchecker_languages : "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv"

        // onchange_callback : "onchange"
    });
    function userfilebrowser(field_name, url, type, win) {
        // alert("Field_Name: " + field_name + "nURL: " + url + "nType: " + type + "nWin: " + win); // debug/testing
        tinyMCE.activeEditor.windowManager.open({
            file : 'includes/user-file-browser.php?type='+type,
            title : 'Mini-File Browser',
            width : 800,  // Your dimensions may differ - toy around with them!
            height : 500,
            resizable : "yes",
            inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
            close_previous : "no"
        }, {
            window : win,
            input : field_name
        });

        return false;
    }
</script>
<!-- /tinyMCE -->

<!-- ****************** sélection des adhérents -->
<script type="text/javascript">
    // un même user ne peut être à la fois Encadrant et bénévole
    function switchUserJoin(checkbox){
        var typeTab=new Array('encadrant', 'coencadrant');
        var tab=checkbox.attr('id').split('-');
        var type=tab[0];
        var id=tab[1];
        // console.log('conseideration de input : '+tab+'-'+id);
        // pour chque type (ensemble de chkbox)
        for(i=0; i<typeTab.length; i++){
            tmpType=typeTab[i];
            // on ne s'ninteresse qu'aux autres blocs de types, pas celui qu'on etudie
            if(type!=tmpType){
                // en fonction de l'état de la checkbox : // case visée cochée : masquage de ses freres dans les autres cases
                if(checkbox.is(':checked')){
                    $('#'+tmpType+'-'+id).attr('disabled', 'disabled')
                        .parents('label').addClass('off');
                }
                // case visée décochée : affichage de ses freres dans les autres cases
                else{
                    $('#'+tmpType+'-'+id).removeAttr('disabled')
                        .parents('label').removeClass('off');
                }
            }
        }
        // effet visuel : déplacé sur 'tout le site'
        // if(checkbox.is(':checked'))	checkbox.parents('label').addClass('up').removeClass('down');
        // else						checkbox.parents('label').addClass('down').removeClass('up');
    }

    function toggleTarifRestaurant() {
        if ($('#repas_restaurant').prop('checked')) {
            $('#tarif_restaurant').show();
        } else {
            $('#tarif_restaurant input').val('');
            $('#tarif_restaurant').hide();
        }
    }

    // bind + onready
    $().ready(function() {
        // au chargement de la page
        $('#individus input:checked').each(function(){
            switchUserJoin($(this));
        });
        toggleTarifRestaurant();
        // au clic
        $('#individus input').bind('click change', function(){
            switchUserJoin($(this));
        });
        $('#repas_restaurant').bind('click change', function(){
            toggleTarifRestaurant();
        });
    });

</script>

<!-- ****************** scripts osm -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css"
    integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
    crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js"
   integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
   crossorigin=""></script>
<script type="text/javascript" src="js/osm-organiser.js"></script>
<!-- ****************** // osm-->

<?php if ($destination) { ?>
<script type="text/javascript">


    $(document).ready(function(){
        // datepicker
        var dayNamesMin = ["Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ];
        var    monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Spetembre", "Octobre", "Novembre", "Décembre" ];

        function setDay(elt, day, hour) {
            from = day.split("-");
            if (hour != undefined)
                fromH = hour.split(":");
            else fromH = new Array(0,0,0);
            daymin = new Date(from[0], from[1] - 1, from[2], fromH[0], fromH[1], fromH[2]);
            daymax = new Date(from[0], from[1] - 1, from[2], '23', '59', '0');
            elt.datepicker( "option", "maxDate", daymax )
                .datetimepicker( "option", "maxDateTime", daymax )
                .datepicker( "option", "minDate", daymin )
                .datetimepicker( "option", "minDateTime", daymin );
            return false;
        }

        $('#date_depose').datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            firstDay: 1,
            closeText:'Ok',
            timeText:'',
            hourText:'Heures',
            minuteText:'Minutes',
            secondText:'Secondes',
            stepMinute:'5',
            dayNamesMin: dayNamesMin,
            monthNames: monthNames
        });

        $('#date_reprise').datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss',
            firstDay: 1,
            closeText:'Ok',
            timeText:'',
            hourText:'Heures',
            minuteText:'Minutes',
            secondText:'Secondes',
            stepMinute:'5',
            dayNamesMin: dayNamesMin,
            monthNames: monthNames
        });

        <?php
            /* TODO */
            // Mis en place : test sur l'heure de destination / sortie
            // => todo : le premier horaire (dépose), devrait être plus tardif que le dernier horaire de ramassage en bus
        ?>
        setDay( $('#date_depose'), '<?php $expDate = explode(' ',$destination['date'] ); echo $expDate[0]; ?>', '<?php echo $expDate[1]; ?>');
        <?php if ($expDate[0] == $destination['date_fin']) { ?>
            setDay( $('#date_reprise'), '<?php echo $expDate[0]; ?>', '<?php echo $expDate[1]; ?>');
        <?php } else { ?>
            setDay( $('#date_reprise'), '<?php echo $destination['date_fin']; ?>');
        <?php } ?>

    });

</script>

    <script>
        var previous_lieux_reprise = '<?php echo display_previous_lieux('reprise', $destination['id']); ?>';
        var new_lieu_reprise = '<?php echo display_new_lieu_complexe('reprise', true); ?>';
        $('#same_as_depose').click(function(e){
            do_action_modify();
        });

        function do_action_modify() {
            $('#new_lieu_reprise').html('');
            maps['lieu_reprise']=false;
            $('#modify_lieu_reprise').show();
        }
        $('#modify_lieu_reprise').click(function(e){
            e.preventDefault();
            $('#same_as_depose').attr('checked', false).parent('label').removeClass('up').addClass('down');
            $('#new_lieu_reprise').html('<a href="" id="cancel_lieu_reprise" class="cancel">Annuler le changement de lieu</a><br>'+previous_lieux_reprise+new_lieu_reprise);
            initialiserBloc($('#lieu_reprise'));
            $('#modify_lieu_reprise').hide();
            $('#cancel_lieu_reprise').on('click', function(e){
                e.preventDefault();
                do_action_modify();
                return false;
            });
            return false;
        });
    </script>
    <script>
        var previous_lieux_depose = '<?php echo display_previous_lieux('depose', $destination['id']); ?>';
        var new_lieu_depose = '<?php echo display_new_lieu_complexe('depose', true); ?>';
        $('#modify_lieu_depose').click(function(e){
            e.preventDefault();
            $('#new_lieu_depose').html('<a href="" id="cancel_lieu_depose" class="cancel">Annuler le changement de lieu</a><br>'+previous_lieux_depose+new_lieu_depose);
            initialiserBloc($('#lieu_depose'));
            $('#modify_lieu_depose').hide();
            $('#cancel_lieu_depose').on('click', function(e){
                e.preventDefault();
                $('#new_lieu_depose').html('');
                $('#modify_lieu_depose').show();
                return false;
            });
            return false;
        });
        <?php if (isset($_POST['lieu']['depose']['use_existant'])) { ?>
        $('#modify_lieu_depose').trigger('click'); $('#modify_lieu_depose').hide();
        <?php } ?>
    </script>
<?php } ?>
