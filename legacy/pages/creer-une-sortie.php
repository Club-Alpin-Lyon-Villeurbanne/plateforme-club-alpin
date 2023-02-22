<script type="text/javascript" src="/js/faux-select.js"></script>

<!-- MAIN -->
<div id="main" role="main" class="bigoo">

	<!-- partie gauche -->
	<div id="left1">
        <!-- // Titre. créa ou modif ? -->
        <?php
        if (!$id_evt_to_update) {
            echo '<h1 class="page-h1">Proposer une <b>sortie</b></h1>';
        } else {
            echo '<h1 class="page-h1"><b>Modifier</b> cette sortie</h1>';
        }
        ?>

        <div style="padding:10px 0 0 30px; line-height:18px; ">
            <?php
            // je n'ai pas le droit de créer une sortie (peu importe quelle commission)
            if (!allowed('evt_create')) {
                echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de création de sortie.</p>';
            }

            // j'ai le droit, mais aucune commission n'est donnée
            elseif (!$p2) {
                echo '<p>Merci de sélectionner la commission visée pour cette sortie :</p>';
                // pour chaque comm que je peux modifier, lien
                foreach ($comTab as $tmp) {
                    if (allowed('evt_create', 'commission:'.$tmp['code_commission'])) {
                        echo '<a class="lien-big" style="color:black;" href="/creer-une-sortie/'.html_utf8($tmp['code_commission']).'.html" title="">&gt; Créer une sortie <b>'.html_utf8($tmp['title_commission']).'</b></a><br />';
                    }
                }
            }

            // je n'ai pas le droit de créer une sortie pour cette commission
            elseif (!allowed('evt_create', 'commission:'.$p2)) {
                echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de création de sortie pour la commission '.html_utf8($p2).'.</p>';
            } elseif (getUser()->getDoitRenouveler()) {
                inclure('info-encadrant-licence-obsolete', 'vide');
            }

            // on a donné une commission pour laquelle j'ai les droits, alors go
            else {
                // modification de sortie actuellement publiée = message d'avertissement
                if ($id_evt_to_update && 1 == $update_status) {
                    echo '<p class="alerte">Attention : si vous modifiez cette sortie, elle devra à nouveau être validée par un responsable avant d\'être affichée sur le site !</p>';
                }
                require __DIR__.'/../includes/evt/creer.php';
            }
        ?>
        </div><br>

	</div><!-- fin left -->

	<!-- partie droite -->
	<?php
    require __DIR__.'/../includes/right-type-agenda.php';
        ?>

	<br style="clear:both" />
</div>