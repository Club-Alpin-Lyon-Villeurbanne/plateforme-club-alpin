<?php
// page dédiée HORS CONNEXION
// 				à la création d'un nouveau profil
// 				au login
// page dédiée CONNECTÉ
// 				à la gestion de son profil/photo
// 				à l'historique des sorties
// 				aux filiations
// 				...

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;

$MAX_ARTICLES_ADHERENT = LegacyContainer::getParameter('legacy_env_MAX_ARTICLES_ADHERENT');

if ('infos' == $p2 && getUser()) {
    $tmpUser = false;
    $req = 'SELECT * FROM caf_user WHERE id_user=' . getUser()->getId() . ' LIMIT 1';
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // filiation : ais-je des "enfants"
        if ('' !== trim($handle['cafnum_user'])) {
            $handle['enfants'] = [];
            $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, birthdate, email_user, tel_user, cafnum_user FROM caf_user WHERE cafnum_parent_user = '" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($handle['cafnum_user']) . "' LIMIT 100";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['enfants'][] = $handle2;
            }
        }

        // filiation : ais-je un parent
        if (isset($handle['cafnum_parent_user']) && '' !== trim($handle['cafnum_parent_user'])) {
            $handle['parent'] = [];
            $req = "SELECT id_user, firstname_user, lastname_user, nickname_user, birthdate, email_user, tel_user, cafnum_user FROM caf_user WHERE cafnum_user = '" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($handle['cafnum_parent_user']) . "' LIMIT 1";
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['parent'] = $handle2;
            }
        }

        $tmpUser = $handle;
    }
}

?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

    <!-- partie gauche -->
    <div id="left1">

        <?php
        // **************** **************************************
        // **************** CONNECTÉ
        // **************** **************************************
        if (user()) {
            if (file_exists(__DIR__ . '/profil-' . $p2 . '.php')) {
                require __DIR__ . '/profil-' . $p2 . '.php';
            } else {
                echo '<p class="erreur">Erreur : fichier introuvable</p>';
            }
        }
?>
        <br style="clear:both" />
    </div>

    <!-- partie droite -->

    <?php require __DIR__ . '/../includes/right-type-agenda.php'; ?>

    <br style="clear:both" />
</div>