<?php

use App\Entity\User;
use App\Legacy\LegacyContainer;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Symfony\Bridge\Twig\AppVariable;

global $_POST;
global $allowedError; // Erreur facultative à afficher si la fonction renvoie false
global $CONTENUS_INLINE;
global $contLog;
global $p_inclurelist;
global $president;
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

    $president = $vicepresident = [];
    $president_sql = 'SELECT * FROM `caf_user` AS U LEFT JOIN `caf_user_attr` AS A  ON A.usertype_user_attr = 6 WHERE U.id_user = A.user_user_attr ORDER BY U.firstname_user ASC, U.lastname_user ASC';
    $president_result = LegacyContainer::get('legacy_mysqli_handler')->query($president_sql);
    while ($row_president = $president_result->fetch_assoc()) {
        if ('1' !== $row_president['id_user']) {
            $president[] = $row_president;
        }
    }

    $vicepresident = [];
    $vicepresident_sql = 'SELECT * FROM `caf_user` AS U LEFT JOIN `caf_user_attr` AS A  ON A.usertype_user_attr = 7 WHERE U.id_user = A.user_user_attr ORDER BY U.firstname_user ASC, U.lastname_user ASC';
    $vicepresident_result = LegacyContainer::get('legacy_mysqli_handler')->query($vicepresident_sql);
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

    $URL_FORMAT = '~(?<!\w)((?:' . implode('|',
        $SCHEMES) . '):' // protocol + :
    . '/*(?!/)(?:' // get any starting /'s
    . '[\w$\+\*@&=\-/]' // reserved | unreserved
    . '|%%[a-fA-F0-9]{2}' // escape
    . '|[\?\.:\(\),;!\'](?!(?:\s|$))' // punctuation
    . '|(?:(?<=[^/:]{2})#)' // fragment id
    . '){2,}' // at least two characters in the main url part
    . ')~';

    preg_match_all($URL_FORMAT, $text, $matches, \PREG_SPLIT_DELIM_CAPTURE);

    $usedPatterns = [];
    foreach ($matches as $patterns) {
        $pattern = $patterns[0];
        if (!array_key_exists($pattern, $usedPatterns)) {
            $usedPatterns[$pattern] = true;
            $text = str_replace($pattern, "<a href='" . $pattern . "' rel='nofollow'>" . $pattern . '</a> ', $text);
        }
    }

    return $text;
}

/*
La fonction "userlink" affiche un lien vers le profil d'un utilisateur en fonction du contexte demandé par "style"
*/
function userlink($id_user, $nickname_user, $civ_user = false, $firstname_user = false, $lastname_user = false, $style = 'public', ?int $idArticle = null)
{
    $complement = '';
    switch ($style) {
        case 'public': 	$return = html_utf8($nickname_user);
            break;
        case 'short': 	$return = html_utf8(ucfirst($firstname_user)) . ' ' . strtoupper(substr(trim($lastname_user), 0, 1));
            break;
        case 'full': 	$return = html_utf8(ucfirst($firstname_user)) . ' ' . html_utf8(strtoupper($lastname_user));
            break;
        default:		return;
    }

    if (!empty($idArticle) && $idArticle > 0) {
        $complement .= '&amp;id_article=' . $idArticle;
    }

    return '<a href="/includer.php?p=includes/fiche-profil.php&amp;id_user=' . (int) $id_user . $complement . '" class="fancyframe userlink" title="' . cont('userlink-title') . '">' . $return . '</a>';
}

/*
La fonction "userImg" prend l'ID d'un user et retourne l'URL absolue du picto ou de la photo
user désirée ou bien le picto par défaut si celle-ci n'existe pas.
*/
function userImg($id_user, $style = '')
{
    switch ($style) {
        case 'pic':
            $style .= '-';
            break;
        case 'min':
            $style .= '-';
            break;
        default:
            $style = '';
            break;
    }

    $rel = '/ftp/user/' . $id_user . '/' . $style . 'profil.jpg';
    if (!file_exists(__DIR__ . '/../../public' . $rel)) {
        $rel = '/ftp/user/0/' . $style . 'profil.jpg';
    }

    return $rel;
}

/*
La fonction "comFd" prend l'ID d'une commission et retourne l'URL absolue de l'aimge de fond
liée à cette commission
*/
function comFd($id_commission)
{
    $rel = '';

    if (!empty($id_commission)) {
        $rel = '/ftp/commission/' . (int) $id_commission . '/bigfond.jpg';
    }

    return $rel;
}

/*
La fonction "comPicto" prend l'ID d'une commisson et retourne l'URL absolue du picto
de la commission désirée ou bien le picto par défaut si celui-ci n'existe pas.
*/
function comPicto($id_commission, $style = '')
{
    switch ($style) {
        case 'light': 	$style = '-' . $style;
            break;
        case 'dark': 	$style = '-' . $style;
            break;
        default:		$style = '';
    }

    $rel = '/ftp/commission/' . (int) $id_commission . '/picto' . $style . '.png';
    if (!file_exists(__DIR__ . '/../../public' . $rel)) {
        $rel = '/ftp/commission/0/picto' . $style . '.png';
    }

    return $rel;
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
    return LegacyContainer::get('legacy_user_rights')->allowed($code_userright, $param);
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
    $value = trim((string) $value);
    if (ctype_digit($value)) {
        return $value;
    }
    $value = preg_replace('/[^-0-9](.*)$/', '', $value);
    if (is_numeric($value)) {
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
 *
 * @return string chaine de caractères formatées
 */
function formatSize($bytes, $format = '%.2f')
{
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];

    $b = (float) $bytes;
    /* On gére le cas des tailles de fichier négatives */
    if ($b > 0) {
        $e = (int) log($b, 1024);
        /**Si on a pas l'unité on retourne en To*/
        if (false === isset($units[$e])) {
            $e = 4;
        }
        $b = $b / 1024 ** $e;
    } else {
        $b = 0;
        $e = 0;
    }

    return sprintf($format . ' %s', $b, $units[$e]);
}

function user(): bool
{
    if ($token = LegacyContainer::get('legacy_token_storage')->getToken()) {
        if ($token->getUser() instanceof User) {
            return true;
        }
    }

    return false;
};

function getUser(): ?User
{
    if ($token = LegacyContainer::get('legacy_token_storage')->getToken()) {
        if ($token->getUser() instanceof User) {
            return $token->getUser();
        }
    }

    return null;
};
function csrfToken(string $intention): ?string
{
    return LegacyContainer::get('legacy_csrf_token_manager')->getToken($intention);
}
function generateRoute(string $path, array $parameters = []): ?string
{
    return LegacyContainer::get('legacy_router')->generate($path, $parameters);
}
function twigRender(string $path, array $params = []): ?string
{
    $params['app'] = new AppVariable();
    $params['app']->setEnvironment(LegacyContainer::getParameter('kernel.environment'));
    $params['app']->setDebug(LegacyContainer::getParameter('kernel.debug'));
    $params['app']->setTokenStorage(LegacyContainer::get('legacy_token_storage'));
    $params['app']->setRequestStack(LegacyContainer::get('legacy_request_stack'));

    return LegacyContainer::get('legacy_twig')->render($path, $params);
}

// enregistrement de l'activité sur le site
function mylog($code, $desc, $connectme = true)
{
    $code_log_admin = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($code));
    $desc_log_admin = LegacyContainer::get('legacy_mysqli_handler')->escapeString(trim($desc));
    $date_log_admin = time();
    $ip_log_admin = LegacyContainer::get('legacy_mysqli_handler')->escapeString($_SERVER['REMOTE_ADDR']);

    $req = "INSERT INTO `caf_log_admin` (`code_log_admin` ,`desc_log_admin` ,`date_log_admin`, `ip_log_admin`)
        VALUES ('$code_log_admin',  '$desc_log_admin',  '$date_log_admin', '$ip_log_admin')";
    if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
        $errTab[] = 'Erreur SQL lors du log';
    }
}

// htmlentities avec utf8
function html_utf8($str)
{
    return htmlentities($str ?? '', \ENT_QUOTES, 'UTF-8');
}

// assurer un lien http
function linker($link)
{
    $link = trim($link);
    if ('http://' != substr($link, 0, 7) && 'https://' != substr($link, 0, 8)) {
        $link = 'https://' . $link;
    }

    return $link;
}

// ma fonction d'insertion élément inline
function cont($code = false, $html = false)
{
    global $CONTENUS_INLINE;

    // log des erreurs
    global $contLog;
    // premier appel à la fonction
    if (!count($CONTENUS_INLINE)) {
        // v2 : BDD
        // sélection de chaque élément par ordre DESC
        $req = "SELECT `code_content_inline`, `contenu_content_inline`
            FROM  `caf_content_inline`
            WHERE  `lang_content_inline` LIKE  'fr'
            ORDER BY  `date_content_inline` DESC
            ";
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
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
function inclure($elt, $style = 'vide', $options = [])
{
    echo LegacyContainer::get('legacy_content_html')->getEasyInclude($elt, $style, $options);
}

// Affiche (ECHO !!) dans un input hidden ou text le contenu de la variable postée échappée quand elle existe, ou une valeur par défaut
function inputVal($inputName, $defaultVal = '')
{
    global $_POST;
    $input = explode('|', $inputName);
    if (empty($input[1])) {
        if (!empty($_POST[$inputName])) {
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
    $tab = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    return isset($tab[(int) $mois - 1]) ? $tab[(int) $mois - 1] : '';
}
function jour($n, $mode = 'full')
{
    $tab = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

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
    $str = htmlentities($str ?? '', \ENT_QUOTES, $charset);
    // $str = htmlentities($str);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'

    return preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
}
function formater($retourner, $type = 1)
{
    // Type 1 : sans espace ni tirets, en minuscule
    if (1 == $type) {
        $retourner = str_replace("'", '-', $retourner ?? '');
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $retourner = wd_remove_accents(strtolower(stripslashes($retourner)));
        $pattern = "#[^a-z0-9\s]#";
        $retourner = preg_replace($pattern, '', $retourner);
    }
    // Type 2 : sans espace ni tirets, majuscule à chaque mot
    if (2 == $type) {
        $retourner = str_replace("'", '-', $retourner ?? '');
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-z0-9\s]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $handle = explode(' ', $retourner);
        for ($i = 0; $i < count($handle); ++$i) {
            $handle[$i] = strtoupper(substr($handle[$i], 0, 1)) . strtolower(substr($handle[$i], 1, strlen($handle[$i])));
        }
        $retourner = implode('', $handle);
    }
    // Type 3 : AVEC tirets, en minuscule
    if (3 == $type) {
        $retourner = str_replace("'", '-', $retourner ?? '');
        $retourner = str_replace(' ', '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-z0-9\s-]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $retourner = str_replace('--', '-', $retourner);
        // $retourner = str_replace("\t", '', $retourner);
    }
    // Type 4 : noms de fichiers (avec points et majuscules)
    if (4 == $type) {
        $retourner = str_replace("'", '-', $retourner ?? '');
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-zA-Z0-9.\s-]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $retourner = str_replace(' ', '-', $retourner);
        $retourner = str_replace('--', '-', $retourner);
    }

    return $retourner;
}

function isGranted($attribute, $subject = null)
{
    return LegacyContainer::get('legacy_authorization_checker')->isGranted($attribute, $subject);
}

// check mail
function isMail($mail)
{
    if (null === $mail) {
        return false;
    }

    return (new EmailValidator())->isValid($mail, new NoRFCWarningsValidation());
}

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

            if (is_dir($dossierSupp . '/' . $fichierSupp)) {
                $r = clearDir($dossierSupp . '/' . $fichierSupp);
                if (!$r) {
                    return false;
                }
            } else {
                $r = @unlink($dossierSupp . '/' . $fichierSupp);
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

function getArrayFirstValue($array)
{
    return $array[0] ?? null;
}
