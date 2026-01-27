<?php

use App\Entity\User;
use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

/*
La fonction "userlink" affiche un lien vers le profil d'un utilisateur en fonction du contexte demandé par "style"
*/
function userlink($id_user, $nickname_user, $civ_user = false, $firstname_user = false, $lastname_user = false, $style = 'public', ?int $idArticle = null)
{
    $complement = '';
    switch ($style) {
        case 'public': 	$return = HtmlHelper::escape($nickname_user);
            break;
        case 'short': 	$return = HtmlHelper::escape(ucfirst($firstname_user)) . ' ' . strtoupper(substr(trim($lastname_user), 0, 1));
            break;
        case 'full': 	$return = HtmlHelper::escape(ucfirst($firstname_user)) . ' ' . HtmlHelper::escape(strtoupper($lastname_user));
            break;
        default:		return;
    }

    if (!empty($idArticle) && $idArticle > 0) {
        $complement .= '&amp;id_article=' . $idArticle;
    }

    $userLink = LegacyContainer::get('legacy_router')->generate('user_profile', ['id' => (int) $id_user], UrlGeneratorInterface::ABSOLUTE_URL);

    return '<a href="' . $userLink . $complement . '" class="fancyframe userlink" title="' . cont('userlink-title') . '">' . $return . '</a>';
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
            return HtmlHelper::escape(stripslashes($_POST[$inputName]));
        }

        return HtmlHelper::escape($defaultVal);
    }
    if (2 == count($input)) {
        if ($_POST[$input[0]][$input[1]]) {
            return HtmlHelper::escape(stripslashes($_POST[$input[0]][$input[1]]));
        }

        return HtmlHelper::escape($defaultVal);
    }
    if (3 == count($input)) {
        if ($_POST[$input[0]][$input[1]][$input[2]]) {
            return HtmlHelper::escape(stripslashes($_POST[$input[0]][$input[1]][$input[2]]));
        }

        return HtmlHelper::escape($defaultVal);
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
