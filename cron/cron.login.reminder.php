<?php

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
\ ======================================================================== */

Use Formalms\lib\Get;

define("IN_FORMA", true);
define("_deeppath_", '../');
require(dirname(__FILE__).'/../base.php');

// start buffer
ob_start();

// initialize
require(_base_.'/lib/lib.bootstrap.php');
Boot::init(BOOT_DATETIME);

// not a pagewriter but something similar
$GLOBALS['operation_result'] = '';
if(!function_exists("docebo_out")) {
	function docebo_cout($string) { $GLOBALS['operation_result'] .= $string; }
}

// do something

	$reminder_status = Get::sett('login_reminder');
	$reminder_days = Get::sett('login_reminder_days');
	
	if ( $_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'] )
		docebo_cout('PEMISSION DENIED');
		
	elseif ($reminder_status != "on")
		docebo_cout('REMINDER NOT ACTIVE');
	
	elseif ($reminder_days > 0) {
		
		docebo_cout('STARTING LOGIN REMINDER CRON, ');
	
		require_once(_base_.'/lib/lib.mailer.php');
		
		$count = 0;
		$sent = false;
		$total_send = 0;
		$error_out = array();
		
		$url =  Get::sett('url');
		$from_address = Get::sett('sender_event');

		$acl_manager = Docebo::user()->getAclManager();
		$mailer = FormaMailer::getInstance();

		$dt = new DateTime('today');
		$interval = new DateInterval('P'.$reminder_days.'D');
		$interval->invert = 1;
		$date_from = $dt->add($interval)->format('Y-m-d');

		// Recupero utenti
		$internal_fields = array(
			ACL_INFO_LASTENTER => ['is_null' => true],
			ACL_INFO_REGISTER_DATE => ['comp_op' => '<=', 'filter' => $date_from]
		);
		
		$idst = $acl_manager->searchUsers($internal_fields);
		$users = $acl_manager->getUsers($idst);

		
		// Imposto record di coda
		if ( $users && method_exists($mailer,'setNewQueue') ) $mailer->setNewQueue('ReminderLogin');
		
		// Ciclo sugli utenti
		foreach ($users as $id_user => $u_info) {

			// Preparo numero giorni dalla registrazione
			$dt = new DateTime('today');
			$reg_date = (new DateTime( $u_info[ACL_INFO_REGISTER_DATE] ))->settime(0,0);
			$days = $dt->diff($reg_date)->days;
			
							
			// Contatore mail da inviare
			$total_send += 1;
			

			// Reupero mai destinatario
			$to_address = $u_info[ACL_INFO_EMAIL];
	
			// Testi mail
			$subject = Lang::t('_LOGIN_REMINDER_SUBJECT', 'email');
			$body_model = Lang::t('_LOGIN_REMINDER_HTML', 'email');				
			
			
			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => $url,
									'[$reminder_days]'	=> $reminder_days,
									'[days]'			=> $days,
									'[firstname]'		=> $u_info[ACL_INFO_FIRSTNAME],
									'[lastname]'		=> $u_info[ACL_INFO_LASTNAME],
									'[register_date]'	=> $u_info[ACL_INFO_REGISTER_DATE]
								);
			
			
				
			// Sostituisco i tag segnaposto				
			$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
				
			
			// Invio
			$sent = $mailer->SendMail($from_address, [$to_address], $subject, $body, [], 
										array(MAIL_REPLYTO => $from_address));
						               
  							
			if($sent) 
				// Contatore mail inviate
				$count +=1;
			else
				// Recupero errore
				$error_out[$id_user] = $id_user.';'.$mailer->ErrorInfo;		

		}


		// Scrivo evenutali errori
		if ($error_out) {
			$filename = dirname(__FILE__).'log/reminder_login_error_'.date('Y-m-d').'.txt';
			file_put_contents($filename, implode(PHP_EOL, $error_out) , FILE_APPEND | LOCK_EX);
		}
		
		// Out
		docebo_cout('SENT EMAILS: '.$count.'/'.$total_send.'.');
	}

// finalize
Boot::finalize();

// remove all the echo
ob_clean();

// Print out the page
echo $GLOBALS['operation_result'];

// flush buffer
ob_end_flush();
