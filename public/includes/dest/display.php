

<?php
// ANNULE
if ('1' == $destination['annule']) {
    echo 'Cette destination a été annulée.';
}
// CONTENU LIBRE
else {
    ?>
    <div class="lieu">
        <b>LOCALISATION</b> : <?php echo $destination['lieu']['nom']; ?><br><br>
        <?php
        if ($destination['lieu']) {
            $lieuIgn = $destination['lieu']['ign'];
        }
    if (!empty($destination['ign'])) {
        $lieuIgn = $destination['ign'];
    }
    $ign = display_frame_geoportail($lieuIgn, '100%');
    if ($ign) {
        ?>
            <div class="ign_display"><?php echo $ign; ?></div>
        <?php
    } ?>
    </div><hr style="clear:both" />

    <?php
    if (!empty($destination['description'])) {
        echo '<div class="description_destination">'.$destination['description'].'</div>'
            .'<hr style="clear:both" />';
    } ?>

    <b>LES INSCRIPTIONS</b> :<br>
    <?php if ($destination['inscription_locked']) { ?>
        <p>Les inscriptions sont momentanément interrompues.</p>
    <?php } else { ?>
        <ul class="nice-list">
            <li class="wide">Ouverture : <?php echo display_jour($destination['inscription_ouverture']); if (allowed('user_read_limited')) {
        echo ', à '.display_time($destination['inscription_ouverture']);
    } ?></li>
            <li class="wide">Fermeture : <?php echo display_jour($destination['inscription_fin']); if (allowed('user_read_limited')) {
        echo ', à '.display_time($destination['inscription_fin']);
    } ?></li>
        </ul>
    <?php } ?>
    <hr class="clear">

    <b>LES SORTIES</b> :<br>

    <?php include INCLUDES.'dest'.DS.'listing_sorties.php'; ?>

    <hr class="clear">

    <?php if (allowed('user_read_limited')) { ?>
        <?php if ($destination['cout_transport'] > 0 || count($destination['bus']) > 0) { ?>
            <b>LE TRANSPORT</b>
            <?php
                if ($destination['cout_transport'] > 0) {
                    echo '<ul class="nice-list">'
                        // tarif ?
                        .($destination['cout_transport'] > 0
                            ? '<li class="wide"><b>COÛT :</b> '.str_replace(',', '.', (float) ($destination['cout_transport'])).'&nbsp;Euros</li>'
                            : '').'</ul><br>';
                }
            ?>
            <?php if (count($destination['bus']) > 0) { ?>
                <b>LES BUS :</b>
                <ul class="nice-list transport">
                <?php $b = 1; foreach ($destination['bus'] as $bus) { ?>
                    <li>
                        <div class="presentation">
                            <b><?php echo $bus['intitule']; ?></b> : <?php echo $bus['places_max']; ?> places max.
                        </div>
                        <?php if ($bus['ramassage']) { ?>
                            <div class="parcours">
                                <p>Points de ramassage :</p>
                                <ul>
                                    <?php foreach ($bus['ramassage'] as $point) { ?>
                                        <li><?php echo $point['nom']; ?></b>, à <?php echo display_time($point['date']); ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        <?php } ?>
                    </li>
                <?php } ?>
            <?php }
            echo '</ul><hr style="clear:both" />'; ?>
        <?php } ?>
    <?php } ?>



    <?php

    // DATES
    echo '<ul class="nice-list">';

    // rdv : heure
    echo '<li><b>DÉPART :</b> Le '.display_jour($destination['date']);
    if (allowed('user_read_limited')) {
        echo ', '.display_time($destination['date']);
    }
    echo '</li>';

    // retour : le meme jour ou un autre jour
    echo '<li><b>RETOUR :</b> '.(display_jour($destination['date']) == display_jour($destination['date_fin']) ? 'Le même jour' : 'Le '.display_jour($destination['date_fin'])).'.</li>';
    echo '</ul><hr style="clear:both" />';

    // LISTE D'INFOS ENCADREMENT
    echo '<ul class="nice-list">'

        // auteur
        .'<li class="wide">
			<b>DESTINATION PROPOSÉE PAR :</b> '.userlink($destination['createur']['id_user'], $destination['createur']['nickname_user']).
        '</li>';
    if (is_array($destination['responsable']) || is_array($destination['co-responsable'])) {
        echo '<li class="wide"><b>ORGANISATION</b> : ';
        if (is_array($destination['responsable'])) {
            echo userlink($destination['responsable']['id_user'], $destination['responsable']['nickname_user']);
            $display_resp = true;
        }
        if (is_array($destination['co-responsable'])) {
            if ($display_resp) {
                echo ', ';
            }
            echo userlink($destination['co-responsable']['id_user'], $destination['co-responsable']['nickname_user']);
        }
        echo '</li>';
    }

    // clearer
    echo '</ul>';
}
?>