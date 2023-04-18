<?php

// INSCRIPTIONS SI ON EST DANS LE BON TIMING :
if (user() && allowed('evt_join')) {
    $my_choices = mon_inscription($id_evt);

    // user non bridé (licence à jour)
    if (!getUser()->getDoitRenouveler()) {
        // sortie pas passée
        if ($evt['tsp_evt'] > time()) {
            // inscriptions démarrées
            if ($evt['join_start_evt'] < time()) {
                // Je ne suis pas déja inscrit (ou bien je dispose de filiations à inscrire)
                if ('neutre' == $monStatut || count($filiations)) {
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
            inclure('info-inscription-passee', 'vide');
        }
    } else {
        inclure('info-inscription-licence-obsolete', 'vide');
    }
} else {
    inclure('info-inscription-non-connecte', 'vide');
}
