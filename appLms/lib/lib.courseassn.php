<?php defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|                                                                           |
|                                                                           |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   BKO libreria by ABR                                                     |
\ ======================================================================== */


define('_ASSN_STATUS_ACTIVE', 1);
define('_ASSN_STATUS_CLOSED', 2);
define('_ASSN_STATUS_PREPARATION', 5);
define('_ASSN_STATUS_CANCELED', 8);

class CourseassnManager
{

	protected $lang;
	protected $acl_man;
	protected $gap_man;
	protected $logUserInfo;

	public function __construct(){
		require_once(_lms_.'/lib/lib.subscribe.php');

		$this->lang = DoceboLanguage::CreateInstance('admin_courseassn', 'lms');
		$this->acl_man = Docebo::user()->getAclManager();
		
	}


	public function __destruct(){
		
	}
	
	
	public function getLogUser($infoType = false){
		//>> Restituisce informazioni sull'utente loggato
		
		if(!isset($this->logUserInfo)){
				//Recupero informazioni utente. Uso di $logUserInfo: $lname = $logUserInfo[ACL_INFO_LASTNAME];
				$this->logUserInfo = $this->acl_man->getUser(getLogUserId(), false);
		}
		
		switch ($infoType){
			case false:
				$retVal = getLogUserId();
				break;
			case 'username':
				$retVal = $this->logUserInfo[ACL_INFO_USERID];
				break;
			case 'lastname':
				$retVal = $this->logUserInfo[ACL_INFO_LASTNAME];
				break;
			case 'firstname':
				$retVal = $this->logUserInfo[ACL_INFO_FIRSTNAME];
				break;
			case 'email':
				$retVal = $this->logUserInfo[ACL_INFO_EMAIL];
				break;
			default:
				$retVal = false;
		}
	
		return $retVal;
	
	}
	
	
	public function getStatusForDropdown(){
		
		return array(	_ASSN_STATUS_ACTIVE => $this->lang->def('_ASSN_STATUS_ACTIVE', 'courseassn'),
						_ASSN_STATUS_CLOSED => $this->lang->def('_ASSN_STATUS_CLOSED', 'courseassn'),
						_ASSN_STATUS_PREPARATION => $this->lang->def('_ASSN_STATUS_PREPARATION', 'courseassn'),
						_ASSN_STATUS_CANCELED => $this->lang->def('_ASSN_STATUS_CANCELED', 'courseassn') );
	}


	public function fdebug($note = '', $conn = false){
		//>> usata per debug
		$note = str_replace("'","''",$note);
		$query = "INSERT INTO `debug_table`(`note`,`datetime_event`) VALUES('".$note."',Now());";
		return sql_query($query, $conn);
	}
	
	
	public function delAssn($id_assn, $upd_gap = true){
		//>> Elimina l'assegnazione passata in argomento (accetta array)
		
		$assn = is_array($id_assn) ? $id_assn : array($id_assn);
		
		$query =	"DELETE FROM %lms_assignment"
					." WHERE id_assn IN ('".implode("','", $assn)."')";
		
		// Elimino
        $res = sql_query($query);

        // Aggiorno lo stato del gap
		if($res && $upd_gap)
			$this->updGapStatus();
                
		return $res;
	}
	
	
	public function cancelAssn($id_assn, $reason, $upd_gap = true) {
		//>> Annulla l'assegnazione passata in argomento
		
		//Preparo where
		$whrExp = "";
		
		if (is_array($id_assn))
				$whrExp = " id_assn IN ('".implode("','", $id_assn)."')";
		else
				$whrExp = " id_assn = ".(int)$id_assn;
				
		//Preparo query		
		$query = "UPDATE %lms_assignment assn 
						SET assn.status = ". _ASSN_STATUS_CANCELED.", date_upd = NOW(), user_upd = ".$this->getLogUser().", desc_upd = CONCAT('Canceled: ','".$reason."'), id_edition = NULL 
				  WHERE assn.status <> ". _ASSN_STATUS_CLOSED." AND ".$whrExp;
				  
		//Lancio la query
		$res = sql_query($query);
		
		//Aggiorno il gap
		if ($res && $upd_gap)
			$this->updGapStatus($id_assn);
			
		//Out
		return $res;		
	}
	
	public function cancelActiveAssn($year, $id_org, $id_gap = false, $upd_gap = true) {
		//>> Annulla le assegnazioni ancora attive per l'anno e l'organizzazione passati in argomento
		//>> Se id_gap è valorizzato, vengono annullate solo quelle dei gap collegati
		
		$res = false;
	
		//Preparo where
		$whrExp = "assn.status = ". _ASSN_STATUS_ACTIVE." AND YEAR(date_ins) = ".(int)$year." AND idOrg = ".(int)$id_org;
		
		if($id_gap) {
			$gaps = is_array($id_gap) ? $id_gap : array($id_gap);
			$whrExp .= " AND id_gap IN ('".implode("','", $gaps)."')";
		}
			
		
		//Recupero assegnazioni
		$assn = $this->getAssnId($whrExp);
		
		//Aggiorno
		if ($assn)
			$res = $this->cancelAssn(array_column($assn, 'id_assn'), 'in bulk');
			
		//Aggiorno i gap
		if ($res && $upd_gap)
			$this->updGapStatus();

		//Out
		return $res;		
	}
	
	
	public function updAssnEditionExistent($id_course, $id_edition_existent, $id_edition_new = false, $id_user = false){
		//>> Aggiorna l'id dell'edizione, nella tabella di assegnazioni, se l'assegnazione non è chiusa.
		//>> Se non viene passato il nuovo id, la funzione cancella l'id esistente.
		//>> Se non viene passato l'utente, l'aggiornamento si estende a tutti gli utenti.
		//>> Può essere usata per rimuovere l'id dell'edizione alla cancellazione di un'edizione o di un corso.
		//>> Oppure per aggiornare l'assegnazione in manutenzione su un'iscrizione già avvenuta (modalità non implementata)
		
		$query = '';
		$id_edition_str = (!$id_edition_new ? 'NULL' : $id_edition_new);
		$desc_upd = ($id_edition_str == 'NULL' ? 'Edition cleared' : 'Edition changed');
		$whrExp =  (!$id_user ? '' : ' AND id_user = '.$id_user);
		
		$query = "UPDATE %lms_assignment 
					SET id_edition = ".$id_edition_str." , date_upd = NOW(), user_upd = ".$this->getLogUser().", desc_upd = '".$desc_upd."' 
					WHERE type_entry = 'course' AND id_entry = '".$id_course."' AND 
						id_edition = '".$id_edition_existent."' AND status <> "._ASSN_STATUS_CLOSED. $whrExp;
						

		//Lancio query
		return sql_query($query);
	}
	
	
	/**
	 * Restituisce le iscrizioni dell'utente compatibili con l'assegnazione
	 * Un utente può avere più iscrizioni che colmano il bisgono formativo dell'edizione
	 * Egli può essere iscritto a più edizioni dello stesso corso.
	 */
	public function getSubsCompatible($id_course, $id_user) {
			
			$res = array();
			
			//Cerco se c'è un'assegnazione aperta per utente e corso
			$where = "status = "._ASSN_STATUS_ACTIVE . " AND id_entry = " . (int)$id_course . " AND id_user = " . (int)$id_user;
			$assn = $this->getSimpleAssn($where);
			
			if ($assn) {
				
				// Prendo la prima (il metodo restituisce l'assegnazione in un array)
				$assn = reset($assn);

				// Recupero informazioni corso
				require_once _lms_ . '/lib/lib.course.php';
				$course_info = Man_Course::getCourseInfo($id_course);
				
				// Recupero iscrizioni compatibili
				if ( $course_info['course_type'] == 'elearning' AND $course_info['course_elearning'] == 1) {
					// Edizione e-learning: trovo tutte le iscrizioni in corso di edizioni aperte compatibili con l'assegnazione
					
						$query = " SELECT du.*, 'elearning' As course_type"
								." FROM %lms_course_editions e JOIN %lms_course_edition_user eu ON e.id_edition = eu.id_edition"
								." WHERE e.id_course = ".(int)$id_course . " AND eu.id_user = ".(int)$id_user. " AND e.status IN (0,1,2)"
								."   AND (eu.date_complete IS NULL OR eu.date_complete = '0000-00-00 00:00:00')"
								."   AND eu.date_subscription >= '" . $assn['date_ins'] . "'"
								." ORDER BY  eu.date_subscription DESC";
										
						// Lancio la query
						$result = sql_query($query);
						
						while($row = sql_fetch_assoc($result))
						{
							$res[$row['id_edition']] = $row;
						}
						
					
				} elseif ($course_info['course_type'] == 'classroom') {
					// Edizione classroom: trovo tutte le iscrizioni in corso di edizioni aperte compatibili con l'assegnazione
					
						$query = " SELECT du.*, 'classroom' AS course_type"
								." FROM %lms_course_date dt JOIN %lms_course_date_user du ON dt.id_date = du.id_date"
								." WHERE dt.id_course = ".(int)$id_course . " AND du.id_user = ".(int)$id_user. " AND dt.status IN (0,3)"
								."   AND (du.date_complete IS NULL OR du.date_complete = '0000-00-00 00:00:00')"
								."   AND du.date_subscription >= '" . $assn['date_ins'] . "'"
								." ORDER BY  du.date_subscription DESC";
														
						// Lancio la query
						$result = sql_query($query);
						
						while($row = sql_fetch_assoc($result))
						{
							$res[$row['id_date']] = $row;
						}
				}
				
			}
				
		// Out
		return $res;
		
	}
	
	
	
	
	public function updAssnEditionSubs($id_user, $id_course, $id_edition){
		//>> Aggiorna l'id dell'edizione, nella tabella di assegnazioni, se l'assegnazione non è chiusa.
		//>> Sovrascrive eventuali edizioni già presenti.
		//>> Usata per aggiornamento dopo iscrizione.
		
		$query = '';
		$res = false;

		if($id_edition){
			
			/*
			//sostituita query sostituzione edizione se più recente
			$query = "UPDATE %lms_assignment a
						JOIN %lms_course c ON id_entry = idCourse 
						SET a.id_edition = ".$id_edition." , a.date_upd = NOW(), a.user_upd = ".$this->getLogUser().", a.desc_upd = 'Edition assigned' 
						WHERE a.type_entry = 'course' AND a.id_entry = '".$id_course."' AND a.id_user = '".$id_user."' AND c.subscribe_method = 9 AND 
							(a.id_edition IS NULL OR a.id_edition < '".$id_edition."') AND a.status = "._ASSN_STATUS_ACTIVE;
			*/
			
			$query = "UPDATE %lms_assignment a
						JOIN %lms_course c ON id_entry = idCourse 
						SET a.id_edition = ".$id_edition." , a.date_upd = NOW(), a.user_upd = ".$this->getLogUser().", a.desc_upd = 'Edition assigned' 
						WHERE a.type_entry = 'course' AND a.id_entry = '".$id_course."' AND a.id_user = '".$id_user."' AND c.subscribe_method = 9 AND 
							a.status = "._ASSN_STATUS_ACTIVE;

			//Lancio query
			$res = sql_query($query);
		}
		
		//Out
		return $res;
	}
	
	
	public function updAssnCourseDeleted($id_course){
		//>> Aggiorna le assegnazioni rimuovendo l'id del corso assegnato. Non rimuove il record di assegnazione per storico.

		$query = '';
						
		$query = "UPDATE %lms_assignment 
					SET id_entry = 0, id_edition = NULL, status = "._ASSN_STATUS_CANCELED.", date_upd = NOW(), 
						user_upd = ".$this->getLogUser().", desc_upd = 'Course deleted'  
					WHERE id_entry = ".$id_course. " AND type_entry = 'course' AND status <> "._ASSN_STATUS_CLOSED;
					
		// Lancio query
		$res = sql_query($query);
		
		// Aggiorno lo stato dei gap
		if($res)
			$this->updGapStatus();
			
		// Out	
		return $res;
	}
	
	
	public function delAssnUserDeleted($id_user, $conn = false){
		//>> Elimina tutte le assegnazioni dell'utente.

		$query = "DELETE FROM %lms_assignment 
					WHERE id_assn > 0 AND id_user = ".(int)$id_user;
					
		//Lancio query
		return sql_query($query, $conn);
	}
	
	
	public function updAssnStatusById($id_assn, $status){
		//>> Aggiorna lo stato dell'asssegnazione (modifica singola)
		
		$res = false;
		$id_assn = (int)$id_assn;
		$valid_status = $this->getStatusForDropdown();

		if(array_key_exists($status, $valid_status)){
							
			$query = "UPDATE %lms_assignment 
						SET status = ".$status.", date_upd = NOW(), 
							user_upd = ".$this->getLogUser().", desc_upd = 'Status changed'  
						WHERE id_assn = ".$id_assn;
					
			//Aggiorno l'assegnazione
			$res = sql_query($query);
			
			//Aggiorno il gap
			if($res) {
				$this->updGapStatus($id_assn);
			}
		}
		
		// Out
		return $res;
	}
	
	
	public function updAssnGapById($id_assn, $id_gap){
		//>> Aggiorna l'associazione al gap dell'assegazione passata in argomento
		
		$val = (!$id_gap ? "NULL" : (int)$id_gap);
					
		$query = "UPDATE %lms_assignment 
					SET id_gap = ".$val.", date_upd = NOW(), 
						user_upd = ".$this->getLogUser().", desc_upd = 'Gap changed'  
					WHERE id_assn = ".(int)$id_assn;
				
		// Lancio query
		$res = sql_query($query);
		
		// Aggiorno gli stati del gap
		if($res)
			$this->updGapStatus($id_assn);
		
		// Out
		return $res;
	}

	
	public function getAssnByUser($id_user, $arr_status = false) {
		//>> Restituisce le assegnazioni degli utenti passati in argomento.
		//>> Se non è valorizzato arr_status, vengono restituite solo le assegnazioni
		//>> aperte e in preparazione.
		
		// Preparo array utenti
		$users = (is_array($id_user) ? $id_user : array($id_user));
		
		// Preparo where
		$whrExp = " id_user IN (".implode(",", $users).")";
		
		if($arr_status !== false) 
			$whrExp .= " AND assn.status IN (".implode(",", $arr_status).")";
		else
			$whrExp .= " AND assn.status NOT IN ("._ASSN_STATUS_CLOSED.","._ASSN_STATUS_CANCELED.")";
		
	
		//Restituisco assegnazioni
		return $this->getAssn($whrExp);	
	}
	
	
	public function getAssnByGap($id_gap, $status = false, $full_info = false) {
		//>> Restituisce le assegnazioni dei gap passati in argomento.
		//>> Se non è valorizzato status (array), vengono restituite tutte le assegnazioni non annullate.
		//>> Se viene passato un valore testuale (es. 'all') vengono restituite in tutti gli stati.

		// Preparo array gap
		$gaps = (is_array($id_gap) ? $id_user : array($id_gap));
		
		// Preparo where
		$whrExp = " assn.id_gap IN (".implode(",", $gaps).")";
		
		if($status === false)
			$whrExp .= " AND assn.status NOT IN ("._ASSN_STATUS_CANCELED.")";
			
		elseif(is_array($status))
			$whrExp .= " AND assn.status IN (".implode(",", $status).")";
			
		
		//Restituisco assegnazioni
		return ($full_info ? $this->getAssn($whrExp) : $this->getAssnId($whrExp));	
	}
		
	
	public function updAssnStatus(int $id_course, int $id_edition, int $status_edition, string $course_type, $id_user = false){
		//>> Aggiorna lo status delle assegnazioni. 
		//>> Classroom:
		//>>		2-chiusa su utenti con almeno una presenza o attività didattica completata e stato dell'edizione completato, 1 in altri casi.
		//>> Elearning:
		//>>		2-chiusa su utenti con attività didattica completata e stato dell'edizione completato, 1 in altri casi.

		$res = false;
		$query = '';
		$whrExp = (!$id_user ? '' : "AND a.id_user = ".(int)$id_user);

		//Preparo query
		if ($course_type == 'classroom'){
			
				$query =   "UPDATE %lms_assignment assn
							JOIN (SELECT a.id_assn, 
										 CASE WHEN ((cu.status = 2 OR SUM(cdp.presence) > 0) AND 1 = ".$status_edition.") THEN ". _ASSN_STATUS_CLOSED . " ELSE ". _ASSN_STATUS_ACTIVE . " END As value
									FROM %lms_assignment a 
										JOIN %lms_courseuser cu ON (a.id_entry = cu.idCourse AND a.id_user = cu.idUser)
										JOIN %lms_course_date_day cdd ON (a.id_edition = cdd.id_date AND cdd.deleted = 0)
										JOIN %lms_course_date_presence cdp ON (cdd.id_date = cdp.id_date AND cdd.id = cdp.id_day AND cu.idUser = cdp.id_user)
									WHERE type_entry = 'course' AND id_entry = ".$id_course." AND a.id_edition = ".$id_edition." AND DAYNAME(cdp.day) IS NOT NULL ".$whrExp." 
									GROUP BY a.id_assn
								 ) AS assn_status
							ON assn.id_assn = assn_status.id_assn 
							SET assn.status = assn_status.value, assn.date_upd = NOW(), assn.user_upd = ".$this->getLogUser().", desc_upd = 'Status changed'  
							WHERE assn.status < " . _ASSN_STATUS_PREPARATION;
			
		} elseif ($course_type == 'elearning'){
			
				$query =   "UPDATE %lms_assignment assn
							JOIN (SELECT a.id_assn, 
										 CASE WHEN (cu.status = 2 AND 3 = ".$status_edition.") THEN ". _ASSN_STATUS_CLOSED . " ELSE ". _ASSN_STATUS_ACTIVE . " END As value
									FROM %lms_assignment a 
										JOIN %lms_courseuser cu ON (a.id_entry = cu.idCourse AND a.id_user = cu.idUser)
									WHERE type_entry = 'course' AND id_entry = ".$id_course." AND a.id_edition = ".$id_edition." ".$whrExp." 
								 ) AS assn_status
							ON assn.id_assn = assn_status.id_assn 
							SET assn.status = assn_status.value, assn.date_upd = NOW(), assn.user_upd = ".$this->getLogUser().", desc_upd = 'Status changed' 
							WHERE assn.status < " . _ASSN_STATUS_PREPARATION;
		}
		
				
		//Aggiorno assegnazioni
		if($query)
			$res = sql_query($query);

			
		// Aggiorno lo stato dei gap
		//if($res)
			//$this->updGapStatus();
		
		//Out
		return $res;
	}
	
	
	public function getAssnNumber($id_org, $year = false, $id_fncrole_ref = false, $where = false){
		//>> Restituisce il numero di assegnazioni per una data società e un dato anno
		
		//Formo la stringa where
		$whrExp = "idOrg = ".(int)$id_org;
		
		if($where) 
			$whrExp .= " AND (".$where.") ";
		
		if($year > 0) 
			$whrExp .= " AND YEAR(date_ins) = ".$year;
			
		if($id_fncrole_ref > 0) 
			$whrExp .= " AND id_fncrole_ref = ".$id_fncrole_ref;		

		//lancio la query e recupero il numero di righe
		$query = $this->_getAssnSqlString($whrExp);
		$res = sql_num_rows(sql_query($query));
		
		
		return $res;
	}
	
	
	public function getAssnUsers($id_org = false, $id_course = false, $date_from = false, $only_valid = false) {
		//>> Restituisce un array con gli utenti assegnatari di un assegnazione attiva per uno specifico corso
		
		$users = array();
		
		//Formo la stringa where
		$whrExp = "assn.status = "._ASSN_STATUS_ACTIVE;
		
		//Preparo where
		if($id_org) 	$whrExp .= " AND idOrg = ". (int) $id_org;
		if($id_course) 	$whrExp .= " AND id_entry = ". (int) $id_course;
		if($date_from)	$whrExp .= " AND assn.date_ins >= '".$date_from."'";
		if($only_valid)	$whrExp .= " AND usr_u.valid = 1 ";


		//Recupero query
		$query = $this->_getAssnSqlString($whrExp);
		
		
		//Lancio la query		
		$result  = sql_query($query);
					
		//Recupero gli utenti			
		while($row = sql_fetch_assoc($result)) {
			$users[$row['id_user']] = array('userid' 		=> $row['user_userid'],
											'firstname' 	=> $row['user_firstname'],
											'lastname' 		=> $row['user_lastname'],
											'email' 		=> $row['user_email'],
											'valid' 		=> $row['user_valid'],
											'id_edition'	=> $row['id_edition'],
											'id_course'		=> $row['id_entry'],
											'course_type'	=> $row['course_type'],
											'id_manager'	=> $row['id_manager']);
		}
					
		return $users;	
	}
	
	
	private function _getAssnSqlString($whrExp = false, $orderExp = false, $limitExp = false){
		//>> Restituisce la stringa SQL per recuperare le assegnazioni con le informazioni correlate
		//>> Gli utenti assegnatari devono essere dentro a un nodo e la query espone il codice del nodo di primo livello

		//Preparo strigna Where
		if($whrExp !== false)
			$whrExp  = ' AND '. $whrExp;
		
		//La completo con ordinamenti
		if(is_string($orderExp))
			$orderExp = ' ORDER BY '.$orderExp;
		
		//La completo con limit
		if(is_string($limitExp))
			$limitExp = ' LIMIT '.$limitExp;
			
		$query = "SELECT assn.*, org.code AS org_code, 
						crs.code AS course_code, crs.name AS course_name, crs.course_type, crs.id_fncrole_ref, crs.course_virtual, 
						crs.max_num_subscribe AS course_max_num_subscribe, crs.min_num_subscribe AS course_min_num_subscribe, 
						crs.difficult AS course_difficult, crs.idCategory AS course_idcategory, crs.status AS course_status, 
						usr_u.firstname AS user_firstname, usr_u.lastname AS user_lastname, usr_u.userid AS user_userid, usr_u.valid AS user_valid, 
						usr_u.email AS user_email, usr_u.time_availability AS user_time_availability, usr_u.job_location AS user_job_location, 
						usr_m.firstname AS manager_firstname, usr_m.lastname AS manager_lastname, 
						usr_m.userid AS manager_userid, usr_m.email AS manager_email,
						usr_o.firstname AS modifier_firstname, usr_o.lastname AS modifier_lastname, 
						usr_l.firstname AS loader_firstname, usr_l.lastname AS loader_lastname, 
						CONCAT(crs.code,' ',crs.name) AS course_fullname, lbl.id_common_label, 
						CONCAT(usr_u.firstname,' ',usr_u.lastname) AS user_fullname, 
						CONCAT(usr_m.firstname,' ',usr_m.lastname) AS manager_fullname, 
						CONCAT(usr_o.firstname,' ',usr_o.lastname) AS modifier_fullname, 
						CONCAT(usr_l.firstname,' ',usr_l.lastname) AS loader_fullname,
						IF(assn.id_edition > 0,'OK','') AS user_subscribed 
					FROM %lms_assignment assn
						LEFT JOIN %lms_course crs ON (assn.id_entry = crs.idCourse AND assn.type_entry = 'course')
						LEFT JOIN %lms_label_course lbl ON crs.idCourse = lbl.id_course 
						LEFT JOIN %adm_user usr_u ON assn.id_user = usr_u.idst
						LEFT JOIN %adm_user usr_o ON assn.user_upd = usr_o.idst
						LEFT JOIN %adm_user usr_m ON assn.id_manager = usr_m.idst
						LEFT JOIN %adm_user usr_l ON assn.user_ins = usr_l.idst
						JOIN %adm_group_members grp ON assn.id_user = grp.idstMember
						JOIN (select path, idst_oc from %adm_org_chart_tree) sorg ON grp.idst = sorg.idst_oc
						JOIN %adm_org_chart_tree org ON LEFT(sorg.path, 5+9*1) = org.path
					WHERE assn.type_entry = 'course' AND crs.subscribe_method = 9 ".$whrExp . $orderExp . $limitExp;	
					
		return $query;

	}
	
	public function getAssn($whrExp = false, $orderExp = false, $limitExp = false){
		//>> Restituisce le assegnazioni applicando le espressioni Where e Order By passate in argomento
		
		//Recupero la stringa SQL
		$query = $this->_getAssnSqlString($whrExp, $orderExp, $limitExp);
			
			
		//Lancio la query
		$result = sql_query($query);
		
		$res = array();
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_assn']] = $row;
		}
		
		return $res;
	}
	
	
	public function getSingleAssn($id_assn) {
		//>> Restituisce l'assegnazione singola con i valori di tabella
		
		// Preparo where
		$whrExp = "id_assn = ". (int)$id_assn;
		
		// Lancio il metodo
		$res = $this->getSimpleAssn($whrExp);
		
		//Recupero la riga
		if ($res) $res = reset($res);
		
		//Out
		return $res;
	}
	
	
	public function getSimpleAssn($whrExp) {
		//>> Restituisce le assegnazioni con i valori di tabella
		
		// Preparo strigna Where e query
		$whrExp  = ($whrExp !== false ? $whrExp : '1');
		
		$query = "SELECT * "
				."FROM %lms_assignment assn "
				."WHERE assn.type_entry = 'course' AND (".$whrExp.")";
						
		// Lancio la query
		$result = sql_query($query);
		
		$res = array();
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_assn']] = $row;
		}
		
		return $res;
	}
	
	
	public function getAssnByStatus($id_org, $status = false, $date_from = false, $date_to = false){
		//>> Restituisce le assegnazioni in base allo stato e alle date di inserimento
		
		//Formo la stringa where
		$whrExp = "idOrg = ". (int) $id_org;

		if($status) 	$whrExp .= " AND assn.status = ".(int)$status ;
		if($date_from)	$whrExp .= " AND assn.date_ins >= '".$date_from."'";
		if($date_to)	$whrExp .= " AND assn.date_ins <= '".$date_to."'";
		
		return $this->getAssn($whrExp);
	}
	
	
	public function checkAssnExists($id_user, $id_course, $status = false, $id_edition = false) {
		//>> Controlla se un'assegnazione esiste
		
		if(!$status) 
			$status = _ASSN_STATUS_ACTIVE;

		$whrExp = "assn.status = ".$status." AND assn.id_user = ".(int)$id_user." AND assn.id_entry = ".(int)$id_course;
		$whrExp .= (!$id_edition ? "" : " AND id_edition = ".(int)$id_edition);
		
		$assn = $this->getAssnId($whrExp);

		return ($assn ? true : false);
	}
	
	
	public function getAssnId($whrExp = false){
		//>> Restituisce l'id delle assegnazioni con relative chiavi esterne

		// Preparo strigna Where e query
		$whrExp  = ($whrExp !== false ? $whrExp : '1');
		
		$query = "SELECT id_assn, id_entry, id_edition, id_user, id_manager, id_gap, course_type, idOrg "
				."FROM %lms_assignment assn "
				."	JOIN %lms_course crs ON assn.id_entry = crs.idCourse "
				."	JOIN %adm_group_members grp ON assn.id_user = grp.idstMember "
				."	JOIN (select path, idst_oc from %adm_org_chart_tree) sorg ON grp.idst = sorg.idst_oc "
				."	JOIN %adm_org_chart_tree org ON LEFT(sorg.path, 5+9*1) = org.path "
				."WHERE assn.type_entry = 'course' AND crs.subscribe_method = 9 AND (".$whrExp.")";
				
				
		// Lancio la query
		$result = sql_query($query);
		

		$res = array();
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_assn']] = $row;
		}
		
		return $res;
	}
	
	
	public function getUserPastEdition($id_user, $course_type, $status = false) {
		//>> Recupera le edizioni trascorse per una data tipologia di corso,
		//>> un dato utente e un certo stato (se array $status è valorizzato).
		
		$query = "";
		$res = array();
		$whrExp = (is_array($status) ? " OR ed.status IN (".implode(', ', $status).")" : "");
		
		if ($course_type == 'classroom') {
			
			$query = "SELECT DISTINCT ed.id_date AS id, ed.* "
					."FROM %lms_course_date ed "
					."	JOIN %lms_course_date_user du ON ed.id_date = du.id_date "
					."	JOIN %lms_course_date_day dy ON ed.id_date = dy.id_date "
					."WHERE du.id_user = ".(int)$id_user. " AND (dy.date_end < '".date('Y-m-d')."'".$whrExp.")" ;
					
		}elseif ($course_type == 'elearning') {
			
			$query = "SELECT DISTINCT ed.id_edition AS id, ed.* "
					."FROM %lms_course_editions ed "
					."	JOIN %lms_course_editions_user eu ON ed.id_edition = eu.id_edition "
					."WHERE eu.id_user = ".(int)$id_user. " AND (ed.date_end < '".date('Y-m-d')."'".$whrExp.")";	
		
		}
		
		//Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id']] = $row;
		}
		
		return $res;	
	}
	
	
	public function getAssnGroupBy($functArr, $groupExp, $whrExp = false, $orderExp = false, $keyField = false) {
		//>> Esegue una query group by sulla tabella delle assegnazioni 
		//>> $functArr è un array di array con:
		//>> chiave -> funzione di aggregazione, valore -> array con campo da calcolare e alias. 
		//>> E' possibile passare una stringa con solo il nome del campo senza alias.
		
		$res = array();
		$func_sql = array('SUM', 'COUNT', 'AVARAGE');
		
		foreach ($functArr as $arr) {
			foreach ($arr as $k => $v) {
				// Controllo funzioni ammesse
				if(!in_array($k, $func_sql)) return;
				
				// Stringa campi con funzione di aggregazione
				if (!is_array($v))
					$field_calc .= ", ".$k."(".$v.") AS " .strtolower($k."_".$v);
				else
					$field_calc .= ", ".$k."(".$v[0].") AS ".$v[1];
			}
		}
		
		// Costruzione query
		$query = $this->_getAssnSqlString($whrExp);
		
		$query = " SELECT ".$groupExp . $field_calc
				." FROM (".$query.") AS view_assn "
				." GROUP BY ".$groupExp;
				
		$query .=  (is_string($orderExp) ? " ORDER BY ".$orderExp : "");
		
		
		//Lancio la query
		$result = sql_query($query);
		
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			if(!$keyField)
				$res[] = $row;
			else
				$res[$row[$keyField]] = $row;
				
		}
		
		return $res;		
	}
	
	
	public function describeTable($table_name, $all_description = false){
		//>> Restituisce le colonne di una tabella con nome campi, tipo di dato ecc. se $all_description è true;
		//>> solo i nomi dei campi se $all_description è false
		
		$res_all = array();
		$res_fld = array();
		
		$query = "SHOW COLUMNS FROM ".$table_name;
		$result = sql_query($query);

		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res_all[] = $row;
			$res_fld[] = $row['Field'];
		}
		
		//Output
		return ($all_description ? $res_all : $res_fld);
	}
	
	
	public function getAssnTemp($id_user, $id_org, $whrExp = false){
		//>> Restituisce le assegnazioni temporanee applicando l'espressione Where passata in argomento
		$whrExp = (!$whrExp ? '' : 'AND '.$whrExp);
			
		//Recupero la stringa SQL
		$query = "SELECT * "
				."FROM %lms_assignment_temp "
				."WHERE user_ins = '".$id_user."' AND id_org = '".$id_org."' ".$whrExp;
	
		//Lancio la query
		$result = sql_query($query);
		
		$res = array();
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_assn_tmp']] = $row;
		}
		
		return $res;
	}		
	
	
	public function getAssnManager($id_user, $id_course, $id_edition = false) {
		//>> Recupera informazioni sul manager dell'assegnazione
		
		//Recupero la stringa SQL
		$whrExp = (!$id_edition  ? "a.status = "._ASSN_STATUS_ACTIVE : "id_edition = ".(int)$id_edition);
		
		$query = "SELECT a.id_assn, u.* "
				."FROM %lms_assignment a JOIN %adm_user u "
				."	ON a.id_manager = u.idst "
				."WHERE a.type_entry = 'course' AND a.id_user = '".$id_user."' AND id_entry = '".$id_course."' AND ".$whrExp." "
				."ORDER BY date_ins DESC "
				."LIMIT 1";
		
		//Lancio la query
		$result = sql_query($query);
		
		return sql_fetch_assoc($result);
		
	}
		
	
	public function delAssnTemp($id_user, $id_org){
		//>> Elimina i record della tabella di appoggio relativi 
		//>> all'id utente che ha caricato i dati e all'organizzazione di lavoro

		$query = "DELETE FROM %lms_assignment_temp "
				."WHERE user_ins = ".(int)$id_user." AND id_org = ".(int)$id_org;
				
		return  sql_query($query);
	}
	
	
	public function insAssn($record, $is_tabletemp, $check_field = true){
		//>> Inserisce una riga di assegnazione nella tabella delle assegnazioni (temporanea o effettiva)
		
		$res = array();
		$table_name = ($is_tabletemp ? '%lms_assignment_temp' : '%lms_assignment');
		
		// Se richiesto, preparo array di campi non validi
		if($check_field){
			
			$field_table = $this->describeTable($table_name);
			$field_invalid = array_diff_key($record, array_flip($field_table));
			
		}else{
			$field_invalid = array();
		}
		
		// Se i campi sono tutti validi o non è richiesto il controllo, inserisco
		if(count($field_invalid) == 0){
		
			$field_names = implode(", ", array_keys($record));
			
			// Correggo i valori del record da eventuali 'quotes'
			$record = array_map("sql_escape_string", $record);
			
			// Preparo la stringa dei 'values' per insert	
			$field_values = "'". implode("', '", array_values($record))."'";
			
			$query =  "INSERT INTO ".$table_name." (".$field_names.") 
						VALUES (".$field_values.")";
		
			//Lancio la insert
			$res = sql_query($query);
						
			//output
			return $res;
		}

	}
	
	
	public function checkIsSubscibed($id_assn) {
		//>> Restituisce se l'assegnazione ha un'iscrizione
		//>> NOTA: al momento non utilizzata
		$query = "SELECT id_edition FROM %lms_assignment WHERE id_assn = ".(int)id_assn;
		
		list($res) = sql_fetch_row(sql_query($query));
		
		return (bool)$res;
	}
	
	
	
	/**
	 * Conta in numero di assegnazioni in un specifico stato di un utente
	 * Se status non è valorizzato, conta solo quelle aperte
	 */
	public function countAssnUser($id_user, $status = false) {
		
		$status = ($status === false ? _ASSN_STATUS_ACTIVE : (int)$status);
		
		$query = "Select count('x') from %lms_assignment where type_entry = 'course' and status = 1 and id_user =". (int)$id_user; 

		// Conteggio assegnazioni aperte
		list($count) = sql_fetch_row(sql_query($query));						
			
		// Out
		return $count;	
	}
	
	
	/**
	 * Conta le assegnazioni aperte sul corso passato in argomento
	 * La data deve già essere in formato stringa già formattata yyyy-mm-dd
	 */
	public function countAssnCourse($id_course, $id_org = false, $date_from = false) {


		$whrExp = "id_entry = ". (int)$id_course. " AND assn.status = " ._ASSN_STATUS_ACTIVE;
		
		//Preparo where
		if($id_org) 	$whrExp .= " AND idOrg = ".$id_org;
		if($date_from)	$whrExp .= " AND assn.date_ins >= '".$date_from."'";
		
		//Recupero la query
		$query = $this->_getAssnSqlString($whrExp);
		
		//Lancio la query		
		$result = sql_query($query);
		
		
		//Out
		return $result->num_rows;
	}
	
		
	public function getCourseCatalogOrg($id_org, $status = false) {
		//>> Restituisce i corsi del catalogo assegnati all'organizzazione 
		//>> (solo quelli a iscrizione per assegnazione)
		//>> L'argomento opzionale $status è un array con i valori di stato corso
			
		$res = array();
		$whrExp = "";
		
		if(is_array($status)) {
			$whrExp = " AND cor.status IN (" . implode(',', $status) . ") ";
		}
		
		$query = "SELECT cte.idCatalogue, cor.* "
				."FROM %lms_catalogue_entry cte "
				."	JOIN %lms_course cor ON cte.idEntry = cor.idCourse "
				."	JOIN learning_catalogue_member ctm ON cte.idCatalogue = ctm.idCatalogue "
				."	JOIN core_org_chart_tree ogc ON (ctm.idst_member = ogc.idst_oc OR ctm.idst_member = ogc.idst_ocd) "
				."WHERE ogc.idOrg = " . (int)$id_org . " AND cte.type_of_entry = 'course' AND cor.subscribe_method = 9 " . $whrExp
				."ORDER BY cor.code";
		
		
		//Lancio la query
		$result = sql_query($query);
				
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['idCourse']] = $row;
		}
		
		return $res;
	}
	
	
	public function getOrgInfoByUser($id_user, $lev_org_chart = 1){
		//>> Restituisce le informazioni sul nodo organizzativo di appartenenza
		//>> Sia di quello assegnato, sia quello parent di primo livello. 
		//>> Nel caso si voglia un livello differente è possibile assegnare un valore diverso all'argomento $lev_org_chart
		
		$res = null;
		
		if(is_numeric($lev_org_chart) && $lev_org_chart >= 1){
			
			$query = "SELECT u.idst, u.firstname, u.lastname, op.idOrg AS idOrg_parent, op.code AS code_parent, op.lev AS lev_parent, 
							o.idOrg, o.code, o.lev
						FROM %adm_org_chart_tree o
							JOIN %adm_group_members g ON o.idst_oc = g.idst
							JOIN %adm_org_chart_tree op ON LEFT(o.path, 5+9*".$lev_org_chart.") = op.path
							JOIN %adm_user u ON g.idstMember = u.idst
						WHERE u.idst = '".$id_user."'";
						
			$result = sql_query($query);
					
			$res = sql_fetch_assoc($result);
				
		}
		
		//restituisco la riga
		return $res;
	}
	
	
	public function getUserReferent($id_user, $id_course, $id_edition = false) {
	   	//>> Restituisce stringhe in array con gli id e gli indirizzi email degli utenti referenti:
	   	//>> Manager delle assegnazioni [0] e Referenti corso [1].
	   	
		require_once(_lms_.'/lib/lib.course.php');
		$res = array();
		
		// 1. Info utente
		$id_org = $this->getOrgInfoByUser($id_user)['idOrg_parent'];
		
		
		// 2. Manager assegnazione
		$manager_info = $this->getAssnManager($id_user, $id_course, $id_edition);
		
		if ($manager_info) {
			$res['idst'][0] = $manager_info['idst'];
			$res['email'][0] = $manager_info['email'];
		} else {
			$res['idst'][0] = "";
			$res['email'][0] = "";
		}
		
		// 3. Referenti corso
		$str_emails = "";
		$str_idst = "";
		
		$course_details = Man_Course::getCourseInfo($id_course);
		$id_referents = $this->acl_man->getGroupUMembers($course_details['id_fncrole_ref']);
		$referents_info = $this->acl_man->getUsers($id_referents);
				
		foreach ($referents_info as $k => $info) {
			
			$reforg_info = $this->getOrgInfoByUser($info[ACL_INFO_IDST]);
			
			if($id_org == $reforg_info['idOrg_parent']) {
				// Recupero id ed e-mail referente corso se appartiene alla stessa organizzazione dell'utente
				
				$str_idst .= $info[ACL_INFO_IDST]." ";
				$str_emails .= $info[ACL_INFO_EMAIL]." ";	
			}
		}
		
		$res['idst'][1] = trim($str_idst);
		$res['email'][1] = trim($str_emails);
	
		// Out
		return $res;
	}
	
	
	public function getOrgInfoByLevel($lev_org_chart = 1){
		//>> Restituisce le informazioni sui nodi organizzativi di un dato livello
		
		if(is_numeric($lev_org_chart) && $lev_org_chart >= 1){
			
			$query = "SELECT *
						FROM %adm_org_chart_tree o
						WHERE o.lev = ".(int)$lev_org_chart. "
						ORDER BY o.code";
						
			$result = sql_query($query);
			
			$res = array();
			
			while($row = sql_fetch_assoc($result))
			{
				//restituisco la riga all'array di risposta
				$res[] = $row;
			}	
		}
		
		return $res;
	}
	
	public function getFilterExpression() {
		//>> Restituisce una stringa where per le ricerche sulla query delle assegnazioni
		//>> Eseguire il replace del tag [@filter_text]
		
		return "(usr_u.userid LIKE '%[@filter_text]%' OR usr_u.firstname LIKE '%[@filter_text]%' OR usr_u.lastname LIKE '%[@filter_text]%')"; 
	}
	
	
	public function updGapStatus($id_assn = false) {
		//>> Aggiorna lo stato dei gap.
		//>> Se id_assn è valorizzato, viene aggiornato solo il gap collegato
		require_once(_lms_.'/lib/lib.gap.php');
		
		$gap_man = new GapManager();
		$id_gap = false;
		
		// Recupero il gap, se richiesto
		if ($id_assn)
			$id_gap = $this->getSimpleAssn($id_assn)['id_gap'];
			
		// Aggiorno
		$gap_man->updGapStatusRecalc($id_gap);
	}
	
}

?>
