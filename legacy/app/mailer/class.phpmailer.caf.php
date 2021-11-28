<?php
/**
 * This example shows how to extend PHPMailer to simplify your coding.
 * If PHPMailer doesn't do something the way you want it to, or your code
 * contains too much boilerplate, don't edit the library files,
 * create a subclass instead and customise that.
 * That way all your changes will be retained when PHPMailer is updated.
 */

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require __DIR__.'/Exception.php';
require __DIR__.'/PHPMailer.php';
require __DIR__.'/SMTP.php';

/**
 * Use PHPMailer as a base class and extend it.
 */
class CAFPHPMailer extends PHPMailer
{
    public $content_full;
    public $recipients;
    public $headerSet;
    public $footerSet;
    public $bodySet;

    /**
     * myPHPMailer constructor.
     *
     * @param bool|null $exceptions
     * @param string    $body       A default HTML message body
     */
    public function __construct($exceptions = false, $useBCC = false)
    {
        $config = require __DIR__.'/../../config/config.php';

        //Don't forget to do this or other things may not be set correctly!
        parent::__construct($exceptions);

        $this->content_full = implode("\n", file(__DIR__.'/../../app/templates/email1.html'));
        $this->content_full = str_replace('templateimgs/', $config['url'].'/app/templates/templateimgs/', $this->content_full);
        $this->content_full = str_replace('[RACINE]', $config['url'], $this->content_full);
        $this->content_full = str_replace('[SITENAME]', 'CAF Lyon Villeurbanne', $this->content_full);

        // Paramétrer le Mailer pour utiliser SMTP
        if ($config['use_smtp']) {
            $this->isSMTP();

            // Spécifier le serveur SMTP
            $this->Host = $config['smtp_conf']['host'];
            $this->Port = $config['smtp_conf']['port'];
            // Accepter SSL
            if ($config['smtp_conf']['secure']) {
                $this->SMTPSecure = 'ssl';
            }
            // Activer authentication SMTP
            if ($config['smtp_conf']['user'] && $config['smtp_conf']['pass']) {
                $this->SMTPAuth = true;
                // Votre adresse email d'envoi
                $this->Username = $config['smtp_conf']['user'];
                // Le mot de passe de cette adresse email
                $this->Password = $config['smtp_conf']['pass'];
            }

            //$this->SMTPDebug = 4;
        }

        $this->SetFrom('ne-pas-repondre@clubalpinlyon.fr', 'CAF Lyon Villeurbanne');
        $this->CharSet = 'UTF-8';
        $this->AltBody = 'Pour voir ce message, utilisez un client mail supportant le format HTML (Outlook, Thunderbird, Mail...)'; // optional, comment out and test
        $this->IsHTML(true);
        $this->XMailer = 'CAF-Mailer';

        if ($useBCC) {
            $this->AddBCC('ne-pas-repondre@clubalpinlyon.fr');
        }

        //This should be the same as the domain of your From address
        //$mail->DKIM_domain = 'clubalpinlyon.fr';
        //See the DKIM_gen_keys.phps script for making a key pair -
        //here we assume you've already done that.
        //Path to your private key:
        //$mail->DKIM_private = 'dkim_private.pem';
        //Set this to your own selector
        //$mail->DKIM_selector = 'caf';
        //Put your private key's passphrase in here if it has one
        //$mail->DKIM_passphrase = '';
        //The identity you're signing as - usually your From address
        //$mail->DKIM_identity = $mail->From;
        //Suppress listing signed header fields in signature, defaults to true for debugging purpose
        //$mail->DKIM_copyHeaderFields = false;
        //Optionally you can add extra headers for signing to meet special requirements
        //$mail->DKIM_extraHeaders = ['List-Unsubscribe', 'List-Help'];
    }

    public function AddBCC($address, $name = '')
    {
        $this->recipients[$address] = $name;
    }

    public function setMailHeader($content_header)
    {
        $this->headerSet = true;
        $this->content_full = str_replace('[HEADER]', $content_header, $this->content_full);
    }

    public function setMailFooter($content_footer)
    {
        $this->footerSet = true;
        $this->content_full = str_replace('[FOOTER]', $content_footer, $this->content_full);
    }

    public function setMailBody($content_main)
    {
        $this->bodySet = true;
        $this->content_full = str_replace('[MAIN]', $content_main, $this->content_full);
    }

    public function setAltMailBody($content_alt)
    {
        $this->AltBody = $content_alt;
    }

    //Extend the send function
    public function send()
    {
        if (false == $this->headerSet) {
            $this->setMailHeader('');
        }
        if (false == $this->footerSet) {
            $this->setMailFooter('');
        }

        $this->MsgHTML($this->content_full);

        $this->Subject = $this->Subject;

        $nb_recipients = 0;
        $log_coupure_mail = false;

        if (is_array($this->recipients)) {
            foreach ($this->recipients as $address => $name) {
                /*
                * CRI 16/01/2016 - enregistrement dans les logs que si $address ou $name vide
                * Inutile de le faire pour les couples $address / $name valides
                */
                if (empty($address) || empty($name)) {
                    error_log('PHPMAILER : TO='.$address);
                } else {
                    parent::AddBCC($address, $name);
                    ++$nb_recipients;
                }

                if ($nb_recipients > 50) {
                    // trop de destinataires on coupe le mail
                    parent::Send();
                    // RAZ destinataires
                    parent::ClearAddresses();
                    $nb_recipients = 0;
                    // CRI 16/01/2016 - On signal de l'on a coupé le mail
                    if (!$log_coupure_mail) {
                        error_log('PHPMAILER : Destinataires > 50 - nb_recipients='.count($this->recipients));
                        $log_coupure_mail = true;
                    }
                }
            }
        }

        return parent::Send();
    }
}
