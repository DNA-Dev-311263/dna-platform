<?php defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|   By ABR			    					            |
\ ======================================================================== */

use Formalms\lib\Get;

class UnsubscriptionLmsController extends LmsController {

	public $name = 'unsubscription';
	
	protected $_mvc_name = 'unsubscription';
	protected $base_link;
	protected $base_link_courseassn;
	protected $base_link_elearning;
	protected $id_user;
	protected $levels;
	protected $_default_action = 'showUserSubscriptions';

	
	public function init() {
		
		YuiLib::load('base,tabview');

		require_once(_lms_.'/lib/lib.levels.php');
        
        $this->id_user = (int)Docebo::user()->getId();
		$this->levels = CourseLevel::getLevels();		//Al momento non usata. Utile se in futuro si vuole far apparire il livello di iscrizione (Tutor, Student ecc.)

		$this->base_link = 'lms/unsubscription';
		$this->base_link_courseassn = 'lms/courseassn';
		$this->base_link_elearning = 'lms/elearning';
	}
	

	/**
	 * Restituisce il link di ritorno per la vista
	 */
	protected function _getBackLink(){
		
		//Formo il link in base al chiamante
		if ( $this->_getCaller() == "el") {
			$retVal = getBackUi('index.php?r='.$this->base_link_elearning.'/show', Lang::t('_BACK', 'standard'));
		} else {
			$retVal = getBackUi('index.php?r='.$this->base_link_courseassn.'/show', Lang::t('_BACK_TO_ASSN', 'courseassn'));
		}
		
		//Out
		return $retVal;
	}
	
	
	/**
	 * Restituisce un codice per indicare la pagina chiamante
	 * return el (elearning), assn (assegnazioni)
	 */
	protected function _getCaller() {
		
		return (FormaLms\lib\Get::req('call', DOTY_STRING, '') == "el" ? "el" : "assn");
	}
	
	
	/**
	 * Invia l'email di segnalazione per cancellazione iscrizione
	 * Usata per disiscrizione diretta senza moderazione
	 * Da spostare quando le disiscrizioni saranno possibili anche altrove 
	 * (CatalogLmsController?)
	 */
	private function _sendUnsubscribeAlert($id_course, $id_edition = false, $id_date = false) {
		
		require_once(_lms_.'/lib/lib.courseassn.php');
		require_once _base_ . '/lib/lib.eventmanager.php';
		
		// Utente che ha cancellato l'iscrizione
		$id_user = (int)Docebo::user()->getId();
		
		// Istanzio model e manager
		$smodel = new SubscriptionAlms($id_course, $id_edition, $id_date);
		$courseassn_man = new CourseassnManager();
		$msg_composer = new EventMessageComposer('subscribe', 'lms');
		

		// Recupero info corso / edizione
		$course_info = $smodel->getCourseInfoForSubscription();
		
		if ($course_info) {	
			// Proprietà oggetto e-mail
			$msg_composer->setSubjectLangText('email', '_USER_UNSUBSCRIBED_SUBJECT', false);
			
			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => FormaLms\lib\Get::sett('url'),
									'[course]' => $course_info['name'],
									'[edition_code]' => $course_info['code'],
									'[date_begin]' => Format::datetimeToString($course_info['date_begin'], 'date'),
									'[date_end]' => Format::datetimeToString($course_info['date_end'], 'date')
								);
								
			// Proprietà corpo dell'e-mail
			$msg_composer->setBodyLangText('email', '_USER_UNSUBSCRIBED_HTML', $array_subst);
			

			// Destinatari
			
			// Se l'iscrizione nasce da un'assegnazione, recupero i referenti
			$cc = array();
				
			if ( $courseassn_man->checkAssnExists($id_user, $id_course, $id_edition) ) {		
				// Non uso id edizione per recuperare il manager perché dopo la disiscrizione non c'è più nella tabella assn
				$ref_info = $courseassn_man->getUserReferent($id_user, $course_info['id_course'], false); 
				$cc = array(trim($ref_info['email'][0]." ".$ref_info['email'][1]));
			}
			
			$recipients['to'] = array($id_user);
			$recipients['cc'] = $cc;
			$recipients['bcc'] = array();
					
			// Invio per ogni utente con suoi referenti
			createNewAlert(	'UserCourseSelfRemoved', 'unsubscribe', 'insert', '1', 'User unsubscribed', $recipients, $msg_composer );

		}
	}

	/**
	 * Usata dall'utente per eliminare una sua iscrizione
	 * Sostituisce quella originale self_unsubscribe
	 */
	public function selfUnsubscribe() {
		
		$id_user = (int)Docebo::user()->getId();
		$id_course = FormaLms\lib\Get::req('id_course', DOTY_INT, 0);
		$id_date = FormaLms\lib\Get::req('id_date', DOTY_INT, false);
		$id_edition = FormaLms\lib\Get::req('id_edition', DOTY_INT, false);
		
		$jump_url = "index.php?r=" . $this->base_link . "/showUserSubscriptions&call=".$this->_getCaller();
		
		$smodel = new SubscriptionAlms($id_course);
		$emodel = new UnsubscriptionLms();
		
		//Controlli
		switch ($emodel->selfUnsubsAllowed($id_course, $id_date)) {
			case 1:
				//Ammessa con moderazione, richiedo disiscrizione (la segnalazione è gestita da formalms nel modello)
				$res = $smodel->setUnsubscribeRequest($id_user, $id_course, $id_edition, $id_date);
				$param = $res ? '&result=ok_unsub_request' : '&result=no_unsub';
				break;
	
			case 2:
				//Ammessa libera, disiscrivo
				$res = $smodel->unsubscribeUser($id_user, $id_course, $id_edition, $id_date);
				$param = $res ? '&result=ok_unsub' : '&result=no_unsub';
				
				if ($res) {
					
					// Sollevo evento		
					$edition = ($id_edition ? $id_edition : $id_date);		
					Events::trigger('lms.subscription.manage', [
						'users'		 	 => [ $id_user => $smodel->getUserLevel($id_user) ],
						'id_course'      => $id_course,
						'id_edition'     => $edition,
						'action'         => _EVENT_SUBSCRIPTION_DELETE,
						'action_details' => ['modActFrom' => 'lms']
					]);
					
					//Invio segnalazione
					$this->_sendUnsubscribeAlert($id_course, $id_edition, $id_date);				
				}
				
				break;
				
			default:
				//Disiscrizione non ammessa
				$param = '&result=no_unsub';
				break;
		}
		
		Util::jump_to($jump_url . $param );
	}
	
	
	/**
	 * Recupera i dati e mostra il report con le iscrizioni
	 * dell'utente per i corsi assegnati 
	 */
	public function showUserSubscriptions() {
		
		$model = new UnsubscriptionLms();
		$id_user = (int)Docebo::user()->getId();
		$info = $model->getValidSubs($id_user);
		
		
		// Formatto le date
		foreach($info as $id_course => $row) {
			
			//Recupero byRef l'array dell'edizione e del corso
			$edition = &$info[$id_course]['edition'];
			$course = &$info[$id_course]['course'];
			
			if ($course['course_type'] == 'classroom') {
				//classroom
				
				//Formatto data disiscrizione
				$edition['unsubscribe_date_limit'] =
					Format::datetimeToString($edition['unsubscribe_date_limit'], 'date', '-', false);
				
				//Recupero i giorni di lezione byRef
				$days = &$info[$id_course]['more_info']['days'];
					
				// Unisco le informazioni sulla data in un unico item    
				foreach($days as $k => $day) {
					$days[$k] = $this->concatDayInfo(
						$day['date_begin'],
						$day['date_end'],
						$day['classroom']
					);
				}
				
			} elseif ($course['course_type'] == 'elearning' && $course['course_edition'] == 1) {
				//elearning a edizioni
				
				$course['unsubscribe_date_limit'] = Format::datetimeToString($course['unsubscribe_date_limit'], 'date', '-');
				$edition['date_begin'] = Format::datetimeToString($edition['date_begin'], 'date', "");
				$edition['date_end']   = Format::datetimeToString($edition['date_end'], 'datetime', "");
					
			} else {
				//elearning
				
				$course['unsubscribe_date_limit'] = Format::datetimeToString($course['unsubscribe_date_limit'], 'date', '-');
				$course['date_begin'] = Format::datetimeToString($course['date_begin'], 'date', "");
				$course['date_end']   = Format::datetimeToString($course['date_end'], 'date', "");
				
				if ($course['date_begin'] && $course['hour_begin'] != -1)
					$course['date_begin'] .= " ".$course['hour_begin'];
				
				if ($course['date_end'] && $course['hour_end'] != -1)
					$course['date_end'] .= " ".$course['hour_end'];                        
			}
		}

		// Messaggio di ritorno (es. ok_unsub, ok_unsub_request, ecc.)
		$result = FormaLms\lib\Get::req('result', DOTY_STRING, '');
		$result_message = '';

		if ($result) {
			switch ($result) {
				case 'ok_unsub':
					$result_message = Lang::t('_UNSUBS_SUCCESS', 'subscribe');
					break;

				case 'ok_unsub_request':
					$result_message = Lang::t('_UNSUBS_REQUEST_SUCCESS', 'subscribe');
					break;

				default:
					$result_message = Lang::t('_UNSUBS_NOT_ALLOWED', 'subscribe');
			}
		}
		


		// Preparo tutte le variabili richieste dal template Twig.
		// Includo titolo, messaggio pagina, back link, caller, icona delete, ecc.

		// Apro la view per mostrare le iscrizioni aperte dell'utente (versione Twig)
		$this->render('subscription-report', [
			'title'            	=> Lang::t('_SUBS_REPORT', 'course'),
			'back_link'        	=> $this->_getBackLink(),
			'base_link'			=> $this->base_link,
			'page_message'     	=> Lang::t('_RSUBS_PAGE_MESSAGE', 'courseassn'),
			'arr_data'         	=> $info,
			'caller'           	=> $this->_getCaller(),
			'result_message'   	=> $result_message,
			'path_delete_icon' 	=> getPathImage('fw') . 'standard/delete.png'
		]);
	}	 
	
	/**
	 * Prepara una stringa per la scrittura delle info dei giorni di lezione
	 */
    private function concatDayInfo($date_begin, $date_end, $classroom, $date_format = "date") {
		
		$val = array();
		$res = '';
		
		$val['date'] = Format::datetimeToString($date_begin, $date_format);
		$val['start'] = Format::datetimeToString($date_begin, 'time');
		$val['finish'] = Format::datetimeToString($date_end, 'time');

		$res .= $val['date'];	
		$res .= (isset($val['start']) ? ', '.$val['start'] : '');
		$res .= (isset($val['start']) && isset($val['finish']) ? ' - '.$val['finish'] : '');
		$res .= ($classroom && $classroom != ' - ' ? ', '.$classroom : '');
		
		return $res;
	}
	
}
