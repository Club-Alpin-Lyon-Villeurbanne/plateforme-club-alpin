<?php

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';
$errTabMail = [];

// vars
$id_evt = (int) ($_GET['id_evt']); // spécial : donné dans l'URL

// suis-je encadrant sur cette sortie ?
$suis_encadrant = false;
$req = "SELECT COUNT(id_evt_join)
FROM caf_evt_join
WHERE evt_evt_join=$id_evt
AND user_evt_join = ".(int) ($_SESSION['user']['id_user'])."
AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'coencadrant')
LIMIT 1";
$result = $mysqli->query($req);
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_encadrant = true;
}

// suis-je l'auteur de cette sortie ?
$suis_auteur = false;
$req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND user_evt = ".(int) ($_SESSION['user']['id_user']).' LIMIT 1';
$result = $mysqli->query($req);
$row = $result->fetch_row();
if ($row[0] > 0) {
    $suis_auteur = true;
}

// checks SQL : l'user doit être l'auteur *OU* avoir le droit de modifier toutes les inscriptions *OU* être encadrant sur la sortie
if (!allowed('evt_join_doall') && !allowed('evt_join_notme') && !$suis_encadrant && !$suis_auteur) {
    $errTab[] = 'Opération interdite : Il semble que vous ne soyez pas autorisé à ajouter des inscrits';
}

    // checks :
if (!$id_evt) {
    $errTab[] = 'ID event manquant';
}
if (!is_array($_POST['id_user'])) {
    $errTab[] = 'Tableau des ID manquant';
} else {
    // if(sizeof($_POST['id_user']) != sizeof($_POST['civ_user'])) $errTab[]="Erreur de correspondance de la var civ_user";
    // if(sizeof($_POST['id_user']) != sizeof($_POST['lastname_user'])) $errTab[]="Erreur de correspondance de la var lastname_user";
    // if(sizeof($_POST['id_user']) != sizeof($_POST['firstname_user'])) $errTab[]="Erreur de correspondance de la var firstname_user";
    if (count($_POST['id_user']) != count($_POST['role_evt_join'])) {
        $errTab[] = 'Erreur de correspondance de la var role_evt_join';
    }
}

if (!isset($errTab) || 0 === count($errTab)) {
    // on vérifie le statut d'inscription de la destination
    if ($_POST['id_destination']) {
        $inscriptions_status = inscriptions_status_destination($_POST['id_destination']);
        if (true != $inscriptions_status['status']) {
            $errTab[] = 'Les inscriptions à la destination ne sont pas possibles';
        }
    }

    // verification de la validité de la sortie
    $req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND status_evt != 1 LIMIT 1";
    $result = $mysqli->query($req);
    $row = $result->fetch_row();
    if ($row[0]) {
        $errTab[] = 'Cette sortie ne semble pas publiée, les inscriptions sont impossible';
    }

    // pour chaque id donné
    foreach ($_POST['id_user'] as $i => $user) {
        $id_user = (int) ($_POST['id_user'][$i]);
        $role_evt_join = stripslashes($_POST['role_evt_join'][$i]);

        if ($_POST['is_cb']) {
            $is_cb = $_POST['is_cb'][$i];
            if ('1' == $is_cb) {
                $is_cb = 1;
            } elseif ('0' == $is_cb) {
                $is_cb = 0;
            } else {
                $is_cb = 'NULL';
            }
        } else {
            $is_cb = 'NULL';
        }

        if ($_POST['is_restaurant']) {
            $is_restaurant = $_POST['is_restaurant'][$i];
            if ('1' == $is_restaurant) {
                $is_restaurant = 1;
            } elseif ('0' == $is_restaurant) {
                $is_restaurant = 0;
            } else {
                $is_restaurant = 'NULL';
            }
        } else {
            $is_restaurant = 'NULL';
        }

        if ($_POST['id_bus_lieu_destination']) {
            $id_bus_lieu_destination = $_POST['id_bus_lieu_destination'][$i];
            $is_covoiturage = '0';
            if ('-1' == $id_bus_lieu_destination) {
                $id_bus_lieu_destination = '0';
                $is_covoiturage = '1';
            }
        } else {
            $id_bus_lieu_destination = 'NULL';
            $is_covoiturage = 'NULL';
        }

        // on vérifie ls places dans les bus
        if ($_POST['id_destination']) {
            $id_destination = $_POST['id_destination'];

            // Vérifier les places dans le bus sélectionné
            if ($id_bus_lieu_destination > 0) { // sinon c'est du covoiturage
                $nbp = nb_places_restante_bus_ramassage($id_bus_lieu_destination);
                if ($nbp <= 0) {
                    $errTab[] = 'Ce bus est désormais plein. Merci de choisir un autre lieu de ramassage pour '.$_POST['civ_user'][$i].' '.$_POST['lastname_user'][$i].' '.$_POST['firstname_user'][$i].' et suivants.';
                }
            }
        } else {
            $id_destination = 'NULL';
        }

        // si pas de pb, intégration
        $role_evt_join = $mysqli->real_escape_string($role_evt_join);

        if (!isset($errTab) || 0 === count($errTab)) {
            // attention : status_evt_join est à 1 ici par défaut
            $status_evt_join = 0;
            if ($suis_encadrant || $suis_auteur) {
                $status_evt_join = 1;
            }

            $req = 'INSERT INTO '.$pbd."evt_join(
                        id_evt_join, status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join,
                        lastchange_when_evt_join, lastchange_who_evt_join,
                        is_cb, is_restaurant, id_bus_lieu_destination, id_destination, is_covoiturage)
                    VALUES (
                        NULL ,		$status_evt_join, '$id_evt',    '$id_user',  '$role_evt_join', ".time().',
                        '.time().', 			'.(int) ($_SESSION['user']['id_user']).",
                        $is_cb, $is_restaurant, $id_bus_lieu_destination, $id_destination, $is_covoiturage );";
            if (!$mysqli->query($req)) {
                $errTab[] = 'Erreur SQL : '.$mysqli->error;
                error_log($mysqli->error);
            } else {
                unset($_POST['id_user'][$i]);
                unset($_POST['civ_user'][$i]);
                unset($_POST['lastname_user'][$i]);
                unset($_POST['firstname_user'][$i]);
                unset($_POST['nickname_user'][$i]);
                unset($_POST['role_evt_join'][$i]);
                unset($_POST['is_cb'][$i]);
                unset($_POST['is_restaurant'][$i]);
                unset($_POST['id_bus_lieu_destination'][$i]);
            }
        }

        if (!isset($errTab) || 0 === count($errTab)) {
            // ENVOI DU MAIL

            // recup de son email & nom
            $toMail = '';
            $toName = '';
            $req = "SELECT email_user, firstname_user, lastname_user, civ_user FROM caf_user WHERE id_user=$id_user LIMIT 1";
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $toMail = $row['email_user'];
                $toName = $row['firstname_user'];
            }
            if (!isMail($toMail)) {
                $errTabMail[] = "Les coordonnées du contact sont erronées (l'inscription est réalisée quand même)";
            }

            // recup infos evt
            $evtUrl = '';
            $evtName = '';
            $req = "SELECT id_evt, code_evt, titre_evt FROM caf_evt WHERE id_evt=$id_evt LIMIT 1";
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $evtUrl = html_utf8($p_racine.'sortie/'.$row['code_evt'].'-'.$row['id_evt'].'.html');
                $evtName = html_utf8($row['titre_evt']);
            }

            if (0 === count($errTabMail)) {
                // phpmailer
                require_once __DIR__.'/../../app/mailer/class.phpmailer.caf.php';

                // contenu
                $subject = 'Vous avez été inscrit à une sortie du CAF';
                $content_main = "<h2>$subject</h2>
                    <p>
                        Bonjour $toName,<br />
                        Vous venez d'être inscrit ".('manuel' == $role_evt_join ? '' : ' en tant que <i>'.$role_evt_join.'</i>')."
                        à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
                    </p>
                    <p>
                        Cliquez sur le lien ci-dessous pour en savoir plus :<br />
                        <a href='$evtUrl'>$evtUrl</a><br />
                        <br />
                        Bonne journée.
                    </p>
                ";

                $content_header = '';
                $content_footer = '';

                $mail = new CAFPHPMailer(); // defaults to using php "mail()"

                $mail->SetFrom($p_noreply, $p_sitename);
                $mail->Subject = $subject;
                //$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
                $mail->setMailBody($content_main);
                $mail->setMailHeader($content_header);
                $mail->setMailFooter($content_footer);
                $mail->AddAddress($toMail, $toName);
                // $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

                // débug local
                if ('127.0.0.1' == $_SERVER['HTTP_HOST']) {
                    $mail->IsMail();
                }

                if (!$mail->Send()) {
                    $errTabMail[] = "Échec à l'envoi du mail à ".html_utf8($toMail).". Plus d'infos : ".($mail->ErrorInfo);
                }
            }
        }
    }
}
if (count($errTabMail)) {
    $errTab = array_merge($errTabMail, isset($errTab) ? $errTab : []);
}

if (!isset($errTab) || 0 === count($errTab)) {
    $_POST['id_user'] = [0];
    $_POST['result'] = 'success';
}
$mysqli->close;
