<?php

require_once __DIR__.'/../../vendor/autoload.php';

    // Destination : ai-je choisi mon moyen de transport ?
    $is_destination = false;
    if ($_POST['id_destination']) {
        $is_destination = true;
        $id_destination = (int) ($_POST['id_destination']);
        if (!isset($_POST['id_bus_lieu_destination'])) {
            $errTab[] = "Vous devez préciser votre lieu de ramassage ou sélectionner l'option de transport individuel / covoiturage";
        } else {
            $id_bus_lieu_destination = (int) ($_POST['id_bus_lieu_destination']);
        }
    }

    // Filiations
    if ('on' == $_POST['filiations']) {
        $filiations = true;
    } else {
        $filiations = false;
    }

    // Evenement défini et utilisateur aussi
    $id_evt = (int) ($_POST['id_evt']);
    $id_user = (int) ($_SESSION['user']['id_user']);
    if (!$id_user || !$id_evt) {
        $errTab[] = 'Erreur de données';
    }

    // CGUs
    if ('on' != $_POST['confirm']) {
        $errTab[] = "Merci de cocher la case &laquo; J'ai lu les conditions...&raquo;";
    }

    if (!count($errTab)) {
        // Bénévole
        if ('on' == $_POST['jeveuxetrebenevole']) {
            $role_evt_join = 'benevole';
        } else {
            $role_evt_join = 'inscrit';
        }

        include SCRIPTS.'connect_mysqli.php';

        // si filiations : création du tableau des joints et vérifications
        if ($filiations) {
            if (!count($_POST['id_user_filiation'])) {
                $errTab[] = 'Merci de choisir au moins une personne à inscrire';
            }
            // pour chaque id envoyé
            foreach ($_POST['id_user_filiation'] as $id_user_tmp) {
                // vérification que c'est bien mon affilié
                // sauf moi-meme
                if ($id_user_tmp != $_SESSION['user']['id_user']) {
                    $req = 'SELECT COUNT(id_user) FROM '.$pbd."user WHERE cafnum_parent_user LIKE '".$mysqli->real_escape_string($_SESSION['user']['cafnum_user'])."' AND id_user=".(int) $id_user_tmp;
                    $result = $mysqli->query($req);
                    $row = $result->fetch_row();
                    if (!$row[0]) {
                        $errTab[] = "ID '".(int) $id_user_tmp."' invalide pour l'inscription d'un adhérent affilié";
                    }
                }
            }
        }

        // verification de la validité de la destination
        if ($is_destination) {
            $req = 'SELECT COUNT(id) FROM '.$pbd."destination WHERE ( id=$id_destination AND annule != 0 ) OR ( id=$id_destination AND  publie != 1 ) LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                $errTab[] = 'Cette destination ne semble pas publiée ou est annulée, les inscriptions sont impossible';
            }
        }

        // verification de la validité de la sortie
        $req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND status_evt != 1 LIMIT 1";
        $result = $mysqli->query($req);
        $row = $result->fetch_row();
        if ($row[0]) {
            $errTab[] = 'Cette sortie ne semble pas publiée, les inscriptions sont impossible';
        }

        // verification du timing de la sortie
        $req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND tsp_evt < $p_time LIMIT 1";
        $result = $mysqli->query($req);
        $row = $result->fetch_row();
        if ($row[0]) {
            $errTab[] = 'Cette sortie a deja demarrée';
        }

        // destination :
        if ($is_destination) {
            // Inscriptions ouvertes ?
            $inscriptions_status = inscriptions_status_destination($id_destination);
            if (true != $inscriptions_status['status']) {
                $errTab[] = 'Les inscriptions ne sont pas possibles';
            }

            // Vérifier les places dans le bus sélectionné
            if ($id_bus_lieu_destination > 0) { // sinon c'est du covoiturage
                $nbp = nb_places_restante_bus_ramassage($id_bus_lieu_destination);
                if ($nbp <= 0) {
                    $errTab[] = 'Ce bus est désormais plein. Merci de choisir un autre lieu de ramassage.';
                }
            }
        } else {
            // verification du timing de la sortie : inscriptions
            $req = "SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND join_start_evt > $p_time LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                $errTab[] = 'Les inscriptions ne sont pas encore ouvertes';
            }
        }

        // Doit on faire une mise à jour ?
        $update = false;

        // verification de l'existence de cette demande
        if (!$filiations) {
            $req = "SELECT COUNT(id_evt_join) FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user LIMIT 1";
            $result = $mysqli->query($req);
            $row = $result->fetch_row();
            if ($row[0]) {
                // $errTab[]="Vous semblez déjà être pré-inscrit à cette sortie.";
                $update[] = $id_user;
            }
        }
        // pour les inscriptions d'affilié
        else {
            foreach ($_POST['id_user_filiation'] as $id_user_tmp) {
                $id_user_tmp = (int) $id_user_tmp;
                $req = "SELECT id_user, lastname_user, firstname_user, civ_user
				FROM caf_evt_join, caf_user
				WHERE evt_evt_join=$id_evt
				AND user_evt_join=id_user
				AND id_user=$id_user_tmp
				LIMIT 1";
                $result = $mysqli->query($req);
                while ($row = $result->fetch_assoc()) {
                    // $errTab[]=$row['firstname_user']." ".$row['lastname_user']." semble déjà être pré-inscrit(e) à cette sortie.";
                    $update[] = $id_user_tmp;
                }
            }
        }

        // SI PAS DE PB, INTÉGRATION BDD
        if (!count($errTab)) {
            if ('on' == $_POST['jeveuxpayerenligne']) {
                $is_cb = 1;
            } else {
                if (isset($_POST['is_cb'])) {
                    $is_cb = 0;
                } else {
                    $is_cb = 'NULL';
                }
            }

            if ('on' == $_POST['jeveuxmangerauresto']) {
                $is_restaurant = 1;
            } else {
                if (isset($_POST['is_restaurant'])) {
                    $is_restaurant = 0;
                } else {
                    $is_restaurant = 'NULL';
                }
            }

            if (!$is_destination) {
                $id_bus_lieu_destination = 'NULL';
                $id_destination = 'NULL';
                $is_covoiturage = 'NULL';
            } else {
                if ($id_bus_lieu_destination < 0) {
                    $id_bus_lieu_destination = 0;
                    $is_covoiturage = 1;
                } else {
                    $is_covoiturage = 0;
                }
            }

            $status_evt_join = ($is_destination ? '1' : '0');

            $evt = get_evt($id_evt);

            // normal
            if (!$filiations) {
                /* if (count(empietement_sortie($id_user, $evt)) > 0) {
                    $errTab[]="Utilisateur $id_user déjà inscrit sur une sortie simultanée.";
                } else { */

                if (!$update) {
                    $req = "INSERT INTO caf_evt_join(id_evt_join, status_evt_join, evt_evt_join, user_evt_join, role_evt_join, tsp_evt_join, is_cb, is_restaurant, id_bus_lieu_destination, id_destination, is_covoiturage)
                              VALUES(NULL ,			 $status_evt_join, 		'$id_evt',  '$id_user',  	'$role_evt_join', $p_time, $is_cb, $is_restaurant, $id_bus_lieu_destination, $id_destination, $is_covoiturage);";
                } elseif (in_array($id_user, $update, true)) {
                    $req = 'UPDATE `'.$pbd."evt_join`
                                SET
                                    `id_bus_lieu_destination` = $id_bus_lieu_destination, `id_destination` = $id_destination, `is_covoiturage` = $is_covoiturage, `is_cb` = $is_cb, `is_restaurant` = $is_restaurant
                                WHERE
                                    `user_evt_join` = $id_user AND evt_evt_join = $id_evt;";
                }
                if (!$mysqli->query($req)) {
                    $errTab[] = 'Erreur SQL '.$req;
                }
                // }
            }
            // filiations
            else {
                foreach ($_POST['id_user_filiation'] as $id_user_tmp) {
                    /* if (count(empietement_sortie($id_user_tmp, $evt)) > 0) {
                        $errTab[]="Utilisateur $id_user_tmp déjà inscrit sur une sortie simultanée.";

                    } else { */
                    if (!$update || !in_array($id_user_tmp, $update, true)) {
                        $req = "INSERT INTO caf_evt_join(id_evt_join, status_evt_join, evt_evt_join, user_evt_join, affiliant_user_join, role_evt_join, tsp_evt_join, is_cb, is_restaurant, id_bus_lieu_destination, id_destination, is_covoiturage)
                                  VALUES(NULL ,			 $status_evt_join, 		'$id_evt',  '$id_user_tmp',  '$id_user',  	'$role_evt_join', $p_time, $is_cb, $is_restaurant, $id_bus_lieu_destination, $id_destination, $is_covoiturage);";
                    } elseif (in_array($id_user_tmp, $update, true)) {
                        $req = 'UPDATE `'.$pbd."evt_join`
                                SET
                                    `id_bus_lieu_destination` = $id_bus_lieu_destination, `id_destination` = $id_destination, `is_covoiturage` = $is_covoiturage, `is_cb` = $is_cb, `is_restaurant` = $is_restaurant
                                WHERE
                                    `user_evt_join` = $id_user_tmp AND evt_evt_join = $id_evt;";
                    }
                    if (!$mysqli->query($req)) {
                        $errTab[] = 'Erreur SQL '.$req;
                    }
                    // }
                }
            }
        }

        // E-MAIL À L'ORGANISATEUR ET AUX ENCADRANTS
        if (!count($errTab)) {
            $destinataires = [];

            // créateur de sortie (on utilise les ID comme clé pour éviter le doublon d'email créateur de sortie + encadreant de sortie)
            $req = 'SELECT id_user, email_user, nickname_user, firstname_user, lastname_user, civ_user '
            .'FROM caf_user, caf_evt '
            .'WHERE id_user = user_evt '
            ."AND id_evt = $id_evt "
            .'LIMIT 1; ';
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $destinataires[''.$row['id_user']] = $row;
            }

            // encadrants (on utilise les ID comme clé pour éviter le doublon d'email créateur de sortie + encadreant de sortie)
            $req = 'SELECT id_user, email_user, nickname_user, firstname_user, lastname_user, civ_user, role_evt_join '
            .'FROM caf_user, caf_evt_join '
            .'WHERE id_user = user_evt_join '
            ."AND evt_evt_join = $id_evt "
            .'AND status_evt_join = 1 '
            ."AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'coencadrant') ";
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $destinataires[''.$row['id_user']] = $row;
            }

            // infos sur la sortie
            $evt = [];
            $req = 'SELECT id_evt, code_evt, titre_evt, tsp_evt '
            .'FROM caf_evt '
            ."WHERE id_evt = $id_evt "
            .'LIMIT 1 ';
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $evt = $row;
            }

            $evtUrl = html_utf8($p_racine.'sortie/'.$evt['code_evt'].'-'.$evt['id_evt'].'.html');
            $evtName = $evt['titre_evt'];

            // infos sur ce nouvel inscrit
            $inscrits = [];
            $req = 'SELECT email_user, nickname_user, firstname_user, lastname_user, civ_user, birthday_user '
            .'FROM caf_user '
            .($filiations ?
                'WHERE id_user = '.(implode(' OR id_user = ', $_POST['id_user_filiation'])).' ' // filiation : liste d'ids
                :
                "WHERE id_user = $id_user "
            )
            .'LIMIT 100 ';
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $inscrits[] = $row;
                // echo 'adding '.$row['firstname_user'];
            }

            // echo '<pre>DEV : '; print_r($destinataires); echo '</pre>';

            foreach ($inscrits as $cetinscrit) {
                foreach ($destinataires as $id_destinataire => $destinataire) {
                    $toMail = $destinataire['email_user'];
                    $toName = $destinataire['firstname_user']; //$destinataire['civ_user'].' '.$destinataire['lastname_user'];

                    // contenu
                    if ($is_destination) {
                        $subject = 'Nouvelle inscription à la sortie « '.$evtName.' »';
                    } else {
                        $subject = "Nouvelle demande d'inscription à la sortie « ".$evtName.' »';
                    }

                    $content_main = "<h2>$subject</h2>
						<p>
							Bonjour $toName,<br />
							Un nouvel adhérent vient de ".($is_destination ? 's\'inscrire' : 'se préinscrire')."
							à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
						</p>
						<p>
							Nom : ".html_utf8($cetinscrit['civ_user'].' '.$cetinscrit['firstname_user'].' '.$cetinscrit['lastname_user']).' <br />
							Pseudo : '.html_utf8($cetinscrit['nickname_user']).' <br />';
                    //Age : ".getYearsSinceDate($cetinscrit['birthday_user'])." ans <br />
                    $content_main .= 'Rôle : '.$role_evt_join.
                            ('NULL' != $is_cb ? '<br />'.'Paiement en ligne : '.(1 == $is_cb ? 'oui' : 'non') : '').
                            ('NULL' != $is_restaurant ? '<br />'.'Restaurant : '.(1 == $is_restaurant ? 'oui' : 'non') : '').
                            ('NULL' != $id_destination ? ' <br />'.(1 == $is_covoiturage ? 'Vient par ses propres moyens au départ de la sortie' : 'Vient en bus collectif') : '')
                            .'
						</p>
						<p>
							Vous recevez ce message car
							'.
                                ($destinataire['role_evt_join'] ?
                                      ' vous êtes '.$destinataire['role_evt_join'].' sur cette sortie.'
                                    : " vous êtes l'auteur de cette sortie."
                                )
                            ."
						</p>
						<p>
							Cliquez sur le lien ci-dessous pour voir la fiche sortie :<br />
							<a href='$evtUrl'>$evtUrl</a><br />
							<br />
							Bonne journée.
						</p>
					";

                    $content_header = '';
                    $content_footer = '';

                    $mail = new CAFPHPMailer(); // defaults to using php "mail()"

                    $mail->AddReplyTo($cetinscrit['email_user']);
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
                        $errTab[] = "Échec à l'envoi du mail à ".html_utf8($toMail).". Plus d'infos : ".($mail->ErrorInfo);
                    }
                }
            }
        }

        // E-MAIL AU PRE-INSCRIT
        if (!count($errTab)) {
            // var cetinscrit déja définie
            // $destinataire = $cetinscrit;
            $destinataire = $_SESSION['user'];

            $toMail = $destinataire['email_user'];
            $toName = $destinataire['firstname_user']; //$destinataire['civ_user'].' '.$destinataire['lastname_user'];

            // contenu

            if ($is_destination) {
                $subject = 'Votre inscription à « '.$evtName.' »';
            } else {
                $subject = "Votre demande d'inscription à « ".$evtName.' »';
            }

            $ramassage = false;
            if ($id_bus_lieu_destination) {
                $ramassage = get_info_bus_lieu_destination($id_bus_lieu_destination);
            }

            // inscription simple de moi à moi
            if (!$filiations) {
                $content_main = "<h2>$subject</h2>
					<p>
						Bonjour $toName,<br />
						Vous venez de vous <i>".($is_destination ? 'inscrire' : 'préinscrire')."</i> à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
					</p>
					<p>
						Nom : ".html_utf8($cetinscrit['civ_user'].' '.$cetinscrit['firstname_user'].' '.$cetinscrit['lastname_user']).' <br />
						Pseudo : '.html_utf8($cetinscrit['nickname_user']).' <br />';
                //Age : ".getYearsSinceDate($cetinscrit['birthday_user'])." ans <br />
                $content_main .= 'Rôle : '.$role_evt_join.
                            ('NULL' != $is_cb ? ' <br />'.'Paiement en ligne : '.(1 == $is_cb ? 'oui' : 'non') : '').
                            ('NULL' != $is_restaurant ? ' <br />'.'Restaurant : '.(1 == $is_restaurant ? 'oui' : 'non') : '').
                            ('NULL' != $id_destination ? ' <br />'.(1 == $is_covoiturage ? 'Vient par ses propres moyens au départ de la sortie' : 'Vient en bus collectif'.($ramassage ? ' : ramassage le '.$ramassage['date'].' à '.$ramassage['lieu']['nom'] : '')) : '')
                        ."
					</p>
					<p>
						Les responsables de cette sortie ont été prévenus de votre demande et sont en charge de la confirmation
						ou non de votre participation. Vous serez averti par e-mail quand votre participation sera confirmée.
					</p>
					<p>
						Cliquez sur le lien ci-dessous pour voir la fiche sortie :<br />
						<a href='$evtUrl'>$evtUrl</a><br />
						<br />
						Bonne journée !
					</p>
				";
            } else {
                // je n'inscrit que moi
                if (1 == count($inscrits) && $inscrits[0]['id_user'] == $_SESSION['user']['id_user']) {
                    $content_main = "<h2>$subject</h2>
						<p>
							Bonjour $toName,<br />
							Vous venez de vous <i>".($is_destination ? 'inscrire' : 'préinscrire')."</i> à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
						</p>
						<p>
							Nom : ".html_utf8($cetinscrit['civ_user'].' '.$cetinscrit['firstname_user'].' '.$cetinscrit['lastname_user']).' <br />
							Pseudo : '.html_utf8($cetinscrit['nickname_user']).' <br />';
                    //Age : ".getYearsSinceDate($cetinscrit['birthday_user'])." ans <br />
                    $content_main .= 'Rôle : '.$role_evt_join.
                            ('NULL' != $is_cb ? ' <br />'.'Paiement en ligne : '.(1 == $is_cb ? 'oui' : 'non') : '').
                            ('NULL' != $is_restaurant ? ' <br />'.'Restaurant : '.(1 == $is_restaurant ? 'oui' : 'non') : '').
                            ('NULL' != $id_destination ? ' <br />'.(1 == $is_covoiturage ? 'Vient par ses propres moyens au départ de la sortie' : 'Vient en bus collectif'.($ramassage ? ' : ramassage le '.$ramassage['date'].' à '.$ramassage['lieu']['nom'] : '')) : '')
                        ."
						</p>
						<p>
							Les responsables de cette sortie ont été prévenus de votre demande et sont en charge de la confirmation
							ou non de votre participation. Vous serez averti par e-mail quand votre participation sera confirmée.
						</p>
						<p>
							Cliquez sur le lien ci-dessous pour voir la fiche sortie :<br />
							<a href='$evtUrl'>$evtUrl</a><br />
							<br />
							Bonne journée !
						</p>
					";
                }
                // j'inscrit n autres que moi
                else {
                    $content_main = "<h2>$subject</h2>
						<p>
							Bonjour $toName,<br />
							Vous venez de <i>".($is_destination ? 'inscrire' : 'préinscrire')."</i> plusieurs personnes à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
						</p>";
                    foreach ($inscrits as $cetinscrit) {
                        $content_main .= '
							<p>
								Nom : '.html_utf8($cetinscrit['civ_user'].' '.$cetinscrit['firstname_user'].' '.$cetinscrit['lastname_user']).' <br />
								Pseudo : '.html_utf8($cetinscrit['nickname_user']).' <br />';
                        //Age : ".getYearsSinceDate($cetinscrit['birthday_user'])." ans <br />".
                        $content_main .= ('NULL' != $is_cb ? 'Paiement en ligne : '.(1 == $is_cb ? 'oui' : 'non') : '').
                                    ('NULL' != $is_restaurant ? 'Restaurant : '.(1 == $is_restaurant ? 'oui' : 'non') : '').
                                    ('NULL' != $id_destination ? ' <br />'.(1 == $is_covoiturage ? 'Vient par ses propres moyens au départ de la sortie' : 'Vient en bus collectif'.($ramassage ? ' : ramassage le '.$ramassage['date'].' à '.$ramassage['lieu']['nom'] : '')) : '')
                                .'
							</p>
							';
                    }
                    $content_main .= "
						<p>
							Les responsables de cette sortie ont été prévenus de votre demande et sont en charge de la confirmation
							ou non de ces participations. Vous serez alors averti par e-mail.
						</p>
						<p>
							Cliquez sur le lien ci-dessous pour voir la fiche sortie :<br />
							<a href='$evtUrl'>$evtUrl</a><br />
							<br />
							Bonne journée !
						</p>
					";
                }
            }

            $content_header = '';
            $content_footer = '';

            $mail = new CAFPHPMailer(); // defaults to using php "mail()"
            //$mail->CharSet = 'UTF-8';
            //$mail->IsHTML(true);
            $mail->SetFrom($p_noreply, $p_sitename);
            // $mail->AddReplyTo($p_noreply, $p_sitename);
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
                $errTab[] = "Échec à l'envoi du mail à ".html_utf8($toMail).". Plus d'infos : ".($mail->ErrorInfo);
            }
        }

        $mysqli->close;
    }
