<?php

global $errTab;
global $p_ftp_proteges;
global $compte;

include __DIR__.'/../app/includes.php';

if (!admin()) {
    header('HTTP/1.0 401 Authorization Required');
    echo 'Votre session administrateur a expiré';
    exit();
}

$errTab = [];
$target = $_GET['target'];
$filename = strtolower(substr(strrchr($target, '/'), 1));
$compte = 0;

// vérification,
// la cible doit commencer par ../ftp/
if ('../ftp/' != substr($target, 0, 7)) {
    $errTab[] = "Le chemin d'accès au dossier est incorrect";
}
// ne doit pas contenir ../ à part au début
if (1 != mb_substr_count($target, '../')) {
    $errTab[] = "Le chemin d'accès au dossier est incorrect : récurrence de chemin retour";
}
// doit être un dossier
if (!is_dir($target)) {
    $errTab[] = "L'élément donné ne semble pas être un dossier";
}

// vérification de la protection du dossier, et de chaque élément dans le dossier. target=dossier à lire
function checkMe($target)
{
    global $errTab;
    global $p_ftp_proteges;
    global $compte;
    ++$compte;

    // cet élément est-il protégé ?
    if (in_array(substr($target, 7), $p_ftp_proteges, true)) {
        $errTab[] = "L'élément ".($compte > 1 ? 'contenu dans ce dossier' : '').' : <b>'.strtolower(substr(strrchr($target, '/'), 1)).'</b> est protégé contre la suppression';
    }

    // si c'est un dossier, on l'ouvre et on verfiie ses contenus
    if (is_dir($target)) {
        $opendir = opendir($target);
        while ($file = readdir($opendir)) {
            if ('.' != $file && '..' != $file && 'index.php' != $file && '.htaccess' != $file) {
                checkMe($target.'/'.$file);
            }
        }
    }
}
checkMe($target);

if (count($errTab) > 0) {
    echo '<div class="erreur"><ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
} else {
    // VERIFS DEJA FAITES, OPERATION OK SUR DEMANDE
    if ('delete' == $_GET['operation']) {
        if ('unlocked' != $_GET['lock']) {
            $errTab[] = 'Erreur : fichier verrouillé.';
        } else {
            if (!clearDir($target)) {
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
            exit();
        }
    }

    // OPERATION PAS LANCEE OU ERREUR
    if (!isset($_POST['operation']) || 'delete' == $_POST['operation'] && count($errTab) > 0) {
        ?><!doctype html>
        <html lang="fr">
            <head>
                <meta charset="utf-8">
                <title>DOSSIER FTP</title>

					<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/css/admin.css">
					<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/css/base.css">
					<!-- jquery -->
					<script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>

            </head>
            <body class="ftp-frame">
                <?php
                // msg d'erreur ?
                if (count($errTab) > 0) {
                    echo '<div class="erreur"><ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
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
