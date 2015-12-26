<?php

// destination trouvée mais normalement pas visible, c'est le cas d'un mode admin ou validateur
if (user()) {

    $messageDiv=false;

    // j'en suis l'auteur/(co-)encadrant ? Elle est pas annulée ? modification possible !
    if(
        allowed('destination_modifier') ||
        allowed('destination_activer_desactiver') ||
        allowed('destination_supprimer') ||
        $destination['id_user_who_create'] == $_SESSION['user']['id_user'] ||
        $destination['id_user_responsable'] == $_SESSION['user']['id_user'] ||
        $destination['id_user_adjoint'] == $_SESSION['user']['id_user']
    ){

        //pas publiée
        if($destination['publie']=='0') {
            $messageDiv = true;
            echo '<div class="alerte"><b>Note : Cette destination n\'est pas publiée sur le site</b>, ni les sorties qui lui sont rattachées.<br>
                    Les organisateurs de sortie peuvent utiliser cette destination.</br></br>
                    Si vous voyez ce message apparaître, c\'est que vous disposez de droits particuliers qui vous autorisent à voir cette page.
                    Les usagers réguliers du site n\'ont pas accès aux informations ci-dessous.<br>';
        } else {
            $messageDiv = true;

            if ($destination['annule'] == 1) {
                echo '<div class="alerte"><b>Note : Cette destination est annulée</b>, ainsi que toutes les sorties ratachées !<br>';
            }
            else {
                echo '<div class="alerte"><b>Note : Cette destination est publiée sur le site</b>. Aucune sortie ne peut désormais lui être liée.<br>';
                $insc = inscriptions_status_destination($destination);
                echo $insc['message'].'<br><br>';
            }
            //pas inscription
        }

        // Management des droits
        include (INCLUDES.'dest'.DS.'quick_update.php');

        if(
            allowed('destination_modifier') ||
            $destination['id_user_who_create'] == $_SESSION['user']['id_user'] ||
            $destination['id_user_responsable'] == $_SESSION['user']['id_user'] ||
            $destination['id_user_adjoint'] == $_SESSION['user']['id_user']
        ){
            if ($destination['annule'] != 1 && !$id_dest_to_update) {
                echo '<a href="creer-une-sortie/creer-une-destination/update-'.$destination['id'].'.html"
                        title="Vous êtes en charge de cette destination ? Cliquez ici pour la modifier."
                        class="nice2 noprint orange">
                    <img src="img/base/pencil.png" alt="" title="" style="" />&nbsp;&nbsp;Modifier cette destination
                </a>';
            }
        }
    }

    if ( $messageDiv ) {
        echo '</div>';
    }

    echo '<br /><br />';

}