<?php defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt      		| 
|	By ABR     																|
\ ======================================================================== */

use Formalms\lib\Get;


class CommcourseAlmsController extends AlmsController {
	
	public $name = 'commcourse';
	
	protected $data;
	protected $json;
	
	protected $model;
	protected $user_level;
	protected $id_user;
	protected $id_org_user;
	protected $permissions;
	protected $base_link_commcourse;
	
	
	public function init() {
		
		require_once(_base_.'/lib/lib.mailer.php');
		
		$this->json = new Services_JSON();
		$this->model = new CommcourseAlms();
		
		$this->id_user = Docebo::user()->getIdSt();
		$this->user_level = Docebo::user()->getUserLevelId();
		
		$org_us = $this->model->getOrgInfoByUser($this->id_user);
		$this->id_org_user = (int)$org_us['idOrg_parent'];
		
		//Recupero i permessi dell'utente
		$this->permissions = array('view' => checkPerm('view', true, 'commcourse', 'lms'));	
		
		//Link base del controller
		$this->base_link_commcourse = 'alms/commcourse';
	}

	
	public function show() {
		
		$model = $this->model;
		$id_org =  $this->id_org_user;
		$is_godadmin = $this->_isUserGodAdmin();
		
		$sel_comm = Get::req('sel_comm', DOTY_STRING, 'NoticeAssn');
		
		//Inserisco librerie
		Util::get_js(Get::rel_path('base').'/lib/js_utils.js', true, true);
		Util::get_js(Get::rel_path('lms').'/admin/views/commcourse/commcourse.js', true, true);
		
		if ($is_godadmin) {
			//Se è godadmin, recupero la prima organizzazione di primo livello come organizzazione di partenza
			$org = $model->getOrgInfoByLevel();
				
			$id_org = (count($org) > 0 ? $org[0]['idOrg'] : 0);
		}
		
		//Invio i parametri di preparazione della view
		$this->render('show', array(
			'model' 		=> $model,
			'id_org' 		=> $id_org,
			'is_godadmin' 	=> $is_godadmin,
			'permissions' 	=> $this->permissions,
			'sel_comm'		=> $sel_comm,
			'list' 			=> $model->getList($is_godadmin),
			'tableInfo' 	=> $model->getTableInfo($sel_comm),
			'base_link_commcourse' => $this->base_link_commcourse
		));

	}
	

	public function getCourseJson() {		
		//>> Restituisce l'elenco dei corsi in formato Json. Usata per chiamate Ajax
		
		$result = array();

		// Controllo permessi
		if (!$this->permissions['view']) exit;

		// Recupero i criteri di selezione
		$post 	= $this->getPostData();
		$id_org = (int)$post->id_org;

	
		// Controllo se l'utente può visualizzare l'azienda passata in post (-1 è un valore inesistente)
		if(!$this->_isUserGodAdmin() && $id_org != $this->id_org_user) 	$id_org = -1;

		
		// Chiamo il metodo di estrazione
		$result = $this->model->getCourseSummary($id_org);
	
		
		// Trasformo i dati in json
		$this->data = $this->json->encode($result);
		
		// Preparo oggetto per dataTables
		$this->data = '{"data":'.$this->data.'}';

		
		// Out
		echo $this->data;
		
	}
	
	
	public function getParamJson() {
		//>> Esegue recupero informazioni su parametri delle comunicazioni. Usata per chiamate Ajax
	
		$code = Get::req('sel_comm', DOTY_STRING, "");
		$info = $this->model->getTableInfo($code);
		
		$params = new stdClass;
		$params->defaults = $info->defaults;
		$params->tabExists = (bool)$info->columns;

	
		// Out
		echo $this->json->encode($params);
		
	}
	
	
	private function _sendCommNoticeAssn($course_list, $id_org, $date_from, &$mailer) {
		//>> Invia le e-mail con le notifica di una nuova assegnazione
		
		$model = &$this->model;
		$count = 0;
		
		// Indirizzo from e url sito
		$from_address = Get::sett('sender_event');
		$url =  Get::sett('url');
		
		// Testi mail
		$subject_model = Lang::t('_NOTICE_ASSN_SUBJECT', 'email');
		$body_model = Lang::t('_NOTICE_ASSN_HTML', 'email');
		
		
		// Recupero le comunicazioni
		$comms_info = $model->getAssnActive($course_list, $id_org, $date_from);
		
		
		foreach($comms_info as $info) {

			// Mail utente
			$to_address = $info['email'];
			
			// Mail responsabile
			$cc_address = $model->getUserInfo($info['id_manager'], ACL_INFO_EMAIL);
			
			if($model->getUserInfo($info['id_manager'], ACL_INFO_VALID ) != '1') $cc_address = false;
			
			
			// Sostituisco il tag corso nell'oggetto
			$subject = str_replace('[course]', $info['course_name'], $subject_model);


			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => $url,
									'[course]' => $info['course_name'],
									'[code]' => $info['course_code'],
									'[firstname]' => $info['firstname'],
									'[lastname]' => $info['lastname']
								);
			
			// Sostituisco i tag					
			$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
			
			
			// Invio
			$mailer->SendMail($from_address, [$to_address], $subject, $body, false, 
								array(MAIL_REPLYTO => $from_address, MAIL_RECIPIENTSCC => $cc_address));
			
					
			// Contatore
			$count +=1;		
		}
		
		return  $count;	
	}
	
	
	private function _sendCommNewEdition($course_list, $id_org, &$mailer) {
		//>> Invia le e-mail con le comunicazioni relative alle nuove edizioni
		
		$count = 0;
		
		// Indirizzo from e url sito
		$from_address = Get::sett('sender_event');
		$url =  Get::sett('url');

		// Testi mail
		$subject_model = Lang::t('_NEW_EDITION_SUBJECT', 'email');
		$body_model = Lang::t('_NEW_EDITION_HTML', 'email');
		
		
		// Recupero le comunicazioni
		$comms_info = $this->model->getAssnForNewEdition($course_list, $id_org, true);
			
		
		foreach($comms_info as $info) {
			
			// Sostituisco tag nell'oggetto
			$subject = str_replace('[course]', $info['course_name'], $subject_model);
					
			// Sede		
			$location = '';
			
			if (!$info['course_virtual']) {
				$location = Lang::t('_TAG_LOCATION', 'commcourse');
				$location = str_replace('[location]', $info['location'], $location);
			}
					
			foreach ($info['users'] as $user) {
				
				// Mail utente
				$to_address = $user['email'];
				
							
				// Preparo array per sostituzione tag della comunicazione
				$array_subst = array(	'[url]' => $url,
										'[course]' => $info['course_name'],
										'[code]' => $info['course_code'],
										'[location]' => $location,
										'[firstname]' => $user['firstname'],
										'[lastname]' => $user['lastname']
									);
				
				// Sostituisco i tag					
				$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
				
				
				// Invio
				$mailer->SendMail($from_address, [$to_address], $subject, $body, false, 
									array(MAIL_REPLYTO => $from_address));
							
						
				// Contatore
				$count +=1;
			}

		}
		
		return  $count;
	}
	
	
	private function _sendCommReminderSubs($course_list, $id_org, $mailer) {
		//>> Invia le e-mail con il promemoria di iscrizione
		
		$mailer = DoceboMailer::getInstance();
		$model = &$this->model;
		$params_queue = array();
		$count = 0;
			
		// Indirizzo from e url sito
		$from_address = Get::sett('sender_event');
		$url =  Get::sett('url');
		
		// Parametri coda
		if ($id_queue)
			$params_queue = array('id_queue' => $id_queue); 
		
		// Testi mail
		$subject_model = Lang::t('_REMINDER_SUBS_SUBJECT', 'email');
		$body_model = Lang::t('_REMINDER_SUBS_HTML', 'email');
		
		
		// Recupero le comunicazioni
		$comms_info = $model->getAssnForNewEdition($course_list, $id_org, false);
		
		
		foreach($comms_info as $info) {
			
			// Sostituisco tag nell'oggetto
			$subject = str_replace('[course]', $info['course_name'], $subject_model);
					
					
			foreach ($info['users'] as $user) {
				
				// Mail utente
				$to_address = $user['email'];
				
				// Mail responsabile
				$cc_address = $model->getUserInfo($user['id_manager'], ACL_INFO_EMAIL);
				
				if($model->getUserInfo($user['id_manager'], ACL_INFO_VALID ) != '1') $cc_address = false;
			
									
				// Preparo array per sostituzione tag della comunicazione
				$array_subst = array(	'[url]' => $url,
										'[course]' => $info['course_name'],
										'[code]' => $info['course_code'],
										'[firstname]' => $user['firstname'],
										'[lastname]' => $user['lastname']
									);
				
				// Sostituisco i tag					
				$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
				

				// Invio
				$mailer->SendMail($from_address, [$to_address], $subject, $body, false, 
									array(MAIL_REPLYTO => $from_address, MAIL_RECIPIENTSCC => $cc_address, MAIL_QUEUE_INFO => $params_queue));
							
						
				// Contatore
				$count +=1;
			}

		}
		
		return  $count;
	}
	
	
	private function _sendCommReminderAssn($id_org, $date_from, &$mailer) {
		//>> Invia le e-mail i promemoria per la scelta dei corsi da gap
		
		$model = &$this->model;
		$count = 0;
		
		// Indirizzo from e url sito
		$from_address = Get::sett('sender_event');
		$url =  Get::sett('url');
	
		// Testi mail
		$subject_model = Lang::t('_REMINDER_GAP_ASSN_SUBJECT', 'email');
		$body_model = Lang::t('_REMINDER_GAP_ASSN_HTML', 'email');
		
		
		// Recupero le comunicazioni
		$comms_info = $model->getGapUndefined($id_org, $date_from);
		
		
		foreach($comms_info as $info) {
			
			// Sostituisco tag nell'oggetto
			$subject = str_replace('[catalogue]', $info['cata_name'], $subject_model);
					
			// Mail utente
			$to_address = $info['user_email'];
			
			// Mail responsabile
			$cc_address = $model->getUserInfo($info['manager_userid'], ACL_INFO_EMAIL);
			
			if($model->getUserInfo($info['manager_userid'], ACL_INFO_VALID ) != '1') $cc_address = false;
		
								
			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => $url,
									'[catalogue]' => $info['cata_name'],
									'[firstname]' => $info['user_firstname'],
									'[lastname]' => $info['user_lastname']
								);
			
			// Sostituisco i tag					
			$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
			

			// Invio
			$mailer->SendMail($from_address, [$to_address], $subject, $body, false, 
								array(MAIL_REPLYTO => $from_address, MAIL_RECIPIENTSCC => $cc_address));
						
					
			// Contatore
			$count +=1;
		

		}
		
		return  $count;
	}
	
	
	protected function getLangCode($code_comm) {
		//>> Restituisce il codice per la traduzione della comunicazione
		
		$retVal = "";
		$array = str_split($code_comm);
		
		foreach ($array as $char) {	
			$retVal .= ctype_upper($char) ? '_'.$char : $char;
		}
			
		return strtoupper($retVal);
	}
	
	
	public function infoCommunication() {
		//>> Recupera le informazioni sulle comunicazioni. Chiamata via Ajax
		
		$model = $this->model;
		$post = $this->getPostData();
		$op_title = Lang::t( $this->getLangCode($post->operation) , 'commcourse');
		
		$result = array();
		$comm_cnt = 0; 			//conteggio comunicazioni 
		$rel_cnt = 0;  			//conteggio corsi o cataloghi
		

		if ($post->operation == 'ReminderGapAssn') {
				//Solleciti assegnazioni da gap
				
				$comms_info = $model->getGapUndefined($post->id_org, $post->date_from);
		
				//Conteggi
				$rel_cnt = count(array_unique(array_column($comms_info, 'cata_name')));
				$comm_cnt = count($comms_info);
			
		} elseif ($post->operation == 'NoticeAssn') {
				//Info nuove assegnazioni
							
				$comms_info = $model->getAssnActive($post->course_list, $post->id_org, $post->date_from);
				
				//Conteggi
				$rel_cnt = count(array_unique(array_column($comms_info, 'course_code')));
				$comm_cnt = count($comms_info);
			
		} else {	
				//Altre comunicazioni (solleciti iscrizioni, nuove edizioni)
				
				$comms_info = $model->getAssnForNewEdition($post->course_list, $post->id_org);
				
				foreach ($comms_info as $info) {
					$comm_cnt += count($info['users']);
					$rel_cnt += 1;
				}
		}
		
		// Preparo array di ritorno
		$result = array('operation' => $op_title, 'related_count' => $rel_cnt, 'communication_count' => $comm_cnt); 	
			
		// Trasformo i dati in json
		$this->data = $this->json->encode($result);
		
		// Out
		echo $this->data;
	}
	
	
	public function sendCommunication() {
		//>> Avvia le comunicazioni. Chiamata via Ajax
		
		$mailer = FormaMailer::getInstance();
		$post = $this->getPostData();
		$res = 0;
		$id_queue = false;
		
		// Impsto la coda, se è attiva
		$mailer->setNewQueue('Comm'. $post->operation);
	
		
		switch ($post->operation)
		{
			case 'NewEdition':
				$res = $this->_sendCommNewEdition($post->course_list, $post->id_org, $mailer);
				break;
				
			case 'ReminderSubs':
				$res = $this->_sendCommReminderSubs($post->course_list, $post->id_org, $mailer);
				break;
				
			case 'NoticeAssn':
				$res = $this->_sendCommNoticeAssn($post->course_list, $post->id_org, $post->date_from, $mailer);
				break;
				
			case 'ReminderGapAssn':
				$res = $this->_sendCommReminderAssn($post->id_org, $post->date_from, $mailer);
				break;		
		}
		
		
		$output = array('success' => (bool)$res, 'count' => $res);
		
		//Out
		echo $this->json->encode($output);	
	}
	
	
	public function getPostData() {
		//>> Restituisce i parametri di chiamata della pagina
				
		$objReq = New StdClass();
		
		$objReq->id_org 		= Get::req('id_org', DOTY_INT, false);
		$objReq->date_from		= Get::req('date_from', DOTY_STRING, "");
		$objReq->course_list	= Get::req('course_list', DOTY_STRING, "");
		$objReq->operation		= Get::req('operation', DOTY_STRING, "");
		$objReq->q_string 		= $_SERVER['QUERY_STRING'];
		$objReq->method 		= $_SERVER['REQUEST_METHOD']; 
		
		return $objReq;
	}
	
	
	private function _isUserGodAdmin(){
		//>> Restituisce true se l'utente corrente è un super amministratore
	
		$res = (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN);
		return $res;
	}

}
