<?php

//_________________________________________________ DEFINITION DES DOSSIERS
define('DS', \DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__).DS);				// Racine
include ROOT.'app'.DS.'includes.php';

if (!admin()) {
    echo 'Votre session administrateur a expiré';
    exit();
}

    $targetRel = $_GET['target'];
    $targetAbs = substr($p_racine, 0, strlen($p_racine) - 6).$_GET['target']; // substr = supprimer admin/

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
		<body class="ftp-frame">

			<h3>URL absolue</h3>
			<p>Pour partager un fichier par e-mail, ou sur un autre site internet.</p>
			<input type="text" name="" value="<?php echo html_utf8($targetAbs); ?>" class="urlSelecter" />
			<br />
			<br />

			<h3>URL relative</h3>
			<p>Pour affichage d'une image, ou pour créer un lien vers un fichier, dans une page de ce site.</p>
			<input type="text" name="" value="<?php echo html_utf8($targetRel); ?>" class="urlSelecter" />

			<script type="text/javascript">
				$('.urlSelecter').bind('focus click', function(){
					$(this).select();
					// return false;
				});
				$('.urlSelecter').bind('mouseup', function(){
					return false
				});
			</script>

		</body>
	</html>
	<?php

?>
