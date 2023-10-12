<?php

use App\Legacy\LegacyContainer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

?>
<form action="<?php echo $versCettePage; ?>" method="post" style="overflow:hidden">
    <input type="hidden" name="operation" value="<?php echo isset($id_evt_to_update) && $id_evt_to_update ? 'evt_update' : 'evt_create' ?>" />
    <input type="hidden" name="id_evt_to_update" value="<?php echo (int) $id_evt_to_update; ?>" />

    <?php
    // masque certaines option si cet evt est une suite de cycle
    if (isset($_POST['cycle_parent_evt']) && $_POST['cycle_parent_evt'] && 'child' == $_POST['cycle']) {
        $suiteDeCycle = true;
    }

    // message d'erreur
    if (isset($_POST['operation']) && isset($errTab) && count($errTab) > 0) {
        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul>';
        echo '<b>Attention :</b> Le marqueur rouge sur la carte a peut-être été déplacé !';
        echo '</div>';
    }
    // message d'info : si c'est une modification de sortie
    if (isset($_POST['operation']) && 'evt_update' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info"><img src="/img/base/tick.png" alt="" title="" /> Mise à jour effectuée à '.date('H:i:s', time()).'. <b>Important :</b> cette sortie doit à présent être validée par un responsable pour être publiée sur le site.<a href="/profil/sorties/self.html" title="">&gt; Retourner à la liste de mes sorties</a></p>';
    }
?>

    <br class="clear">


    <!-- liste des commissions où poster l'evt -->
    <div style="float:left; padding:0 20px 10px 0">

        Sortie liée à la commission :<br />
        <?php
    // liens dans le cas de la creation d'une sortie
    if (!$id_evt_to_update) {
        ?>
            <div class="faux-select-wrapper" id="choix-commission">
                <div class="faux-select">
                    <?php
                foreach ($comTab as $code => $data) {
                    if (allowed('evt_create', 'commission:'.$code)) {
                        echo '<a href="/creer-une-sortie/'.html_utf8($code).'.html" title="" class="'.($code == $current_commission ? 'up' : '').'">'.html_utf8($data['title_commission']).'</a> ';
                    }
                } ?>
                </div>
            </div>
            <?php
            echo '<input type="hidden" name="commission_evt" value="'.(int) $comTab[$current_commission]['id_commission'].'" />';
    }
    // juste l'info  et la variable dans le cas d'une modification de sortie existante
    else {
        echo '<b>'.$comTab[$current_commission]['title_commission'].'</b><input type="hidden" name="commission_evt" value="'.(int) $_POST['commission_evt'].'" />';
    }
?>
    </div>

    <div style="float:right;margin-right:20px;" >
        Titre :<br />
        <input style="width:320px;" type="text" name="titre_evt" class="type1" value="<?php echo inputVal('titre_evt', ''); ?>" placeholder="ex : Escalade du Grand Som" />
    </div>

    <?php $groupes = get_groupes($comTab[$current_commission]['id_commission'], true); ?>

    <?php if (count($groupes) > 0) { ?>
        <select name="id_groupe" class="type1" style="width:95%">
            <option value="">- Précisez le groupe concerné par cette sortie (facultatif) :</option>
            <?php
    // articles liés aux commissions
    foreach ($groupes as $code => $groupe) {
        echo '<option value="'.$groupe['id'].'" '.($_POST['id_groupe'] == $groupe['id'] ? 'selected="selected"' : '').'>Groupe : '.html_utf8($groupe['nom']).' &raquo;</option>';
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
        $encadrants = isset($_POST['encadrants']) && is_array($_POST['encadrants']) ? $_POST['encadrants'] : [];
if (!count($encadrantsTab)) {
    // echo '<p class="erreur">Erreur : aucun adhérent n\'est déclaré <b>encadrant</b> pour cette commission. Vous ne pourrez pas créer de sortie...</p>';
    echo '<p class="info">Aucun adhérent n\'est déclaré <b>encadrant</b> pour cette commission.</p>';
}
foreach ($encadrantsTab as $encadrant) {
    echo '<label for="encadrant-'.$encadrant['id_user'].'">
									<input type="checkbox" '.(in_array($encadrant['id_user'], $encadrants, true) ? 'checked="checked"' : '').' name="encadrants[]" value="'.$encadrant['id_user'].'" id="encadrant-'.$encadrant['id_user'].'" />
									'.$encadrant['firstname_user'].'
									'.$encadrant['lastname_user'].'
									<a class="fancyframe" href="/includer.php?p=includes/fiche-profil.php&amp;id_user='.$encadrant['id_user'].'" title="Voir la fiche"><img src="/img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
								</label>';
}
?>
            <br style="clear:both" />
        </div>

        <h2 class="trigger-h2">Stagiaire(s) :</h2>
        <div class="trigger-me check-nice">
            <?php
$stagiaires = isset($_POST['stagiaires']) && is_array($_POST['stagiaires']) ? $_POST['stagiaires'] : [];
if (!count($stagiairesTab)) {
    echo '<p class="info">Aucun adhérent n\'est déclaré <b>stagiaire</b> pour cette commission.</p>';
}
foreach ($stagiairesTab as $stagiaire) {
    echo '<label for="stagiaire-'.$stagiaire['id_user'].'">
                    <input type="checkbox" '.(in_array($stagiaire['id_user'], $stagiaires, true) ? 'checked="checked"' : '').' name="stagiaires[]" value="'.$stagiaire['id_user'].'" id="encadrant-'.$stagiaire['id_user'].'" />
                    '.$stagiaire['firstname_user'].'
                    '.$stagiaire['lastname_user'].'
                    <a class="fancyframe" href="/includer.php?p=includes/fiche-profil.php&amp;id_user='.$stagiaire['id_user'].'" title="Voir la fiche"><img src="/img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
                </label>';
}
?>
            <br style="clear:both" />
        </div>

        <h2 class="trigger-h2">Co-Encadrant(s) :</h2>
        <div class="trigger-me check-nice">
            <?php
$coencadrants = isset($_POST['coencadrants']) && is_array($_POST['coencadrants']) ? $_POST['coencadrants'] : [];
if (!count($coencadrantsTab)) {
    // echo '<p class="info">Aucun adhérent n\'est déclaré <b>encadrant</b> pour cette commission.</p>';echo '<p class="erreur">Erreur : aucun adhérent n\'est déclaré <b>coencadrant</b> pour cette commission. Vous ne pourrez pas créer de sortie...</p>';
    echo '<p class="info">Aucun adhérent n\'est déclaré <b>co-encadrant</b> pour cette commission.</p>';
}
foreach ($coencadrantsTab as $coencadrant) {
    echo '<label for="coencadrant-'.$coencadrant['id_user'].'">
									<input type="checkbox" '.(in_array($coencadrant['id_user'], $coencadrants, true) ? 'checked="checked"' : '').' name="coencadrants[]" value="'.$coencadrant['id_user'].'" id="coencadrant-'.$coencadrant['id_user'].'" />
									'.$coencadrant['firstname_user'].'
									'.$coencadrant['lastname_user'].'
									<a class="fancyframe" href="/includer.php?p=includes/fiche-profil.php&amp;id_user='.$coencadrant['id_user'].'" title="Voir la fiche"><img src="/img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
								</label>';
}
?>
            <br style="clear:both" />
        </div>

        <h2 class="trigger-h2">Bénévoles <?php if ($id_evt_to_update) {
            echo '(modifiable dans la gestion des inscrits) ';
        } ?>:</h2>
        <div class="trigger-me check-nice">
            <?php
        // modification possible seulement en cas de creation d'une nouvelle sortie
        if (!$id_evt_to_update) {
            $benevoles = isset($_POST['benevoles']) && is_array($_POST['benevoles']) ? $_POST['benevoles'] : [];
            if (!count($benevolesTab)) {
                echo '<p class="info">Aucun adhérent n\'est déclaré <b>bénévole</b> pour cette commission ou cette sortie.</p>';
            }
            foreach ($benevolesTab as $benevole) {
                echo '<label for="benevole-'.$benevole['id_user'].'">
									<input '.($id_evt_to_update ? 'disabled' : '').' type="checkbox" '.(in_array($benevole['id_user'], $benevoles, true) ? 'checked="checked"' : '').' name="benevoles[]" value="'.$benevole['id_user'].'" id="benevole-'.$benevole['id_user'].'" />
									'.$benevole['firstname_user'].'
									'.$benevole['lastname_user'].'
									<a class="fancyframe" href="/includer.php?p=includes/fiche-profil.php&amp;id_user='.$benevole['id_user'].'" title="Voir la fiche"><img src="/img/base/bullet_toggle_plus.png" alt="I" title="" /></a>
								</label>';
            }
            echo '<br style="clear:both" />';
        }
?>

            <label for="need_benevoles_evt" style="margin-top:15px; display:block; float:none; width:93%; background-color:white; background-position:8px 5px; padding-left:10px; padding-top:5px; box-shadow:0 0 15px -8px black;">
                <input type="checkbox" class="custom" name="need_benevoles_evt" id="need_benevoles_evt" <?php if (!empty($_POST['need_benevoles_evt']) && in_array($_POST['need_benevoles_evt'], [1, 'on', '1'])) {
                    echo 'checked="checked"';
                }?> /> Afficher un encart &laquo;Nous aurions besoin de bénévoles&raquo; sur la page de la sortie ?
            </label>

            <br style="clear:both" />
        </div>
    </div>

    <h2 class="clear trigger-h2">Informations :</h2>
    <div class="trigger-me">

        <div>
            Massif :<br />
            <input style="width:95%;" type="text" name="massif_evt" class="type2" value="<?php echo inputVal('massif_evt', ''); ?>" placeholder="ex : Chartreuse" />
        </div>

        <input type="hidden" name="cycle" id="cycle_none" value="none" />

        <br />
        <div style="float:left; width:45%; padding:0 20px 5px 0;">
            Ville, et lieu de rendez-vous covoiturage :<br />
            <?php
                inclure('infos-lieu-de-rdv', 'mini');
?>
            <input type="text" name="rdv_evt" class="type2" style="width:95%" value="<?php echo inputVal('rdv_evt', ''); ?>" placeholder="ex : Pralognan la Vanoise, les fontanettes" />
        </div>

        <div style="float:left; width:45%; padding:0 20px 0 0;">
            Précisez sur la carte :<br />
            <?php
inclure('infos-carte', 'mini');
?>
            <input type="button" name="codeAddress" class="type2" style="border-radius:5px; cursor:pointer;" value="Placer le point sur la carte" />
            <input type="hidden" name="lat_evt" value="<?php echo inputVal('lat_evt', ''); ?>" />
            <input type="hidden" name="long_evt" value="<?php echo inputVal('long_evt', ''); ?>" />

        </div>
        <br style="clear:both" />

        <div id="place_finder_error" class="erreur" style="display:none"></div>
        <div id="map-creersortie"></div>

        <br />
        <div style="width:45%; padding-right:3%; float:left">
            Date et heure de RDV / covoiturage :<br />
            <input type="text" name="tsp_evt_day" class="type2" style="width:45%; float:left;" value="<?php echo inputVal('tsp_evt_day', ''); ?>" placeholder="jj/mm/aaaa" />
            <input type="text" name="tsp_evt_hour" class="type2" style="width:45%" value="<?php echo inputVal('tsp_evt_hour', ''); ?>" placeholder="hh:ii" />
        </div>

        <div style="width:50%; float:left">
            Date de fin de la sortie :<br />
            <input type="text" name="tsp_end_evt_day" class="type2" style="width:45%; float:left;" value="<?php echo inputVal('tsp_end_evt_day', ''); ?>" placeholder="jj/mm/aaaa" />
            <!--
							<input type="text" name="tsp_end_evt_hour" class="type2" style="width:45%;" value="<?php echo inputVal('tsp_end_evt_hour', ''); ?>" placeholder="hh:ii" />
							-->
            <input type="button" value="même jour ?" class="nice" onclick="$('input[name=tsp_end_evt_day]').val($('input[name=tsp_evt_day]').val())" style="margin-top:7px" />
        </div>


        <br style="clear:both" />
        <br />
    </div>


    <h2 class="trigger-h2">Tarif :</h2>
    <div class="trigger-me check-nice" style="padding-right:20px">

        <div style="float:left; padding:0 20px 5px 0;">
            Tarif :<br />
            <input type="text" name="tarif_evt" class="type2" value="<?php echo inputVal('tarif_evt', ''); ?>" placeholder="ex : 35.50 " />€
        </div>
        <br style="clear:both" />

        <?php
        inclure('infos-tarifs', 'mini');
?>
        Détails des frais :
        <textarea name="tarif_detail" class="type2" style="width:95%; min-height:80px" placeholder="Ex : Remontées mécaniques 12€, Péage 11.50€, Car 7€, Vin chaud 5€ = somme 35.50"><?php echo inputVal('tarif_detail', ''); ?></textarea>
        <br />
    </div>

    <h2 class="trigger-h2">Inscriptions :</h2>
    <div class="trigger-me" style="padding-right:20px">

        <!-- si on rensigne une suite de cycle, cette section est blqoquée  -->
        <div id="inscriptions-on" style="display:<?php echo isset($suiteDeCycle) && $suiteDeCycle ? 'none' : 'block' ?>">


            Nombre maximum de personnes sur cette sortie (encadrement compris) :<br />
            <p class="mini">
                <input onblur="if($(this).val()) $(this).val(parseInt($(this).val()) -0);" type="text" name="ngens_max_evt" class="type2" style="width:40px; text-align:center" value="<?php echo inputVal('ngens_max_evt', ''); ?>" placeholder=" ex : 8" />
                personnes affichées. Ceci n'influence <u>pas</u> le nombre d'inscriptions possibles en ligne.
            </p>
            <br style="clear:both" />

            <div style="width:45%; padding-right:3%; float:left">
                Les inscriptions démarrent :<br />
                <input onblur="if($(this).val()) $(this).val(parseInt($(this).val()) -0);" type="text" name="join_start_evt_days" class="type2" style="width:40px; text-align:center" value="<?php echo inputVal('join_start_evt_days', ''); ?>" placeholder=" > 2" />
								<span class="mini">
									jours avant la sortie.
								</span>
            </div>

            <div style="width:50%; float:left">
                Inscriptions maximum via le formulaire internet :<br />
                <input onblur="if($(this).val()) $(this).val(parseInt($(this).val()) -0);" type="text" name="join_max_evt" class="type2" style="width:40px; text-align:center" value="<?php echo inputVal('join_max_evt', ''); ?>" placeholder="ex : 5" />
                <span class="mini">
                    inscriptions en ligne max.
                </span>
            </div>

        </div>

        <!-- message d'info -->
        <div id="inscriptions-off" style="display:<?php echo isset($suiteDeCycle) && $suiteDeCycle ? 'block' : 'none' ?>">
            <p class="alerte">Les inscriptions à cette sortie sont gérées sur la première sortie du cycle dont elle fait partie.</p>
        </div>

        <br style="clear:both" />
    </div>


    <h2 class="trigger-h2">Difficulté / matériel :</h2>
    <div class="trigger-me">

        Difficulté, niveau : 50 caractères max.<br />
        <input type="text" name="difficulte_evt" class="type2" value="<?php echo inputVal('difficulte_evt', ''); ?>" placeholder="ex : PD, 5d+, exposé..." />

        <br />
        Dénivelé positif :<br />
        <input type="text" name="denivele_evt" class="type2" value="<?php echo inputVal('denivele_evt', ''); ?>" placeholder="ex : 1200 (m)" />m.

        <br />
        Distance :<br />
        <input type="text" name="distance_evt" class="type2" value="<?php echo inputVal('distance_evt', ''); ?>" placeholder="ex : 13.50 (km)" />km.

        <br />
        <div style="float:right; padding-right:20px;">
            <select>
                <option value="">- Listes prédéfinies </option>
                <option value="Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos.  SANS OUBLIER : DVA, sonde, pelle qui peuvent être prêtés par le CAF contre participation aux frais, skis, bâtons, peaux, couteaux. Casque conseillé">Ski alpinisme</option>
                <option value="Carte vitale, Mutuelle, assurance et CAF. Sac à dos adapté à la randonnée raquettes et suffisamment grand pour contenir les vêtements de l’activité extérieure : fourrure polaire, goretex ou équivalent, bonnet, gants, lunettes de soleil, crème solaire, guêtres. Pique-nique et boisson (thermos ou gourde ou autre). Raquettes adaptées à vos chaussures et réglées au préalable / Bâtons / Kit de sécurité : DVA, pelle et sonde qui peut être prêté par le CAF contre participation aux frais et un chèque de caution de 350 €. Prévoir un jeu de piles de rechange pour le DVA. Chaussures de rechange pour la voiture (avec sac plastique). Pour le covoiturage : espèces ou chèque ou autre moyen comme Lydia.">Rando raquettes</option>
                <option value="Cartes vitale, Mutuelle, assurance et CAF. Sac à dos adapté à la randonnée et suffisamment grand pour contenir les vêtements de l’activité extérieure : fourrure polaire, goretex ou équivalent, cape de pluie, sur-sac, gants, bonnet ou chapeau, pique-nique,  boisson, lunettes de soleil et crème solaire. Chaussures de montagne avec une semelle crantée, bâtons, chaussures de rechange pour la voiture (avec sac plastique). Espèces ou chèque pour les frais de covoiturage.">Randonnée Montagne</option>
                <option value="Sac de couchage, tapis de sol, lampe de poche, briquet, gamelles, repas, tente">Bivouac</option>
                <option value="Chaussures avec des semelles adhérentes, casque, baudrier, chaussons, longe de 8mm, 2 mousquetons à vis, un tube d'assurage, 2 machards, sac  dos petit ou moyen, coupe vent, 2l d'eau, vivres de courses, lampe de poche, téléphone portable chargé et allumé, lunettes de soleil. En hiver : gants, bonnet.">Grandes voies </option>
                <option value="Casque, baudrier, longe de via ferrata, gants de jardinage, vêtements de sport, petit sac à dos, 1-2 litres d'eau, pique nique">Via ferrata </option>
                <option value="Vêtements de sport sales, pull en laine, bottes ou chaussures de marche, gants Mappa, 1 litre d'eau, pique nique, 4 piles rondes type LR 6 (vous les récupérez à la fin de la sortie)">Spéléo </option>
                <option value="Sac de couchage (avec sac à viande), tapis de sol, popote (assiette + bol), gourde, couverts, lampe de poche (frontale c'est mieux), petit nécessaire de toilette">Camping </option>
                <option value="Baudrier, assureur et mousqueton de sécurité, chaussons d&#39;escalade, licence CAF à jour, gourde d’eau, vêtements adaptés à l’escalade, haut chaud (il peut faire froid quand on assure), chaussures fermées propres pour assurer, élastique pour attacher les cheveux, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour le baudrier, attention à ne pas dépasser la durée d’usage indiquée sur la notice constructeur. Dans tous les cas, cet équipement doit être mis au rebut au plus tard 10 ans après leur fabrication.">Escalade SAE </option>
                <option value="Casque normé EN12492, baudrier, assureur avec son mousqueton de sécurité, longe cousue par le fabricant avec son mousqueton de sécurité, un jeu de minimum 7 dégaines, un machard avec son mousqueton de sécurité, chaussons d&#39;escalade, licence CAF à jour, gourde d’eau et/ou thermos, encas, vêtements adaptés à l’escalade, haut chaud (il peut faire froid quand on assure), une membrane coupe-vent, chaussures fermées pour assurer, lunettes de soleil, crème solaire, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour les éléments textiles de vos équipements de sécurité (baudrier, longes, sangles de dégaines, machard, etc.), attention à ne pas dépasser la durée d’usage indiquée sur la notice constructeur. Dans tous les cas, ces équipements doivent être mis au rebut au plus tard 10 ans après leur fabrication.">Escalade SNE </option>
                <option value="Casque normé EN12492, baudrier, assureur double gorges avec son mousqueton de sécurité, longe double cousue par le fabricant avec deux mousquetons de sécurité, un jeu de minimum 7 dégaines, un machard avec son mousqueton de sécurité, un anneau de corde dynamique cousu de 1,2m pour trianguler le relais avec 3 mousquetons de sécurité, chaussons d&#39;escalade, licence CAF à jour, petit sac à dos (20L max), gourde d’eau ou thermos, encas, vêtements adaptés à l’escalade, haut chaud (il peut faire froid quand on assure), une membrane coupe-vent, lunettes de soleil, crème solaire, frontale avec batterie, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour les éléments textiles de vos équipements de sécurité (baudrier, longes, sangles de dégaines, machard, etc.), attention à ne pas dépasser la durée d’usage indiquée sur la notice constructeur. Dans tous les cas, ces équipements doivent être mis au rebut au plus tard 10 ans après leur fabrication.">Escalade GV </option>
                <option value="Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos">Affaires personnelles </option>
                <option value="Piolet, casque, baudrier, crampons, 3 mousquetons à vis, longe en corde dynamique (pas de sangle pour se vacher), une sangle de 120, 2 anneaux de cordelette pour machard, gourde, sac à dos (30 litres), chaussures à semelles rigides, lampe frontale, lunettes de soleil cat 4. Vetements : système 3 couches : veste, et pantalon gore-tex ou équivalent, t-shirt merinos, polaire, guêtres, gants (prévoir une paire de rechange), bonnet. ">Alpinisme</option>
                <option value="Une paire de piolets techniques, une paire de crampons techniques, grosses chaussures à tiges rigides, 2 voire 3 paires de gants (dont imperméables), veste imperméable, vêtements chauds, bonnet, thé chaud...">Cascade de glace </option>
                <option value="Casque, gants et protections, chaussures, eau et nourriture de course, une chambre à air, une pompe, démonte-pneus, un multi-tool, une attache rapide de chaine, une patte de dérailleur, et un VTT en bon état de fonctionnement: freins, pneus, transmission, serrages... Et savoir réparer les petites pannes!">Vélo de Montagne</option>
                <option value="Carte CAF, doudoune, frontale, gants rechange, bonnet rechange, lunettes de soleil, crème solaire, appareil photos. SANS OUBLIER : DVA, sonde, pelle qui peuvent être prêtés par le CAF contre participation aux frais, boots, splitboard, bâtons, peaux, couteaux, visserie de rechange. Casque recommandé">Snowboard rando</option>
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
        <textarea name="matos_evt" class="type2" style="width:95%; min-height:80px" placeholder="ex : 10 Dégaines, 1 Baudrier, 1 Maillot de bain..."><?php echo inputVal('matos_evt', ''); ?></textarea>
        <?php
inclure('infos-matos', 'mini');
?>
    </div>


    <h2 class="trigger-h2">Itinéraire :</h2>
    <div class="trigger-me">
        <textarea name="itineraire" class="type2" style="width:95%; min-height:80px"><?php echo stripslashes($_POST['itineraire'] ?? ''); ?></textarea>
    </div>


    <h2 class="trigger-h2">Description complète :</h2>
    <div class="trigger-me">
        <p>
            Entrez ci-dessous toutes les informations qui ne figurent pas dans le formulaire.
            N'hésitez pas à mettre un maximum de détails, cet élément formera le corps de la page dédiée à cette sortie.
        </p>
        <?php require __DIR__.'/../../includes/help/tinymce.php'; ?>
        <textarea name="description_evt" style="width:99%"><?php echo stripslashes($_POST['description_evt'] ?? ''); ?></textarea>
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
<script language="javascript" type="text/javascript" src="/tools/tinymce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript" src="/js/jquery.webkitresize.min.js"></script><!-- debug handles -->
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

        document_base_url : '<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>',

        content_css : "<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>css/base.css,<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>css/style1.css,<?php echo LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL); ?>fonts/stylesheet.css",
        body_id : "bodytinymce_user",
        body_class : "description_evt",
        theme_advanced_styles : "Entete Article=ArticleEntete;Titre de menu=menutitle;Bleu clair du CAF=bleucaf;Image flottante gauche=imgFloatLeft;Image flottante droite=imgFloatRight;Lien fancybox=fancybox;Mini=mini;Bloc alerte=erreur;Bloc info=info",

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
        var typeTab=new Array('encadrant', 'stagiaire', 'coencadrant');
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

    // bind + onready
    $().ready(function() {
        // au chargement de la page
        $('#individus input:checked').each(function(){
            switchUserJoin($(this));
        });
        // au clic
        $('#individus input').bind('click change', function(){
            switchUserJoin($(this));
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
<script type="text/javascript" src="/js/osm-organiser.js"></script>
<!-- ****************** // osm-->
