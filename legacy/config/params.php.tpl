<?php

date_default_timezone_set('Europe/Paris');
setlocale(\LC_ALL, 'fr_FR');

// NOM DU SITE ( apparaît notamment dans les e-mailings )
$p_sitename = 'Club Alpin Français - XXX';

// destinataire principal
$p_contactdusite = 'webmaster@xxx.fr';

// -------------------
// PARAMS STATIQUES

error_reporting(\E_ALL ^ \E_NOTICE);

// vars de navigation, depuis l'URL via URL REWRITING // vars get toujours dispo grace au htaccess
$p1 = formater($_GET['p1'] ?? null, 3);
$p2 = formater($_GET['p2'] ?? null, 3);
$p3 = formater($_GET['p3'] ?? null, 3);
$p4 = formater($_GET['p4'] ?? null, 3);

// par défaut, la page courante n'est pas admin (modifié en aval dans pages.php)
$p_pageadmin = false;
