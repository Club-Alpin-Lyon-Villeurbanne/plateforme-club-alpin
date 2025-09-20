<?php

use App\Ftp\FtpFile;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

global $errTab;
global $compte;

require __DIR__ . '/../app/includes.php';

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
    exit;
}

$errTab = [];
$ftpPath = LegacyContainer::getParameter('legacy_ftp_path');
$target = $_GET['target'];
$compte = 0;

// vérification,
// ne doit pas contenir ../
if (0 < mb_substr_count($target, '../')) {
    $errTab[] = "Le chemin d'accès au dossier est incorrect : récurrence de chemin retour";
}
// doit être un dossier
if (!is_dir($ftpPath . $target)) {
    $errTab[] = "L'élément donné ne semble pas être un dossier";
}

// vérification de la protection du dossier, et de chaque élément dans le dossier. target=dossier à lire
function checkMe($ftpPath, $target)
{
    global $errTab;
    global $compte;
    ++$compte;

    if (FtpFile::isProtected($target)) {
        $errTab[] = "L'élément " . ($compte > 1 ? 'contenu dans ce dossier' : '') . ' : <b>' . $target . '</b> est protégé contre la suppression';
    }

    // si c'est un dossier, on l'ouvre et on verfiie ses contenus
    if (is_dir($ftpPath . $target)) {
        $opendir = opendir($ftpPath . $target);
        while ($file = readdir($opendir)) {
            if ('.' != $file && '..' != $file && 'index.php' != $file && '.htaccess' != $file) {
                checkMe($ftpPath, $target . '/' . $file);
            }
        }
    }
}
checkMe($ftpPath, $target);

if (count($errTab) > 0) {
    echo '<div class="erreur"><ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
} else {
    // VERIFS DEJA FAITES, OPERATION OK SUR DEMANDE
    $operation = isset($_GET['operation']) || array_key_exists('operation', $_GET) ? $_GET['operation'] : null;
    if ('delete' == $operation) {
        if ('unlocked' != $_GET['lock']) {
            $errTab[] = 'Erreur : fichier verrouillé.';
        } else {
            if (!clearDir($ftpPath . $target)) {
                $errTab[] = "Erreur : suppression de $target echouée.";
            }
        }
        // fermeture de la box/ actualissation du ftp
        if (0 === count($errTab)) {
            ?>
            <script type="text/javascript">
                parent.document.location.href='ftp.php?dossier='+parent.currentDir;
            </script>
            <?php
            exit;
        }
    }

    // OPERATION PAS LANCEE OU ERREUR
    if (!isset($_POST['operation']) || 'delete' == $_POST['operation'] && count($errTab) > 0) {
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
            <body class="ftp-frame">
                <?php
                // msg d'erreur ?
                if (count($errTab) > 0) {
                    echo '<div class="erreur"><ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                } ?>

                <h3>Suppression du dossier</h3>

                <p>
                    Voulez-vous vraiment supprimer le dossier ci-dessous ? Cette opération est <u>définitive</u>. Si des liens existent vers des fichiers
                    présents dans ce dossier, ils deviendront des liens morts, renvoyant une erreur 404.
                </p>
                <p>
                    Ce dossier contient actuellement <b><?php echo $compte - 1; ?></b> éléments.
                </p>
                <p><b><?php echo html_utf8(substr($target, 7)); ?></b></p>

                <form action="ftp-deletedir.php" method="GET">
                    <input type="hidden" name="operation" value="delete" />
                    <input type="hidden" name="target" value="<?php echo html_utf8($target); ?>" />
                    <input type="hidden" name="lock" value="locked" />
                    <br />
                    <input type="submit" class="nice red" value="Supprimer ce dossier et les éléments contenus" onclick="$(this).siblings('input[name=lock]').val('unlocked')" />
                    <input type="button" class="nice orange" value="Annuler" onclick="parent.$.fancybox.close()" />
                </form>
            </body>
        </html>
        <?php
    }
}
