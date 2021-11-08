<div class="radio-nice ">
    <p><b>Je choisis mon point de ramassage :</b></p>
    <ul class="transport">
        <?php $b = 1; foreach ($destination['bus'] as $bus) { ?>
            <li class="">
                <div class="presentation">
                    <b><?php echo $bus['intitule']; ?></b> : <br>
                    <?php if ($bus['places_max'] == $bus['places_disponibles']) { ?>
                        <?php echo $bus['places_max']; ?> places disponibles
                    <?php } else { ?>
                        <span style="text-decoration: line-through;"><?php echo $bus['places_max']; ?></span> <b><?php echo $bus['places_disponibles']; ?></b> places restantes
                    <?php } ?>
                </div>
                <?php if ($bus['ramassage']) { ?>
                    <div class="parcours">
                        <p>Points de ramassage :</p>
                        <?php foreach ($bus['ramassage'] as $point) { ?>
                            <label>
                                <?php if ($bus['places_disponibles'] > 0) { ?>
                                    <input type="radio" name="id_bus_lieu_destination" value="<?php echo $point['bdl_id']; ?>" <?php if ($_POST['id_bus_lieu_destination'] == $point['bdl_id'] || $my_choices['id_bus_lieu_destination'] == $point['bdl_id']) {
    echo ' checked="checked" ';
} ?> >
                                    <?php $all_bus_plein = false; ?>
                                <?php } else { ?>
                                    <img src="/img/base/cross.png">
                                    <?php if (!isset($all_bus_plein)) {
    $all_bus_plein = true;
} ?>
                                <?php } ?>
                                <?php echo $point['nom']; ?>, à <?php echo display_time($point['date']); ?>
                            </label>
                        <?php } ?>
                    </div>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
    <?php if ($all_bus_plein) { ?>
        <p><b>Tous les bus sont déjà pleins !</b> Mais vous pouvez quand même vous inscrire en respectant cette consigne :</p>
    <?php } ?>
    <div class="presentation">
        <b>Covoiturage</b> : <br>
    </div>
    <label>
        <input type="radio" name="id_bus_lieu_destination" value="-1" <?php if (-1 == $_POST['id_bus_lieu_destination'] || 1 == $my_choices['is_covoiturage']) {
    echo ' checked="checked" ';
} ?> >
        Je me rend au départ de la sortie par mes propres moyens. Je serais donc au lieu de dépose du bus à l'horaire indiqué sur la fiche de sortie.
    </label>
</div>
<hr class="clear">