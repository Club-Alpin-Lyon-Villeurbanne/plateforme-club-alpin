<?php

/**
 * Cette page a pour fonction d'envoyer les emails de rappels :
 * - RAPPEL D'eVeNEMENTS UTILISATEURS 1 : à X (=4) jours avant evt
 * - RAPPEL D'eVeNEMENTS UTILISATEURS 2 : à X (=2) jours avant evt
 * - CONTACT DES RESPONSABLES DE COMMISSION POUR VALIDER LES SORTIES EN ATTENTE
 * - CONTACT DES PRESIDENTS - VICE-PRES POUR VALIDER LEGALEMENT LES SORTIES EN ATTENTE.
 */
include __DIR__.'/../../app/includes.php';
//_________________________________________________ MYSQLi
$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

?><!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<title>CRON - Rappels</title>
	</head>
	<body onload="reloadTimeout()" style="font-family:Lucida Console; font-size:12px">
	<?php
    // JS POUR RECHARGER LA PAGE TOUTES LES DEMI-HEURES APRES CHARGEMENT COMPLET
    ?>	<script type="text/javascript">
		function reloadTimeout(){
			setTimeout('document.location.reload()', 30*1000*60);
		}
		</script>
<?php
// TRIGGER CAPITAL ! ACTIVE ET DESACTIVE L'EFFICACITE DU CHRON - ENVOI DE MAIL - INSERTION BDD
$chron_sendmails = true;
$chron_savedatas = true;

// la fonction permettant l'envoi d'e-mails et l'enregistrement en BDD
function cron_email($datas)
{
    global $chron_savedatas;
    global $chron_sendmails;
    global $p_sitename;
    global $p_noreply;
    global $pbd;
    global $mysqli;

    $tmpErr = '';
    if (!$datas['parent']) {
        $tmpErr .= ' parent = '.html_utf8($datas['parent']).'';
    }
    if (!$datas['code']) {
        $tmpErr .= ' code : '.html_utf8($datas['code']).'';
    }
    if (!isMail($datas['email'])) {
        $tmpErr .= ' E-mail invalide : '.html_utf8($datas['email']).'';
    }
    if (!$datas['name']) {
        $tmpErr .= ' name : '.html_utf8($datas['name']).'';
    }
    if (!$datas['subject']) {
        $tmpErr .= ' subject : '.html_utf8($datas['subject']).'';
    }
    if (!$datas['content']) {
        $tmpErr .= ' content : '.html_utf8($datas['content']).'';
    }
    if ($tmpErr && admin()) {
        echo '>>>>>>>>>>>>> ERROR <<<<<<<<<<<< '.$tmpErr;
    }

    if (!$tmpErr) {
        // Verification que ce code n'existe pas deja :
        // Chaque operation ne doit être effectuee qu'une fois (un seul e-mail envoye)
        $req = 'SELECT COUNT(id_chron_operation) FROM '.$pbd."chron_operation WHERE code_chron_operation LIKE '".$mysqli->real_escape_string($datas['code'])."'";
        $handleSql = $mysqli->query($req);
        if (getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM))) {
            error_log('Envoi de mail ignore car deja envoye : '.html_utf8($datas['code']).'');
        } else {
            error_log('- Operation lancee : '.html_utf8($datas['code']).'');

            // PHPMAILER
            require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';
            $mail = new CAFPHPMailer(); // defaults to using php "mail()"
            $mail->SetFrom($p_noreply, $p_sitename);
            $mail->AddAddress($datas['email'], $datas['name']);
            $mail->Subject = $datas['subject'];
            $mail->setMailBody($datas['content']);

            if ($chron_sendmails) {
                if (!$mail->Send()) {
                    error_log('Erreur envoi de mail : '.($mail->ErrorInfo).'');
                }
            } else {
                error_log('ENVOI DE MAIL A <i>'.$datas['name'].' ['.$datas['email'].']</i> INTERROMPU.<hr />');
            }
            if ($chron_savedatas) {
                $req = 'INSERT INTO '.$pbd."chron_operation(id_chron_operation, tsp_chron_operation, code_chron_operation, parent_chron_operation)
											VALUES ('',  '".time()."',  '".$datas['code']."',  '".$datas['parent']."');";
                if (!$mysqli->query($req)) {
                    error_log('Erreur sauvegarde SQL ');
                }
            }
        }
    }
}

// faire tourner en continu le script même en cas de fermeture du navigateur
ignore_user_abort(true);
set_time_limit(0);

// ****** VARS DE TEMPS ********

// heure actuelle
$h = date('h');

// timestamp auquel le dernier envoi aurait du être envoye: Par defaut c'est la veille a la dernière heure
$minTsp = mktime(array_pop($p_chron_dates_butoires), 0, 0, date('m', strtotime('-1 day')), date('d', strtotime('-1 day')), date('Y', strtotime('-1 day')));
// Si aujourd'hui on a deja depasse une heure de lancement alors le dernier envoie aurait du être ajourd'hui a cette heure
foreach ($p_chron_dates_butoires as $tmpH) {
    if (date('H') > $tmpH) {
        $minTsp = mktime($tmpH, 0, 0, date('m'), date('d'), date('Y'));
    }
}

// ****** FAUT IL REQUETER ? ********
// dernier envoi en BD date de... :
$req = 'SELECT `tsp_chron_launch` FROM `'.$pbd.'chron_launch` ORDER BY  `tsp_chron_launch` DESC LIMIT 0 , 1';
$handleSql = $mysqli->query($req);
$lastTsp = getArrayFirstValue($handleSql->fetch_array(\MYSQLI_NUM));

// envoi necessaire ? Alors go !
if ($lastTsp < $minTsp) {
    // sauvegarde de ce launch
    error_log('Envoi necessaire ! Enregistrement de la nouvelle date en BDD');
    if ($chron_savedatas) {
        if (!$mysqli->query('INSERT INTO '.$pbd."chron_launch(id_chron_launch, tsp_chron_launch) VALUES('', '$p_time');")) {
            $errTab[] = "Erreur d'enregistrement du launch";
        }
        $id_chron_launch = $mysqli->insert_id;
    } else {
        $id_chron_launch = 1;
    } // Developpement : fixe un parent fictif a un envoi de mail par exemple

    if (!isset($errTab) || 0 === count($errTab)) {
        // *******************************
        // RAPPEL D'eVeNEMENTS UTILISATEURS 1 : a 4 jours - PAS DE RAPPEL AU CREATEUR D'EVT [p_chron_rappel_user_avant_event_1]
        if ($do_p_chron_rappel_user_avant_event_1) {
            error_log('---------------------------- p_chron_rappel_user_avant_event_1 ----------------------------');
            $req = 'SELECT id_user, nickname_user, firstname_user, lastname_user, email_user
						, id_evt, code_evt, tsp_evt, place_evt, titre_evt, rdv_evt
						, tsp_evt_join
					FROM caf_user, caf_evt, caf_evt_join
					WHERE status_evt = 1
					AND id_evt = evt_evt_join
					AND id_user = user_evt_join
					AND valid_user = 1
					AND status_evt_join = 1
					AND tsp_evt > '.time().'
					AND tsp_evt < '.(time() + $p_chron_rappel_user_avant_event_1).'
					LIMIT 2000';
            $handleSql2 = $mysqli->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                error_log('- Envoi necessaire : '.html_utf8($handle2['nickname_user']).' participe a '.$handle2['code_evt'].'-'.$handle2['id_evt'].'');
                // lien vers l'evenement
                $url = $p_racine.'sortie/'.$handle2['code_evt'].'-'.$handle2['id_evt'].'.html';
                // vars d'envoi
                $datas = [];
                $datas['parent'] = $id_chron_launch;
                $datas['code'] = 'p_chron_rappel_user_avant_event_1;id_user='.$handle2['id_user'].';id_evt='.$handle2['id_evt']; // ce code, unique, evite de renvoyer ce mail a chaque appel du chron
                $datas['email'] = $handle2['email_user'];
                $datas['name'] = $handle2['nickname_user'];
                $datas['subject'] = 'Rappel : Participation à une sortie';
                $datas['content'] = '
					<h1>Bonjour '.html_utf8($handle2['nickname_user']).',</h1>
					<p>Vous êtes inscrit à un évènement sur le site du '.$p_sitename.', et ça se passe bientôt : Le '.date('d/m/Y à H:i', $handle2['tsp_evt']).' !</p>
					<p>Vous pouvez retrouver la page de l\'évènement et votre inscription à cette adresse :</p>
					<p><a href="'.$url.'" >'.$url.'</a></p>
				';
                cron_email($datas);
            }
        }

        // *******************************
        // RAPPEL D'eVeNEMENTS UTILISATEURS 2 : a 2 jours - PAS DE RAPPEL AU CREATEUR D'EVT [p_chron_rappel_user_avant_event_2]
        if ($do_p_chron_rappel_user_avant_event_2) {
            error_log('---------------------------- p_chron_rappel_user_avant_event_2 ----------------------------');
            $req = 'SELECT id_user, nickname_user, firstname_user, lastname_user, email_user
						, id_evt, code_evt, tsp_evt, place_evt, titre_evt, rdv_evt
						, tsp_evt_join
					FROM caf_user, caf_evt, caf_evt_join
					WHERE status_evt = 1
					AND id_evt = evt_evt_join
					AND id_user = user_evt_join
					AND valid_user = 1
					AND status_evt_join = 1
					AND tsp_evt > '.time().'
					AND tsp_evt < '.(time() + $p_chron_rappel_user_avant_event_2).'
					LIMIT 2000';
            $handleSql2 = $mysqli->query($req);
            while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                error_log('- Envoi nécessaire : '.html_utf8($handle2['nickname_user']).' participe à '.$handle2['code_evt'].'-'.$handle2['id_evt'].'');
                // lien vers l'evenement
                $url = $p_racine.'sortie/'.$handle2['code_evt'].'-'.$handle2['id_evt'].'.html';
                // vars d'envoi
                $datas = [];
                $datas['parent'] = $id_chron_launch;
                $datas['code'] = 'p_chron_rappel_user_avant_event_2;id_user='.$handle2['id_user'].';id_evt='.$handle2['id_evt']; // ce code, unique, evite de renvoyer ce mail a chaque appel du chron
                $datas['email'] = $handle2['email_user'];
                $datas['name'] = $handle2['nickname_user'];
                $datas['subject'] = 'Rappel : Participation à une sortie';
                $datas['content'] = '
					<h1>Bonjour '.html_utf8($handle2['firstname_user']).',</h1>
					<p>Vous êtes inscrit à un évènement sur le site du '.$p_sitename.' qui a lieu dans moins de deux jours : Le '.date('d/m/Y à H:i', $handle2['tsp_evt']).' !</p>
					<p>Vous pouvez retrouver la page de l\'évènement et votre inscription à cette adresse :</p>
					<p><a href="'.$url.'" >'.$url.'</a></p>
				';
                cron_email($datas);
            }
        }

        // *******************************
        // CONTACT DES RESPONSABLES DE COMMISSION POUR VALIDER LES SORTIES EN ATTENTE
        error_log('---------------------------- please_validate_evt ----------------------------');
        $nEltsToValidate = 0;
        // c'est un tableau associatif qui va contenir en cle les emails des responsables, et en valeur la liste des sorties liees
        // on peut alors creer un e-mail unqiue pour chaque user, avec la liste de ses sorties a valider
        $datasTab = [];

        // Pour chaque sortie non validee
        $req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt
					, id_commission, code_commission
				FROM caf_evt, caf_commission
				WHERE status_evt = 0
				AND commission_evt = id_commission '
                // AND tsp_end_evt >$p_time
                .'ORDER BY tsp_crea_evt ASC ';
        $handleSql = $mysqli->query($req);
        while ($evt = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            ++$nEltsToValidate;

            // on recupère les responsable de la commission liee...
            $req = " SELECT
					id_user, civ_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user
				FROM
					caf_user
					, caf_usertype
					, caf_user_attr
				WHERE code_usertype LIKE 'responsable-commission'
				AND usertype_user_attr = id_usertype
				AND user_user_attr = id_user
				AND params_user_attr LIKE 'commission:".$evt['code_commission']."'
				;
				";

            // et on ajoute leur e-mail en cle au tableau, et l'evt a ce tableau
            $found = false;
            $handleSql2 = $mysqli->query($req);
            while ($user = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $found = true;
                $datasTab[$user['email_user']][] = array_merge($evt, $user);
            }
            if (!$found) {
                error_log('<hr />>>>>>>>>>>>>ERREUR<<<<<<<<<<< il ne semble pas y avoir de responsable de commission '.$evt['code_commission'].'<hr />');
            }
        }
        error_log(''.$nEltsToValidate.' evenements a valider, '.count($datasTab).' responsables a contacter.');

        // chaque var datas contient toutes les infos des sorties
        foreach ($datasTab as $email => $evtdatas) {
            // echo '<pre>'; echo '********'.$email."\n"; print_r($datas); echo '</pre>';

            error_log('- Envoi necessaire : '.$email);

            // liste des ID des evenements / pour generer un code d'email unqiue
            $ids_evt = '';
            foreach ($evtdatas as $tmp) {
                $ids_evt .= ($ids_evt ? '-' : '').$tmp['id_evt'];
            }

            // liste des evts
            $evtList = [];
            foreach ($evtdatas as $tmp) {
                $evtList[] = '<a href="'.$p_racine.'sortie/'.$tmp['code_evt'].'-'.$tmp['id_evt'].'.html">'.date('d/m/Y', $tmp['tsp_evt']).' - '.html_utf8($tmp['titre_evt']).'</a>';
            }
            //			foreach($evtdatas as $tmp) $evtList[] = '<a href="'.$p_racine.'gestion-des-sorties.html">'.date('d/m/Y', $tmp['tsp_evt']).' - '.html_utf8($tmp['titre_evt']).'</a>';

            // vars d'envoi
            $datas = [];
            $datas['parent'] = $id_chron_launch;
            $datas['code'] = 'please_validate_evt;id_user='.$evtdatas[0]['id_user'].';ids_evt='.$ids_evt; // ce code, unique, evite de renvoyer ce mail a chaque appel du chron
            $datas['email'] = $email;
            $datas['name'] = html_utf8($evtdatas[0]['civ_user'].' '.$evtdatas[0]['firstname_user'].' '.$evtdatas[0]['lastname_user']);
            $datas['subject'] = count($evtdatas).' sorties à valider';
            $datas['content'] = '
				<h1>Bonjour '.html_utf8($evtdatas[0]['firstname_user']).',</h1>
				<p>
				Vous êtes responsable de commission, et il y a '.count($evtdatas).' sortie(s) en attente de publication sur le site du '.$p_sitename.'.
				Prenez soin de vous rendre sur <a href="'.$p_racine.'gestion-des-sorties.html">votre page de gestion</a>,
				et de vérifier attentivement toutes les informations liées à chaque sortie en attente de publication.
				Vous disposez d\'un accès réservé à chaque fiche évènement avant la publication.
				</p>
				<h2>Sorties en attente :</h2>
				<ul>
					<li>'.implode('</li><li>', $evtList).'</li>
				</ul>
			';
            cron_email($datas);
        }

        // *******************************
        //CONTACT DES PRESIDENTS - VICE-PRES POUR VALIDER LEGALEMENT LES SORTIES EN ATTENTE
        error_log('---------------------------- please_validate_legal_evt ----------------------------');
        $nEltsToValidate = 0;
        // c'est un tableau associatif qui va contenir en cle les emails des responsables, et en valeur la liste des sorties liees
        // on peut alors creer un e-mail unqiue pour chaque user, avec la liste de ses sorties a valider
        $datasTab = [];

        // Pour chaque sortie non validee dans le timing demande, et publiee
        $req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt
					, id_commission, code_commission
				FROM caf_evt, caf_commission
				WHERE status_legal_evt = 0
				AND commission_evt = id_commission
				AND tsp_evt > '.time().'
				AND tsp_evt < '.($p_tsp_max_pour_valid_legal_avant_evt).'
				AND status_evt = 1
				ORDER BY tsp_evt ASC ';
        $handleSql = $mysqli->query($req);
        while ($evt = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            ++$nEltsToValidate;

            // on recupère les responsable : president ou vice pre
            $req = " SELECT
					id_user, civ_user, firstname_user, lastname_user, nickname_user, tel_user, tel2_user, email_user
				FROM
					caf_user
					, caf_usertype
					, caf_user_attr
				WHERE (
						code_usertype LIKE 'president' OR code_usertype LIKE 'vice-president'
					)
				AND usertype_user_attr = id_usertype
				AND user_user_attr = id_user
				";

            // et on ajoute leur e-mail en cle au tableau, et l'evt a ce tableau
            $found = false;
            $handleSql2 = $mysqli->query($req);
            while ($user = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
                $found = true;
                $datasTab[$user['email_user']][] = array_merge($evt, $user);
            }
            if (!$found) {
                error_log('<hr />>>>>>>>>>>>>ERREUR<<<<<<<<<<< il ne semble pas y avoir de president ou vice president<hr />');
            }
        }
        error_log(''.$nEltsToValidate.' evenements a valider, '.count($datasTab).' responsables a contacter.');

        // chaque var datas contient toutes les infos des sorties
        foreach ($datasTab as $email => $evtdatas) {
            // echo '<pre>'; echo '********'.$email."\n"; print_r($datas); echo '</pre>';

            error_log('- Envoi necessaire : '.$email);

            // liste des ID des evenements / pour generer un code d'email unqiue
            $ids_evt = '';
            foreach ($evtdatas as $tmp) {
                $ids_evt .= ($ids_evt ? '-' : '').$tmp['id_evt'];
            }

            // liste des evts
            $evtList = [];
            foreach ($evtdatas as $tmp) {
                $evtList[] = '<a href="'.$p_racine.'sortie/'.$tmp['code_evt'].'-'.$tmp['id_evt'].'.html">'.date('d/m/Y', $tmp['tsp_evt']).' - '.html_utf8($tmp['titre_evt']).'</a>';
            }

            // vars d'envoi
            $datas = [];
            $datas['parent'] = $id_chron_launch;
            $datas['code'] = 'please_validate_evt;id_user='.$evtdatas[0]['id_user'].';ids_evt='.$ids_evt; // ce code, unique, evite de renvoyer ce mail a chaque appel du chron
            $datas['email'] = $email;
            $datas['name'] = html_utf8($evtdatas[0]['civ_user'].' '.$evtdatas[0]['firstname_user'].' '.$evtdatas[0]['lastname_user']);
            $datas['subject'] = count($evtdatas).' sorties a valider';
            $datas['content'] = '
				<h1>Bonjour '.html_utf8($evtdatas[0]['firstname_user']).',</h1>
				<p>
				Vous êtes en charge de la validation légale des sorties, et il y a  '.count($evtdatas).' sortie(s) en attente de validation sur le site du '.$p_sitename.'.
				Prenez soin de vous rendre sur chaque fiche de sortie ci-dessous et de vérifier attentivement toutes les informations liées.
				</p>
				<h2>Sorties en attente de validation :</h2>
				<ul>
					<li>'.implode('</li><li>', $evtList).'</li>
				</ul>
			';
            cron_email($datas);
        }
    }
}

$mysqli->close();

if (isset($errTab) && count($errTab) > 0) {
    if (admin()) {
        foreach ($errTab as $err) {
            error_log("$err");
        }
    }
}

// echo nl2br($log);

?>
	</body>
</html>
