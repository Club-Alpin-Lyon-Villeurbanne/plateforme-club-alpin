<?php

include __DIR__.'/../app/includes.php';

$max_tentatives = 3; // nombre de tentatives autorisées
$tps_tentatives = 6; // Temps en heure avant possibilité de renouvellement des tentatives

$errTab = []; // gestion des erreurs

$login_admin = $p_admin_login;
$pass_admin = $p_admin_password;

// Tentatives de login
// trick admin en local :
if ('superadmin' == $_POST['loginLocal'] && '127.0.0.1' == $_SERVER['HTTP_HOST']) {
    superadmin_start();
}
if ('hwc' == $_POST['loginLocal'] && 'cafdemo.dev' == $_SERVER['HTTP_HOST']) {
    superadmin_start();
}
if ('admin' == $_POST['loginLocal'] && '127.0.0.1' == $_SERVER['HTTP_HOST']) {
    admin_start();
}
if ($_POST['login']) {
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    $login = $mysqli->real_escape_string(stripslashes(substr($_POST['login'], 0, 20)));

    // verification de l'existence du comtpe
    if (0 === count($errTab)) {
        if (stripslashes($_POST['login']) != $login_admin) {
            $errTab[] = 'Login ou mot de passe incorrect.';
        } // bluff : c'est le login qui est faux. Ecrire le même message en cas d'erreur mdp
    }

    // vérification de compte bloqué ?
    if (0 === count($errTab)) {
        $req = 'SELECT date_log_admin FROM `'.$pbd."log_admin` WHERE code_log_admin LIKE 'lock-admin-account-$login' AND `date_log_admin` >0 ORDER BY  `date_log_admin` DESC LIMIT 1";
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $dateButoire = $handle['date_log_admin'] + $tps_tentatives * 60 * 60;
            $errTab[] = "Désolé, ce compte a été verrouillé jusqu'au ".date('d/m/Y à H:i', $dateButoire).' suite à de trop nombreuses tentatives de connexion.';
        }
    }

    // vérification du mot de passe
    if (stripslashes($_POST['login']) == $login_admin && md5(stripslashes($_POST['password'])) == $pass_admin) {
        admin_start(false); // false=ne pas connecter/deconnecter de la BD
    }

    // si faux,   +
    $ip_log_admin = $mysqli->real_escape_string($_SERVER['REMOTE_ADDR']);
    if (!admin() && 0 === count($errTab)) {
        // message d'erreur
        $errTab[] = 'Login ou mot de passe incorrect.';
        // log tetative echouee
        $req = 'INSERT INTO  `'.$pbd."log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`date_log_admin`, `ip_log_admin`)
						VALUES (NULL ,  'login-attempt-failure-$login',  'Tentative de connexion échouée au compte $login',  $p_time, '$ip_log_admin');";
        $mysqli->query($req);
        // si trop nombreuses dans le délai imparti, bloquage du compte et message d'erreur
        $dateVerif = $p_time - $tps_tentatives * 60 * 60;
        $req = 'SELECT COUNT(id_log_admin) FROM  `'.$pbd."log_admin` WHERE  `code_log_admin` LIKE  'login-attempt-failure-$login' AND  `date_log_admin` >$dateVerif";
        $handleSql = $mysqli->query($req);
        $nAttempts = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));
        // bloquage necessaire
        if ($nAttempts > $max_tentatives) {
            $dateButoire = $p_time + $tps_tentatives * 60 * 60;
            $errTab[] = "Désolé, ce compte a été verrouillé jusqu'au ".date('d/m/Y à H:i', $dateButoire).' suite à de trop nombreuses tentatives de connexion.';
            $req = 'INSERT INTO  `'.$pbd."log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`date_log_admin`)	VALUES (NULL ,  'lock-admin-account-$login',  'Bloquage du compte administrateur $login suite à de trop nombreuses tentatives de connexion',  $p_time);";
            if (!$mysqli->query($req)) {
                $errTab[] = '';
            }
        }
        // pas de bloquage mais compte des essais restants
        else {
            $errTab[] = ($max_tentatives - $nAttempts).' essais restants';
        }
    }
    // si bon, login, [et rechargement de la page en mode admin ?] enregistrement du login
    else {
        if (admin()) {
            $req = 'INSERT INTO  `'.$pbd."log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`date_log_admin`, `ip_log_admin`)
										VALUES (NULL ,  'login-success-$login',  'Login de $login',  '$p_time', '$ip_log_admin');";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL lors du log';
            }
        }
    }

    $mysqli->close();
}

// :::::::::::::::::::::: Affichage page
?>

<!doctype html>
<html class="no-js" lang="en">
<head>
	<!-- More ideas for your <head> here: h5bp.com/d/head-Tips -->
	<meta charset="utf-8">
	<link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
	<script src="/js/fonctions.js" language="javascript" type="text/javascript"></script>
    <!-- jquery -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
	<link rel="stylesheet" href="/css/loginadmin.css" type="text/css"  media="screen" />
	<!--[if IE]>
		<style type="text/css">
				form{		behavior: url(../border-radius.htc);}
		</style>
	<![endif]-->
</head>
<body style="text-align:center;">
	<?php
    if (count($errTab) > 0) {
        echo '<div class="error"><ul><li>'.implode('</li><li>', $errTab).'</div><br />';
    }
    if (!admin()) {
        if ('127.0.0.1' == $_SERVER['HTTP_HOST']) {
            ?>
			<form action="index.php" method="post" class="bloc">
				Dev en local : go superadmin :
				<input type="hidden" name="loginLocal" value="superadmin" />
				<input type="image" src="./ok.gif" alt="OK" title="OK" style="vertical-align:middle" class="upimage" />
			</form>
			<br />
			<form action="index.php" method="post" class="bloc">
				Dev en local : go admin :
				<input type="hidden" name="loginLocal" value="admin" />
				<input type="image" src="./ok.gif" alt="OK" title="OK" style="vertical-align:middle" class="upimage" />
			</form>
			<br />
			<?php
        } ?>
		<form action="index.php" method="post" style="" class="bloc">
			<input type="text"      style="width:240px;height:17px;background:#34383c;border:1px solid #34383c;padding:3px;margin:0px 0px 3px 0px; color:#a7a7a7; font-size:11px	" name="login" placeholder="Votre identifiant ..." /><br />
			<input type="password"  style="width:207px;height:17px;background:#34383c;border:1px solid #34383c;padding:3px;margin:0px 0px 3px 0px; color:#a7a7a7; font-size:11px; 	" name="password" placeholder="Votre mot de passe" />

			<input type="image" src="./ok.gif" alt="OK" title="OK" class="upimage" style="vertical-align:middle" />
			<br />
			<a href="../" title="" style="font-size:9px;color:#a7a7a7;text-decoration:none">Retour</a>
		</form>
		<?php
    }
    // loggué
    else {
        ?>
		<div class="bloc">
			<h2><img src="/img/base/star.png" alt="" titile="" /> Mode <?php echo $_SESSION['admin']['mode']; ?></h2>
			<p>Connexion effectuée avec succès !</p>
			<p>N'oubliez pas de vous déconnecter à la fin de votre session.</p>
			<a href="../" title="" class="bigbutton" id="keepgoing">Continuer...</a>
		</div>
		<br />
		<script type="text/javascript">
		$('#keepgoing').focus();
		setTimeout("location.href = '/';",5000);
		</script>
		<?php
    }
    ?>
	<br />
	Un site réalisé par :<br />
	<a href="https://www.herewecom.fr" title="Conception de sites internet dynamiques à chambéry, Savoie"><img src="logo-hwc.gif" alt="Conception de sites internet dynamiques à chambéry, Savoie" title="Conception de sites internet dynamiques à chambéry, Savoie" /></a>


</body>
</html>
