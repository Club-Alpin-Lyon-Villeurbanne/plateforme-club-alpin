<?php

	$id_evt=intval($_POST['id_evt']);
	$id_user=intval($_SESSION['user']['id_user']);


	if(!$id_user or !$id_evt) $errTab[]="Erreur de données";

	if(!sizeof($errTab)){

		include SCRIPTS.'connect_mysqli.php';;

		// récupération du statut de l'inscription : si elle est valide, l'orga recoit un e-mail
		$req="SELECT status_evt_join FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user ORDER BY tsp_evt_join DESC LIMIT 1 ";
		$result=$mysqli->query($req);
		$status_evt_join=0;
		while($row=$result->fetch_assoc()){
			$status_evt_join=$row['status_evt_join'];
		}

		if($status_evt_join==1 || $status_evt_join==0){
			// envoi du mail à l'orga
			// recup de son email & nom
			$toMail='';
			$toName='';
			$req="SELECT email_user, firstname_user, lastname_user FROM caf_user, caf_evt WHERE id_evt=$id_evt AND user_evt=id_user LIMIT 1";
			$result=$mysqli->query($req);
			while($row=$result->fetch_assoc()){
				$toMail=$row['email_user'];
				$toName=$row['firstname_user'].' '.$row['lastname_user'];
			}
			if(!isMail($toMail)) $errTab[]="Les coordonnées du contact sont erronées";

			if(!sizeof($errTab)){

				// si pas de pb, suppression de l'inscription
				$req="DELETE FROM caf_evt_join WHERE evt_evt_join=$id_evt AND user_evt_join=$id_user";
				if(!$mysqli->query($req)) {
					$errTab[]="Erreur SQL : ".$mysqli->error;
					error_log($mysqli->error);
				}

				// phpmailer
				require_once(APP.'mailer'.DS.'class.phpmailer.caf.php');

				// vars
				$tmpUserName=($_SESSION['user']['firstname_user'].' '.$_SESSION['user']['lastname_user']);
				$evtName=html_utf8($_POST['titre_evt']);
				$evtUrl=html_utf8($p_racine.'sortie/'.stripslashes($_POST['code_evt']).'-'.$_POST['id_evt'].'.html');

				// contenu
				$subject='Désinscription de '.$tmpUserName;
				$content_main="<h2>$subject</h2>
					<p>
						".html_utf8($tmpUserName)."
						était inscrit à la sortie <a href='$evtUrl'>$evtName</a>. <b>Il vient de supprimer son inscription.</b>
					</p>
					<p>
						Plus d'infos :
					</p>
					<ul>
						<li><b>Pseudo : </b> ".html_utf8($_SESSION['user']['nickname_user'])."</li>
						<li><b>Email : </b> <a href='mailto:".html_utf8($_SESSION['user']['email_user'])."'>".html_utf8($_SESSION['user']['email_user'])."</a></li>
						<li><b>Tel : </b> ".html_utf8($_SESSION['user']['tel_user'])."</li>
						<li><b>Tel2 : </b> ".html_utf8($_SESSION['user']['tel2_user'])."</li>
					</ul>
					";
				$content_header="";
				$content_footer="";

				$mail=new CAFPHPMailer(); // defaults to using php "mail()"

				$mail->AddReplyTo($_SESSION['user']['email_user']);
				$mail->SetFrom($p_noreply, $p_sitename);
				$mail->Subject  = $subject;
				//$mail->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
				$mail->setMailBody($content_main);
				$mail->setMailHeader ($content_header);
				$mail->setMailFooter ($content_footer);
				$mail->AddAddress($toMail, $toName);
				// $mail->AddAttachment("images/phpmailer_mini.gif"); // attachment


				if(!$mail->Send()){
					$errTab[]="Échec à l'envoi du mail à ".$toMail.". Plus d'infos : ".($mail->ErrorInfo);
				}
			}
		}

		$mysqli->close;
	}

?>