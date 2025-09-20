<?php

use App\Ftp\FtpFile;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

require __DIR__ . '/../app/includes.php';

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
    exit;
}

$errTab = [];
$target = $_GET['target'];
$filename = strtolower(substr(strrchr($target, '/'), 1));
$ftpPath = LegacyContainer::getParameter('legacy_ftp_path');
$fullPath = $ftpPath . substr($target, 5);

// vérification,
// la cible doit commencer par /ftp/
if ('/ftp/' != substr($target, 0, 5)) {
    $errTab[] = "Le chemin d'accès au fichier est incorrect";
}
// ne doit pas contenir ../
if (0 != mb_substr_count($target, '../')) {
    $errTab[] = "Le chemin d'accès au fichier est incorrect : récurrence de chemin retour";
}
// doit être un fichier
if (!is_file($fullPath)) {
    $errTab[] = "L'élément donné ne semble pas être un fichier";
}
// ne pas être un fichier sensible (htaccess)
if ('.htaccess' == $filename) {
    $errTab[] = 'Le fichier .htaccess ne peut pas être supprimé';
}
if ('index.php' == $filename) {
    $errTab[] = 'Le fichier index.php ne peut pas être supprimé';
}

if (FtpFile::isProtected(substr($target, 7))) {
    $errTab[] = "Le fichier $filename est protégé contre la suppression";
}

if (count($errTab) > 0) {
    echo '<div class="erreur"><ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
} else {
    // VERIFS DEJA FAITES, OPERATION OK SUR DEMANDE
    if ('delete' == $_GET['operation']) {
        if (isset($_GET['lock']) && 'unlocked' != $_GET['lock']) {
            $errTab[] = 'Erreur : fichier verrouillé.';
        } else {
            if (!unlink($fullPath)) {
                $errTab[] = "Erreur : suppression de $target echouée.";
            }
        }
        // fermeture de la box/ actualissation du ftp
        if (0 === count($errTab)) {
            ?>
            <script type="text/javascript">
                parent.$('#ftp-2-arbo a.selected').click();
                parent.$.fancybox.close();
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
            <body class="ftp-frame" onload="$('form input[type=submit]').focus();">
                <?php
                // msg d'erreur ?
                if (count($errTab) > 0) {
                    echo '<div class="erreur"><ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
                } ?>

                <h3>Suppression du fichier</h3>

                <p>
                    Voulez-vous vraiment supprimer le fichier ci-dessous ? Cette opération est <u>définitive</u>. Si des liens existent vers ce fichier, ils deviendront
                    des liens morts, renvoyant une erreur 404.
                </p>
                <p><b><a href="<?php echo html_utf8($target); ?>" target="_blank"><?php echo html_utf8(substr($target, 7)); ?></a></b></p>

                <form action="ftp-deletefile.php" method="GET">
                    <input type="hidden" name="operation" value="delete" />
                    <input type="hidden" name="target" value="<?php echo html_utf8($target); ?>" />
                    <input type="hidden" name="lock" value="locked" />
                    <br />
                    <input type="submit" class="nice red" value="Supprimer ce fichier" onclick="$(this).siblings('input[name=lock]').val('unlocked')" />
                    <input type="button" class="nice orange" value="Annuler" onclick="parent.$.fancybox.close()" />
                </form>
            </body>
        </html>
        <?php
    }
}
