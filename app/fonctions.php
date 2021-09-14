<?php
// gestion des contenus
$contLog=array();
$CONTENUS_HTML=array();
$CONTENUS_INLINE=array();

// ----------------------------------------------------------------------------------------------------------------
// -------------------------------------------- FONCTIONS SPECIFIQUES AU SITE DU CLUB ALPIN FRANCAIS
// ----------------------------------------------------------------------------------------------------------------

function    presidence() { 

    GLOBAL $president;
    GLOBAL $vicepresident;

    include SCRIPTS.'connect_mysqli.php';

    $president = $vicepresident = array();
    $president_sql = "SELECT * FROM `caf_user` AS U LEFT JOIN `caf_user_attr` AS A  ON A.usertype_user_attr = 6 WHERE U.id_user = A.user_user_attr";
    $president_result = $mysqli->query($president_sql);
    while ($row_president = $president_result->fetch_assoc()) {
        if ($row_president['id_user'] !== '1') {
            $president[] = $row_president;
        }
    }

    $vicepresident = array();
    $vicepresident_sql = "SELECT * FROM `caf_user` AS U LEFT JOIN `caf_user_attr` AS A  ON A.usertype_user_attr = 7 WHERE U.id_user = A.user_user_attr";
    $vicepresident_result = $mysqli->query($vicepresident_sql);
    while ($row_vicepresident = $vicepresident_result->fetch_assoc()) {
        if ($row_vicepresident['id_user'] !== '1') {
            $vicepresident[] = $row_vicepresident; 
        }
    }
}

/*
La fonction "isMobile" permet de detecter les mobiles
*/

function isMobile() {

    // Check the server headers to see if they're mobile friendly
    if(isset($_SERVER["HTTP_X_WAP_PROFILE"])) {
        return true;
    }

    // If the http_accept header supports wap then it's a mobile too
    if(preg_match("/wap\.|\.wap/i",$_SERVER["HTTP_ACCEPT"])) {
        return true;
    }

    // Still no luck? Let's have a look at the user agent on the browser. If it contains
    // any of the following, it's probably a mobile device. Kappow!
    if(isset($_SERVER["HTTP_USER_AGENT"])){
        $user_agents = array("midp", "j2me", "avantg", "docomo", "novarra", "palmos", "palmsource", "240x320", "opwv", "chtml", "pda", "windows\ ce", "mmp\/", "blackberry", "mib\/", "symbian", "wireless", "nokia", "hand", "mobi", "phone", "cdm", "up\.b", "audio", "SIE\-", "SEC\-", "samsung", "HTC", "mot\-", "mitsu", "sagem", "sony", "alcatel", "lg", "erics", "vx", "NEC", "philips", "mmm", "xx", "panasonic", "sharp", "wap", "sch", "rover", "pocket", "benq", "java", "pt", "pg", "vox", "amoi", "bird", "compal", "kg", "voda", "sany", "kdd", "dbt", "sendo", "sgh", "gradi", "jb", "\d\d\di", "moto");
        foreach($user_agents as $user_string){
            if(preg_match("/".$user_string."/i",$_SERVER["HTTP_USER_AGENT"])) {
                return true;
            }
        }
    }

    // Let's NOT return "mobile" if it's an iPhone, because the iPhone can render normal pages quite well.
    if(preg_match("/iphone/i",$_SERVER["HTTP_USER_AGENT"])) {
        return false;
    }

    // None of the above? Then it's probably not a mobile device.
    return false;
}

/*
Find URLs in Text, Make Links
*/
function getUrlFriendlyString($text) {

    $SCHEMES = array('http', 'https', 'ftp', 'mailto', 'news',
        'gopher', 'nntp', 'telnet', 'wais', 'prospero', 'aim', 'webcal');
    // Note: fragment id is uchar | reserved, see rfc 1738 page 19
    // %% for % because of string formating
    // puncuation = ? , ; . : !
    // if punctuation is at the end, then don't include it

    $URL_FORMAT = '~(?<!\w)((?:'.implode('|',
        $SCHEMES).'):' # protocol + :
    .   '/*(?!/)(?:' # get any starting /'s
    .   '[\w$\+\*@&=\-/]' # reserved | unreserved
    .   '|%%[a-fA-F0-9]{2}' # escape
    .   '|[\?\.:\(\),;!\'](?!(?:\s|$))' # punctuation
    .   '|(?:(?<=[^/:]{2})#)' # fragment id
    .   '){2,}' # at least two characters in the main url part
    .   ')~';

    preg_match_all($URL_FORMAT, $text, $matches, PREG_SPLIT_DELIM_CAPTURE);

    $usedPatterns = array();
    foreach($matches as $patterns){
        $pattern = $patterns[0];
        if(!array_key_exists($pattern, $usedPatterns)){
            $usedPatterns[$pattern]=true;
            $text = str_replace ($pattern, "<a href='".$pattern."' rel='nofollow'>".$pattern."</a> ", $text);
        }
    }
    return $text;
}

/*
La fonction "userlink" affiche un lien vers le profil d'un utilisateur en fonction du contexte demandé par "style"
*/
function userlink($id_user, $nickname_user, $civ_user=false, $firstname_user=false, $lastname_user=false, $style='public'){
    global $p_racine;
    $return='';

    switch($style){
        case 'public': 	$return=html_utf8($nickname_user);	break;
        case 'short': 	$return=html_utf8($civ_user).' '.html_utf8($firstname_user).' '.strtoupper(substr(trim($lastname_user),0,1));	break;
        case 'full': 	$return=html_utf8($civ_user).' '.html_utf8($firstname_user).' '.html_utf8($lastname_user);	break;
        default:		return;
    }

    // habillage du lien (CSS:userlink)
    $return='<a href="'.$p_racine.'includer.php?p=includes/fiche-profil.php&amp;id_user='.intval($id_user).'" class="fancyframe userlink" title="'.cont('userlink-title').'">'.$return.'</a>';

    return $return;
}

/*
La fonction "userImg" prend l'ID d'un user et retourne l'URL absolue du picto ou de la photo
user désirée ou bien le picto par défaut si celle-ci n'existe pas.
*/
function userImg($id_user, $style=''){
    global $p_racine;

    switch($style){
        case 'pic': 	$style=$style.'-';	break;
        case 'min': 	$style=$style.'-';	break;
        default:		$style='';
    }

    $rel='ftp/user/'.intval($id_user).'/'.$style.'profil.jpg';
    if(!file_exists($rel)) $rel='ftp/user/0/'.$style.'profil.jpg';
    return $p_racine.$rel;
}

/*
La fonction "comFd" prend l'ID d'une commission et retourne l'URL absolue de l'aimge de fond
liée à cette commission, ou bien de celle par défaut
*/
function comFd($id_commission){
    global $p_racine;

    $rel='ftp/commission/'.intval($id_commission).'/bigfond.jpg';
    if(!file_exists($rel)) $rel='ftp/commission/0/bigfond.jpg';
    return $p_racine.$rel;
}

/*
La fonction "comPicto" prend l'ID d'une commisson et retourne l'URL absolue du picto
de la commission désirée ou bien le picto par défaut si celui-ci n'existe pas.
*/
function comPicto($id_commission, $style=''){
    global $p_racine;

    switch($style){
        case 'light': 	$style='-'.$style;	break;
        case 'dark': 	$style='-'.$style;	break;
        default:		$style='';
    }

    $rel='ftp/commission/'.intval($id_commission).'/picto'.$style.'.png';
    if(!file_exists($rel)) $rel='ftp/commission/0/picto'.$style.'.png';
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
function allowed($code_userright, $param=''){
    global $userAllowedTo; // liste des opérations auxquelles l'user est autorisé. tableau associatif : la clé est le code de l'opératin, sa valeur les parametres
    global $allowedError; // Erreur facultative à afficher si la fonction renvoie false


    $usertypes=Array('1'); // id du niveau visiteur, le plus bas, commun à tous
    $allowedError=false;
    $return=false;


    $id_user=intval($_SESSION['user']['id_user']);

    // le tableau des droits est-il déja défini ? Non ? alors on le définit ici
    if(!$userAllowedTo or !is_array($userAllowedTo)){

        // raz/créa tableau global
        $userAllowedTo=array('default'=>'1'); // minimum une valeur

        // connexion à la bdd si nécessaire
        $connSet=is_object($mysqli);
        if(!$connSet)	include SCRIPTS.'connect_mysqli.php';

        $id_user=$mysqli->real_escape_string($id_user);

        if($mysqli->ping()){ // si on est bien connecté à la BD

            // Si un adhérent est connecté et licence valide, récupération des droits attribués à cet adhérent
            if($id_user && $_SESSION['user']['doit_renouveler_user'] == 0){
                // la requête remonte la chaine alimentaire, de l'ID de l'user jusqu'à l'ensemble de ses droit, avec les paramètres liés
                $req=""
                ."SELECT DISTINCT code_userright, params_user_attr, limited_to_comm_usertype " // on veut le code, et les paramètres de chaque droit, et savoir si ce droit est limité à une commission ou non
                ."FROM caf_userright, caf_usertype_attr, caf_usertype, caf_user_attr " // dans la liste des droits > attr_droit_type > type > attr_type_user
                ."WHERE user_user_attr=$id_user " // de user à user_attr
                ."AND usertype_user_attr=id_usertype " // de user_attr à usertype
                ."AND id_usertype=type_usertype_attr " // de usertype à usertype_attr
                ."AND right_usertype_attr=id_userright " // de usertype_attr à userright
                ."ORDER BY  params_user_attr ASC, code_userright ASC, limited_to_comm_usertype ASC LIMIT 0 , 500 " // order by params permet d'optimiser la taille de la var globale. Si, si promis (14 lignes plus loin) !
                ;

                // lecture du resultat
                $result = $mysqli->query($req);

                // ajout du droit, avec ses paramètres, au tableau global des droits de cet user
                // sans paramètre, la valeur est une string 'true'
                // Il est possible que le même droit prenne plusieurs paramètres (ex : vous avez le droit d'écrire un article dans
                // deux commissions auquel cas, ils sont concaténés via le caractère |
                while($row = $result->fetch_assoc()){

                    // echo $row['code_userright'].'--limite_a_comm='.$row['limited_to_comm_usertype'].'='.$row['params_user_attr'].'<hr />';

                    // valeur : true ou param
                    if($row['params_user_attr'] && $row['limited_to_comm_usertype']) 	$val=$row['params_user_attr'];
                    else 																$val='true';

                    // si la valeur est true, pas besoin d'ajouter des parametres par la suite car true = "ok pour tout sans params"
                    if($val=='true') $userAllowedTo[$row['code_userright']]=$val;
                    // écriture, ou concaténation des paramètres existant
                    elseif($userAllowedTo[$row['code_userright']] != 'true'){
                        $userAllowedTo[$row['code_userright']] = ($userAllowedTo[$row['code_userright']]?$userAllowedTo[$row['code_userright']].'|':'').$val;
                    }
                    
    				if (admin() || superadmin()) {
        				$userAllowedTo[$row['code_userright']] = 'true';
    				}
                    
                }

                if(!in_array('Salarié', $_SESSION['user']['status'])){
                    // **********
                    // DEBUG : SI CONNECTÉ, ON A FORCÉMENT LE STATUT ADHÉRENT MAIS PAS LIE DANS LA BASE, SAUF SALARIE
                    $req=""
                    ."SELECT DISTINCT code_userright, limited_to_comm_usertype "
                    ."FROM caf_userright, caf_usertype_attr, caf_usertype "
                    ."WHERE code_usertype LIKE 'adherent' " // usertype adherent
                    ."AND id_usertype=type_usertype_attr "
                    ."AND right_usertype_attr=id_userright "
                    ."ORDER BY  code_userright ASC, limited_to_comm_usertype ASC LIMIT 0 , 500 "
                    ;

                    // lecture du resultat
                    $result = $mysqli->query($req);

                    // ajout du droit, avec ses paramètres, au tableau global des droits de cet user
                    // sans paramètre, la valeur est une string 'true'
                    // Il est possible que le même droit prenne plusieurs paramètres (ex : vous avez le droit d'écrire un article dans
                    // deux commissions auquel cas, ils sont concaténés via le caractère |
                    while($row = $result->fetch_assoc()){

                        // valeur : true ou param
                        if($row['params_user_attr'] && $row['limited_to_comm_usertype']) 	$val=$row['params_user_attr'];
                        else 																$val='true';

                        // si la valeur est true, pas besoin d'ajouter des parametres par la suite car true = "ok pour tout sans params"
                        if($val=='true') $userAllowedTo[$row['code_userright']]=$val;
                        // écriture, ou concaténation des paramètres existant
                        elseif($userAllowedTo[$row['code_userright']] != 'true'){
                            $userAllowedTo[$row['code_userright']] = ($userAllowedTo[$row['code_userright']]?$userAllowedTo[$row['code_userright']].'|':'').$val;
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
            else{
                // la requête récupère tous les droits liés à un compte visiteur
                $req=""
                ."SELECT DISTINCT code_userright "
                ."FROM caf_userright, caf_usertype_attr, caf_usertype " // des droits au type
                ."WHERE code_usertype='visiteur' " // type visiteur
                ."AND id_usertype = type_usertype_attr " // du type visiteur à ses attributions
                ."AND id_userright = right_usertype_attr " // de ses attributions a ses droits
                ."LIMIT 500 "
                ;

                // lecture du resultat
                $result = $mysqli->query($req);

                // ajout du droit au tableau global
                // sans paramètre, la valeur est une string 'true'
                while($row = $result->fetch_assoc()){
                    // les droits visteurs sont tous à true, et ne dependent jamais de parametres
                    $val='true';
                    $userAllowedTo[$row['code_userright']]=$val;
                    
    				if (admin() || superadmin()) {
        				$userAllowedTo[$row['code_userright']] = 'true';
    				}
                }
            }
        }
        else{
            $allowedError="Erreur à la connexion à la BDD";
            echo '<p class="erreur">'.$allowedError.'</p>';
        }

        // déconnexion si besoin
        if(!$connSet)	$mysqli->close();
    }

    // Ici, le tableau des droits existe, cherchons ce qui nous intéresse : print_r($userAllowedTo);
    if($userAllowedTo[$code_userright]){
        // ce droit fait partie de la liste. Contient-il des paramètres ?
        if($userAllowedTo[$code_userright]=='true') $return=true;
        // sinon, si les paramètres ne sont pas précisés dans l'appel de la fonction, il ne sont pas à prendre en compte
        elseif(!$param) $return=true;
        // oui, il a des paramètres, faut donc les vérifier
        else{
            $tab=explode('|', $userAllowedTo[$code_userright]);
            foreach($tab as $tmpParam){
                if($param==$tmpParam)  $return=true;
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
function getYearsSinceDate($then){
    global $p_time;

    // return $then;
    // $then = intval($then);
    $then = bigintval($then);

    // get difference between years
    $years = date("Y", $p_time) - date("Y", $then);

    // get months of dates
    $mthen = date("n", $then);
    $mnow = date("n", $p_time);
    // get days of dates
    $dthen = date("j", $then);
    $dnow = date("j", $p_time);

    // if date not reached yet this year, we need to remove one year.
    if ($mnow < $mthen || ($mnow==$mthen && $dnow<$dthen)) {
        $years--;
    }
    
    // gestion des dates NULL
    if ($then == NULL) {
        return "inconnu";
    } else {
        return $years;
    }
}

// utile ci dessus
function bigintval($value) {
  $value = trim($value);
  if (ctype_digit($value)) {
    return $value;
  }
  $value = preg_replace("/[^-0-9](.*)$/", '', $value);
  if (ctype_digit( abs ($value))) {
    return $value;
  }
  return 0;
}


// ----------------------------------------------------------------------------------------------------------------
// -------------------------------------------- // FIN des fonctions specifiques
// ----------------------------------------------------------------------------------------------------------------

/**
 * Retourne la taille plus l'unité arrondie
 *
 * @param mixed $bytes taille en octets
 * @param string $format formatage (http://www.php.net/manual/fr/function.sprintf.php)
 * @param string $lang indique la langue des unités de taille
 * @return string chaine de caractères formatées
 */
function formatSize($bytes,$format = '%.2f',$lang = 'fr')
{
    static $units = array(
    'fr' => array(
    'o',
    'Ko',
    'Mo',
    'Go',
    'To'
    ),
    'en' => array(
    'B',
    'KB',
    'MB',
    'GB',
    'TB'
    ));
    $translatedUnits = &$units[$lang];
    if(isset($translatedUnits)  === false)
    {
        $translatedUnits = &$units['en'];
    }
    $b = (double)$bytes;
    /*On gére le cas des tailles de fichier négatives*/
    if($b > 0)
    {
        $e = (int)(log($b,1024));
        /**Si on a pas l'unité on retourne en To*/
        if(isset($translatedUnits[$e]) === false)
        {
            $e = 4;
        }
        $b = $b/pow(1024,$e);
    }
    else
    {
        $b = 0;
        $e = 0;
    }
    return sprintf($format.' %s',$b,$translatedUnits[$e]);
}


// login d'un user par son ID ou son e-mail
function user_login($identifiant, $connectme=true){
    global $pbd;
    global $p_time;

    $_SESSION['user']=false;

    include SCRIPTS.'connect_mysqli.php';

    $identifiant=$mysqli->real_escape_string($identifiant);

    if(isMail($identifiant)){
        $req="SELECT
          id_user, email_user, cafnum_user, firstname_user, lastname_user, nickname_user, civ_user, doit_renouveler_user, alerte_renouveler_user, tel_user, tel2_user
        FROM  ".$pbd."user WHERE email_user = '$identifiant' AND valid_user =1 ORDER BY  created_user DESC LIMIT 1";
    }
    elseif(is_int($identifiant))
        $req="SELECT
          id_user, email_user, cafnum_user, firstname_user, lastname_user, nickname_user, civ_user, doit_renouveler_user, alerte_renouveler_user, tel_user, tel2_user
        FROM  ".$pbd."user WHERE id_user =$identifiant AND valid_user =1 ORDER BY  created_user DESC LIMIT 1";
    else return false;

    $handleSql=$mysqli->query($req);
    while($handle=$handleSql->fetch_array(MYSQLI_ASSOC)){
        $_SESSION['user']=$handle;
        $_SESSION['user']['logged']='logged';
        $_SESSION['user']['status']=array();// définition des statuts

        // chargement des droits si licence valide
        if($handle['doit_renouveler_user'] == 0){
            $req="SELECT title_usertype, params_user_attr
                FROM caf_user_attr, caf_usertype
                WHERE user_user_attr=".intval($handle['id_user'])."
                AND id_usertype=usertype_user_attr
                ORDER BY hierarchie_usertype DESC
                LIMIT 50";
            $handleSql2=$mysqli->query($req);
            while($handle2=$handleSql2->fetch_array(MYSQLI_ASSOC)){
                $commission = substr(strrchr($handle2['params_user_attr'], ':'), 1);
                $_SESSION['user']['status'][] = $handle2['title_usertype'].($commission?', '.$commission:'');
            }
        }

        // CRÉATION DU COOKIE POUR RESTER CONNECTÉ
        $cookietoken=md5(rand(100, 999));
        $id_user=intval($handle['id_user']);
        setcookie('cafuser', $id_user.'-'.$cookietoken, $p_time+(86400*7), '/', '.clubalpinlyon.fr', (isset($_SERVER['HTTPS']) ? true : false), true); // duree : une semaine
        // sauvegarde du token en BD
        $mysqli->query("UPDATE  `".$pbd."user` SET  `cookietoken_user` =  '$cookietoken' WHERE  `id_user` =$id_user LIMIT 1 ;");

        // FIN
        return true;
    }

    $mysqli->close();

    return false;
}
// logout user
function user_logout(){
    setcookie('cafuser', '', -1, '/', '.clubalpinlyon.fr', (isset($_SERVER['HTTPS']) ? true : false), true); // suppression cookie
    unset($_SESSION['user']);
}
function user(){
    if($_SESSION['user']['logged']=='logged') return true;
    else return false;
}

// enregistrement de l'activité sur le site
function mylog($code, $desc, $connectme=true){
    global $pbd;

    include SCRIPTS.'connect_mysqli.php';
    $code_log_admin=$mysqli->real_escape_string(trim($code));
    $desc_log_admin=$mysqli->real_escape_string(trim($desc));
    $date_log_admin=time();
    $ip_log_admin=$mysqli->real_escape_string($_SERVER['REMOTE_ADDR']);

    $req="INSERT INTO `".$pbd."log_admin` (`id_log_admin` ,`code_log_admin` ,`desc_log_admin` ,`date_log_admin`, `ip_log_admin`)
        VALUES (NULL , '$code_log_admin',  '$desc_log_admin',  '$date_log_admin', '$ip_log_admin')";
        if(!$mysqli->query($req));	$errTab[]="Erreur SQL lors du log";
    $mysqli->close();
}

// htmlentities avec utf8
function html_utf8($str){
    return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

// anti-cache à placer à la fine des extensions de fichiers appelés / utilise des vars du fichier params.php
function antiCache($mode){
    global $p_time;
    global $p_devmode;
    switch($mode){
        // si dev en local
        case 'localonly':
            if($_SERVER['HTTP_HOST']=='http://127.0.0.1')	echo '?ac='.$p_time; break;
        // si var p_devmode
        case 'devonly':
            if($p_devmode)	echo '?ac='.$p_time;	break;
        // si dev en local
        default 	:
            echo '?ac='.$p_time;
    }
    return false;
}

// assurer un lien http
function linker($link){	$link=trim($link);	if(substr($link, 0, 7)!='http://' && substr($link, 0, 8)!='https://')	$link='https://'.$link;	return $link;	}

// vider les champs vides d'un tableau
function array_noempty($tab){	$tempTab=array();	foreach($tab as $temp){		if($temp && $temp!='')			$tempTab[]=$temp;	}	return $tempTab;}

// ma fonction d'insertion élément inline
function cont($code=false, $html=false){
    $defLang='fr';
    global $pbd;
    global $CONTENUS_INLINE;
    global $lang;
    $tmplang=$lang;
    if(!$tmplang) $tmplang=$defLang;

    // recup langue en cours
    // lecture des contenus
    // mode local ?
    if($_SERVER['HTTP_HOST']=='127.0.0.1') $local=true;
    // log des erreurs
    global $contLog;
    // premier appel à la fonction
    if(!sizeof($CONTENUS_INLINE)){
        // v2 : BDD
        include SCRIPTS.'connect_mysqli.php';
        // sélection de chaque élément par ordre DESC
        $req="SELECT `code_content_inline`, `contenu_content_inline`
            FROM  `".$pbd."content_inline`
            WHERE  `lang_content_inline` LIKE  '$tmplang'
            ORDER BY  `date_content_inline` DESC
            ";
        $handleSql=$mysqli->query($req);
        while($handle=$handleSql->fetch_array(MYSQLI_ASSOC)){
            // uniquement si pas déja renseigné
            if(!isset($CONTENUS_INLINE[$handle['code_content_inline']]))
                $CONTENUS_INLINE[$handle['code_content_inline']] = $handle['contenu_content_inline'];
        }
        $mysqli->close();
        // debug
        $CONTENUS_INLINE['dev']='dev';
    }
    // var_dump($CONTENUS);
    // retour contenu
    if(isset($CONTENUS_INLINE[$code])){
        if(!$html) 	return (strip_tags($CONTENUS_INLINE[$code]));
        else	 	return ($CONTENUS_INLINE[$code]);
    }
    // pas de contenu
    else{
        if(!in_array($code, $contLog) && $code)	$contLog[]=$code;
        // afficher rien
        return '';
    }
}

$p_inclurelist=array();
// ma fonction d'insertion /modification élément HTML en front office
function inclure($elt, $style='vide', $options=array()){
    $defLang='fr';
    global $lang; if(!$lang) $lang=$defLang;
    global $p_abseditlink;
    global $versCettePage;
    global $p_inclurelist;
    global $pbd;

    // assurer un seul id d'élément par page
    if(!in_array($elt, $p_inclurelist)){

        // default options values
        $editVis=true;
        $connect=true;

        foreach($options as $key=>$val){
            if($key=='editVis')	$editVis=$val;
            if($key=='connect')	$connect=$val;
        }

        if($connect) include (SCRIPTS.'connect_mysqli.php');
        $code_content_html=$mysqli->real_escape_string($elt);

        // Contenu
        $req="SELECT `vis_content_html`,`contenu_content_html` FROM `".$pbd."content_html` WHERE `code_content_html` LIKE '$code_content_html' AND lang_content_html LIKE '".$lang."' ORDER BY `date_content_html` DESC LIMIT 1";
        $handleTab=array();
        $handleSql=$mysqli->query($req);
        $found=false;
        $currentElement=array('vis_content_html'=>1); // default values
        while($handle=$handleSql->fetch_array(MYSQLI_ASSOC)){
            $found=true;
            $currentElement=$handle;
        }

        // AFFICHAGE DES OUTILS ADMIN
        // mode admin : permet la modification
        if(admin()){
            // fancybox
            echo '<div id="'.$elt.'" class="contenuEditable '.$style.'">
                <div class="editHtmlTools" style="text-align:left;">
                    <a href="editElt.php?p='.$elt.'&amp;class='.$style.'" title="Modifier l\'&eacute;l&eacute;ment '.$elt.'" class="edit fancyframeadmin" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-size:11px; font-family:Arial;">
                        <img src="img/base/page_edit.png" id="imgEdit'.$elt.'" alt="EDIT" title="Modifier l\'&eacute;l&eacute;ment '.$elt.'" />Modifier</a>
                    '.($editVis?'
                    <a href="javascript:void(0)" onclick="window.document.majVisBlock(this, \''.$elt.'\')" rel="'.$currentElement['vis_content_html'].'" title="Activer / Masquer ce bloc de contenu" class="edit" style="color:white; font-weight:100; padding:2px 3px 2px 1px; font-size:11px; font-family:Arial; ">
                        <img src="img/base/page_white_key.png" alt="VIS" title="Activer / Masquer ce bloc de contenu" />Visibilité</a>
                        ':'').'
                </div>';
        }
        else{
            echo '<div id="'.$elt.'" class="'.$style.'">';
        }
        // AFFICHAGE DU CONTENU
        if($currentElement['vis_content_html'])
            echo $currentElement['contenu_content_html'];
        // contenu masqué
        else{
            if(admin()) echo '<div class="blocdesactive"><img src="img/base/bullet_key.png" alt="" title="" /> Bloc de contenu désactivé</div>';
        }

        if(!$found) echo '&nbsp;';
        // pour débugger les blocs flottants
        echo '</div>';
        if($connect) $mysqli->close();

        // enregistrer l'inclusino de ce elt
        $p_inclurelist[]=$elt;
    }
    else echo '<p class="erreur" style="clear:both; ">Erreur de développement : les codes d\'éléments HTML ne peuvent être en doublon dans une même page</p>';
}
// anti brute force
function antiBruteForce($etape='test', $logDir, $login, $maxTry=3){
    /*
    logdir=dossier des fichiers temporaires
    login=login utilisé
    maxtry=tentatives max avant blocage
    etape=juste vérifier l'accès et retourner true or false (0), ou ajouter une fausse réponse et renvoyer false (1)
    */
    // le dossier n'existe pas
    if(!file_exists($logDir)) mkdir($logDir);
    // Le fichier n'existe pas encore
    if(!file_exists($logDir.'/log-'.$login.'.tmp')){
        $creation_fichier = fopen($logDir.'/log-'.$login.'.tmp', 'a+'); // On crée le fichier puis on l'ouvre
        fputs($creation_fichier, date('d/m/Y').';0'); // On écrit à l'intérieur la date du jour et on met le nombre de tentatives à 1
        fclose($creation_fichier); // On referme
        $tentatives = 0;
    }
    // Le fichier existe :
    else{
        // On ouvre le fichier
        $fichier_tentatives = fopen($logDir.'/log-'.$login.'.tmp', 'r+');
        $contenu_tentatives = fgets($fichier_tentatives);
        $infos_tentatives = explode(';', $contenu_tentatives);
        // Si la date du fichier est celle d'aujourd'hui, on récupère le nombre de tentatives
        if($infos_tentatives[0] == date('d/m/Y')){
            $tentatives = $infos_tentatives[1];
        }
        // Si la date du fichier est dépassée, on met le nombre de tentatives à 0
        else{
            $tentatives = 0; // On met la variable $tentatives à 0
        }
        fclose($fichier_tentatives);
    }


    // vérification simple
    if($etape=='test'){
        if($tentatives >= $maxTry)	return false;
        else return true;
    }

    // incrementation des tentatives
    if($etape=='plus'){
        $tentatives++;
        // on incremente les tentatives
        $fichier_tentatives = fopen($logDir.'/log-'.$login.'.tmp', 'w');
        fputs($fichier_tentatives, date('d/m/Y').';'.($tentatives)); // On écrit à l'intérieur la date du jour et on met le nombre de tentatives à 1
        fclose($fichier_tentatives); // On referme
    }
    // remise à zero
    if($etape=='raz'){
        $tentatives=0;
        $fichier_tentatives = fopen($logDir.'/log-'.$login.'.tmp', 'w');
        fputs($fichier_tentatives, date('d/m/Y').';'.($tentatives)); // On écrit à l'intérieur la date du jour et on met le nombre de tentatives à 1
        fclose($fichier_tentatives); // On referme
    }
}

// FONCTIONS UTILES AU FORMULAIRES
// Checke ou pas une checkbox (tableau ou valeur simple)
function checkboxVal($inputName, $inputVal=false){
    // cas d'un checkbox seul
    if(!is_array($_POST[$inputName]))
        echo $_POST[$inputName]=='on'?'checked="checked"':'';
    // tableau
    else
        if(in_array($inputVal, $_POST[$inputName]))
            echo 'checked="checked"';
}
// Affiche (ECHO !!) dans un input hidden ou text le contenu de la variable postée échappée quand elle existe, ou une valeur par défaut
function inputVal($inputName, $defaultVal=''){
    global $_POST;
    global $p_utf8;
    $input = explode('|', $inputName);
    if (!$input[1]) {
        if($_POST[$inputName])
            return $p_utf8?html_utf8(stripslashes($_POST[$inputName])):htmlentities(stripslashes($_POST[$inputName]));
        else
            return $p_utf8?html_utf8($defaultVal):htmlentities($defaultVal);
    } elseif (count($input) == 2) {
        if($_POST[$input[0]][$input[1]])
            return $p_utf8?html_utf8(stripslashes($_POST[$input[0]][$input[1]])):htmlentities(stripslashes($_POST[$input[0]][$input[1]]));
        else
            return $p_utf8?html_utf8($defaultVal):htmlentities($defaultVal);
    } elseif (count($input) == 3) {
        if($_POST[$input[0]][$input[1]][$input[2]])
            return $p_utf8?html_utf8(stripslashes($_POST[$input[0]][$input[1]][$input[2]])):htmlentities(stripslashes($_POST[$input[0]][$input[1]][$input[2]]));
        else
            return $p_utf8?html_utf8($defaultVal):htmlentities($defaultVal);
    }
}

// affiche date format humain
function mois($mois){
    global $lang;
    switch($lang){
        case 'en'	: $tab = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"); break;
        default 	: $tab = array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
    }
    return($tab[intVal($mois) -1]);
}
function jour($n, $mode='full'){
    global $lang;
    switch($lang){
        case 'en'	: $tab = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");break;
        default 	: $tab = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche");break;
    }
    $return=$tab[intVal($n) -1];
    if($mode=='short') $return=substr($return, 0, 3);
    return $return;
}

// limitateur de texte au niveau des espace. Longueur en lettres
function limiterTexte($text, $length, $html=false){
    if(!$html){
        $text=str_replace('<br />', ' ', $text);
        $text=str_replace('<br>', ' ', $text);
        $text=strip_tags($text);
    }
    if(strlen($text) > $length){
        $pos=strpos($text, ' ', $length);
        if(!$pos)	$pos=strlen($text);
        $text=substr($text, 0, $pos);
    }

    return $text;
}

// convention de nommage automatique
function wd_remove_accents($str, $charset='UTF-8'){
    $str = htmlentities($str, ENT_QUOTES, $charset);
    // $str = htmlentities($str);
    /* */
    $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
    /* */
    return $str;
}
function formater($retourner, $type=1){
    global $pbd;
    // Type 1 : sans espace ni tirets, en minuscule
    if($type==1){
        $retourner = str_replace("'", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $retourner = wd_remove_accents(strtolower(stripslashes($retourner)));
        $pattern = "#[^a-z0-9\s]#";
        $retourner = preg_replace($pattern, '', $retourner);
    }
    // Type 2 : sans espace ni tirets, majuscule à chaque mot
    if($type==2){
        $retourner = str_replace("'", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-z0-9\s]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $handle=explode(' ', $retourner);
        for($i=0; $i<sizeof($handle); $i++)	$handle[$i]=strtoupper(substr($handle[$i], 0, 1)).strtolower(substr($handle[$i], 1, strlen($handle[$i])));
        $retourner=implode($handle, '');
    }
    // Type 3 : AVEC tirets, en minuscule
    if($type==3){
        $retourner = str_replace("'", '-', $retourner);
        $retourner = str_replace(" ", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-z0-9\s-]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $retourner = str_replace('--', '-', $retourner);
        // $retourner = str_replace("\t", '', $retourner);
    }
    // Type 4 : noms de fichiers (avec points et majuscules)
    if($type==4){
        $retourner = str_replace("'", '-', $retourner);
        $retourner = strtolower(stripslashes(wd_remove_accents($retourner)));
        $pattern = "#[^a-zA-Z0-9\s-.]#";
        $retourner = preg_replace($pattern, '', $retourner);
        $retourner = str_replace(' ', '-', $retourner);
        $retourner = str_replace('--', '-', $retourner);
    }
    return $retourner;
}

// connexions admin et superadmin
function admin(){
    if($_SESSION['admin']['on']==true) return true;
    return false;
}
function admin_start($connectMe=true){
    $_SESSION['admin']['on']=true;
    $_SESSION['admin']['mode']='admin';
    mylog('login-admin', "Connection d'un admin", $connectMe);
    return true;
}
function admin_stop(){
    unset($_SESSION['admin']);
    return true;
}
function superadmin(){
    if($_SESSION['admin']['on']==true && $_SESSION['admin']['mode']=='superadmin') return true;
    return false;
}
function superadmin_start($connectMe=true){
    $_SESSION['admin']['on']=true;
    $_SESSION['admin']['mode']='superadmin';
    mylog('login-superadmin', "Connection d'un super-admin", $connectMe);
    return true;
}

// est-ce que c'est un telephone qui visite mon site ?
function utiliseMobile(){
    $agents = array('Android', 'BlackBerry', 'iPhone', 'Palm');
    foreach ( $agents as $a )    {
        if ( stripos($_SERVER["HTTP_USER_AGENT"], $a) !== false )        {
            return true;
        }
    }
    //return true; // dev test
    return false;
}

// check mail
function isMail($mail){
    if(preg_match("#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#", $mail)) return true;
    else return false;
}

// function de supp de dossier (dangerous)
// if(admin()){
    function clearDir($dossierSupp) {
        $ouverture=@opendir($dossierSupp);
        if (!$ouverture) return;
        if(strlen($dossierSupp) >1){
            while($fichierSupp=readdir($ouverture)) {
                if($fichierSupp == '.' || $fichierSupp == '..') continue;

                if(is_dir($dossierSupp."/".$fichierSupp)) {
                    $r=clearDir($dossierSupp."/".$fichierSupp);
                    if (!$r) return false;
                }
                else {
                    $r=@unlink($dossierSupp."/".$fichierSupp);
                    if (!$r) return false;
                }
            }
            closedir($ouverture);
            $r=@rmdir($dossierSupp);
            if (!$r) return false;
            return true;
        }
    }
// }

/**
 * Replace language-specific characters by ASCII-equivalents.
 * @param string $s
 * @return string
 */
function normalizeChars($s) {
    $replace = array(
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'Ae', 'Å'=>'A', 'Æ'=>'A', 'Ă'=>'A',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'ae', 'å'=>'a', 'ă'=>'a', 'æ'=>'ae',
        'þ'=>'b', 'Þ'=>'B',
        'Ç'=>'C', 'ç'=>'c',
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
        'Ğ'=>'G', 'ğ'=>'g',
        'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'İ'=>'I', 'ı'=>'i', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
        'Ñ'=>'N',
        'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe', 'Ø'=>'O', 'ö'=>'oe', 'ø'=>'o',
        'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'Š'=>'S', 'š'=>'s', 'Ş'=>'S', 'ș'=>'s', 'Ș'=>'S', 'ş'=>'s', 'ß'=>'ss',
        'ț'=>'t', 'Ț'=>'T',
        'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'Ue',
        'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'ue',
        'Ý'=>'Y',
        'ý'=>'y', 'ý'=>'y', 'ÿ'=>'y',
        'Ž'=>'Z', 'ž'=>'z'
    );
    return strtr($s, $replace);
}

/**
 * Replace language-specific characters by ASCII-equivalents.
 * @param string $s
 * @return string
 */
function getArrayFirstValue($array) {
    return $array[0];
}

?>
