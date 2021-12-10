<?php

// INSCRIPTIONS SI ON EST DANS LE BON TIMING :
if (user() && allowed('evt_join')) {
    $my_choices = mon_inscription($id_evt);

    // user non bridé (licence à jour)
    if (!getUser()->getDoitRenouvelerUser()) {
        // sortie pas passée
        if ($evt['tsp_evt'] > time()) {
            if ($destination) {
                $inscriptions_status = inscriptions_status_destination($destination);
                echo '<hr /><h2>Inscriptions :</h2><p>'.$inscriptions_status['message'].'</p>';
                if (true == $inscriptions_status['status']) {
                    require __DIR__.'/../../includes/evt/user_inscription_button.php'; ?>

                        <div id="inscription" style="display:<?php if (isset($_POST['operation']) && 'user_join' == $_POST['operation']) {
                        echo 'block';
                    } else {
                        echo 'none';
                    } ?>">

                            <h2>Note importante sur les inscriptions :</h2>

                            <?php $uid = user_in_destination(user() ? (string) getUser()->getIdUser() : '', $destination['id']);
                    if ($uid && $uid != $evt['id_evt']) { ?>

                                <p>Désolé, vous êtes déjà inscrit à une autre sortie de cette destination. Vous ne pouvez pas participer à deux sorties simultanées.</p>

                            <?php } else { ?>

                                <?php /* messages informatifs */ inclure('formalites-inscription-destination', 'formalites'); ?>


                                <hr>

                                <?php
                                    // TABLEAU d'erreurs
                                    if (isset($_POST['operation']) && 'user_join' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                                    }
                                    if (isset($_POST['operation']) && 'user_join' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                                        echo '<div class="info">Opération effectuée avec succès : '.count($inscrits).' personnes inscrite(s)</div>';
                                    }
                                ?>

                                <form action="<?php echo $versCettePage; ?>#inscription" method="post" class="loading">

                                    <input type="hidden" name="operation" value="user_join" />
                                    <input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />
                                    <input type="hidden" name="id_destination" value="<?php echo $destination['id']; ?>" />

                                    <?php require __DIR__.'/../../includes/evt/user_inscription_transport.php'; ?>

                                    <div class="check-nice ">
                                        <?php require __DIR__.'/../../includes/evt/user_inscription_options.php'; ?>
                                    </div>

                                    <?php require __DIR__.'/../../includes/evt/user_inscription_submit.php'; ?>

                                </form>

                            <?php } ?>

                        </div>
                    <?php
                }
            }
            // Sortie simple sans destination
            else {
                // sortie dans plus de deux jours
                if ($evt['tsp_evt'] > strtotime('midnight +2 days')) {
                    // inscriptions démarrées
                    if ($evt['join_start_evt'] < time()) {
                        // Je ne suis pas déja inscrit (ou bien je dispose de filiations à inscrire)
                        if ('neutre' == $monStatut || count($filiations) || $evt['repas_restaurant']) {
                            require __DIR__.'/../../includes/evt/user_inscription_button.php'; ?>

                            <div id="inscription" style="display:<?php if (isset($_POST['operation']) && 'user_join' == $_POST['operation']) {
                                echo 'block';
                            } else {
                                echo 'none';
                            } ?>">

                                <?php /* messages informatifs */ inclure('formalites-inscription', 'formalites'); ?>

                                <?php
                                    // TABLEAU d'erreurs
                                    if (isset($_POST['operation']) && 'user_join' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                                    }
                            if (isset($_POST['operation']) && 'user_join' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                                echo '<div class="info">Opération effectuée avec succès : '.count($inscrits).' personnes pré-inscrite(s)</div>';
                            } ?>

                                <form action="<?php echo $versCettePage; ?>#inscription" method="post" class="loading">

                                    <input type="hidden" name="operation" value="user_join" />
                                    <input type="hidden" name="id_evt" value="<?php echo $id_evt; ?>" />

                                    <div class="check-nice ">

                                        <?php require __DIR__.'/../../includes/evt/user_inscription_options.php'; ?>

                                    </div>

                                    <?php require __DIR__.'/../../includes/evt/user_inscription_submit.php'; ?>

                                </form>

                            </div>
                            <?php
                        }
                    } else {
                        echo '<hr /><h2>Inscriptions :</h2><p>Les inscriptions pour cette sortie commenceront le '.date('d/m/y', $evt['join_start_evt']).'.</p>';
                    }
                } else {
                    inclure('info-inscription-moins-deux-jours', 'vide');
                }
            }
        } else {
            inclure('info-inscription-passee', 'vide');
        }
    } else {
        inclure('info-inscription-licence-obsolete', 'vide');
    }
} else {
    inclure('info-inscription-non-connecte', 'vide');
}
