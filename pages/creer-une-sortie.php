<?php
/*
if (!empty($_COOKIE[session_name()])) {
    // we only start session if there is a session running
    session_id() || session_start();
}
*/
if (empty($_POST) && !empty($_SESSION['POST_DATA_FROM_UPDATE'])) {
    $_POST = $_SESSION['POST_DATA_FROM_UPDATE'];
	unset($_SESSION['POST_DATA_FROM_UPDATE']);
}

?>

<script type="text/javascript" src="js/faux-select.js"></script>

<!-- MAIN -->
<div id="main" role="main" class="bigoo">

	<!-- partie gauche -->
	<div id="left1">

        <?php if (!$p2) { ?>
            <h1 class="page-h1">La fonctionnalité "<b>destination</b>"</h1>
            <div style="padding:10px 0 0 30px; line-height:18px; ">
            <p>Vous avez besoin d'un <b>bus</b> pour votre sortie ? La fonctionnalité <b>destination</b> vous permet de gérer cette situation.</p>

            <?php if (count($destinations_modifier) > 0) { ?>
                <p>Ces destinations, actuellement masquées aux adhérents, peuvent être utilisées actuellement pour proposer vos sorties :</p>
                <ul class="">
                    <?php foreach ($destinations_modifier as $destination){ ?>
                        <li><?php echo display_jour($destination['date']).' : '.html_utf8($destination['nom']); ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
                <?php if (allowed('destination_creer')) { ?>
                    <a class="lien-big" style="color:black;" href="creer-une-sortie/creer-une-destination.html" title="">&gt; Créer une nouvelle destination</a>
                <?php } else { ?>
                    <p>Vous ne semblez pas disposer des droits suffisants pour en créer une, toutefois <b>vous pouvez lier votre sortie à une destination déjà existante</b>.</p>
                <?php } ?>



                    <?php if (allowed('destination_creer') OR allowed('destination_modifier') OR allowed('destination_supprimer') OR allowed('destination_activer_desactiver')) { ?>
                        <a class="lien-big" style="color:black;" href="profil/destinations.html" title="">&gt; Gérer les destinations à venir</a><br />
                    <?php } ?><br />


            </div>
        <?php } ?>
        
        <?php if ($p2 && $p2 == 'creer-une-destination') { ?>

				<?php
                // MODIFICATION DESTINATION
                if ($p3) { ?>
					<?php if(
                        allowed('destination_modifier') ||
                        $destination['id_user_who_create'] == $_SESSION['user']['id_user'] ||
                        $destination['id_user_responsable'] == $_SESSION['user']['id_user'] ||
                        $destination['id_user_adjoint'] == $_SESSION['user']['id_user']
                    ) { ?>
                        <h1 class="page-h1">Modifier une <b>destination</b></h1>
                        <?php include(INCLUDES.'dest'.DS.'creer.php'); ?>
					<?php } else { ?>
                        <div style="padding:10px 0 0 30px; line-height:18px; ">
                            <p class="erreur">Vous n'avez pas l'autorisation d'accéder à cette page car vous ne semblez pas avoir les droits de modification pour cette destination.</p>
                        </div>
					<?php } ?>
				<?php }
				// CREATION DESTINATION
				else { ?>
					<?php if(!allowed('destination_creer')) { ?>
						<div style="padding:10px 0 0 30px; line-height:18px; ">
							<p class="erreur">Vous n'avez pas l'autorisation d'accéder à cette page car vous ne semblez pas avoir les droits de création de destination.</p>
						</div>
					<?php } else { ?>
						<h1 class="page-h1">Créer une <b>destination</b></h1>
						<?php include(INCLUDES.'dest'.DS.'creer.php'); ?>
					<?php } ?>
				<?php } ?>

        <?php } else { ?>

            <!-- // Titre. créa ou modif ? -->
            <?php
            if(!$id_evt_to_update) 	echo '<h1 class="page-h1">Proposer une <b>sortie</b></h1>';
            else 					echo '<h1 class="page-h1"><b>Modifier</b> cette sortie</h1>';
            ?>

            <div style="padding:10px 0 0 30px; line-height:18px; ">
                <?php
                // je n'ai pas le droit de créer une sortie (peu importe quelle commission)
                if(!allowed('evt_create'))
                    echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de création de sortie.</p>';

                // j'ai le droit, mais aucune commission n'est donnée
                else if(!$p2){
                    echo '<p>Merci de sélectionner la commission visée pour cette sortie :</p>';
                    // pour chaque comm que je peux modifier, lien
                    foreach($comTab as $tmp){
                        if(allowed('evt_create', 'commission:'.$tmp['code_commission']))
                            echo '<a class="lien-big" style="color:black;" href="creer-une-sortie/'.html_utf8($tmp['code_commission']).'.html" title="">&gt; Créer une sortie <b>'.html_utf8($tmp['title_commission']).'</b></a><br />';
                    }
                }

                // je n'ai pas le droit de créer une sortie pour cette commission
                else if(!allowed('evt_create', 'commission:'.$p2))
                    echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de création de sortie pour la commission '.html_utf8($p2).'.</p>';
                else if($_SESSION['user']['doit_renouveler_user'] != 0)
                    inclure('info-encadrant-licence-obsolete', 'vide');

                // on a donné une commission pour laquelle j'ai les droits, alors go
                else{

                    // modification de sortie actuellement publiée = message d'avertissement
                    if($id_evt_to_update && $update_status==1) 	echo '<p class="alerte">Attention : si vous modifiez cette sortie, elle devra à nouveau être validée par un responsable avant d\'être affichée sur le site !</p>';
                    include(INCLUDES.'evt'.DS.'creer.php');

                }
                ?>
            </div><br>
        <?php } ?>
        
	</div><!-- fin left -->

	<!-- partie droite -->
	<?php
	include INCLUDES.'right-type-agenda.php';
	?>

	<br style="clear:both" />
</div>