<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define('DS', \DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__).DS);				// Racine
include ROOT.'app'.DS.'includes.php';

if (!admin()) {
    echo 'Votre session administrateur a expiré';
    exit();
}

    $errTab = [];
    $target = $_GET['target'];
    $filename = strtolower(substr(strrchr($target, '/'), 1));

    // vérification,
    // la cible doit commencer par ../ftp/
    if ('../ftp/' != substr($target, 0, 7)) {
        $errTab[] = "Le chemin d'accès au fichier est incorrect";
    }
    // ne doit pas contenir ../ à part au début
    if (1 != mb_substr_count($target, '../')) {
        $errTab[] = "Le chemin d'accès au fichier est incorrect : récurrence de chemin retour";
    }
    // doit être un fichier
    if (!is_file($target)) {
        $errTab[] = "L'élément donné ne semble pas être un fichier";
    }
    // ne pas être un fichier sensible (htaccess)
    if ('.htaccess' == $filename) {
        $errTab[] = 'Le fichier .htaccess ne peut pas être supprimé';
    }
    if ('index.php' == $filename) {
        $errTab[] = 'Le fichier index.php ne peut pas être supprimé';
    }
    // ne pas être protégé (params.php)
    if (in_array(substr($target, 7), $p_ftp_proteges, true)) {
        $errTab[] = "Le fichier $filename est protégé contre la suppression";
    }

    if (isset($errTab) && count($errTab) > 0) {
        echo '<div class="erreur"><ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
    } else {
        // VERIFS DEJA FAITES, OPERATION OK SUR DEMANDE
        if ('delete' == $_GET['operation']) {
            if ('unlocked' != $_GET['lock']) {
                $errTab[] = 'Erreur : fichier verrouillé.';
            } else {
                if (!unlink($target)) {
                    $errTab[] = "Erreur : suppression de $target echouée.";
                }
            }
            // fermeture de la box/ actualissation du ftp
            if (!isset($errTab) || 0 === count($errTab)) {
                ?>
				<script type="text/javascript">
					parent.$('#ftp-2-arbo a.selected').click();
					parent.$.fancybox.close();
				</script>
				<?php
                exit();
            }
        }

        // OPERATION PAS LANCEE OU ERREUR
        if (!$_POST['operation'] || 'delete' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            ?><!doctype html>
			<html lang="fr">
				<head>
					<meta charset="utf-8">
					<title>DOSSIER FTP</title>

					<link rel="stylesheet" media="screen" type="text/css" title="Design" href="../css/admin.css">
					<link rel="stylesheet" media="screen" type="text/css" title="Design" href="../css/base.css">
					<!-- jquery -->
					<script type="text/javascript" src="../js/jquery-1.5.2.min.js"></script>

				</head>
				<body class="ftp-frame" onload="$('form input[type=submit]').focus();">
					<?php
                    // msg d'erreur ?
                    if (isset($errTab) && count($errTab) > 0) {
                        echo '<div class="erreur"><ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
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

?>
