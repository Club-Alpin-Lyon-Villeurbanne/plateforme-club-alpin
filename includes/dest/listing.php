<?php
echo '<a class="agenda-evt-debut '.(0 == (int) ($destination['publie']) ? 'vis-off' : '').'" target="_top" href="destination/'.html_utf8($destination['code']).'-'.(int) ($destination['id']).'.html';
if (allowed('evt_validate') && 1 != $evt['status_evt']) {
    echo '?&forceshow=true';
}
echo '" title="">';
?>

<!-- picto -->
<div class="picto">
    <img src="<?php echo comPicto(0, 'light'); ?>" alt="" title="" class="picto-light" />
    <img src="<?php echo comPicto(0, 'dark'); ?>" alt="" title="" class="picto-dark" />
</div>

<div class="droite">

    <!-- titre -->
    <h2>
        <?php
        if ($dest['annule']) {
            echo ' <span style="padding:1px 3px; color:red; font-size:11px; font-family:Arial">DESTINATION ANNULÉE - </span>';
        }
        echo html_utf8($destination['nom']);
        ?>
    </h2>

    <!-- infos -->
    <p>
        <?php
        // Nombre de sorties :
        // Liste des sorties
        // rôle de l'user dans cette sortie
            if ($destination['id_user_responsable'] == $_SESSION['user']['id_user']) {
                echo ' - Votre rôle : <b> Encadrant </b>';
            } elseif ($destination['id_user_adjoint'] == $_SESSION['user']['id_user']) {
                echo ' - Votre rôle : <b> Co-encadrant </b>';
            } elseif ($destination['id_user_who_create'] == $_SESSION['user']['id_user']) {
                echo ' - Votre rôle : <b> Créateur </b>';
            } else {
                echo ' - Votre rôle : <b> Editeur </b>';
            }
        ?>
    </p>

</div>
<br style="clear:both" />

</a>

<div class="note">
<?php include INCLUDES.'dest'.DS.'listing_sorties.php'; ?>
</div>