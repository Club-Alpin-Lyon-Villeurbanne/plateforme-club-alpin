<?php

include('class.phpmailer.php');
include('class.smtp.php');

class CAFPHPMailer extends PHPMailer {
	protected $content_full='';
	protected $headerSet = false;
	protected $footerSet = false;
	protected $bodySet = false;

	public function __construct($exceptions = false, $useBCC = false) {
		parent::__construct($exceptions);

		$this->content_full=implode("\n", file(APP.'templates/email1.html'));
		$this->content_full=str_replace('templateimgs/', $p_racine.'app/templates/templateimgs/', $this->content_full);
		$this->content_full=str_replace('[RACINE]', $p_racine, $this->content_full);
		$this->content_full=str_replace('[SITENAME]', $p_sitename, $this->content_full);
		
		// CONFIG SMTP GANDI
				/*
				$this->IsSMTP();
				$this->SMTPAuth = true;
				$this->Host = 'mail.gandi.net';
				$this->Port = 465;
				$this->Username = 'utilisateur@domaine.tld';
				$this->Password = 'pass';
				*/
				// END CONFIG SMTP GANDI
		
		// CONFIG SMTP OVH
				/*
				$this->IsSMTP();
				$this->SMTPAuth = true;
				$this->Host = 'smtp.domaine.tld';
				$this->Port = 5025;
				$this->Username = 'utilisateur@domaine.tld';
				$this->Password = 'pass';
				*/
				// END CONFIG SMTP OVH
				
		// CONFIG SMTP GMAIL
				/*
				$this->IsSMTP();
				$this->SMTPAuth = true;
				$this->Host = "tls://smtp.gmail.com";
				$this->Port = 465;
				$this->Username = 'X0X0X0X0X';
				$this->Password = 'X0X0X0X0X';
				*/
				// END CONFIG SMTP GMAIL
				
		GLOBAL $p_smtp_use;
		GLOBAL $p_smtp;
		if ($p_smtp_use) {
			$this->IsSMTP();
			$this->SMTPAuth = true;
			if ($p_smtp['host'] == 'smtp.gmail.com') $this->SMTPSecure = 'ssl';
			$this->Host = $p_smtp['host'];
			$this->Port = $p_smtp['port'];
			$this->Username = $p_smtp['user'];
			$this->Password = $p_smtp['pass'];
			if (isset($p_smtp['debug']) && $p_smtp['debug'] === true) {
				$this->SMTPDebug  = 2;
			}
		}
		
		GLOBAL $p_noreply;
		GLOBAL $p_sitename;
		$this->SetFrom($p_noreply, $p_sitename);
		$this->CharSet = 'UTF-8';
		$this->AltBody  = "Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)"; // optional, comment out and test
		$this->IsHTML(true);
		$this->XMailer = 'CAF-Mailer';

		if ($useBCC) $this->AddBCC ($p_noreply);

	}

    public function AddBCC($address, $name = '') {
		$this->recipients[$address] = $name;
	}

	public function setMailHeader ($content_header) {
		$this->headerSet = true;
		$this->content_full=str_replace('[HEADER]', $content_header, $this->content_full);
	}

	public function setMailFooter ($content_footer) {
		$this->footerSet = true;
		$this->content_full=str_replace('[FOOTER]', $content_footer, $this->content_full);
	}

	public function setMailBody ($content_main) {
		$this->bodySet = true;
		$this->content_full=str_replace('[MAIN]', $content_main, $this->content_full);
	}

	public function setAltMailBody ($content_alt) {
		$this->AltBody = $content_alt;
	}

	public function Send() {

		if ($this->headerSet == false) {
			$this->setMailHeader('');
		}
		if ($this->footerSet == false) {
			$this->setMailFooter('');
		}

		$this->MsgHTML($this->content_full);

		$nb_recipients=0;
		$log_coupure_mail = false;
		# Retiré par CRI le 06/12/2015
		# Inutile de logger le nombre de destinataires si < 50. Surtout si tout est Ok.
		# error_log ("PHPMAILER : nb_recipients=".count($this->recipients));
		
        if (is_array($this->recipients)) {
            foreach ($this->recipients as $address=>$name){
                
				/*
				 * CRI 16/01/2016 - enregistrement dans les logs que si $address ou $name vide
				 * Inutile de le faire pour les couples $address / $name valides
				*/
				if ( empty($address) || empty($name) ){
					error_log ("PHPMAILER : TO=".$address);
				} else {
					parent::AddBCC($address, $name);
					$nb_recipients++;
				}

                if($nb_recipients > 50){
                    # trop de destinataires on coupe le mail
                    parent::Send();
                    # RAZ destinataires
                    parent::ClearAddresses();
                    $nb_recipients=0;
					# CRI 16/01/2016 - On signal de l'on a coupé le mail
					if (!$log_coupure_mail){
						error_log ("PHPMAILER : Destinataires > 50 - nb_recipients=".count($this->recipients));
						$log_coupure_mail = true;
					}
                }
            }
		}
		return parent::Send();
	}

}


?>
