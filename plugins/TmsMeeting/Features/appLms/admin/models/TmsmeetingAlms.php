<?php 
defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|   By ABR                                                                  |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
\ ======================================================================== */

use FormaLms\lib\Get;

class TmsmeetingAlms extends Model {
	
	const LMS_PATH = _plugins_.'/TmsMeeting/Features/appLms/';
	protected $acl_man;
	protected $meeting_man;
	protected $date_man;
	protected $future_period;
	
	
	public function __construct() {
		
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.fund.php');
		require_once( self::LMS_PATH.'/lib/lib.meeting.php');
		
		$this->meeting_man = new TmsMeetingManager();
		$this->date_man = new DateManager();
		$this->acl_man =& Docebo::user()->getAclManager();
		
		$this->future_period = [ date('Y-m-d'), (new DateTime())->modify('+99 years')->format('Y-m-d') ]; //oggi e data futura
	}
	 
	 
	/**
	 * Recupera gli utenti iscritti all'edizione
	 * Funzione Cache utile per utilizzo informazioni nei cicli o in chiamate multiple
	 */
	private function _getDateUsers(int $id_date, bool $only_valid = true) {
		
		static $dateUsers = [];
		static $dateValidUsers = [];

		if ( empty($dateUsers[$id_date]) ) {
			
			$users = $this->meeting_man->getSubscribedUsers($id_date);
			
			// Recupero solo le date valide
			$validUsers = array_filter($users, function($row) {
				return ($row['status'] == _CUS_SUBSCRIBED || $row['status'] == _CUS_BEGIN);
			});
			
			$dateUsers[$id_date] = $users;
			$dateValidUsers[$id_date] = $validUsers;
		}

		return $only_valid ? $dateValidUsers[$id_date] : $dateUsers[$id_date];
	}
	
	
	
	/**
	 * Recupera le informazioni dell'edizione
	 * Funzione Cache utile per utilizzo informazioni nei cicli o in chiamate multiple
	 */
	private function _getDateInfo($id_date) {
		
		static $dateInfo = [];

		if ( empty($dateInfo[$id_date]) ) {
			$dateInfo[ $id_date ] = $this->date_man->getDateInfo($id_date);
		}

		return $dateInfo[$id_date];
	}
	
	
	/**
	 * Recupera le informazioni sui giorni dell'edizione
	 * Funzione Cache per utilizzo informazioni nei cicli o in chiamate multiple
	 */
	private function _getDateDays(int $id_date, bool $only_valid = true) {
		
		static $dateDays = [];
		static $dateValidDays = [];
		
		if ( empty($dateDays[$id_date]) ) {
			
			// Recuper tutti i giorni della data salvati a sistema
			$days 	= $this->date_man->getDateDayForDates(array($id_date), false);	
			$days = current($days);
			
			// Recupero solo le date valide
			$validDays = array_filter($days, function($row) {
				return $row['deleted'] == 0;
			});
			
			$dateDays[$id_date] = $days;
			$dateValidDays[$id_date] = $validDays; 	
			
		}
			 
		return $only_valid ? $dateValidDays[$id_date] : $dateDays[$id_date];
			
	}
	
	
	/**
	 * Recupera le informazioni sui meeting evento dell'edizione
	 * Funzione Cache per utilizzo informazioni nei cicli o in chiamate multiple
	 */
	private function _getInfoMeetings($id_date) {
		static $meetings = [];
		
		if ( empty($meetings[$id_date]) ) {
			$meetings[$id_date] = $this->meeting_man->getInfoMeeting($id_date);
		}
		
		
		return $meetings [$id_date];
		
	}
	
	
	/**
	 * Recupera le aule registrate a sistema
	 * Funzione Cache per utilizzo informazioni nei cicli o in chiamate multiple
	 */
	private function _getClassroom() {
		
		static $classList = [];
		
		if ( empty($classList) ) {
			$classList = $this->meeting_man->getClassrooms();
		}
		
		return $classList;
	}
	
	
	/**
	 * Restituisce se la chiave segreta per il token è valida
	 */
	public function isClientSecretValid() {

		return $this->meeting_man->isClientSecretValid();
	}
	
	
	/**
	 * Elimina un meeting in Teams e nelle tabelle di sistema
	 */
	public function deleteEventMeeting(string $eventId, string $organizerEmail):bool {
	
		$res = false;
		$meeting_man = &$this->meeting_man;
		
		// Non elimino se non è presente l'aula / host
		if (!filter_var($organizerEmail, FILTER_VALIDATE_EMAIL)) return $res;

		// Elimino il meeting dalle tabelle di sistema
		$res = $meeting_man->delInfoMeeting($eventId);
		
		if ($res) {	
			// Elimino il meeting da teams
			$res = $meeting_man->deleteEventMeeting($eventId, $organizerEmail);
		}
		
		// Out
		return $res;
	}
	
	
	/**
	 * Inserisce un meeting in Teams e nelle tabelle di sistema 
	 */
	public function insertEventMeeting(array $day, ?array $invitees = null):bool {

			$meeting_man = $this->meeting_man;
			
			// Recupero nome aula / host
			$organizerEmail = $day['class_room'];
			
			// Recupero info edition
			$id_date = $day['id_date'];
			$edition = $this->_getDateInfo($id_date);

			// Preparo il tag con le chiavi di forma
			$edition_code = ($edition['code'] ? $edition['code'] : 'no_code');
			$integrationTags = array('key_day' => $day['id'], 'id_date' => $id_date, 'id_course' => $edition['id_course'], 'edition_code' => $edition_code);
			
			
			// Recupero il testo custom della notifica (verrà riproposta a ogni aggiornamento)
			$body = $this->getBodyNotify("common", $edition);
			
			// Preparo parametri API
			$params = array(
				'subject' => $edition['name'],
				'start' => $day['date_begin'],
				'end' => $day['date_end'],
				'organizerEmail' => $organizerEmail,
				'body' => $body
			);
			
			
			// Creo il meeting
			$res = $meeting_man->addEventMeeting($params, $invitees, $integrationTags);
			
				
			// Inserisco il meeting nelle tabelle di sistema
			if ($res && $res['success'] == true) {
				
				// Inserisco
				$res['success'] = $meeting_man->insInfoMeeting( $res['response'] );	
			}	
	
		
		return $res['success'];
	}
	
	
	/**
	 * Aggiorna un meeting in Teams e nelle tabelle di sistema 
	 */
	public function updateEventMeeting(string $eventId, string $organizerEmail, array $day):bool {

			$res = false;
			$meeting_man = &$this->meeting_man;
			
			// Non inserisco se non è presente l'aula / host o se non corrisponde
			if (!filter_var($organizerEmail, FILTER_VALIDATE_EMAIL) || $organizerEmail != $day['class_room']) return $res;
			
			// Recupero info edition
			$id_date = $day['id_date'];
			$edition = $this->_getDateInfo($id_date);
			
			// Preparo parametri API
			$params = array(
				'subject' => $edition['name'],
				'start' => $day['date_begin'],
				'end' => $day['date_end'],
				'organizerEmail' => $organizerEmail,
			);
			
			
			// Aggiorno il meeting
			$res = $meeting_man->updateMeetingTeams($eventId, $params);
			
			
			// Aggiorno il meeting nelle tabelle di sistema
			if ($res && $res['success'] == true) {
				
				// Aggiorno
				$res = $meeting_man->updInfoMeeting( $res['response'] );	
			}	
	
		
		return $res;
	}
	
	
	/**
	 * Gestisce i meeting in base all'aggiornamento anagrafioc dell'edizione.
	 */
	public function manageUpdateEdition(int $id_date, array $date_details):bool  {
		
		$res = true;
		
		if (! empty($date_details) ) {
			
			//$old = $date_details['previous_record'];
			$new = $date_details['new_record'];
	
			if ( $new['status'] === _DATE_STATUS_ACTIVE ) {
				// Se passo a stato disponibile
				// controllo de aggiungere / modificare meeting
				$res = $this->manageEventMeetings($id_date);
				
			} elseif ( $new['status'] === _DATE_STATUS_CANCELLED ) {
				// Se passo a stato cancellato
				// elimino i meeting
				$res = $this->deleteEditionMeetings($id_date);
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Aggiorna le info del meeting.
	 * Se il meeting è già stato avviato non può essere aggiornato, ma l'evento sì
	 */
	public function manageEventMeetings(int $id_date, array &$counter = []):bool {
	
		$res = true;
		
		$counter['del'] = 0;
		$counter['ins'] = 0;
		$counter['upd'] = 0;
		
		$meetings = $this->_getInfoMeetings($id_date);
		$dateDays = $this->_getDateDays($id_date, false);
		$status = $this->getEditionStatus($id_date);
		
		// Recupero eventuali iscritti all'edizione
		$users = $this->_getDateUsers($id_date);
			
		//	exit( '{"success":false,"message":"'.$unchanged.'"}' );

		// Gestisco modifiche e nuovi inserimenti
		if ($status === _DATE_STATUS_ACTIVE) {
			
			// Imposto come chiave dell'array l'id della tabella day
			$meetings = array_column($meetings, null, 'key_day'); 

			// Recupero un array di sole chiavi per il confronto
			$meetingDayKeys = array_column($meetings, 'key_day');
			
			// Ciclo di gestione
			foreach ($dateDays as $day) {
				
				// Intervengo solo se la data non è nel passato
				// if ($day < $this->future_period[0]) continue;	
				
				// Recupero info chiave
				$keyDay = $day['id'];
				$eventId = $meetings[$keyDay]['eventId'];
				$organizerEmail = $meetings[$keyDay]['organizerEmail'] ;
				
				// Controllo se il giorno è nella tabella dei meeting
				$found = in_array($keyDay, $meetingDayKeys);
				
				// Determino l'operazione
				if ( $found && $day['deleted'] == 1 ) {
					// Eliminazione
					
					$res = $this->deleteEventMeeting( $eventId, $organizerEmail, $err );
					$counter['del'] += $res ? 1 : 0;
					
				} elseif ( $found && !$this->checkMeetingOrganizer($day, $meetings[$keyDay]) ) {
					// Reset, l'organizzatore è cambiato
					
					$res = $this->deleteEventMeeting( $eventId, $organizerEmail );
					$counter['del'] += $res ? 1 : 0;
					
					if ($res && $day['classroom']) {
						// Inserisco solo il cambiamento prevede l'aula virtuale di organizzazione
						$res = $this->insertEventMeeting($day, $this->_getDateUsers($id_date));
						$counter['ins'] += $res ? 1 : 0;					
					}
			
				} elseif ( $found && !$this->checkMeetingSchedule($day, $meetings[$keyDay]) ) {
					// Aggiornamento
					
					$res = $this->updateEventMeeting($eventId, $organizerEmail, $day);
					$counter['upd'] += $res ? 1 : 0;

					
				} elseif (!$found && $day['deleted'] == 0 && $day['classroom']) {
					// Inserimento
					
					$res = $this->insertEventMeeting($day, $this->_getDateUsers($id_date));
					$counter['ins'] += $res ? 1 : 0;
				}	
			} //end foreach	
		}
		
		// Out
		return $res;	
	}
	
	
	/**
	 * Aggiorna l'invito all'evento in base al cambiamento di stato sull'iscrizione
	 * Solo lo stato Iscritto e Iniziato sono accettati per l'invito, negli altri casi l'invito viene rimosso
	 * Il cambiamento di stato è unico per tutte le edizioni, quindi uso id_course e ciclo sulle edizioni del futuro
	 * @param int 	$id_date	chiave dell'edizione
	 * @param array $users 		array di chiavi id_user
	 */
	public function updateInvitees(int $id_course, array $users_ids, array $action_details):bool {
		
		$res = true;
		$usersToRemove = [];
		$usersToAdd = [];
		$meetingMan = &$this->meeting_man;
		
		
		// Intervengo solo se è un cambiamento di status
		if ( !isset($action_details['status']) ) return $res;
		
		// Recupero gli eventi meeting del futuro
		$meetings = $meetingMan->getMeetingsByCourse($id_course, $this->future_period[0], $this->future_period[1]);
		

		// Ciclo sulle edizioni
		foreach ($meetings as $mt) {
			
			$id_date = $mt['id_date'];

			// Recupero lo stato del cambiamento
			$newStatus = (int)$action_details['status'];
			
			// Ciclo sugli invitati che andrebbero rimossi o aggiunti in funzione del cambiamento di stato
			foreach ($users_ids as $id_user) {
				
				if ($newStatus !== _CUS_SUBSCRIBED && $newStatus !== _CUS_BEGIN) {
					$usersToRemove[  $id_user ] = $id_user;
				
				} else {
					$usersToAdd[ $id_user ] = $id_user;
				}
			}
			
			// Rimuovo
			if (!empty($usersToRemove))
				$res = $this->deleteInvitees($id_date, $usersToRemove);

			
			// Aggiugno
			if (!empty($usersToAdd))
				$res = $this->insertInvitees($id_date, $usersToAdd);
		}
		
		// Out
		return $res;
	}

	
	/**
	 * Inserisce gli invitati all'evento meeting e nelle tabelle di sistema
	 * @param int 	$id_date	chiave dell'edizione
	 * @param array $users 		array di chiavi id_user
	 */
	public function insertInvitees(int $id_date, array $user_ids):bool {

		$res = true;
		
		$meetingMan = &$this->meeting_man;
		$meetings = $this->_getInfoMeetings($id_date);

		// Recupero le informazioni complete degli utenti iscritti da invitare
		$users = $meetingMan->getSubscribedUsers($id_date,  $user_ids);
		
		// Recupero l'email dei nuovi invitati
		$emailsToAdd = array_column($users, 'email', 'id_user');
		
		
		// Ciclo di inserimento
		foreach ($meetings as $mt) {

			// Salto l'aggiunta di invitati se il meeting è del passato (altrimenti parte la notifica)
			if ($mt['startDateTime'] < $this->future_period[0]) continue;
			
			// Inserisco gli invitati
			$eventResult = $meetingMan->insertInvitees($mt['eventId'], $mt['organizerEmail'], $emailsToAdd);
			$response = $eventResult['response'];
			
			// Inserisco invito nella tabella di sistema
			if ( $eventResult['success'] && isset($response['attendees']) ) {
				
				foreach ( $response['attendees'] as $attendee ) {
			
					// Recupero le info utente da il response di teams (ci sono tutti gli utenti invitati, vecchi e nuovi)
					$info['emailAddress'] = $attendee['emailAddress']['address'];
					$info['displayName'] = $attendee['emailAddress']['name'];
					$info['type'] = $attendee['type'];
					$info['eventId'] = $response['id'];
					
					// Recupero id utente dall'array dei nuovi invitati ($emailsToAdd ha come chiave id_user)
					$id_user = array_search($info['emailAddress'], $emailsToAdd);

					
					// Inserisco il nuovo invitato nella tabella di sistema
					if ( $id_user )
						$res = $meetingMan->insInfoInvitee($info, $id_user);
				}
			}
			
		}
		// Out
		return $res;
	}

	
	/**
	 * Elimina uno o più utenti dai meeting futuri dell'edizione.
	 * $user_ids è un array di chiavi id_user
	 */

	public function deleteInvitees(int $id_date, array $user_ids):bool {
		
		$res = true;
		
		$meetingMan = &$this->meeting_man;
		$meetings = $this->_getInfoMeetings($id_date);
		
		
		// Cicli di eliminazione
		foreach ($meetings as $mt) {
			
			// Salto la rimozione di invitati se il meeting è del passato (altrimenti parte la notifica)
			if ($mt['startDateTime'] < $this->future_period[0]) continue;
			
			// Recupero gli invitati presenti a sistema
			$invitees = $meetingMan->getInfoInvitee($id_date, $mt['key_day']);
			
			// Filtro per solo quelli da rimuovere
			$users = array_intersect_key($invitees, array_flip($user_ids));
			
			// Recupero le email
			$emailsToRemove = array_column($users, 'emailAddress', 'inviteeId');
						
			// Rimuovo da Teams
			$eventResult = $meetingMan->deleteInvitees($mt['eventId'], $mt['organizerEmail'], $emailsToRemove);
			
			
			// Rimuovo da tabella di sistema
			if ( $eventResult['success'] ) {
				
				foreach ( array_keys($emailsToRemove) as $inviteeId ) {
			
					$res = $meetingMan->delInfoInvitee($inviteeId);
				}
			}				

		}
		
		// Out
		return $res;
	}

	
	/**
	 * Elimina tutti i meeting di tutte le edizioni di uno specifico corso
	 */
	public function deleteCourseEditionMeeting($id_course) {

		$meeting_man = &$this->meeting_man;
		$res = true;
		
		// Recupero le edizioni
		$editions = $meeting_man->getCourseEdition($id_course);
		
		// Ciclo di eliminazione
		foreach ($editions as $id_date) {	
			$res *= $this->deleteEditionMeetings($id_date);
		}
		
		// Out
		return $res;
	}
	
	/**
	 * Controlla che il meeting della lezione abbia la programmazione in linea
	 * con la pianificazione di sistema
	 * return bool false se la scedulazione non va bene
	 */
	public function checkMeetingSchedule($day, $meeting):bool {
		
		$res = true;
		$meetingMan = $this->meeting_man;

		// Controllo che giorni / orari e classe siano uguali
		$mt = $meeting;

		$start = $meetingMan->checkDateString($mt['startDateTime'], 'Y-m-d H:i:s');
		$end = $meetingMan->checkDateString($mt['endDateTime'], 'Y-m-d H:i:s');
		
			
		if ( !($day['date_begin'] == $start && $day['date_end'] == $end ) ) {
			$res = false;
		}	

				
		// Out
		return $res;
	}
	
	
	/**
	 * Controlla che il meeting abbia sempre lo stesso organizzatore
	 * return bool false se c'è un cambiamento
	 */
	public function checkMeetingOrganizer($day, $meeting):bool {
		
		$res = true;
		$classList = $this->_getClassroom();
		$mt = $meeting;
		
		// C'è un cambiamento di organizzatore / aula o è stata rimossa
		if (  !$day['classroom'] || $classList[ $day['classroom'] ] != $mt['organizerEmail'] ) {
			$res = false;
		}	
				
		// Out
		return $res;
	}
	
	
	/**
	 * Elimina gli eventi meeting nelle tabelle di sistema in base all'edizione di appartenenza
	 */
	public function deleteEditionMeetings(int $id_date):bool {
		
		$result = true;
		$meeting_man = &$this->meeting_man;
		
		$meetings = $meeting_man->getInfoMeeting($id_date);
		
		foreach ($meetings as $mt) {
			
			// Elimino il meeting
		
			$res = $this->deleteEventMeeting($mt['eventId'], $mt['organizerEmail']);
				
			$result *= $res;
		}
		
		// Out
		return $result;
	}
	
	
	/**
	 * Restituisce se il corso è di tipo virtuale
	 */
	public function isVirtualCourse($id_course) {
		
		$course_manager = new Man_Course();
		$result = false;
	
		$res = $course_manager->getCourseInfo($id_course);
		
		if ($res && $res['course_virtual'] == 1 && $res['course_type'] == 'classroom') {
			$result = true;
		}
		
		return $result;
	}
	
	
	/**
	 * Restituisce se il corso passato in argomento è stato pianificato con meeting
	 */
	public function isMeetingCourse($id_course, $check_integrity = true) {
		return $this->meeting_man->isMeetingCourse($id_course, $check_integrity);
	}
	
	
	/**
	 * Restituisce se una specifica edizione è attiva
	 */
	public function getEditionStatus($id_date) {
	
		$res = false;
		$query = "	SELECT status FROM %lms_course_date 
					WHERE id_date = ".(int)$id_date;
		
		// Lancio la query
		$result = sql_query($query);
		
		while(list($status) = sql_fetch_row($result))
			$res = (int)$status;

		// Out
		return $res;
	}
	
	
	public function getListInfo($code) {
		
		$res = array('header' => array(), 'title' => '', 'code' => $code);
		
		
		switch ($code)
		{
			case 'TmsEditionList':
				$res['title'] = Lang::t('_TMSMEETING.EXCT_EDITION', 'extraction');
				
				$res['header'] = 	array(	'name' => 'nome_edizione',
											'code' => 'codice_edizione',
											'date_begin' => 'inizio',
											'date_end' => 'fine',
											'status' => 'stato_edizione',
											'fund' => 'fondo',
											'details' => 'dettagli');
				
				break;
			case 'TmsMeetingList':
				$res['title'] = Lang::t('_TMSMEETING.EXCT_MEETING', 'extraction');
				
				$res['header'] = 	array(	'name' => 'nome_edizione',
											'code' => 'codice_edizione',
											'day' => 'incontro',
											'date_begin' => 'data',
											'status' => 'stato_edizione',
											'classroom' => 'aula',
											'joinUrl' => 'link',
											'fund' => 'fondo' );
				break;
		}
				
				
		return $res;
	}
	
	
	public function getTmsEditionList($date_begin, $date_end, $status, $id_org = false, $add_buttons = true) {
		//>> Restituisce le edizioni di un dato periodo

		$res = array();
		$status_list = $this->date_man->getStatusForDropdown();
		$fund_list = (new FundManager)->getFundList();
		$base_link = 'index.php?r=alms/tmsmeeting/exporttofile';
		
		$rep = $this->getListInfo('TmsEditionList');
		$alias = $rep['header'];
						
		// Recupero i dati
		$result = $this->meeting_man->getEditionList($date_begin, $date_end, $status, $id_org);
		
		// Preparo array di uscita
		foreach ($result as &$row) {
			
			// Preparo nuova riga
			$new_row = array();
			
			// Modifico alcuni valori per l'output
			$row['date_begin'] 	= $this->meeting_man->checkDateString($row['date_begin']);
			$row['date_end'] 	= $this->meeting_man->checkDateString($row['date_end']);
			$row['status'] 		= $status_list[$row['status']];
			$row['fund'] 		= (isset($fund_list[$row['id_fund']]) ? $fund_list[$row['id_fund']] : '');
			$row['details']		= null;
						
			// Aggiungo pulsanti dettaglio
			if ($row['meeting_count'] && $add_buttons) {		
				$link = $base_link.'&id_date='.$row['id_date'].'&id_org='.(int)$id_org."&list=participant";
				
				$row['details']  = '<a class="ico-sprite subs_xls" title="'.Lang::t('_EXPORT_XLS', 'standard').'" href="'.$link.'&type=xls"></a>';
				$row['details'] .= '<a style="margin-left:15px" class="ico-sprite subs_pdf" title="'.Lang::t('_EXPORT_PDF', 'standard').'" href="'.$link.'&type=pdf"></a>';
			}
			
			// Copio i valori con nomi di campo tradotti
			foreach ($alias as $k => $v)
				$new_row[$v] = $row[$k];
			
			
			// Memorizzo la riga modificata
			$res[] = $new_row;
		}
		
		// Out
		return $res;
	}
	
	
	public function getTmsMeetingList($date_begin, $date_end, $status, $id_org = false) {
		//>> Restituisce i meeting (lezioni edizione) di un datpo periodo
		
		$res = array();
		$status_list = $this->date_man->getStatusForDropdown();
		$class_list = $this->meeting_man->getClassrooms();
		$fund_list = (new FundManager)->getFundList();
		
		$rep = $this->getListInfo('TmsMeetingList');
		$alias = $rep['header'];
						
		// Recupero i dati
		$result = $this->meeting_man->getMeetingList($date_begin, $date_end, $status, $id_org);
		
		// Preparo array di uscita
		foreach ($result as $row) {
			
			// Preparo nuova riga
			$new_row = array();
	
			// Modifico alcuni valori per l'output
			$row['day'] 		= $row['id_day']+1;
			$row['date_begin'] 	= $this->meeting_man->checkDateString($row['date_begin']);
			$row['status'] 		= $status_list[$row['status']];
			$row['classroom'] 	= (isset($class_list[$row['classroom']]) ? $class_list[$row['classroom']] : '');
			$row['fund'] 		= (isset($fund_list[$row['id_fund']]) ? $fund_list[$row['id_fund']] : '');
						
						
			// Copio i valori con nomi di campo tradotti
			foreach ($alias as $k => $v)
				$new_row[$v] = $row[$k];


			// Memorizzo la riga modificata
			$res[] = $new_row;
		}
			
		// Out
		return $res;
	}
	
	
	public function getTmsAttendeeList($id_date, $key_day = null, $id_org = null) {
		//>> Recupera i partecipanti di un meeting per la reportistica
		
		require_once(_lms_.'/lib/lib.subscribe.php');
		
		// Recupero date dei giorni programmati
		
		
		// Recupero livelli con traduzione
		$user_levels = (new CourseSubscribe_Manager())->getUserLevel();
		
		// Recupero dati partecipanti
		$attendees = $this->meeting_man->getAttendeeList($id_date, $key_day, $id_org);
		
		// Sistemazione dati
		foreach ($attendees as &$presence) {
			foreach ($presence as &$row) {
				$row['userid'] 			= $this->meeting_man->getRelativeUserId($row['userid']);
				$row['level_t']			= $user_levels[ $row['level'] ];
				$row['email']			= strtolower($row['email']);
				$row['alert_day'] 		= ( date('d', strtotime($row['joinDateTime'])) != date('d', strtotime($row['scheduled_day'])) );
				$row['joinDateTime'] 		= Format::datetimeToString($row['joinDateTime'], 'time');
				$row['leaveDateTime'] 		= Format::datetimeToString($row['leaveDateTime'], 'time');
			}
		}
		
		// Out
		return $attendees;
	}
	
	
	public function getEditionName($id_date) {
		//>> Restituisce il nome dell'edizione
		
		$res = false;
		
		// Info edizione
		$date_info = $this->date_man->getDateInfo($id_date);
		
		if($date_info)
			$res = $date_info['name'];
			
		// Out
		return $res;
	}
	
	
	public function getEditionInfo($id_date) {
		//>> Restituisce le informazioni edizione con quelle del fondo
		
		$fund_man = new FundManager('date');
		
		// Info edizione
		$date_info = $this->date_man->getDateInfo($id_date);
		
		// Info fondo
		$fund_info = $fund_man->getFundEntry($id_date);
		
		// Aggiunta info fondo a edizione
		if($fund_info) {
			$entry_fields = $fund_man->getFundEntryFields($fund_info['id_fund']);
			
			foreach($entry_fields as $k => $v) {
				$date_info[$v] = $fund_info[$k];
			}
		}
		
		// Out
		return $date_info;
	}
	
	
	public function getEditionDayInfo($id_date) {
		//>> Restituisce le informazioni edizione con quelle del fondo
		
		$fund_man = new FundManager('date');
	
		// Info edizione
		$date_info = $this->date_man->getDateInfo($id_date);
		
		// Info giorni
		$day_info = $this->date_man->getDateDay($id_date);
		
		// Info fondo
		$fund_info = $fund_man->getFundEntry($id_date);
		
		// Info fondo con traduzione campi custom
		if($fund_info) {	
			$tmp = array();
			$entry_fields = $fund_man->getFundEntryFields($fund_info['id_fund']);
			
			foreach($entry_fields as $k => $v)
				$tmp[$v] = $fund_info[$k];
	
			$fund_info = $tmp;
		}
		
		// Aggiunta info edizione a giorni
		foreach($day_info as &$day) {
			$day['id_course'] = $date_info['id_course'];
			$day['edition_name'] = $date_info['name'];
			$day['edition_code'] = $date_info['code'];
			$day['fund_fields'] = $fund_info;
		}
		
		// Out
		return $day_info;
	}
	
	
	public function getMeetingLinks($id_date) {
		//>> Restituisce i link dei meeting di una specifica edizione
		return $this->meeting_man->getMeetingLinks($id_date);
	}
	
	
	/**
	 * Controlla la pianificazione dell'edizione (orari)
	 */
	public function checkSchedule(array $action_details):bool {
		
		$dates = $action_details;
		$check = true;
		
		if ( !empty($dates) && isset($dates[0]['date_begin']) ) {
				
			foreach ($dates as $dt) {
				
				if ($dt['date_begin'] >= $dt['date_end']) {
					$check = false;
					break;
				}
			}
		}
		
		// Out		
		return $check;
	}
	
	
	/**
	 * Inserisce i report di partecipazione nelle tabelle di sistema
	 * @return int numero di eventi/meeting elaborati
	 */
	public function insAttendanceReport(string $startDate, string $endDate, ?int &$elab = null, ?string &$errorMsg = null):int {
		
		$elab = 0;
		$open_transaction = false;

		$db = DbConn::getInstance();
		$meeting_man = $this->meeting_man;
		
		// Prelevo eventId conclusi nel periodo di estrazione
		$events =  $meeting_man->getEventMeetingEndBetween($startDate, $endDate);
		
		// Conto gli eventi interessati
		$events_count = count($events);

		// Se ci sono eventi/meeting finiti, in base alla programmazione, avvio la transazione
		if($events_count > 0) {		
			$db->start_transaction();
			$open_transaction = true;
		}
		
		// Inserisco i dati
		foreach ($events as $row) {

			$errorMsg = null;
			$eventId  = $row['eventId'];
			$joinUrl  = $row['joinUrl'];
			$success  = true;   // Flag globale per questo evento

			// Recupero report
			$reports = $meeting_man->getAttendanceReports($joinUrl, $row['organizerEmail'], $errorMsg);

			if (empty($reports) || !is_array($reports)) {
				// Log errore API se necessario
				continue;
			}

			foreach ($reports as $rep) {
				
				// Salto se il report è già stato scaricato
				if ( $meeting_man->reportExists($rep['reportId']) ) continue;

				// Recupero i record del report trovato
				$records = $rep['attendanceRecords'] ?? [];

				// Se non ci sono record non inserisco nemmeno la testata del report e interrompo
				if (empty($records)) { $success = false; break; }
				
				// Se il report è valido, inserisco testata
				$result = $meeting_man->insInfoAttendanceReport($eventId, $rep, $db);

				// Uscita su errore inserimento report
				if (!$result) {	$success = false; break; }

				foreach ($records as $record) {
					// Inserisco record
					$resRecord = $meeting_man->insInfoAttendanceRecord($record, $rep['reportId'], $eventId, $db);

					// Uscita su errore inserimento record
					if (!$resRecord) { $success = false; break; }
				}
				
				// Interrompo ciclo eventi se qualcosa non è andato a buon fine
				if (!$success) break;
			}
	
			// Contatore eventi elaborati
			$elab++;
		}
		
		// Chiusura transazione
		if ($open_transaction == true) {
			
			if ($elab == $events_count)
				$db->commit();
			else
				$db->rollback();
			
			$open_transaction = false;
		}
		
		// Out
		return ($events_count == $elab);
	}
	
	
	/**
	 * Restituisce i testi di notifica da aggiungere a quelli standard di Teams
	 */
	private function getBodyNotify(string $type, array $info):string {
		
		// Corpo notifica
		$message = "";
		
		switch ( $type ) {
			
			case "common":
			
			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => Get::sett('url'),
									'[edition_code]' => $info['code'],
									'[edition_name]' => $info['name'] );
									
				
			// Recupero il messaggio completo					
			$message =  Lang::t('_TMSMEETING.MEETING_NOTIFY', 'message', $array_subst);
			
			break;
		}
	
		// Out
		return $message;		
		
	}
	
	

}
