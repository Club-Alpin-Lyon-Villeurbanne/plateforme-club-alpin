<?php

date_default_timezone_set('Europe/Paris');
setlocale(\LC_ALL, 'fr_FR');

// ---------------------
// PARAMS AU CAS PAR CAS

// chemin absolu vers la racine des fichiers
$p_racine = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1);

// ADMIN
$p_admin_login = 'hwc';
$p_admin_password = md5('hwc');

// NOM DU SITE ( apparaît notamment dans les e-mailings )
$p_sitename = 'Club Alpin Français - XXX';

// destinataire principal
$p_contactdusite = 'webmaster@xxx.fr';
$p_noreply = 'noreply@xxx.fr';

// GOOGLE
$p_google_analytics_account = 'UA-42585248-1';

// préfixe de la base de donnée
$pbd = 'caf_';

// Mise à jour manuelle des adhérents
$p_user_updatefiles = false;
// nombre de colonnes dans le fichier adherent xxxx.csv
$p_csv_adherent_nb_colonnes = 33;

// -------------------
// PARAMS ACCUEIL
$limite_articles_accueil = 16;
$limite_sorties_accueil = 30;

// -------------------
// PARAMS ADHERENT
$limite_articles_adherent = 10;
$limite_sorties_adherent = 10;

// -------------------
// PARAMS VALIDATION
$limite_articles_validation = 10;
$limite_sorties_validation = 10;

// -------------------
// PARAMS CAF
$limite_longeur_numero_adherent = 12;

// mail de rappel lors de la validation juridique d'une sortie
$do_mail_evt_legal_update = false;

// temps en secondes avant un événement pour le rappeler à l'utilisateur (créateur exclu)
$do_p_chron_rappel_user_avant_event_1 = false;
$do_p_chron_rappel_user_avant_event_2 = false;
$p_chron_rappel_user_avant_event_1 = 60 * 60 * 24 * 4; // à j-4
$p_chron_rappel_user_avant_event_2 = 60 * 60 * 24 * 2; // à j-2

// timestamp maximum d'un evt pour demander validation président
$p_tsp_max_pour_valid_legal_avant_evt = strtotime('midnight +8 days'); // timestamp de maintenant +8 jours à minuit = couvre tous les événements commencant dans les SEPT jours à venir

// timestamp maximum d'un evt pour autoriser les inscriptions (j+1)
$p_tsp_max_pour_join = strtotime('midnight +2 days');

// timestamp butoire de fin d'un evt pour rappeler à son auteur de rédiger un compte rendu
$p_tsp_max_pour_rappel_redac_cr = strtotime('midnight'); // cette nuit à minuit (donc l'evt a fini hier)

// nombre de versions des contenus à garder dans l'historique (entier positif)
$p_nmaxversions = 5;

// PARTENAIRES
$p_showPartenairesSlider = '';

// modules activés
$p_modules = [];
// langues
$p_langs = ['fr']; // la langue par défaut en premier
// positionnement des liens "editer ce bloc" en absolu (true), ou static (false)
$p_abseditlink = true;
// active / désactive le bouton modifier sur le rapport des contenus statiques manquant
$p_editmissingstatics = true;
// REGEX sélectionnant les caractères autorisés dans les USER IPUTS
$p_authchars = "/([^A-Za-z0-9 'âàéêèëîïôœûùüçßøOÐØÞþÅÂÀÉÊÈËÎÏÔŒÛÙÜÇßØOÐØÞÞÅ])/";
// Extensions autorisées dans le FTP
$p_ftpallowed = ['gpx', 'kml', 'kmz', 'jpg', 'gif', 'jpeg', 'png', 'doc', 'docx', 'odt', 'pdf', 'avi', 'mov', 'mp3', 'rar', 'zip', 'txt', 'xls', 'csv', 'ppt', 'pptx', 'ai', 'psd', 'fla', 'swf', 'eps'];
// tinymce vars
$p_tiny_theme_advanced_styles = 'Entete Article=ArticleEntete;Titre de menu=menutitle;Bleu clair du CAF=bleucaf;Image flottante gauche=imgFloatLeft;Image flottante droite=imgFloatRight;Lien fancybox=fancybox;Mini=mini;Bloc alerte=erreur;Bloc info=info';
// dimensino (w & h) max des images uploadées quand le redimensionnement est possible
$p_max_images_dimensions_before_redim = 1000;

// nbr de sous-niveaux dans l'arbo
$p_sublevels = 1;

//Selection des fichiers a afficher ou pas dans les outils FTP
$p_ftp_masquer = ['index.php', '.', '..', '.htaccess', 'Thumbs.db', 'transit', 'article', 'articles', 'commission', 'user', 'sorties', 'galeries', 'partenaires'];
// dossiers et fichiers a ne pas supprimer (racine: ftp/)
$p_ftp_proteges = ['images', 'telechargements', 'transit', 'fichiers-proteges'];

// couples question/reponses antispam
$p_as1 = ['Combien font quatre plus cinq ?', 'Combien font quatre fois deux ?', 'Combien font trois plus deux ?', 'Combien font deux plus cinq ?', 'Combien font cinq moins un ?', 'Combien font six moins trois ?', 'Combien font deux plus quatre ?', 'Combien font zéro plus deux ?', 'Combien font deux moins un ?'];
$p_as2 = [9, 8, 5, 7, 4, 3, 6, 2, 1];

// droits de l'user
$userAllowedTo = [];

// dates butoires : tableau des heures auxquelles déclencher l'envoi du chron :
$p_chron_dates_butoires = [8, 13, 18];
// -------------------
// LOGIN
$use_md5_salt = false;
$md5_salt = 'T~3|,#oBr"J8="n=\aX!@0Y>"Zf)+20rS_Ig7CkSpEG8(2z)KL';

// -------------------
// PARAMS STATIQUES

$p_multilangue = count($p_langs) < 2 ? false : true;
// Report simple running errors
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(\E_ALL ^ \E_NOTICE);
// vars de navigation, depuis l'URL via URL REWRITING // vars get toujours dispo grace au htaccess
$p1 = formater($_GET['p1'], 3);
$p2 = formater($_GET['p2'], 3);
$p3 = formater($_GET['p3'], 3);
$p4 = formater($_GET['p4'], 3);
// debug pour calls ajax
if ('scripts/' == substr($p_racine, -8)) {
    $p_racine = substr($p_racine, 0, strlen($p_racine) - 8);
}
// par défaut, la page courante n'est pas admin (modifié en aval dans pages.php)
$p_pageadmin = false;
