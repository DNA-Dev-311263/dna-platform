<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 *
 * from docebo 4.0.5 CE 2008-2012 (c) docebo
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

defined('IN_FORMA') or exit('Direct access is forbidden.');

//require_once(_base_.'/addons/phpmailer/language/phpmailer.lang-en.php'); // not need for phpmailer 5.2.

//property name: multisending mode
define('MAIL_MULTIMODE', 'multimode');
//multisending properties
define('MAIL_SINGLE', 'single');
define('MAIL_CC', 'cc');
define('MAIL_BCC', 'bcc');

define('MAIL_RECIPIENTSCC', 'recipientscc');
define('MAIL_RECIPIENTSBCC', 'recipientsbcc');

define('MAIL_WORDWRAP', 'wordwrap');
define('MAIL_CHARSET', 'charset');
define('MAIL_HTML', 'is_html');

//property name: use or not acl names (taken from DB, slower if used)
define('MAIL_SENDER_ACLNAME', 'use_sender_aclname');
define('MAIL_RECIPIENT_ACLNAME', 'use_recipient_aclname');
define('MAIL_REPLYTO_ACLNAME', 'use_replyto_aclname');

//property name: reply to parameters
define('MAIL_REPLYTO', 'replyto');

define('MAIL_HEADERS', 'headers');

//specify if class properties should be reset after sending
define('MAIL_RESET', 'reset');

//ABR: chiave per specificare il modo in cui devono essere trattati gli allegati ('path' (default), 'path_no_del', 'file_serialize')
define("MAIL_ATTACH_MODE", "attach_mode"); 

//ABR: chiave per specificare informazioni aggiuntive per l'inserimento in coda
define("MAIL_QUEUE_INFO", "queue_info");
define("MAIL_TO", "to");

class FormaMailer extends PHPMailer\PHPMailer\PHPMailer
{
    /** @var FormaMailer */
    private static $instance = null;

    private DoceboACLManager $aclManager;

    //default config for phpmailer, to set any time we send a mail, except for user-defined params
    private array $config;

    private string $mailTemplate = 'mail.html.twig';
    
	protected bool $queue_on = false; 	//ABR
	protected int $id_queue = 0;		//ABR

    //the constructor
    public function __construct()
    {
        $this->aclManager = new DoceboACLManager();

        $this->config = [
            MAIL_MULTIMODE => MAIL_SINGLE,
            MAIL_SENDER_ACLNAME => FormaLms\lib\Get::sett('use_sender_aclname', false),
            MAIL_RECIPIENTSCC => FormaLms\lib\Get::sett('send_cc_for_system_emails', ''),
            MAIL_RECIPIENTSBCC => FormaLms\lib\Get::sett('send_ccn_for_system_emails', ''),
            MAIL_RECIPIENT_ACLNAME => false,
            MAIL_REPLYTO_ACLNAME => false,
            MAIL_HTML => true,
            MAIL_WORDWRAP => 0,
            MAIL_CHARSET => 'Utf-8',
            MAIL_ATTACH_MODE => false //ABR
        ];
        //set initial default value
        $this->ResetToDefault();
        $this->addDefaultMailPaths();
        
		//ABR: per visualizzare errore sulla pagina
		$this->exceptions = false;
		
		//ABR: Recupero se la coda è attiva
		$this->queue_on = ( FormaLms\lib\Get::sett('mail_queue') == 'on' );
		
        parent::__construct();
    }
    
    
    /** ABR **
     * Restituisce se la coda email è attiva
     */
	public function isQueueOn() {
		return $this->queue_on;
	}
	
	
    /** ABR **
     * Imposta le info di una nuova coda per la gestione delle e-mail
     */
	public function setNewQueue($code) {
		
		require_once(_adm_.'/lib/lib.queue.php');
		
		if ($this->queue_on) {
			// Istanzio manager
			$queue_man = new QueueManager();
			
			// Imposto il record di coda
			$this->id_queue = $queue_man->insQueue('email', $code);
		}
		
		return $this->id_queue;
	}


    /**
     * @return FormaMailer|mixed
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new FormaMailer();
        }

        return self::$instance;
    }

    public function setMailTemplate(string $mailTemplate): void
    {
        $this->mailTemplate = $mailTemplate;
    }

    private function addDefaultMailPaths()
    {
        $defaultPaths = [
            _adm_ . '/views/mail',
            _lms_ . '/views/mail',
            _lms_ . '/admin/views/mail',
            _templates_ . '/' . getTemplate() . '/layout/mail',
        ];

        if (getTemplate() !== 'standard') {
            $defaultPaths[] = _templates_ . '/standard/layout/mail';
        }

        foreach ($defaultPaths as $path) {
            if (file_exists($path)) {
                FormaLms\appCore\Template\TwigManager::getInstance()->addPathInLoader($path);
            }
        }
    }

    //convert html into plain txt in utf-8 avoiding the bug
    private function ConvertToPlain_UTF8(&$html)
    {
        $allowedProtocols = ['http', 'https', 'ftp', 'mailto', 'color', 'background-color'];

        $config = HTMLPurifier_Config::createDefault();
        $allowed_elements = [];
        $allowed_attributes = [];

        $config->set('HTML.AllowedElements', $allowed_elements);
        $config->set('HTML.AllowedAttributes', $allowed_attributes);
        if ($allowedProtocols !== null) {
            $config->set('URI.AllowedSchemes', $allowedProtocols);
        }
        $purifier = new HTMLPurifier($config);
        $res = $purifier->purify($html);

        $res = str_replace('&amp;', '&', $res);

        return $res;
    }

    //restore default configuration after sending mail
    public function ResetToDefault()
    {
        $this->From = '';
        $this->FromName = '';
        $this->CharSet = $this->config[MAIL_CHARSET];
        $this->WordWrap = $this->config[MAIL_WORDWRAP];
        $this->IsHTML($this->config[MAIL_HTML]);
        $this->Subject = '';
        $this->Body = '';
        $this->AltBody = '';
        $this->msgHTML('');

        $this->ClearAddresses();
        $this->ClearCCs();
        $this->ClearBCCs();
        $this->ClearReplyTos();
        $this->ClearAllRecipients();
        $this->ClearAttachments();
        $this->ClearCustomHeaders();
    }
    
    
    /** ABR **
     * Restituisce gli allegati della mail creandoli da un array serializzato
     * L'array deve avere due chiavi, 'name' e 'data'
     */
    function getTextAttach($serialized_attach) {

			$res = array();
			$arr_attach	= unserialize($serialized_attach);
			
			// Ciclo gestione dati in allegato
			foreach ($arr_attach as $atc) {
				
				// Recupero il nome che avrà l'allegato (funge da chiave)
				$k = $atc['name'];
							
				// Genero il file temp 
				// l'handle del file è passato all'array di output. Finché c'è un riferimento ad esso, il file non viene eliminato.
				$temp = tmpfile();
				
				// Recupero il path
				$metaData = stream_get_meta_data($temp);
				$path = $metaData['uri'];
				
				// Scrivo il contenuto nel file temporaneo
				fwrite($temp, $atc['data']);
								
				// Aggiungo path e handle del file all'array di output
				$res[$k]['path'] = $path;
				$res[$k]['handle'] = $temp;
				$res[$k]['name'] = $k;
			}
			
		// Out
		return $res;
	}
	
	
    /** ABR **
     * Controlla se la stringa è un array serializzato
     */
	protected function isArraySerialized($str) {
		$res = false;
		
		if (is_string($str)) {
			$data = unserialize($str);
			
			if (is_array($data))
				$res = true;
		}
		
		return $res;
	}
	
	
	/** ABR **
	 * Inserisce la mail in coda
	 */
	protected function insQueueMail($sender, $recipients, $subject, $body, $attachments=false, $params=false) {
		
		require_once(_adm_.'/lib/lib.queue.php'); 
		
		$res = false;
		$queue_man	= new QueueManager();
		
		// Default per inserimento email
		$recip[MAIL_TO] = array();
		$recip[MAIL_CC] = array();
		$recip[MAIL_BCC] = array();

		// Recipients deve essere un array
		$recipients = is_array($recipients) ? $recipients : array($recipients);
		
		// Definisco modalità multi-invio
		if (!isset($params[MAIL_MULTIMODE])) {
			$multi_mode = MAIL_TO;
		} else {
			$multi_mode = $params[MAIL_MULTIMODE] == MAIL_SINGLE ? MAIL_TO : $params[MAIL_MULTIMODE];
			
			unset($params[MAIL_MULTIMODE]);
		}

		// Recupero gli indirizzi passati in array e li inseriso nel tipo destinatario specificato in MULTIMODE
		$recip[$multi_mode] = $recipients;

		// Recupero destinatari passati nei params
		if (isset($params[MAIL_RECIPIENTSCC])) {
			$arr_cc = explode(" ", $params[MAIL_RECIPIENTSCC]);
			$recip[MAIL_CC] = array_merge($recip[MAIL_CC], $arr_cc);
			
			unset($params[MAIL_RECIPIENTSCC]);
		}
		if (isset($params[MAIL_RECIPIENTSBCC])) {
			$arr_bcc = explode(" ", $params[MAIL_RECIPIENTSBCC]);
			$recip[MAIL_BCC] = array_merge($recip[MAIL_BCC], $arr_bcc);
			
			unset($params[MAIL_RECIPIENTSBCC]);
		}
		
		//Passo l'id della coda se già creato
		if ($this->id_queue)
			$params[MAIL_QUEUE_INFO]['id_queue'] = $this->id_queue;
		
		
		// Inserisco email nella queue
		$res = $queue_man->insMail($sender, $recip, $subject, $body, $attachments, $params);	

		// Out
		return $res;
	}
	
	
	/** ABR **
	 * Invia mail o inserisce le mail in coda se il modulo è stato attivato nelle configurazioni di sistema
	 */
	function SendMail( $sender, array $recipients, string $subject, string $body, $attachments = false, array $params = [] ) {
		
		$res = false;
		$cc_system = Formalms\lib\Get::sett('send_cc_for_system_emails', '');
		
		
		// Aggiungo copia conoscenza destinatari piattaforma
		if($cc_system !== ''){
			if ( !isset($params[MAIL_RECIPIENTSCC]) )
				$params[MAIL_RECIPIENTSCC] = $cc_system;
			else
				$params[MAIL_RECIPIENTSCC] = $params[MAIL_RECIPIENTSCC]." ".trim($cc_system);
		}
		
		
		if (!$this->queue_on) {
			// * Coda non attiva, invio direttamente
			
			$res = $this->SendFormaMail($sender, $recipients, $subject, $body, $attachments, $params);
		
		} else {
			// * Coda attiva, inserisco in tabella coda
		
			$res = $this->insQueueMail($sender, $recipients, $subject, $body, $attachments, $params);
		}
		
		return $res;
	}


    /**
     * @return array|false
     *
     * @throws \PHPMailer\PHPMailer\Exception
     * Mod. ABR sendmail original function
     */
    public function SendFormaMail(string $sender, array $recipients, string $subject, string $body, $attachments = false, array $params = [])
    {
        $output = [];
        if (FormaLms\lib\Get::cfg('demo_mode')) {
            $this->ResetToDefault();

            return false;
        }

        $params = array_merge($this->config, $params);
		$del_attach = false; //ABR
		

		//ABR: Recupero eventuali allegati serializzati
		if ($this->isArraySerialized($attachments)) {
		
			if ($params[MAIL_ATTACH_MODE] == 'file_serialized') {
				// File di testo serializzati
				// genero il file e recupero array di path
				$atc_uns = $this->getTextAttach($attachments);
				$attachments = array_column($atc_uns, 'path', 'name');
			} else {
				// Array di path serializzato, recupero array
				$attachments = unserialize($attachments);
				
				// Valuto variabile di eliminazione allegati
				if ($params[MAIL_ATTACH_MODE] != 'path_no_del') $del_attach = true;
			}
		}


        //check each time because global configuration may have changed since last call

        if (SmtpAdm::getInstance()->isUseSmtp()) {
            $this->IsSMTP();
            $this->Hostname = SmtpAdm::getInstance()->getHost();
            $this->Host = SmtpAdm::getInstance()->getHost();
            if (!empty(SmtpAdm::getInstance()->getPort())) {
                $this->Port = SmtpAdm::getInstance()->getPort();
            }
            $smtp_user = SmtpAdm::getInstance()->getUser();
            if (!empty($smtp_user)) {
                $this->Username = $smtp_user;
                $this->Password = SmtpAdm::getInstance()->getPwd();
                $this->SMTPAuth = true;
            } else {
                $this->SMTPAuth = false;
            }
            $this->SMTPSecure = SmtpAdm::getInstance()->getSecure();    // secure: '' , 'ssl', 'tsl'
            $this->SMTPAutoTLS = SmtpAdm::getInstance()->isAutoTls();
            $this->SMTPDebug = SmtpAdm::getInstance()->getDebug();    // debug level 0,1,2,3,...
            
			//ABR: 	Aggiunte Custom connection options
			$this->SMTPOptions = array ( 'ssl' => array ('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true) );
            
        } else {
            $this->IsMail();
        }
        


        //configure sending address
        //----------------------------------------------------------------------------
        $this->From = $sender;
        if ($params[MAIL_SENDER_ACLNAME]) {
            $temp = $this->aclManager->getUserByEmail($sender);
            $this->FromName = $params[MAIL_SENDER_ACLNAME] !== true ? $params[MAIL_SENDER_ACLNAME] : $temp[ACL_INFO_FIRSTNAME] . ' ' . $temp[ACL_INFO_LASTNAME];
        }
        //----------------------------------------------------------------------------

        //configure attachments
        //----------------------------------------------------------------------------
        
        //Mod. ABR
 		if (is_array($attachments)) {
			
			foreach($attachments as $key => $path) {
				
 				//ABR: Se la chiave non è numerica, la uso come nome del file
				$file_name = (!is_numeric($key) ? $key : '');
				
				$this->addAttachment($path, $file_name);
			}
		} 

        //----------------------------------------------------------------------------

        //configure replyto(s)
        //----------------------------------------------------------------------------
        $replyTo = [];
        if (isset($params[MAIL_REPLYTO])) {
            //retrieve replyto(s) from params
            if (is_string($params[MAIL_REPLYTO])) {
                $replyTo[] = $params[MAIL_REPLYTO];
            } elseif (is_array($params[MAIL_REPLYTO])) {
                foreach ($params[MAIL_REPLYTO] as $value) {
                    $replyTo[] = $value;
                }
            }
        }
        foreach ($replyTo as $value) {
            if ($params[MAIL_REPLYTO_ACLNAME]) {
                $temp = $this->aclManager->getUserByEmail($value);
                $this->AddReplyTo($value, $temp[ACL_INFO_FIRSTNAME] . ' ' . $temp[ACL_INFO_LASTNAME]);
            } else {
                $this->AddReplyTo($value);
            }
        }
        //----------------------------------------------------------------------------

        if (isset($params[MAIL_CHARSET])) {
            $this->CharSet = $params[MAIL_CHARSET];
        }

        if (isset($params[MAIL_WORDWRAP])) {
            $this->WordWrap = $params[MAIL_WORDWRAP];
        }

        if (isset($params[MAIL_HTML])) {
            $this->IsHTML($params[MAIL_HTML]);
        }

        $this->Subject = $subject;
        if (isset($params[MAIL_HTML])) {
            $eventResponse = Events::trigger('core.mail.template.rendering',
                [
                    'layout' => $this->mailTemplate,
                    'layoutPath' => '',
                    'subject' => $subject,
                    'body' => $body,
                    'otherParams' => [],
                ]
            );

            try {
                if (!empty($eventResponse['path'])) {
                    FormaLms\appCore\Template\TwigManager::getInstance()->addPathInLoader($eventResponse['layoutPath']);
                }

                $html = FormaLms\appCore\Template\TwigManager::getInstance()->render($eventResponse['layout'],
                    [
                        'subject' => $eventResponse['subject'],
                        'body' => $eventResponse['body'],
                        'otherParams' => $eventResponse['otherParams'],
                    ]
                );
            } catch (\Exception $exception) {
                $html = $body;
            }

            $eventResponse = Events::trigger('core.mail.template.rendered',
                [
                    'html' => $html,
                    'subject' => $subject,
                    'body' => $body,
                    'otherParams' => $eventResponse['otherParams'],
                ]
            );

            $this->msgHTML($eventResponse['html']);
        } else {
            $this->Body = $body;
            $this->AltBody = $this->ConvertToPlain_UTF8($body);
        }

        // MAIL_RECIPIENTSCC
        if (isset($params[MAIL_RECIPIENTSCC])) {
            $arr_mail_recipientscc = explode(' ', $params[MAIL_RECIPIENTSCC]);
            foreach ($arr_mail_recipientscc as $user_mail_recipientscc) {
                try {
                    $this->addCC($user_mail_recipientscc);
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                }
            }
        }

        // MAIL_RECIPIENTSBCC
        if (isset($params[MAIL_RECIPIENTSBCC])) {
            $arr_mail_recipientsbcc = explode(' ', $params[MAIL_RECIPIENTSBCC]);
            foreach ($arr_mail_recipientsbcc as $user_mail_recipientsbcc) {
                try {
                    $this->addBCC($user_mail_recipientsbcc);
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                }
            }
        }


        // ABR: spostata in sendMail per conteggi limiti in coda
        //if (FormaLms\lib\Get::sett('send_cc_for_system_emails', '') !== '') {
            //$arr_cc_for_system_emails = $this->getEmailListFromString(FormaLms\lib\Get::sett('send_cc_for_system_emails'));
            //foreach ($arr_cc_for_system_emails as $user_cc_for_system_emails) {
                //try {
                    //$this->addCC($user_cc_for_system_emails);
                //} catch (\PHPMailer\PHPMailer\Exception $e) {
                //}
            //}
        //}

        if (FormaLms\lib\Get::sett('send_ccn_for_system_emails', '') !== '') {
            $arr_ccn_for_system_emails = $this->getEmailListFromString(FormaLms\lib\Get::sett('send_ccn_for_system_emails'));
            foreach ($arr_ccn_for_system_emails as $user_ccn_for_system_emails) {
                try {
                    $this->addBCC($user_ccn_for_system_emails);
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                }
            }
        }
        //----------------------------------------------------------------------------

        foreach ($recipients as $recipient) {
            if ($params[MAIL_RECIPIENT_ACLNAME]) {
                $temp = $this->aclManager->getUserByEmail($recipient);
                $name = $temp[ACL_INFO_FIRSTNAME] . ' ' . $temp[ACL_INFO_LASTNAME];
            } else {
                $name = $recipient;
            }

            try {
                switch ($params[MAIL_MULTIMODE]) {
                    case MAIL_CC     :
                        $this->AddCC($recipient, $name);
                        break;
                    case MAIL_BCC    :
                        $this->AddBCC($recipient, $name);
                        break;
                    case MAIL_SINGLE :
                    default:
                        $this->addAddress($recipient, $name);
                        break;
                }
            } catch (\PHPMailer\PHPMailer\Exception $e) {
            }

			// Invio
            $sent = $this->send();
            

            Events::trigger('core.mail.sent',
                [
                    'sender' => $sender,
                    'recipient' => $recipient,
                    'sent' => $sent,
                ]
            );
            $output[$recipient] = $sent;
            $this->ClearAddresses();
        }
        
		// ABR: Rimuovo gli allegati se necessario
		if ($del_attach) {
			
			foreach ($attachments as $filename) 
				if(file_exists($filename)) unlink($filename);
		}


        //reset the class
        $this->ResetToDefault();

        return $output;
    }

    private function getEmailListFromString($emails)
    {
        $delimiters = [' ', ',', '|'];

        $emails = str_replace($delimiters, $delimiters[0], $emails); // 'foo. bar. baz.'

        $emailsArray = explode($delimiters[0], $emails);
        if (is_array($emailsArray) && count($emailsArray) > 1) {
            return $emailsArray;
        }

        return $emailsArray ?: [];
    }
}
