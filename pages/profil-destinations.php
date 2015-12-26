<?php
if(user()){
    ?>
    <div class="main-type">
        <h1>Mon profil - Mes destinations</h1>

        <?php if (allowed('destination_creer')) { ?>
            <a class="lien-big" style="color:black;" href="creer-une-sortie/creer-une-destination.html" title="">&gt; Créer une nouvelle destination</a><br>
        <?php } ?>

        <?php $destinations_modifier =  get_future_destinations(true); ?>

        <?php if (count($destinations_modifier) > 0) { ?><br>
            <h2>Gérer les <b class="bleucaf"><?php echo count($destinations_modifier); ?></b> destinations me concernant à venir</h2>

            <table id="agenda">
                <?php
                foreach ($destinations_modifier as $destination){

                    echo '<tr>'
                        .'<td class="agenda-gauche">'.display_jour($destination['date']).'</td>'
                        .'<td>'

                        // Boutons
                        .'<div class="evt-tools">'

                        // apercu
                        .'<a class="nice2"
                                            href="destination/'.html_utf8($destination['code']).'-'.intval($destination['id']).'.html'.($destination['publie']==0?'?forceshow=true':'').'"
                                            title="Ouvre une nouvelle fenêtre de votre navigateur pour jeter un oeil à la page avant publication"
                                            target="_blank">Aperçu</a> ';

                    include (INCLUDES.'dest'.DS.'quick_update.php');

                    echo '</div>';

                    include (INCLUDES.'dest'.DS.'listing.php');

                    echo 	'</td>'
                        .'</tr>';
                }
                ?>
            </table>
        <?php } ?>

    </div>
    <?php
}