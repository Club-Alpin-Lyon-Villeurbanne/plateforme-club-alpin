<?php

use App\Entity\CafUser;
use Symfony\Bridge\Twig\AppVariable;

global $_POST;
global $allowedError; // Erreur facultative à afficher si la fonction renvoie false
global $CONTENUS_INLINE;
global $contLog;
global $kernel;
global $lang;
global $p_abseditlink;
global $p_devmode;
global $p_inclurelist;
global $p_racine;
global $president;
global $userAllowedTo; // liste des opérations auxquelles l'user est autorisé. tableau associatif : la clé est le code de l'opératin, sa valeur les parametres
global $versCettePage;
global $vicepresident;

// gestion des contenus
$contLog = [];
$CONTENUS_HTML = [];
$CONTENUS_INLINE = [];

// ----------------------------------------------------------------------------------------------------------------
// -------------------------------------------- FONCTIONS SPECIFIQUES AU SITE DU CLUB ALPIN FRANCAIS
// ----------------------------------------------------------------------------------------------------------------

function presidence()
{
    global $president;
    global $vicepresident;

    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

    $president = $vicepresident = [];
    $president_sql = 'SELECT * FROM `caf_user` AS U LEFT JOIN `caf_user_attr` AS A  ON A.usertype_user_attr = 6 WHERE U.id_user = A.user_user_attr';
    $president_result = $mysqli->query($president_sql);
    while ($row_president = $president_result->fetch_assoc()) {
        if ('1' !== $row_president['id_user']) {
            $president[] = $row_president;
        }
    }

    $vicepresident = [];
    $vicepresident_sql = 'SELECT * FROM `caf_user` AS U LEFT JOIN `caf_user_attr` AS A  ON A.usertype_user_attr = 7 WHERE U.id_user = A.user_user_attr';
    $vicepresident_result = $mysqli->query($vicepresident_sql);
    while ($row_vicepresident = $vicepresident_result->fetch_assoc()) {
        if ('1' !== $row_vicepresident['id_user']) {
            $vicepresident[] = $row_vicepresident;
        }
    }
}

/*
Find URLs in Text, Make Links
*/
function getUrlFriendlyString($text)
{
    $SCHEMES = ['http', 'https', 'ftp', 'mailto', 'news',
        'gopher', 'nntp', 'telnet', 'wais', 'prospero', 'aim', 'webcal', ];
    // Note: fragment id is uchar | reserved, see rfc 1738 page 19
    // %% for % because of string formating
    // puncuation = ? , ; . : !
    // if punctuation is at the end, then don't include it

    $URL_FORMAT = '~(?<!\w)((?:'.implode('|',
        $SCHEMES).'):' // protocol + :
    .'/*(?!/)(?:' // get any starting /'s
    .'[\w$\+\*@&=\-/]' // reserved | unreserved
    .'|%%[a-fA-F0-9]{2}' // escape
    .'|[\?\.:\(\),;!\'](?!(?:\s|$))' // punctuation
    .'|(?:(?<=[^/:]{2})#)' // fragment id
    .'){2,}' // at least two characters in the main url part
    .')~';

    preg_match_all($URL_FORMAT, $text, $matches, \PREG_SPLIT_DELIM_CAPTURE);

    $usedPatterns = [];
    foreach ($matches as $patterns) {
        $pattern = $patterns[0];
        if (!array_key_exists($pattern, $usedPatterns)) {
            $usedPatterns[$pattern] = true;
            $text = str_replace($pattern, "<a href='".$pattern."' rel='nofollow'>".$pattern.'</a> ', $text);
        }
    }

    return $text;
}

/*
La fonction "userlink" affiche un lien vers le profil d'un utilisateur en fonction du contexte demandé par "style"
*/
function userlink($id_user, $nickname_user, $civ_user = false, $firstname_user = false, $lastname_user = false, $style = 'public')
{
    global $p_racine;
    $return = '';

    switch ($style) {
        case 'public': 	$return = html_utf8($nickname_user); break;
        case 'short': 	$return = html_utf8($civ_user).' '.html_utf8($firstname_user).' '.strtoupper(substr(trim($lastname_user), 0, 1)); break;
        case 'full': 	$return = html_utf8($civ_user).' '.html_utf8($firstname_user).' '.html_utf8($lastname_user); break;
        default:		return;
    }

    // habillage du lien (CSS:userlink)
    $return = '<a href="'.$p_racine.'includer.php?p=includes/fiche-profil.php&amp;id_user='.(int) $id_user.'" class="fancyframe userlink" title="'.cont('userlink-title').'">'.$return.'</a>';

    return $return;
}

/*
La fonction "userImg" prend l'ID d'un user et retourne l'URL absolue du picto ou de la photo
user désirée ou bien le picto par défaut si celle-ci n'existe pas.
*/
function userImg($id_user, $style = '')
{
    global $p_racine;

    switch ($style) {
        case 'pic':
            $style = $style.'-';
            break;
        case 'min':
            $style = $style.'-';
            break;
        default:
            $style = '';
            break;
    }

    $rel = 'ftp/user/'.$id_user.'/'.$style.'profil.jpg';
    if (!file_exists(__DIR__.'/../../public/'.$rel)) {
        $rel = 'ftp/user/0/'.$style.'profil.jpg';
    }

    return $p_racine.$rel;
}

/*
La fonction "comFd" prend l'ID d'une commission et retourne l'URL absolue de l'aimge de fond
liée à cette commission, ou bien de celle par défaut
*/
function comFd($id_commission)
{
    global $p_racine;

    $rel = 'ftp/commission/'.(int) $id_commission.'/bigfond.jpg';
    if (!file_exists(__DIR__.'/../../public/'.$rel)) {
        $rel = 'ftp/commission/0/bigfond.jpg';
    }

    return $p_racine.$rel;
}

/*
La fonction "comPicto" prend l'ID d'une commisson et retourne l'URL absolue du picto
de la commission désirée ou bien le picto par défaut si celui-ci n'existe pas.
*/
function comPicto($id_commission, $style = '')
{
    global $p_racine;

    switch ($style) {
        case 'light': 	$style = '-'.$style; break;
        case 'dark': 	$style = '-'.$style; break;
        default:		$style = '';
    }

    $rel = 'ftp/commission/'.(int) $id_commission.'/picto'.$style.'.png';
    if (!file_exists(__DIR__.'/../../public/'.$rel)) {
        $rel = 'ftp/commission/0/picto'.$style.'.png';
    }

    return $p_racine.$rel;
}

/*
V2
*allowed* vérifie que l'user connecté en session a le droit d'accéder à certains opérations.
Selon le code demandé, des options sont nécessaires, via PARAMS_USER_ATTR
par exemple pour s'assurer que l'user cherche à écrire un article DANS LA BONNE COMMISSION
les options sont transmises par chaine de paires : commission:snowboard , ou param:valeur|param2:valeur2...
Au premier appel de cette fonction, le tableau USERALLOWEDTO est déclaré (var globale) contenant les autorisations nécessaire,
et pour éviter un grand nombre d'opérations en BDD. Ce tableau associatif prend pour clé le code de l'opération. Ex :
$userAllowedTo=
    [
        'article_read'=>true,
        'article_comment'=>true,
        'article_write'=>'commission:alpinisme'
    ]
*/
function allowed($code_userright, $param = '')
{
    global $userAllowedTo; // liste des opérations auxquelles l'user est autorisé. tableau associatif : la clé est le code de l'opératin, sa valeur les parametres
    global $allowedError; // Erreur facultative à afficher si la fonction renvoie false

    if (!user()) {
        return false;
    }

    $usertypes = ['1']; // id du niveau visiteur, le plus bas, commun à tous
    $allowedError = false;
    $return = false;

    $id_user = getUser()->getIdUser();

    // le tableau des droits est-il déja défini ? Non ? alors on le définit ici
    if (!$userAllowedTo || !is_array($userAllowedTo)) {
        // raz/créa tableau global
        $userAllowedTo = ['default' => '1']; // minimum une valeur

        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';

        $id_user = $mysqli->real_escape_string($id_user);

        if ($mysqli->ping()) { // si on est bien connecté à la BD
            // Si un adhérent est connecté et licence valide, récupération des droits attribués à cet adhérent
            if ($id_user && !getUser()->getDoitRenouvelerUser()) {
                // la requête remonte la chaine alimentaire, de l'ID de l'user jusqu'à l'ensemble de ses droit, avec les paramètres liés
                $req = ''
                .'SELECT DISTINCT code_userright, params_user_attr, limited_to_comm_usertype ' // on veut le code, et les paramètres de chaque droit, et savoir si ce droit est limité à une commission ou non
                .'FROM caf_userright, caf_usertype_attr, caf_usertype, caf_user_attr ' // dans la liste des droits > attr_droit_type > type > attr_type_user
                ."WHERE user_user_attr=$id_user " // de user à user_attr
                .'AND usertype_user_attr=id_usertype ' // de user_attr à usertype
                .'AND id_usertype=type_usertype_attr ' // de usertype à usertype_attr
                .'AND right_usertype_attr=id_userright ' // de usertype_attr à userright
                .'ORDER BY  params_user_attr ASC, code_userright ASC, limited_to_comm_usertype ASC LIMIT 0 , 500 ' // order by params permet d'optimiser la taille de la var globale. Si, si promis (14 lignes plus loin) !
                ;

                // lecture du resultat
                $result = $mysqli->query($req);

                // ajout du droit, avec ses paramètres, au tableau global des droits de cet user
                // sans paramètre, la valeur est une string 'true'
                // Il est possible que le même droit prenne plusieurs paramètres (ex : vous avez le droit d'écrire un article dans
                // deux commissions auquel cas, ils sont concaténés via le caractère |
                while ($row = $result->fetch_assoc()) {
                    // echo $row['code_userright'].'--limite_a_comm='.$row['limited_to_comm_usertype'].'='.$row['params_user_attr'].'<hr />';

                    // valeur : true ou param
                    if ($row['params_user_attr'] && $row['limited_to_comm_usertype']) {
                        $val = $row['params_user_attr'];
                    } else {
                        $val = 'true';
                    }

                    // si la valeur est true, pas besoin d'ajouter des parametres par la suite car true = "ok pour tout sans params"
                    if ('true' == $val) {
                        $userAllowedTo[$row['code_userright']] = $val;
                    }
                    // écriture, ou concaténation des paramètres existant
                    elseif ('true' != $userAllowedTo[$row['code_userright']]) {
                        $userAllowedTo[$row['code_userright']] = ($userAllowedTo[$row['code_userright']] ? $userAllowedTo[$row['code_userright']].'|' : '').$val;
                    }

                    if (admin() || superadmin()) {
                        $userAllowedTo[$row['code_userright']] = 'true';
                    }
                }

                if (getUser()->hasAttribute('Salarié')) {
                    // **********
                    // DEBUG : SI CONNECTÉ, ON A FORCÉMENT LE STATUT ADHÉRENT MAIS PAS LIE DANS LA BASE, SAUF SALARIE
                    $req = ''
                    .'SELECT DISTINCT code_userright, limited_to_comm_usertype '
                    .'FROM caf_userright, caf_usertype_attr, caf_usertype '
                    ."WHERE code_usertype LIKE 'adherent' " // usertype adherent
                    .'AND id_usertype=type_usertype_attr '
                    .'AND right_usertype_attr=id_userright '
                    .'ORDER BY  code_userright ASC, limited_to_comm_usertype ASC LIMIT 0 , 500 '
                    ;

                    // lecture du resultat
                    $result = $mysqli->query($req);

                    // ajout du droit, avec ses paramètres, au tableau global des droits de cet user
                    // sans paramètre, la valeur est une string 'true'
                    // Il est possible que le même droit prenne plusieurs paramètres (ex : vous avez le droit d'écrire un article dans
                    // deux commissions auquel cas, ils sont concaténés via le caractère |
                    while ($row = $result->fetch_assoc()) {
                        // valeur : true ou param
                        if ($row['params_user_attr'] && $row['limited_to_comm_usertype']) {
                            $val = $row['params_user_attr'];
                        } else {
                            $val = 'true';
                        }

                        // si la valeur est true, pas besoin d'ajouter des parametres par la suite car true = "ok pour tout sans params"
                        if ('true' == $val) {
                            $userAllowedTo[$row['code_userright']] = $val;
                        }
                        // écriture, ou concaténation des paramètres existant
                        elseif ('true' != $userAllowedTo[$row['code_userright']]) {
                            $userAllowedTo[$row['code_userright']] = ($userAllowedTo[$row['code_userright']] ? $userAllowedTo[$row['code_userright']].'|' : '').$val;
                        }

                        if (admin() || superadmin()) {
                            $userAllowedTo[$row['code_userright']] = 'true';
                        }
                    }
                    // FIN DEBUG
                    // **********
                }
            }
            // sinon, le visiteur aussi a des droits
            else {
                // la requête récupère tous les droits liés à un compte visiteur
                $req = ''
                .'SELECT DISTINCT code_userright '
                .'FROM caf_userright, caf_usertype_attr, caf_usertype ' // des droits au type
                ."WHERE code_usertype='visiteur' " // type visiteur
                .'AND id_usertype = type_usertype_attr ' // du type visiteur à ses attributions
                .'AND id_userright = right_usertype_attr ' // de ses attributions a ses droits
                .'LIMIT 500 '
                ;

                // lecture du resultat
                $result = $mysqli->query($req);

                // ajout du droit au tableau global
                // sans paramètre, la valeur est une string 'true'
                while ($row = $result->fetch_assoc()) {
                    // les droits visteurs sont tous à true, et ne dependent jamais de parametres
                    $val = 'true';
                    $userAllowedTo[$row['code_userright']] = $val;

                    if (admin() || superadmin()) {
                        $userAllowedTo[$row['code_userright']] = 'true';
                    }
                }
            }
        } else {
            $allowedError = 'Erreur à la connexion à la BDD';
            echo '<p class="erreur">'.$allowedError.'</p>';
        }
    }

    // Ici, le tableau des droits existe, cherchons ce qui nous intéresse : print_r($userAllowedTo);
    if ($userAllowedTo[$code_userright]) {
        // ce droit fait partie de la liste. Contient-il des paramètres ?
        if ('true' == $userAllowedTo[$code_userright]) {
            $return = true;
        }
        // sinon, si les paramètres ne sont pas précisés dans l'appel de la fonction, il ne sont pas à prendre en compte
        elseif (!$param) {
            $return = true;
        }
        // oui, il a des paramètres, faut donc les vérifier
        else {
            $tab = explode('|', $userAllowedTo[$code_userright]);
            foreach ($tab as $tmpParam) {
                if ($param == $tmpParam) {
                    $return = true;
                }
            }
        }
    }

    // par défaut, pas le droit
    return $return;
}

/*
Fonction pour calcuer l'âge
http://shanelabs.com/blog/2008/03/26/calculating-age-from-unix-timestamps-in-php/
Modifiée pour prendre directement un timestamp en variable
*/
function getYearsSinceDate($then)
{
    // return $then;
    // $then = intval($then);
    $then = bigintval($then);

    // get difference between years
    $years = date('Y', time()) - date('Y', $then);

    // get months of dates
    $mthen = date('n', $then);
    $mnow = date('n', time());
    // get days of dates
    $dthen = date('j', $then);
    $dnow = date('j', time());

    // if date not reached yet this year, we need to remove one year.
    if ($mnow < $mthen || ($mnow == $mthen && $dnow < $dthen)) {
        --$years;
    }

    // gestion des dates NULL
    if (null == $then) {
        return 'inconnu';
    }

    return $years;
}

// utile ci dessus
function bigintval($value)
{
    $value = trim($value);
    if (ctype_digit($value)) {
        return $value;
    }
    $value = preg_replace('/[^-0-9](.*)$/', '', $value);
    if (ctype_digit(abs($value))) {
        return $value;
    }

    return 0;
}

// ----------------------------------------------------------------------------------------------------------------
// -------------------------------------------- // FIN des fonctions specifiques
// ----------------------------------------------------------------------------------------------------------------

/**
 * Retourne la taille plus l'unité arrondie.
 *
 * @param mixed  $bytes  taille en octets
 * @param string $format formatage (http://www.php.net/manual/fr/function.sprintf.php)
 * @param string $lang   indique la langue des unités de taille
 *
 * @return string chaine de caractères formatées
 */
function formatSize($bytes, $format = '%.2f', $lang = 'fr')
{
    static $units = [
    'fr' => [
    'o',
    'Ko',
    'Mo',
    'Go',
    'To',
    ],
    'en' => [
    'B',
    'KB',
    'MB',
    'GB',
    'TB',
    ], ];
    $translatedUnits = &$units[$lang];
    if (false === isset($translatedUnits)) {
        $translatedUnits = &$units['en'];
    }
    $b = (float) $bytes;
    /*On gére le cas des tailles de fichier négatives*/
    if ($b > 0) {
        $e = (int) (log($b, 1024));
        /**Si on a pas l'unité on retourne en To*/
        if (false === isset($translatedUnits[$e])) {
            $e = 4;
        }
        $b = $b / 1024 ** $e;
    } else {
        $b = 0;
        $e = 0;
    }

    return sprintf($format.' %s', $b, $translatedUnits[$e]);
}
function user(): bool
{
    global $kernel;

    if ($token = $kernel->getContainer()->get('security.token_storage')->getToken()) {
        if ($token->getUser() instanceof CafUser) {
            return true;
        }
    }

    return false;
}
function getUser(): ?Cafuser
{
    global $kernel;

    if ($token = $kernel->getContainer()->get('security.token_storage')->getToken()) {
        if ($token->getUser() instanceof CafUser) {
            return $token->getUser();
        }
    }

    return null;
}
function csrfToken(string $intention): ?string
{
    global $kernel;

    return $kernel->getContainer()->get('legacy_csrf_token_manager')->getToken($intention);
}
function generateRoute(string $path): ?string
{
    global $kernel;

    return $kernel->getContainer()->get('legacy_router')->generate($path);
}
function twigRender(string $path, array $params = []): ?string
{
    global $kernel;

    $params['app'] = new AppVariable();
    $params['app']->setEnvironment($kernel->getContainer()->getParameter('kernel.environment'));
    $params['app']->setDebug($kernel->getContainer()->getParameter('kernel.debug'));
    $params['app']->setTokenStorage($kernel->getContainer()->get('legacy_token_storage'));
    $params['app']->setRequestStack($kernel->getContainer()->get('legacy_request_stack'));

    return $kernel->getContainer()->get('legacy_twig')->render($path, $params);
}

// enregistrement de l'activité sur le site
function mylog($code, $desc, $connectme = true)
{
    global $kernel;

    $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
    $code_log_admin = $mysqli->real_escape_string(trim($code));
    $desc_log_admin = $mysqli->real_escape_string(trim($desc));
    $date_log_admin = time();
    $ip_log_admin = $mysqli->real_escape_string($_SERVER['REMOTE_ADDR']);

    $req = "INSERT INTO `caf_log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`date_log_admin`, `ip_log_admin`)
        VALUES (NULL , '$code_log_admin',  '$desc_log_admin',  '$date_log_admin', '$ip_log_admin')";
    if (!$mysqli->query($req)) {
        $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
            'error' => $mysqli->error,
            'file' => __FILE__,
            'line' => __LINE__,
            'sql' => $req,
        ]);
        $errTab[] = 'Erreur SQL lors du log';
    }
}

// htmlentities avec utf8
function html_utf8($str)
{
    return htmlentities($str, \ENT_QUOTES, 'UTF-8');
}

// assurer un lien http
function linker($link)
{
    $link = trim($link);
    if ('http://' != substr($link, 0, 7) && 'https://' != substr($link, 0, 8)) {
        $link = 'https://'.$link;
    }

    return $link;
}

// ma fonction d'insertion élément inline
function cont($code = false, $html = false)
{
    $defLang = 'fr';
    global $CONTENUS_INLINE;
    global $lang;
    $tmplang = $lang;
    if (!$tmplang) {
        $tmplang = $defLang;
    }

    // log des erreurs
    global $contLog;
    // premier appel à la fonction
    if (!count($CONTENUS_INLINE)) {
        // v2 : BDD
        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        // sélection de chaque élément par ordre DESC
        $req = "SELECT `code_content_inline`, `contenu_content_inline`
            FROM  `caf_content_inline`
            WHERE  `lang_content_inline` LIKE  '$tmplang'
            ORDER BY  `date_content_inline` DESC
            ";
        $handleSql = $mysqli->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            // uniquement si pas déja renseigné
            if (!isset($CONTENUS_INLINE[$handle['code_content_inline']])) {
                $CONTENUS_INLINE[$handle['code_content_inline']] = $handle['contenu_content_inline'];
            }
        }
        // debug
        $CONTENUS_INLINE['dev'] = 'dev';
    }

    if (isset($CONTENUS_INLINE[$code])) {
        if (!$html) {
            return strip_tags($CONTENUS_INLINE[$code]);
        }

        return $CONTENUS_INLINE[$code];
    }
    // pas de contenu

    if (!in_array($code, $contLog, true) && $code) {
        $contLog[] = $code;
    }
    // afficher rien
    return '';
}

$p_inclurelist = [];
// ma fonction d'insertion /modification élément HTML en front office
function inclure($elt, $style = 'vide', $options = [])
{
    $defLang = 'fr';
    global $lang;
    if (!$lang) {
        $lang = $defLang;
    }
    global $p_abseditlink;
    global $versCettePage;
    global $p_inclurelist;

    // assurer un seul id d'élément par page
    if (!in_array($elt, $p_inclurelist, true)) {
        // default options values
        $editVis = true;
        $connect = true;

        foreach ($options as $key => $val) {
            if ('editVis' == $key) {
                $editVis = $val;
            }
            if ('connect' == $key) {
                $connect = $val;
            }
        }

        $mysqli = include __DIR__.'/../scripts/connect_mysqli.php';
        $code_content_html = $mysqli->real_escape_string($elt);

        // Contenu
        $req = "SELECT `vis_content_html`,`contenu_content_html` FROM `caf_content_html` WHERE `code_content_html` LIKE '$code_content_html' AND lang_content_html LIKE '".$lang."' ORDER BY `date_content_html` DESC LIMIT 1";
        $handleTab = [];
        $handleSql = $mysqli->query($req);
        $found = false;
        $currentElement = ['vis_content_html' => 1, 'contenu_content_html' => null]; // default values
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $found = true;
            $currentElement = $handle;
        }

        // AFFICHAGE DES OUTILS ADMIN
        // mode admin : permet la modification
        if (admin()) {
            // fancybox
            echo '<div id="'.$elt.'" class="contenuEditable '.$style.'">
                <div class="editHtmlTools" style="text-align:left;">
                    <a href="editElt.php?p='.$elt.'&amp;class='.$style.'" title="Modifier l\'&eacute;l&eacute;ment '.$elt.'" class="edit fancyframeadmin" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-size:11px; font-family:Arial;">
                        <img src="/img/base/page_edit.png" id="imgEdit'.$elt.'" alt="EDIT" title="Modifier l\'&eacute;l&eacute;ment '.$elt.'" />Modifier</a>
                    '.($editVis ? '
                    <a href="javascript:void(0)" onclick="window.document.majVisBlock(this, \''.$elt.'\')" rel="'.$currentElement['vis_content_html'].'" title="Activer / Masquer ce bloc de contenu" class="edit" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-size:11px; font-family:Arial; ">
                        <img src="/img/base/page_white_key.png" alt="VIS" title="Activer / Masquer ce bloc de contenu" />Visibilité</a>
                        ' : '').'
                </div>';
        } else {
            echo '<div id="'.$elt.'" class="'.$style.'">';
        }
        // AFFICHAGE DU CONTENU
        if ($currentElement['vis_content_html']) {
            echo $currentElement['contenu_content_html'];
        }
        // contenu masqué
        else {
            if (admin()) {
                echo '<div class="blocdesactive"><img src="/img/base/bullet_key.png" alt="" title="" /> Bloc de contenu désactivé</div>';
            }
        }

        if (!$found) {
            echo '&nbsp;';
        }
        // pour débugger les blocs flottants
        echo '</div>';

        // enregistrer l'inclusino de ce elt
        $p_inclurelist[] = $elt;
    } else {
        echo '<p class="erreur" style="clear:both; ">Erreur de développement : les codes d\'éléments HTML ne peuvent être en doublon dans une même page</p>';
    }
}
// Affiche (ECHO !!) dans un input hidden ou text le contenu de la variable postée échappée quand elle existe, ou une valeur par défaut
function inputVal($inputName, $defaultVal = '')
{
    global $_POST;
    $input = explode('|', $inputName);
    if (!$input[1]) {
        if ($_POST[$inputName]) {
            return html_utf8(stripslashes($_POST[$inputName]));
        }

        return html_utf8($defaultVal);
    }
    if (2 == count($input)) {
        if ($_POST[$input[0]][$input[1]]) {
            return html_utf8(stripslashes($_POST[$input[0]][$input[1]]));
        }

        return html_utf8($defaultVal);
    }
    if (3 == count($input)) {
        if ($_POST[$input[0]][$input[1]][$input[2]]) {
            return html_utf8(stripslashes($_POST[$input[0]][$input[1]][$input[2]]));
        }

        return html_utf8($defaultVal);
    }
}

// affiche date format humain
function mois($mois)
{
    global $lang;
    switch ($lang) {
        case 'en': $tab = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']; break;
        default: $tab = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    }

    return $tab[(int) $mois - 1];
}
function jour($n, $mode = 'full')
{
    global $lang;
    switch ($lang) {
        case 'en': $tab = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; break;
        default: $tab = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']; break;
    }
    $return = $tab[(int) $n - 1];
    if ('short' == $mode) {
        $return = substr($return, 0, 3);
    }

    return $return;
}

// limitateur de texte au niveau des espace. Longueur en lettres
function limiterTexte($text, $length, $html = false)
{
    if (!$html) {
        $text = str_replace('<br />', ' ', $text);
        $text = str_replace('<br>', ' ', $text);
        $text = strip_tags($text);
    }
    if (strlen($text) > $length) {
        $pos = strpos($text, ' ', $length);
        if (!$pos) {
            $pos = strlen($text);
        }
        $text = substr($text, 0, $pos);
    }

    return $text;
}

// convention de nommage automatique
function wd_remove_accents($str, $charset = 'UTF-8')
{
    $str = htmlentities($str, \ENT_QUOTES, $charset);
    // $str = htmlentities($str);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'

    return preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
}
function formater($retourner, $type = 1)
{
    // Type 1 : sans espace ni tirets, en minuscule
    if (1 == $type) {
        $retourner = str_replace("'", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $retourner = wd_remove_accents(strtolower(stripslashes($retourner)));
        $pattern = "#[^a-z0-9\s]#";
        $retourner = preg_replace($pattern, '', $retourner);
    }
    // Type 2 : sans espace ni tirets, majuscule à chaque mot
    if (2 == $type) {
        $retourner = str_replace("'", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-z0-9\s]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $handle = explode(' ', $retourner);
        for ($i = 0; $i < count($handle); ++$i) {
            $handle[$i] = strtoupper(substr($handle[$i], 0, 1)).strtolower(substr($handle[$i], 1, strlen($handle[$i])));
        }
        $retourner = implode('', $handle);
    }
    // Type 3 : AVEC tirets, en minuscule
    if (3 == $type) {
        $retourner = str_replace("'", '-', $retourner);
        $retourner = str_replace(' ', '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-z0-9\s-]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $retourner = str_replace('--', '-', $retourner);
        // $retourner = str_replace("\t", '', $retourner);
    }
    // Type 4 : noms de fichiers (avec points et majuscules)
    if (4 == $type) {
        $retourner = str_replace("'", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-zA-Z0-9.\s-]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $retourner = str_replace(' ', '-', $retourner);
        $retourner = str_replace('--', '-', $retourner);
    }

    return $retourner;
}

function admin()
{
    global $kernel;

    $request = $kernel->getContainer()->get('legacy_request_stack')->getMainRequest();

    if (!$request || !$request->hasSession()) {
        return false;
    }

    return $request->getSession()->get('admin_caf', false);
}
function superadmin()
{
    return admin();
}

// check mail
function isMail($mail)
{
    if (preg_match("#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#", $mail)) {
        return true;
    }

    return false;
}

// function de supp de dossier (dangerous)
// if(admin()){
    function clearDir($dossierSupp)
    {
        $ouverture = @opendir($dossierSupp);
        if (!$ouverture) {
            return;
        }
        if (strlen($dossierSupp) > 1) {
            while ($fichierSupp = readdir($ouverture)) {
                if ('.' == $fichierSupp || '..' == $fichierSupp) {
                    continue;
                }

                if (is_dir($dossierSupp.'/'.$fichierSupp)) {
                    $r = clearDir($dossierSupp.'/'.$fichierSupp);
                    if (!$r) {
                        return false;
                    }
                } else {
                    $r = @unlink($dossierSupp.'/'.$fichierSupp);
                    if (!$r) {
                        return false;
                    }
                }
            }
            closedir($ouverture);
            $r = @rmdir($dossierSupp);
            if (!$r) {
                return false;
            }

            return true;
        }
    }
// }

/**
 * Replace language-specific characters by ASCII-equivalents.
 *
 * @param string $s
 *
 * @return string
 */
function normalizeChars($s)
{
    $replace = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A', 'Æ' => 'A', 'Ă' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'ă' => 'a', 'æ' => 'ae',
        'þ' => 'b', 'Þ' => 'B',
        'Ç' => 'C', 'ç' => 'c',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ğ' => 'G', 'ğ' => 'g',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'İ' => 'I', 'ı' => 'i', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'O', 'ö' => 'oe', 'ø' => 'o',
        'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'Š' => 'S', 'š' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ş' => 's', 'ß' => 'ss',
        'ț' => 't', 'Ț' => 'T',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue',
        'Ý' => 'Y',
        'ý' => 'y', 'ÿ' => 'y',
        'Ž' => 'Z', 'ž' => 'z',
    ];

    return strtr($s, $replace);
}

/**
 * Replace language-specific characters by ASCII-equivalents.
 *
 * @param string $s
 *
 * @return string
 */
function getArrayFirstValue($array)
{
    return $array[0] ?? null;
}
