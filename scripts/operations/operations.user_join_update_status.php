<?php


	include SCRIPTS.'connect_mysqli.php';;

	// vars
	$id_evt=intval($_POST['id_evt']);
	$errTabMail=array();

	$addAlert = array(); // pour affichage de messages supplémentaires
	// on recoit un tableau des ID de JOINTS
	// et toutes les valeurs du statut présentées sous la forme : status_evt_join_ID_EVT_JOIN

	// suis-je encadrant sur cette sortie ?
	$suis_encadrant=false;
	$req="SELECT COUNT(id_evt_join)
	FROM caf_evt_join
	WHERE evt_evt_join=$id_evt
	AND user_evt_join = ".intval($_SESSION['user']['id_user'])."
	AND (role_evt_join LIKE 'encadrant' OR role_evt_join LIKE 'coencadrant')
	LIMIT 1";
	$result = $mysqli->query($req);
	$row = $result->fetch_row();
	if($row[0]>0) $suis_encadrant=true;

	// suis-je l'auteur de cette sortie ?
	$suis_auteur=false;
	$req="SELECT COUNT(id_evt) FROM caf_evt WHERE id_evt=$id_evt AND user_evt = ".intval($_SESSION['user']['id_user'])." LIMIT 1";
	$result = $mysqli->query($req);
	$row = $result->fetch_row();
	if($row[0]>0) $suis_auteur=true;


	if(!allowed('evt_join_doall') && !allowed('evt_join_notme') && !$suis_encadrant && !$suis_auteur)
		$errTab[] = "Opération interdite : Il semble que vous ne soyez pas autorisé à modifier les inscrits.";


	if(!sizeof($errTab)){

//				print_r($_POST);

		foreach($_POST['id_evt_join'] as $id_evt_join){
			$send_mail=false;

			// securite :
			$id_evt_join=intval($id_evt_join);

			if($id_evt_join){

				// nouvelles valeurs demandées
				$status_evt_join_new='';
				if(array_key_exists('status_evt_join_'.$id_evt_join, $_POST)){
					$status_evt_join_new=$mysqli->real_escape_string(intval($_POST['status_evt_join_'.$id_evt_join]));
					$send_mail=true;
				}
				$role_evt_join_new='';
				if(array_key_exists('role_evt_join_'.$id_evt_join, $_POST)){
					$role_evt_join_new=$mysqli->real_escape_string($_POST['role_evt_join_'.$id_evt_join]);
				}


				if(strlen($status_evt_join_new) == 0 && strlen($role_evt_join_new) == 0){
					continue;
				}

				if($status_evt_join_new < 0){
					$req="DELETE FROM caf_evt_join WHERE id_evt_join=$id_evt_join";

					$result=$mysqli->query($req);
					if(!$mysqli->query($req)) {
						$errTab[]="Erreur SQL DELETE ($req)";
						error_log($mysqli->error);
					}
					continue;
				}

				// récupération de la valeur actuelle, savoir si on la change ou pas
				$req="SELECT status_evt_join, user_evt_join, affiliant_user_join, role_evt_join, evt_evt_join FROM caf_evt_join WHERE id_evt_join=$id_evt_join ORDER BY tsp_evt_join DESC LIMIT 1 ";

				$result=$mysqli->query($req);

				$status_evt_join=0;
				$user_evt_join=0;
				$evt_evt_join=0;
				$isFiliation=false; // si cette inscription a été enregistrée par un parent : autre e-mail de contact (voir plus loin)

				if($row=$result->fetch_assoc()){
					$status_evt_join=intval($row['status_evt_join']);
					$user_evt_join=intval($row['user_evt_join']);
					$evt_evt_join=intval($row['evt_evt_join']);
					$affiliant_user_join=intval($row['affiliant_user_join']);
					$role_evt_join=$row['role_evt_join'];

					if($affiliant_user_join > 0) $isFiliation = true;
				}

				if((strlen($status_evt_join_new) > 0 && ($status_evt_join_new != $status_evt_join)) || (strlen($role_evt_join_new) > 0 && (strcmp ($role_evt_join_new,$role_evt_join)!=0))) {

					// check technique
					if($user_evt_join == 0 || $evt_evt_join == 0) next;// $errTab[]="Erreur de données ($user_evt_join / $evt_evt_join). Mise à jour interrompue.";

					if(!sizeof($errTab)){

						// update inscription
						$req="UPDATE caf_evt_join
							SET lastchange_when_evt_join = $p_time
							, lastchange_who_evt_join = ".intval($_SESSION['user']['id_user']);

						// s'il y a modification : update et envoi de mail
						if (strlen($status_evt_join_new) > 0 && ($status_evt_join_new != $status_evt_join))
							$req.= " , status_evt_join='".$status_evt_join_new."'";
						if (strlen($role_evt_join_new) > 0 && (strcmp ($role_evt_join_new,$role_evt_join)!=0))
							$req.= " , role_evt_join='".$role_evt_join_new."'";

						$req.= " WHERE caf_evt_join.id_evt_join =$id_evt_join";

//						print "$req<br />";continue;
						if(!$mysqli->query($req)) {
							$errTab[]="Erreur SQL update $id_evt_join (de $status_evt_join à $status_evt_join_new)";
							error_log($mysqli->error);
						}

						// si la mise à jour s'est bien passée
						else{

							// si la nouvelle valeur est 1 ou 2
							if($send_mail && ($status_evt_join_new==1 || $status_evt_join_new==2)){

								// si la var pour empecher les mails n'est pas passée (dans le cas d'un événement deja passé)
								// if(!$_POST['dontsendmail']){
								if($_POST['disablemails']!='on' && !$_POST['dontsendmail']){
									// envoi du mail à l'inscrit (ou au désinscrit du coup)
									// recup de son email & nom
									$toMail='';
									$toName='';
									$isNomade=false;
									$req="SELECT email_user, firstname_user, lastname_user, civ_user, nomade_user, tel_user, tel2_user FROM caf_user WHERE id_user=$user_evt_join LIMIT 1";
									$result=$mysqli->query($req);
									while($row=$result->fetch_assoc()){
										$toMail=$row['email_user'];
										$toName=$row['firstname_user'];
										$toNameFull=$row['firstname_user'].' '.$row['lastname_user'];
										$toTel=$row['tel_user'].' '.($row['tel2_user']?' - '.$row['tel2_user'] :'');
										$isNomade=intval($row['nomade_user']);
									}

									// nomade ?
									if($isNomade){
										$addAlert[]="
											<b>$toNameFull</b> est un adhérent nomade. Il n'a pas d'email et doit être prévenu par téléphone de son nouveau statut : "
											.($status_evt_join_new==0?'<b>En attente</b>':'')
											.($status_evt_join_new==1?'<b>Inscrit</b>':'')
											.($status_evt_join_new==2?'<b>Refusé</b>':'')
											.". <br />Tél : ".$toTel
											;
									}

									// filiation ? Dans ce cas on change la valeurs du mail
									if($isFiliation){
										$req="SELECT email_user, firstname_user, lastname_user, civ_user, nomade_user, tel_user, tel2_user FROM caf_user WHERE id_user=$affiliant_user_join LIMIT 1";
										$result=$mysqli->query($req);
										while($row=$result->fetch_assoc()){
											$toMail=$row['email_user'];
											// $toName=$row['civ_user'].' '.$row['lastname_user'];
											// $toTel=$row['tel_user'].' '.($row['tel2_user']?' - '.$row['tel2_user'] :'');
										}
									}


									// PAS nomade : email
									if(!$isNomade && (strlen($toMail) > 0)) {

										// if(!isMail($toMail)) $errTab[]="Aucun e-mail n'a été envoyé à $toName.";

										if(!sizeof($errTab)){

											// phpmailer
											require_once(APP.'mailer'.DS.'class.phpmailer.caf.php');

											// vars
											$evtName=html_utf8($_POST['titre_evt']);
											$evtUrl=html_utf8($p_racine.'sortie/'.stripslashes($_POST['code_evt']).'-'.$_POST['id_evt'].'.html');

											switch($role_evt_join){
												case 'encadrant':
												case 'coencadrant':
												case 'benevole':
													$role = $role_evt_join;
													break;
												default :
													$role = "participant";
											}


											// contenu
											if($status_evt_join_new==1){
												$subject='Votre inscription est confirmée';
												$content_main="<h2>$subject</h2>
													<p>
														Bonjour $toName,<br />
														Vous venez d'être confirmé(e) comme $role à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;.
													</p>
													<p>
														Cliquez sur le lien ci-dessous pour en savoir plus :<br />
														<a href='$evtUrl'>$evtUrl</a><br />
														<br />
														Bonne journée.
													</p>
												";
											}
											if($status_evt_join_new==2){
												$subject='Votre inscription est déclinée';
												$content_main="<h2>$subject</h2>
													<p>
														Bonjour $toName,<br />
														Vous avez demandé à participer à la sortie &laquo; <a href='$evtUrl'>$evtName</a> &raquo;, mais
														votre demande a malheureusement été déclinée.
													</p>
													<p>
														Cliquez sur le lien ci-dessous pour en savoir plus :<br />
														<a href='$evtUrl'>$evtUrl</a><br />
														<br />
														Bonne journée.
													</p>
												";
											}


											$content_header="";
											$content_footer="";

											$mail=new CAFPHPMailer(); // defaults to using php "mail()"

											$mail->SetFrom($p_noreply, $p_sitename);
											$mail->Subject  = $subject;
											//$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
											$mail->setMailBody($content_main);
											$mail->setMailHeader ($content_header);
											$mail->setMailFooter ($content_footer);
											$mail->AddAddress($toMail, $toName);
											// $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

											// débug local
											if($_SERVER['HTTP_HOST'] == '127.0.0.1')	$mail->IsMail();

											if(!$mail->Send()){
												$errTabMail[]="Échec à l'envoi du mail à ".html_utf8($toMail).". Plus d'infos : ".($mail->ErrorInfo);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	$mysqli->close;
	$errTab = array_merge($errTab, $errTabMail);

?>