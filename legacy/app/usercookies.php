<?php

// Gestion des cookies utilisateur : si pas connecté mais cookie existe
if (!$_GET['user_logout'] && !user() && strpos($_COOKIE['cafuser'], '-')) {
    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    // Verification du token puis connection
    $tab = explode('-', $_COOKIE['cafuser']);
    $cookietoken_user = $mysqli->real_escape_string($tab[1]);
    $id_user = (int) ($tab[0]);
    $req = 'SELECT email_user FROM  `'.$pbd."user` WHERE  `id_user` = $id_user AND valid_user=1 AND `cookietoken_user` LIKE  '$cookietoken_user' LIMIT 1";
    // echo "<!-- $req -->";
    $handleSql = $mysqli->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        user_login($handle['email_user'], true);
    }
    $mysqli->close();
}
