<?php

// choix de la langue en fonction du navigateur
function autoSelectLanguage($aLanguages, $sDefault = 'fr')
{
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $aBrowserLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($aBrowserLanguages as $sBrowserLanguage) {
            $sLang = strtolower(substr($sBrowserLanguage, 0, 2));
            if (in_array($sLang, $aLanguages, true)) {
                return $sLang;
            }
        }
    }

    return $sDefault;
}

// LANGUES
if (in_array($_GET['lang'], $p_langs, true)) {				// si une (bonne) langue est donn?e par get
    $lang = $_SESSION['lang'] = $_GET['lang'];
} 		// var locale et session prennent la langue donnee
elseif (in_array($_SESSION['lang'], $p_langs, true)) {		// sinon, si une session contient la langue
    $lang = $_SESSION['lang'];
}					// la var locale utilise la sessions
else { // sinon, ni donn?e ni en session
    $lang = $_SESSION['lang'] = autoSelectLanguage($p_langs, $p_langs[0]);
}		// langue du navigateur par d?faut

if (!in_array($lang, $p_langs, true)) {
    $lang = $p_langs[0];
}	// si rien n'y fit, premiere langue par d?faut.
