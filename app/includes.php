<?php

    define ('ADMIN', ROOT.'admin'.DS);					// Admin
    define ('APP', ROOT.'app'.DS);						// Applicatif
    define ('SCRIPTS', ROOT.'scripts'.DS); 				// MySQL Queries
    $scriptsDir = SCRIPTS;
    define ('PAGES', ROOT.'pages'.DS);					// Pages
    define ('CONFIG', ROOT.'config'.DS);					// Pages
    define ('INCLUDES', ROOT.'includes'.DS);					// Pages

    /*
        Ce script ne fonctionnera plus le jour où plusieurs sites seront hébergés dans le même ROOT, car il existera plusieurs dossiers de configuration dans CONFIG.
        Il faudra alors définir un paramètre pour les tâches CRON !
    */
    if ($_SERVER && array_key_exists('HTTP_HOST', $_SERVER)) {
        define ('MON_DOMAINE', $_SERVER['HTTP_HOST']);
    } else {
        if($dossier = opendir(CONFIG)) {
            $mon_domaine = null;
            while($Entry = @readdir($dossier)) {
                if(is_dir(CONFIG.$Entry)&& !in_array($Entry, array('.', '..', 'cafdemo.dev'))) {
                    $mon_domaine = $Entry;
                }
            }
        }
        define ('MON_DOMAINE', $mon_domaine);
    }

    //_________________________________________________ GESTION ET SECURISATIONS DES SESSIONS
    include APP.'sessions.php';
    //_________________________________________________ FONCTIONS MAISON
    include APP.'fonctions.php';
    //_________________________________________________ VARIABLES "GLOBALES" DU SITE
    include APP.'params.php';
    //_________________________________________________ LANGUES
    include APP.'langs.php';
    //_________________________________________________ FONCTIONS PARTAGEES
    include SCRIPTS.'fonctions.php';
    //_________________________________________________ OPERATIONS ADMIN & CLIENT
    include SCRIPTS.'operations.php';
    //_________________________________________________ GESTION DES COOKIES UTILISATEUR
    include APP.'usercookies.php';
    //_________________________________________________ PARAMS PAGE EN COURS (META/TITRES/EXIST.)
    include APP.'pages.php';
    //_________________________________________________ REQUETES INHERENTES A LA PAGE
    include SCRIPTS.'reqs.php';
