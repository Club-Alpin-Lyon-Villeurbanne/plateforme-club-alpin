<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

require __DIR__ . '/../app/includes.php';

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
    exit;
}

$errTab = [];
$target = stripslashes($_GET['target']);
$ftpPath = LegacyContainer::getParameter('legacy_ftp_path');

// vérification,
// ne doit pas contenir ..
if (mb_substr_count($target, '..') > 0) {
    $errTab[] = "Le chemin d'accès au fichier est incorrect : récurrence de chemin retour";
}
// doit être un dossier
if (!is_dir($ftpPath . $target)) {
    $errTab[] = "L'élément donné ne semble pas être un dossier";
}

if (count($errTab) > 0) {
    echo '<div class="erreur"><ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
} else {
    // VERIFS DEJA FAITES, OPERATION OK SUR DEMANDE
    $operation = $_GET['operation'] ?? null;
    if ('create' == $operation) {
        if ('unlocked' != $_GET['lock']) {
            $errTab[] = 'Erreur : request locked.';
        }

        // traitement du nom de dossier
        $nouveauDossier = formater(stripslashes($_GET['nouveauDossier']), 3);
        if (strlen($nouveauDossier) > 40) {
            $errTab[] = 'Le nom de dossier ne peut pas dépasser 40 caractères : <b>' . html_utf8($nouveauDossier) . '</b>';
        }
        if ('' === $nouveauDossier) {
            $errTab[] = 'Entrez un nom de dossier valide';
        } elseif (file_exists($ftpPath . $target . $nouveauDossier)) {
            $errTab[] = "Le dossier <b>$target$nouveauDossier</b> existe déja. Merci de trouver un autre  nom";
        }

        // fermeture de la box/ actualissation du ftp
        if (0 === count($errTab)) {
            if (!mkdir($concurrentDirectory = $ftpPath . $target . $nouveauDossier) && !is_dir($concurrentDirectory)) {
                $errTab[] = 'Erreur PHP à la création du dossier';
            }
        }

        if (0 === count($errTab)) {
            ?>
            <script type="text/javascript">
                // parent.$('#ftp-2-arbo a.selected').click(); parent.$.fancybox.close();
                parent.document.location.href='ftp.php?dossier='+parent.currentDir;
            </script>
            <?php
            exit;
        }
    }

    // OPERATION PAS LANCEE OU ERREUR
    if (!isset($_POST['operation']) || 'create' == $_POST['operation'] && count($errTab) > 0) {
        ?><!doctype html>
        <html lang="fr">
            <head>
                <meta charset="utf-8">
                <title>DOSSIER FTP</title>

                <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('admin-styles'); ?>
                <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles'); ?>
                <!-- jquery -->
                <script type="text/javascript" src="/js/jquery-1.8.min.js"></script>

            </head>
            <body class="ftp-frame" onload="$('input[type=text]').focus()">
                <?php
                // msg d'erreur ?
                if (count($errTab) > 0) {
                    echo '<div class="erreur"><ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                } ?>

                <h3>Nouveau dossier</h3>

                <p>
                    Le nom de votre nouveau dossier sera automatiquement formaté pour remplacer les majauscules, espaces et caractères spéciaux
                    qui pourraient générer des erreurs. Entrez le nom désiré :
                </p>

                <form action="ftp-adddir.php" method="GET">
                    <input type="hidden" name="operation" value="create" />
                    <input type="hidden" name="target" value="<?php echo html_utf8($target); ?>" />
                    <input type="hidden" name="lock" value="locked" />
                    <br />

                    <span style="font-size: 0.9rem; font-weight:100; color:silver;">ftp/<?php echo html_utf8(substr($target, 7)); ?></span>
                    <input type="text" class="nice" name="nouveauDossier" value=""placeholder="nom-du-dossier" />
                    <br />
                    <br />
                    <input type="submit" class="nice green" value="Ajouter !" onclick="$(this).siblings('input[name=lock]').val('unlocked')" />
                    <input type="button" class="nice orange" value="Annuler" onclick="parent.$.fancybox.close()" />
                </form>
            </body>
        </html>
        <?php
    }
}
