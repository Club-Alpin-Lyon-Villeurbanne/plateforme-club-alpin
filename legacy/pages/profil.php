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
} elseif ('articles' == $p2 && getUser()) {
    // pagination
    $limite = $MAX_ARTICLES_ADHERENT; // nombre d'elements affiches
    $pagenum = (int) ($_GET['pagenum'] ?? 0);
    if ($pagenum < 1) {
        $pagenum = 1;
    } // les pages commencent à 1

    $articleTab = [];
    $req = 'SELECT SQL_CALC_FOUND_ROWS * FROM caf_article
            LEFT JOIN media_upload m ON caf_article.media_upload_id = m.id
			WHERE user_article = ' . getUser()->getId() . '
			ORDER BY updated_at DESC LIMIT ' . ($limite * ($pagenum - 1)) . ", $limite";
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

    // calcul du total grâce à SQL_CALC_FOUND_ROWS
    $totalSql = LegacyContainer::get('legacy_mysqli_handler')->query('SELECT FOUND_ROWS()');
    $total = getArrayFirstValue($totalSql->fetch_array(\MYSQLI_NUM));
    $nbrPages = ceil($total / $limite);

    // boucle
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // info de la commission liée
        if ($handle['commission_article'] > 0) {
            $req = 'SELECT * FROM caf_commission
				WHERE id_commission = ' . (int) $handle['commission_article'] . '
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['commission'] = $handle2;
            }
        }

        // info de la sortie liée
        if ($handle['evt_article'] > 0) {
            $req = 'SELECT code_evt, id_evt, titre_evt FROM caf_evt
				WHERE id_evt = ' . (int) $handle['evt_article'] . '
				LIMIT 1';
            $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $handle['evt'] = $handle2;
            }
        }

        $articleTab[] = $handle;
    }
}

?>

<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">

    <!-- partie gauche -->
    <div id="left1">

        <?php
        // **************** **************************************
        // **************** Non connecté
        // **************** **************************************
        if (!user()) {
            ?>
            <div style="padding:20px;">
                <h1>Activer votre compte</h1>
                <?php
                // ************************
                // FORMULAIRE D'INSCRIPTION

                // texte explicatif
                inclure('activer-profil', 'vide');

            // error
            if (isset($_POST['operation']) && 'user_subscribe' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
                echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
            }
            // success
            if (isset($_POST['operation']) && 'user_subscribe' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
                echo "
					<h3>Compte créé avec succès</h3>
					<p class='info'>
						Votre compte a été créé, <b>mais vous devez le valider</b> en cliquant sur le lien
						contenu dans l'e-mail que nous venons d'envoyer à " . HtmlHelper::escape(stripslashes($email_user)) . '
					</p>';
            }

            // affichage
            if ('user_subscribe' != ($_POST['operation'] ?? null) || ('user_subscribe' == $_POST['operation'] && isset($errTab) && count($errTab) > 0)) {
                ?>
                    <br />
                    <form action="<?php echo $versCettePage; ?>" method="post">
                        <input type="hidden" name="operation" value="user_subscribe" />

                        <div style="float:left; width:45%; padding:5px 20px 5px 0;">
                            <b>Votre nom de famille</b><br />
                            <p class="mini">Le même que donné lors de votre inscription</p>
                            <input type="text" name="lastname_user" class="type1" value="<?php echo inputVal('lastname_user', ''); ?>" placeholder="" /><br />
                        </div>

                        <div style="float:left; width:45%; padding:5px 20px 5px 0;">
                            <b>Votre numéro de licence FFCAM</b>
                            <p class="mini">Numéro reçu par mail lors de votre adhésion</p>
                            <input type="text" name="cafnum_user" class="type1" value="<?php echo inputVal('cafnum_user', ''); ?>" placeholder="" maxlength="12" /><br />
                        </div>

                        <div style="float:left; width:45%; padding:5px 20px 5px 0;">
                            <b>Votre e-mail</b>
                            <p class="mini">Utilisé comme identifiant pour vous connecter</p>
                            <input type="text" name="email_user" class="type1" value="<?php echo inputVal('email_user', ''); ?>" placeholder="" /><br />
                        </div>

                        <div style="float:left; width:45%; padding:5px 20px 5px 0;">
                            <b>Choisissez un mot de passe</b>
                            <p class="mini">8 à 40 caractères sans espace</p>
                            <input type="password" name="mdp_user" class="type1" value="<?php echo inputVal('mdp_user', ''); ?>" placeholder="" /><br />
                        </div>

                        <br style="clear:both" />
                        <br />
                        <div style="padding:10px 0">
                            <a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
                                <span class="bleucaf">&gt;</span>
                                ACTIVER MON COMPTE
                            </a>
                        </div>
                    </form>
                    <?php
            } ?>
            </div>
            <?php
        }

        // **************** **************************************
        // **************** CONNECTÉ
        // **************** **************************************
        else {
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