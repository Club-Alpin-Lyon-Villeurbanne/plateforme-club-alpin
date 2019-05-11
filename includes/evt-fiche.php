<?php
// affichage complete d'une sortie.
// var necessaire : $evt

echo '<div id="fiche-sortie">';
if(sizeof($errTab) && (!in_array ($_POST['operation'], array('user_join_del','user_join_update_status','evt_user_contact','user_join'))))	echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
if(!$evt) echo '<p class="erreur">Erreur : événement non défini</p>';
else{
    
    include (INCLUDES.'evt'.DS.'admin_status.php');

	// compte rendu ?
	if($evt['cr']){
		echo '<a style="float:right; font-family:DIN, Arial; font-weight:normal; display:block; padding:5px 20px 0 0;" href="'.$p_racine.'article/'.$evt['cr']['code_article'].'-'.$evt['cr']['id_article'].'.html" title="'.html_utf8($evt['cr']['titre_article']).'">
			<span class="bleucaf">&gt;</span> Voir le compte rendu de sortie
		</a>';
	}

	// titre
	echo '<h1>
			<span class="commission">'.html_utf8($evt['title_commission']).'</span>
			<span class="bleucaf">&gt;</span> '.html_utf8($evt['titre_evt']);
            if (!empty($evt['id_groupe'])) echo '<small> ('.html_utf8($evt['groupe']['nom']).') </small>';
			echo '<span class="date"> / '.jour(date('N', $evt['tsp_evt'])).' '.date('d', $evt['tsp_evt']).' '.mois(date('m', $evt['tsp_evt'])).'</span>
		</h1>';

	// j'en suis l'auteur mais elle est passée ? Rédiger un compte rendu
	if($evt['user_evt'] == $_SESSION['user']['id_user'] && $evt['tsp_end_evt'] < $p_time){
		?>
		<a href="article-new.html?compterendu=true&amp;commission_article=-1&amp;evt_article=<?php echo $evt['id_evt']; ?>&amp;titre_article=<?php echo urlencode("Compte rendu de sortie : ".$evt['titre_evt']); ?>" title="Vous êtes l'auteur de cette sortie ? Rédigez un petit compte rendu !" class="nice2 noprint">
			<img src="img/base/pencil_add.png" alt="" title="" style="" />
			Compte rendu
		</a>
		<?php
	}

	// imprimer la fiche de sortie
	$ids_encadrants = get_encadrants($id_evt, true);
	// Correctif CRI 25/09/2015
	// ajout de is_array($ids_encadrants) && 
	// Car affichage warning sur certaines sorties car personne consultant la page pas encadrant
	if(
        (
            allowed('evt_print', 'commission:'.$current_commission) 
            || ( is_array($ids_encadrants) && in_array($_SESSION['user']['id_user'], $ids_encadrants) ) 
        ) && $evt['cancelled_evt']!='1'
    ){
		?>
		<!--
		<a href="javascript:void(0)" onclick="window.print()" title="Imprimer cette fiche" class="nice2 noprint">
			<img src="img/base/print.png" alt="PRINT" title="" style="height:20px" />
			Imprimer la fiche de sortie
		</a>
		-->

			<a href="<?php echo 'feuille-de-sortie/evt-'.intval($evt['id_evt']).'.html'; ?>" title="Ouvrir une nouvelle page avec la fiche complète des participants" class="nice2">
				<img src="img/base/print.png" alt="PRINT" title="" style="height:20px" />
				Imprimer la fiche de sortie
			</a>
		<?php
	}
    
    

        /* imprimer la fiche de destination */
        if( isset($destination) && (allowed('destination_print')
            || $destination['id_user_who_create']==$_SESSION['user']['id_user']
            || $destination['id_user_responsable']==$_SESSION['user']['id_user']
            || $destination['id_user_adjoint']==$_SESSION['user']['id_user']
            || in_array($_SESSION['user']['id_user'], get_all_encadrants_destination($destination['id'])) // je suis l'un des co/encadrant de l'une des sorties
        )){
            ?>
            <a href="<?php echo 'feuille-de-sortie/dest-'.intval($destination['id']).'.html'; ?>" title="Ouvrir une nouvelle page avec la fiche complète des participants" class="nice2">
                <img src="img/base/print.png" alt="PRINT" title="" style="height:20px" />
                Imprimer la fiche de destination
            </a>
        <?php } 

        echo '
		<br />
		<br />';

	// les messages et options liées aux inscriptions ne s'appliquent pas sur les suites de cycles
	if(!$evt['cycle_parent_evt']){

		// message d'erreur d'inscription, à priori rare sauf tentative de piratage
		if($_POST['operation'] == 'user_join' && sizeof($errTab))	echo '<div class="erreur">Erreur lors de l\'inscription : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';



		// ***
		// MESSAGE : LE VISITEUR PARTICIPE À CET EVENT ? (VAR DÉFINIE DANS SCRIPTS/REQS.PHP)
		if($monStatut=='en attente'){
			// avant l'evt
			if($evt['tsp_end_evt'] > $p_time)
				echo '<p class="alerte">
						<img src="img/inscrit-standby.png" alt="" title="" style="float:left" />
						<br />Vous avez demandé à participer à cette sortie, et votre demande est en attente de validation.<br />
						<input type="button" class="nice" value="Annuler mon inscription" onclick="$(\'#inscription-annuler\').slideToggle(200)" style="margin-top:6px;" />
					</p><br />';
			// apres l'evt
			else
				echo '<p class="alerte">
						<img src="img/inscrit-standby.png" alt="" title="" style="float:left" />
						<br />Vous avez demandé à participer à cette sortie, mais votre demande est restée en attente.<br />&nbsp;
					</p><br />';
		}

		if($monStatut=='refusé'){
			echo '<p class="erreur">
					<img src="img/inscrit-cross.png" alt="" title="" style="float:left" />
					<br />Vous avez demandé à participer à cette sortie, mais l\'organisateur a décliné votre inscription. N\'hésitez pas à le contacter pour en savoir plus.<br />&nbsp;
				</p><br />';
		}

		if($monStatut=='encadrant' || $monStatut=='coencadrant' || $monStatut=='benevole'){
			// avant l'evt
			if($evt['tsp_end_evt'] > $p_time)
				echo '<p class="info">
						<img src="img/inscrit-encadrant.png" alt="" title="" style="float:left" />
						<br />Vous êtes inscrit à cette sortie en tant que : &laquo; '.$monStatut.' &raquo;.<br />&nbsp;
						<input type="button" class="nice" value="Annuler mon inscription" onclick="$(\'#inscription-annuler\').slideToggle(200)" style="margin-top:6px;" />
					</p><br />';
			// apres l'evt
			else
				echo '<p class="info">
						<img src="img/inscrit-encadrant.png" alt="" title="" style="float:left" />
						<br />Vous avez participé à cette sortie en tant que : &laquo; '.$monStatut.' &raquo;.<br />&nbsp;
					</p><br />';
		}

		if($monStatut=='inscrit' || $monStatut=='manuel'){
			// avant l'evt
			if($evt['tsp_end_evt'] > $p_time)
				echo '<p class="info">
					<img src="img/inscrit-check.png" alt="" title="" style="float:left" />
					<br />Vous êtes inscrit comme participant à cette sortie.<br />&nbsp;
					<input type="button" class="nice" value="Annuler mon inscription" onclick="$(\'#inscription-annuler\').slideToggle(200)" style="margin-top:6px;" />
				</p><br />';
			// apres l'evt
			else
				echo '<p class="info">
					<img src="img/inscrit-check.png" alt="" title="" style="float:left" />
					<br />Vous avez participé à cette sortie.<br />&nbsp;
				</p><br />';
		}
        ?>

        <?php if(user() && user_in_destination($_SESSION['user']['id_user'], $destination['id']) ){ ?>
            <div class="note mr10 bbox">
                <p>Je suis inscrit à une sortie de cette destination. Je souhaite connaissance des personnes inscrites
                    et je peux organiser mon covoiturage vers les lieux de ramassage des bus et vers les lieux de départ des sorties.</p>
                <p>
                    <a    href="destination/<?php echo html_utf8($destination['code']).'-'.intval($destination['id']); ?>.html#organisation_covoiturage"
                            title="Plus d'infos en cliquant"
                            target="_blank">J'accède à ces informations complémentaires</a>
                    concernant <span class="bleucaf"><?php echo $destination['nom']; ?></span>.
                </p>
            </div>
        <?php } ?>


        <?php
        // FORMULAIRE DE SUPPRESSION D'INSCRIPTION
        if(user() && $monStatut!='neutre' ){ ?>
            <?php
            if (allowed('evt_unjoin')) {
			?>
			<div id="inscription-annuler" style="display:<?php if($_POST['operation']=='user_join_del') echo 'block'; else echo 'none'; ?>">
				<?php
				// messages informatifs
				inclure('formalites-inscription-suppression', 'formalites');

				// TABLEAU d'erreurs
				if($_POST['operation'] == 'user_join_del' && sizeof($errTab))	echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
				?>

				<form action="<?php echo $versCettePage;?>#inscription-annuler" method="post" class="loading">
					<input type="hidden" name="operation" value="user_join_del" />
					<input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
					<input type="hidden" name="titre_evt" value="<?php echo html_utf8($evt['titre_evt']); ?>" />
					<input type="hidden" name="code_evt" value="<?php echo html_utf8($evt['code_evt']); ?>" />

					<p style="text-align:center">
						<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
							<span class="bleucaf">&gt;</span>
							ANNULER MA DEMANDE D'INSCRIPTION
						</a>
					</p>
				</form>

			</div>
			<br />
			<?php
		}}
		// message en cas de succès de désinscription
		if($_POST['operation'] == 'user_join_del' && !sizeof($errTab))	echo '<div class="info">Vous n\'êtes plus inscrit à cette sortie.</div>';

		// GESTION DES INSCRIPTIONS
        include (INCLUDES.'evt'.DS.'gestion_inscriptions.php');
        
	}


    if ($destination) {
    ?>
        <h2 class="trigger-h2 " >Cette sortie fait partie de la destination <span class="bleucaf"><?php echo $destination['nom']; ?></span></h2>
        <div class="trigger-me">
            <div class="note">
                <?php include(INCLUDES.'dest'.DS.'display.php'); ?>
            </div>
        </div>
        <br>
        <h2>La sortie :</h2>
    <?php
    }


	// LISTE D'INFOS DIFFICULTE ETC...
    if ($evt['massif_evt'] || $evt['itineraire'] != '' ) {
        echo '<ul class="nice-list">'
            // massif ?
            .($evt['massif_evt'] ?
                '<li class="wide"><b>MASSIF :</b> '.html_utf8($evt['massif_evt']).'</li>'
            :'')
            // itineraire ?
            .($evt['itineraire'] != '' ?
                '<li class="wide"><b>ITINÉRAIRE :</b> '.html_utf8(str_replace(array(" \n", "\n"), ', ', trim($evt['itineraire']))).'</li>'
            :'')
        .'</ul>'
        .'<br style="clear:both" />'
        .'<hr />';
    }

    if ($evt['denivele_evt'] || $evt['distance_evt']>0 || $evt['difficulte_evt'] || $evt['matos_evt'] != '') {
        echo '<ul class="nice-list">'
            // denivele ?
            .($evt['denivele_evt'] ?
                '<li><b>DÉNIVELÉ :</b> '.html_utf8($evt['denivele_evt']).'</li>'
            :'')
            // distance_evt ?
            .($evt['distance_evt']>0 ?
                '<li><b>DISTANCE :</b> '.html_utf8($evt['distance_evt']).' km</li>'
            :'')
            // difficulte ?
            .($evt['difficulte_evt'] ?
                '<li class="wide"><b>DIFFICULTÉ :</b> '.html_utf8($evt['difficulte_evt']).'</li>'
            :'')
            // materiel ?
            .($evt['matos_evt'] != '' ?
                '<li class="wide"><b>MATÉRIEL :</b> '.html_utf8(str_replace(array(" \n", "\n"), ', ', trim($evt['matos_evt']))).'</li>'
            :'')
        .'</ul>'
        .'<br style="clear:both" />'
        .'<hr />';
    }

	// CONTENU LIBRE
	if ($evt['cancelled_evt']!='1') {
		echo ''
		.'<div class="description_evt">'.$evt['description_evt'].'</div>'
		.'<hr style="clear:both" />';

        // TARIFICATIONS DE LA SORTIE (réservé aux membres)
        if(allowed('user_read_limited') && ($evt['tarif_evt']>0 || $evt['tarif_detail'] || $evt['repas_restaurant']>0)){
            echo '<ul class="nice-list">'
				// tarif ?
				.($evt['tarif_evt']>0 ?
					'<li class="wide"><b>TARIF :</b> '.str_replace(',', '.', floatVal($evt['tarif_evt'])).'&nbsp;Euros '.($destination ? ' <small>(hors transport collectif : + '.str_replace(',', '.', floatVal($destination['cout_transport'])).'&nbsp;Euros)</small>':'').'</li>'
				:'')
                // Détail du tarif
                .($evt['tarif_detail'] ?
                    '<li class="wide"><b>DÉTAIL :</b> '.html_utf8($evt['tarif_detail']).'</li>'
                :'')
                // Restaurant
                .($evt['repas_restaurant']>0 ?
                    '<li class="wide"><b>RESTAURATION :</b> Un repas au restaurant (choix individuel) au cours de la sortie est possible '.( $evt['tarif_restaurant']>0 ?' pour un montant de '.str_replace(',', '.', floatVal($evt['tarif_restaurant'])).'&nbsp;Euros':' (tarif non précisé)' ).'</li>'
                :'')
            .'</ul><hr style="clear:both" />';
        }

        if (!$destination) {
            // DATES
            echo '<ul class="nice-list">';

                // rdv : heure
                echo '<li><b>DÉPART :</b> Le '.date('d', $evt['tsp_evt']).' '.mois(date('m', $evt['tsp_evt'])).' '.date('Y', $evt['tsp_evt']);
                if(allowed('user_read_limited')){ echo ', '.date('H:i', $evt['tsp_evt']);}
                echo '</li>';

            // retour : le meme jour ou un autre jour
            echo '<li><b>RETOUR :</b> '.( date('dmy', $evt['tsp_evt']) == date('dmy', $evt['tsp_end_evt']) ? 'Le même jour' : 'Le '.date('d', $evt['tsp_end_evt']).' '.mois(date('m', $evt['tsp_end_evt']))).'.</li>';
            echo '</ul>';

            // LIEUX et TRANSPORTS (réservé aux membres)
            if(allowed('user_read_limited')){
                echo '<ul class="nice-list">'
                    // rdv : lieu
                    .'<li><b>RDV :</b> '.html_utf8($evt['rdv_evt']).'</li>'
                    // titre carte
                    .'<li class="wide"><b>POINT DE RENDEZ-VOUS SUR LA CARTE :</b> </li>'
                .'</ul><br style="clear:both" />';

                // MAP TRANSPORT
                ?>
                <div id="gmap-fichesortie"></div>
                <!-- ****************** script gmap -->
                <script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
                <script type="text/javascript">
                var latMap = <?php echo str_replace(',', '.', floatVal($evt['lat_evt'])); ?>;
                var lonMap = <?php echo str_replace(',', '.', floatVal($evt['long_evt'])); ?>;
                var rdvMap = '<?php echo addslashes(strip_tags($evt['rdv_evt'])); ?>';
                </script>
                <script type="text/javascript" src="js/gmap-fichesortie.js"></script>
                <!-- ****************** // gmap-->

                <?php
            }
            echo '<hr />';
        } else {
            if(allowed('user_read_limited')){
            ?>

                <?php $l = 0; foreach($evt['lieu'] as $type => $lieu) {?>
                <div class="lft half">
                        <?php if ($type=="depose") { ?>
                            <img src="img/start.png"  class="lft mr10"><b>Lieu de dépose :</b><br><?php echo html_utf8($lieu['nom']); ?>, le <?php $djdd = display_jour($lieu['date_depose']); echo $djdd; ?> à <?php echo display_time($lieu['date_depose']); ?>
                        <?php } elseif ($type=="reprise") { ?>
                            <img src="img/finish.png"  class="lft mr10""><b>Lieu de reprise :</b><br><?php echo html_utf8($lieu['nom']); ?>, <?php $djdr = display_jour($lieu['date_reprise']); if ($djdr != $djdd) echo 'le '.$djdr; ?> à <?php echo display_time($lieu['date_reprise']); ?>
                        <?php } ?>
                </div>
                    <?php }  ?><br class="clear">

                <div id="map_dr"></div>
                <br>
                <hr />

                <?php if ($destination) { ?>

                    <script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
                    <script type="text/javascript" src="js/gmap-organiser.js"></script>
                    <script type="text/javascript">

                        $(document).ready(function(){
                            // Carte de prévisualisation des points de dépose et reprise
                            gmap_initialize('map_dr');
                            var markers_dr = [
                                <?php
                                    $l = 0;
                                    foreach($evt['lieu'] as $type => $lieu) {
                                        if ($l > 0) { echo ','; } ?>["<?php echo html_utf8($lieu['nom']); ?>",<?php echo $lieu['lat']; ?>,<?php echo $lieu['lng'];
                                        if ($type == 'depose') { echo ', "depose"'; } else { echo ', "reprise"'; } ?>]<?php $l++; } ?>
                            ];
                            var infos_dr = [
                                <?php $l = 0; foreach($evt['lieu'] as $type => $lieu) {?><?php if ($l > 0) echo ','; ?>
                                ["<?php
                                    echo '<b>';
                                    if ($type == 'depose') echo 'Lieu de dépose : ';
                                    else echo 'Lieu de reprise : ';
                                    echo '</b><br>';
                                    echo html_utf8($lieu['nom']); ?>"]<?php $l++; }  ?>
                            ];
                            renderMultipleMarkers(markers_dr, infos_dr);
                        });
                    </script>
                <?php } ?>
            <?php
            }
        }
	}

	// LISTE D'INFOS ENCADREMENT
	echo '<ul class="nice-list">'

		// auteur
		.'<li class="wide">
			<b>SORTIE DÉPOSÉE PAR :</b> '.userlink($evt['user_evt'], $evt['nickname_user'])
			.' <span class="mini">'
				.'- le '.date('d/m/y', $evt['tsp_crea_evt'])
				.($evt['tsp_edit_evt']?', modifiée le '.date('d/m/y à H:i', $evt['tsp_edit_evt']):'')
			.'</span>
		</li>';

		// dev
		// echo '<pre>'; print_r($evt['joins']); echo '</pre>';

		// encadrant(s)
		if(sizeof($evt['joins']['encadrant'])){
			echo '<li class="wide"><b>ENCADRANTS :</b> ';
			for($i=0; $i<sizeof($evt['joins']['encadrant']); $i++){
				if($i) echo ', ';
				echo userlink($evt['joins']['encadrant'][$i]['id_user'], $evt['joins']['encadrant'][$i]['nickname_user']);
			}
			echo '</li><br style="clear:both" />';
		}

		// coencadrant(s)
		if(sizeof($evt['joins']['coencadrant'])){
			echo '<li class="wide"><b>CO-ENCADRANTS :</b> ';
			for($i=0; $i<sizeof($evt['joins']['coencadrant']); $i++){
				if($i) echo ', ';
				echo userlink($evt['joins']['coencadrant'][$i]['id_user'], $evt['joins']['coencadrant'][$i]['nickname_user']);
			}
			echo '</li><br style="clear:both" />';
		}

		// benevole(s)
		if(sizeof($evt['joins']['benevole'])){
			echo '<li class="wide"><b>BÉNÉVOLES :</b> ';
			for($i=0; $i<sizeof($evt['joins']['benevole']); $i++){
				if($i) echo ', ';
				echo userlink($evt['joins']['benevole'][$i]['id_user'], $evt['joins']['benevole'][$i]['nickname_user']);
			}
			echo '</li><br style="clear:both" />';
		}

		// clearer
		echo '<br style="clear:both" />';

		// On recrute ?
		if($evt['need_benevoles_evt'] && !$evt['cancelled_evt']){
			inclure('alerte-benevoles', 'alerte-benevoles');
		}

		echo '
	</ul><br style="clear:both" />';

    include (INCLUDES.'evt'.DS.'validation_legale.php');

	// NOMBRE DE PLACES
	// les messages et options liées aux inscriptions ne s'appliquent pas sur les suites de cycles
	if(!$evt['cycle_parent_evt']){

		if($evt['cancelled_evt']!='1'){

			// participants
			$nInscritsHorsEncadrement	=	sizeof($evt['joins']['inscrit']) + sizeof($evt['joins']['benevole']) + sizeof($evt['joins']['manuel']);
			$nInscritsTotal = $nInscritsHorsEncadrement + sizeof($evt['joins']['coencadrant']) + sizeof($evt['joins']['encadrant']);
			$nPlacesRestantesOnline		=	$evt['join_max_evt'] - sizeof($evt['joins']['inscrit']) - sizeof($evt['joins']['benevole']);
			$nEnAttente					=	sizeof($evt['joins']['enattente']);

			if($nPlacesRestantesOnline > ($evt['ngens_max_evt'] - $nInscritsTotal) )	$nPlacesRestantesOnline = ($evt['ngens_max_evt'] - $nInscritsTotal);
			if($nPlacesRestantesOnline < 0)	$nPlacesRestantesOnline = 0;

			echo '<hr />'
			.'<ul class="nice-list">'
				.'<li><b>NOMBRE TOTAL DE PLACES :</b> '.$evt['ngens_max_evt']
				.'<li><b>DISPONIBLES VIA INTERNET :</b> '.$nPlacesRestantesOnline
			.'</ul>'

			.'<br style="clear:both" /><hr />'

			.'<p>
				<span class="temoin-places-dispos '.($nPlacesRestantesOnline>0?'on':'off').'"></span>
				<span  style="font-size:14px; font-family:DINBold; color:#666666">
					'.$nInscritsTotal.' PARTICIPANT'.($nInscritsTotal>1?'S':'').' INSCRIT'.($nInscritsTotal>1?'S':'').'
					SUR  '.$evt['ngens_max_evt'].' PLACE'.($evt['ngens_max_evt']>1?'S':'').' AU TOTAL ('.($evt['ngens_max_evt']>0?sprintf("%2d%%", (100*$nInscritsTotal/$evt['ngens_max_evt'])):'0').')
				</span><br />
				'.$nPlacesRestantesOnline.' place'.($nPlacesRestantesOnline>1?'s':'').' restante'.($nPlacesRestantesOnline>1?'s':'').' pour les inscriptions en ligne
				'.($nEnAttente ? ' - '.$nEnAttente.' inscriptions en attente de confirmation' :'').'
			</p>'
			;

			// AFFICHAGE DES INSCRIPTIONS, AVEC CERTAINES INFOS SELON LES DROITS EN COURS
			if($nInscritsHorsEncadrement>0 && !$droitDeModif){
				echo '<table class="big-lines-table" style="width:570px; margin-left:20px;">';

				// inscrits en ligne via formulaire
				foreach($evt['joins']['inscrit'] as $tmpUser) {

                    echo '<tr>
							<td>
								'
                        .(allowed('user_read_private', $evt['code_commission']) ? '<p class="mini">'.html_utf8(strtoupper($tmpUser['lastname_user'])).', '.html_utf8(ucfirst(strtolower($tmpUser['firstname_user']))).'</p>' :'' )
                        .userlink($tmpUser['id_user'], $tmpUser['nickname_user'])

                        .'</td>
							<td class="small">'.( allowed('user_read_private', 'commission:'.$evt['code_commission']) ? $tmpUser['tel_user'] :'' ).'</td>
							<td class="small">'.( allowed('user_read_private', 'commission:'.$evt['code_commission']) ? $tmpUser['tel2_user'] :'' ).'</td>
							<td class="small">'.( allowed('user_read_private', 'commission:'.$evt['code_commission']) ? '<a href="mailto:'.$tmpUser['email_user'].'">'.$tmpUser['email_user'].'</a>' :'' ).'</td>'.
                        (allowed('user_read_limited')?'<td class="small">'.($tmpUser['is_covoiturage']?'<img src="img/voiture.png" title="Covoiturage" width="16px">':'').'</td>':'')
						.'</tr>';
                }
				// inscrits manuellement
				foreach($evt['joins']['manuel'] as $tmpUser)
					echo '<tr>
							<td>
								'.userlink($tmpUser['id_user'], $tmpUser['nickname_user'])
								.(allowed('user_read_private', $evt['code_commission']) ? '<p class="mini">'.html_utf8(strtoupper($tmpUser['lastname_user'])).', '.html_utf8(ucfirst(strtolower($tmpUser['firstname_user']))).'</p>' :'' )
							.'</td>
							<td class="small">'.( allowed('user_read_private', 'commission:'.$evt['code_commission']) ? $tmpUser['tel_user'] :'' ).'</td>
							<td class="small">'.( allowed('user_read_private', 'commission:'.$evt['code_commission']) ? $tmpUser['tel2_user'] :'' ).'</td>
							<td class="small">'.( allowed('user_read_private', 'commission:'.$evt['code_commission']) ? '<a href="mailto:'.$tmpUser['email_user'].'">'.$tmpUser['email_user'].'</a>' :'' ).'</td>'.
                        (allowed('user_read_limited')?'<td class="small">'.($tmpUser['is_covoiturage']?'<img src="img/voiture.png" title="Covoiturage" width="16px">':'').'</td>':'')
                        .'</tr>';

				echo '</table>';
			}
            

            // Patch le 23/08/15 car pas de bloquage des inscriptions internet
            // lorsque le nombre de places disponibles via internet = 0
            $acces_au_module = array();
            $statut_access = array(
                'inscrit', 
                'manuel', 
                'encadrant', 
                'coencadrant', 
                'benevole', 
                'enattente'
            );
            foreach ($statut_access as $statut_acces) {
                if (sizeof($evt['joins'][$statut_acces])) {
                    foreach($evt['joins'][$statut_acces] as $user_access) {
                        $acces_au_module[] = $user_access['id_user'];
                    }
                }
            }
            
            /*
            if ($_SERVER['REMOTE_ADDR'] == '176.145.21.240') {
                echo '<pre>';
                print_r($evt['joins']);
                print_r($_SESSION);
                print_r($acces_au_module);
                echo '</pre>';
            } */
            
            
            if (
                $nPlacesRestantesOnline > 0 
                    OR in_array($_SESSION['user']['id_user'] , $acces_au_module)
            ){
                include(INCLUDES.'evt'.DS.'user_inscription.php');
            } else {
                echo "<p>Le nombre de places disponibles à la réservation depuis internet est atteint.</p>";
            }
		}
	}

	// si besoin : lien vers le premier evt du cycle
	else inclure('info-inscription-nieme-cycle', 'vide');

	if($evt['cycleparent']){
		$evt=$evt['cycleparent'];
		echo '<table id="agenda">';
		echo '<tr>'
				.'<td class="agenda-gauche">'.date('d/m/Y', $evt['tsp_evt']).'</td>'
				.'<td>';
					include INCLUDES.'agenda-evt-debut.php';
		echo	'</td>'
			.'</tr>';
		echo '</table>';
	}
	elseif($evt['cyclechildren']){
		$evtArray = $evt['cyclechildren'];
		$nbChildren = count($evtArray);

		print '<br style="clear:both" /><hr /><ul class="nice-list">'
				.'<li class="wide"><b>SORTIE'.($nbChildren>1?'S':'').' SUIVANTE'.($nbChildren>1?'S':'').' DE CE CYCLE :</b> '
			.'</ul>'
			.'';

		echo '<table id="agenda">';
		foreach ($evtArray as $evt){
			echo '<tr>'
					.'<td class="agenda-gauche">'.date('d/m/Y', $evt['tsp_evt']).'</td>'
					.'<td>';
						include INCLUDES.'agenda-evt-debut.php';
			echo	'</td>'
				.'</tr>';
		}
		echo '</table>';
	}
}
echo '</div>';
?>


