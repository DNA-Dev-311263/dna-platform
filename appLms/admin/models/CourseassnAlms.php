<?php defined("IN_FORMA") or die("Direct access is forbidden");

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|	BKO: by ABR													            |
\ ======================================================================== */

use Formalms\lib\Get;

define('_MISSING_INFO', "MISSING_INFO");
define('_ORG_NOT_MATCH', "ORG_NOT_MATCH");
define('_COURSE_NOT_REG', "COURSE_NOT_REG");
define('_ASSIGNMENT_EXISTS', "ASSIGNMENT_EXISTS");
define('_ASSIGNMENT_DUPLICATE', "ASSIGNMENT_DUPLICATE");
define('_ASSIGNMENT_NOT_FOUND', "ASSIGNMENT_NOT_FOUND");
define('_USER_STATUS_SUSPEND', "USER_STATUS_SUSPEND");

define('_OP_INS', "INS");
define('_OP_UPD', "UPD");

Class CourseassnAlms extends Model
{
	
	protected $courseassn_man;
	protected $acl_man;
	protected $id_org;
	protected $id_user;


	public function __construct($id_org = 0, $id_user = 0) {
		
		//require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.courseassn.php');

		$this->acl_man =& Docebo::user()->getAclManager();
		
		$this->courseassn_man = new CourseassnManager();  	//ABR: manager corsi assegnazioni
		
		//>> $id_user è l'id dell'operatore che sta caricando/visualizzando i dati.
		//>> $id_org l'organizzazione di lavoro (se è godadmin può non essere la sua).
		
		$this->id_user = $id_user;
		$this->id_org = $id_org;
		
	}

	public function getPerm(){
		//>> Usata per restituire quali "check-box" proporre sul profilo amministratori (permessi)
		
		return array(
			'view' => 'standard/view.png',
			'mod' => 'standard/edit.png'
		);
	}


	public function getIdOrg(){
		
		return $this->id_org;
	}
	
	
	public function loadAssn($year, $id_fncrole_ref, $start_index, $results, $sort, $dir, $filter_text = false){	
		//>> Restituisce le assegnazioni
		
		$orderExp = false;
		$limitExp = false;
	
		//Formo espressione filtro
		$whrExp = "idOrg = ".(int)$this->id_org;
		
		if($year > 0) 
			$whrExp .= " AND YEAR(date_ins) = ".$year;
			
		if($id_fncrole_ref > 0) 
			$whrExp .= " AND id_fncrole_ref = ".$id_fncrole_ref;
			
		if($filter_text)
			$whrExp .= " AND ".str_replace("[@filter_text]", $filter_text, $this->courseassn_man->getFilterExpression());
			
			
		//Formo espressione ordinamenti
		switch ($sort){
			case 'date_ins':
				$orderExp = $sort." ".$dir.", user_fullname, status ".$dir;
			break;

			case 'date_upd':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;
			
			case 'user_userid':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", status ".$dir;
			break;

			case 'user_fullname':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", status ".$dir;
			break;

			case 'manager_fullname':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;

			case 'modifier_fullname':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;
				
			case 'loader_fullname':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;
				
			case 'course_fullname':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;
			
			case 'course_type':
				$orderExp = $sort." ".$dir.", course_fullname ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;
			
			case 'status':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname ".$dir;
			break;
			
			default:
				$orderExp = $sort." ".$dir.", status, id_assn";
		}

		//Formo espressione limit
		($start_index === false ? '' : $limitExp = $start_index.", ".$results);
		
		//Lancio il metodo 
		$res = $this->courseassn_man->getAssn($whrExp, $orderExp, $limitExp);

		//Output
		return $res;
	}
	
	
	public function getAssnActive($id_org, $year = false) {
		//>> Restituisce le assegnazioni aperte di una data organizzazione
		
		//Argomenti di chiamata
		$status = _ASSN_STATUS_ACTIVE;
		$date_to = false;
		$date_from = false;
		
		if($year > 0) {
			$date_from = $year.'-01-01';
			$date_to = $year.'-23-31';		
		}
			
		//Lancio il metodo 
		$res = $this->courseassn_man->getAssnByStatus($id_org, $status, $date_from, $date_to);

		//Output
		return $res;
	}
	
	
	public function getOrgForDropdown(){
		//>> Usata per restituire le organizzazioni da inserire in combo
		
		$res = array();
		$org = $this->getOrgInfoByLevel();
			
		foreach($org as $k => $v) 
			$res[$v['idOrg']] = $v['code'];
		
		return $res;	
	}
	
	
	public function getStatusForDropdown(){
		//>> Usata per restituire gli stati delle assegnazioni
		
		return $this->courseassn_man->getStatusForDropdown();
	}
	
	
	public function getCourseCatalogOrg() {
		//>> Restituisce i corsi del catalogo assegnati all'organizzazione
		
		$retVal = $this->courseassn_man->getCourseCatalogOrg($this->id_org);
		
		//Out
		return $retVal;
	}


	public function delAssn($id_assn){
		//>> Elimina l'assegnazione

		$retVal = $this->courseassn_man->delAssn($id_assn);
		
		//Out
		return $retVal;
	}
	
	
	public function cancelActiveAssn($year, $id_org = false) {
		//>> Annulla le assegnazioni ancora aperte
		
		// Recupero id_org se non è passato in argomento
		$id_org = ($id_org ? $id_org : $this->id_org);
		
		// Lancio il metodo
		return $this->courseassn_man->cancelActiveAssn($year, $id_org);
	}
	
	
	public function cancelAssnByUser($users, $reason = 'user suspended') {
		//>> Annulla le assegnazioni aperte/in preparazione per gli utenti passati in argomento
		//>> e rimuove le iscrizioni per le edizioni aperte
		
		// file model iscrizioni
		require_once(_lms_.'/admin/models/SubscriptionAlms.php');
		
		$subs_model = new SubscriptionAlms();
		$arr_assn = array();
		
		// Recupero assegnazioni attive
		$assn = $this->courseassn_man->getAssnByUser($users);
		

		// Rimuovo iscrizioni
		foreach ($assn as $row) {
			
			// Recupero id Assegnazione
			$arr_assn[] = $row['id_assn'];
			
			// Se non c'è l'iscrizione, passo al prossimo
			if (!$row['id_edition']) continue;
			
			// Recupero info assegnazione
			$id_user 	= $row['id_user'];
			$id_course 	= $row['id_entry'];

			$id_edition = ($row['course_type'] == 'edition' ? $row['id_edition'] : 0);
			$id_date 	= ($row['course_type'] == 'classroom' ? $row['id_edition'] : 0);
			
			$edition_finished = $subs_model->checkEditionFinished($id_date, $id_edition);
			
			// Rimuovo iscrizione se l'edizione è aperta
			if(!$edition_finished)
				$subs_model->unsubscribeUser($id_user, $id_course, $id_edition, $id_date);
			
		}
		
		// Annullo assegnazioni
		return $this->courseassn_man->cancelAssn($arr_assn, $reason);
		
	}
	
	
	public function updAssnStatusById($id_assn, $status){
		//>> Aggiorna lo stato dell'asssegnazione in base all'id
		
		$retVal = $this->courseassn_man->updAssnStatusById($id_assn, $status);
		
		//Out
		return $retVal;
	}
	
	
	public function getAssnNumber($year = false, $id_fncrole_ref = false, $id_org = false, $filter_text = false){
		//>> Restituisce il numero di assegnazioni in base ai criteri in argomento
		
		$whrExp = false;
		
		// Recupero id_org se non è passato in argomento
		$id_org = ($id_org ? $id_org : $this->id_org);
		
		// Formo espressione where se è in corso una ricerca
		if($filter_text)
			$whrExp = str_replace("[@filter_text]", $filter_text, $this->courseassn_man->getFilterExpression());
			
		
		// Recupero il numero delle assegnazioni in base agli argomenti
		$retVal = $this->courseassn_man->getAssnNumber($id_org, $year, $id_fncrole_ref, $whrExp);
		
		// Out
		return $retVal;	
	}


	public function getOrgInfoByUser($id_user = false, $lev_org_chart = 1){
		//>> Restituisce le informazioni sul nodo organizzativo di appartenenza
		
		// Recupero id_user se non è passato in argomento
		$id_user = ($id_user ? $id_user : $this->id_user);
		
		// Recupero il nodo organizzativo
		$retVal = $this->courseassn_man->getOrgInfoByUser($id_user, $lev_org_chart);
		
		// Out
		return $retVal;	
	}
	
	
	public function getOrgInfoByLevel($lev_org_chart = 1){
		//>> Restituisce le informazioni sui nodi organizzativi di un dato livello
		
		return $this->courseassn_man->getOrgInfoByLevel($lev_org_chart);
	}
	

	public function getAssnTempInvalid($type_check){
		//>> Restituisce le righe caricate non valide per l'importazione
		
		$id_org		= (int) $this->id_org;
		$id_user	= (int) $this->id_user;
		
		$res = array();
				
		// Preparo where	
		switch ($type_check) {
			case _MISSING_INFO:
				
				$whrExp = "	( "
						."	IFNULL(user_userid,'') = '' OR IFNULL(user_lname,'') = '' OR IFNULL(user_email,'') = '' OR "
						."	IFNULL(manager_userid,'') = '' OR IFNULL(manager_lname,'') = '' OR IFNULL(manager_email,'') = '' OR "
						."	IFNULL(org_code,'') = '' OR  IFNULL(course_code,'') = '' "
						."	) ";
			
			break;
			case _ORG_NOT_MATCH:
			
				$whrExp = "org_code NOT IN (SELECT code FROM %adm_org_chart_tree WHERE lev = 1 AND idOrg = ".$id_org.") ";
			
			break;
			case _COURSE_NOT_REG:
				
				$courses = $this->getCourseCatalogOrg();	
				
				if(count($courses) > 0)
					$whrExp = "IFNULL(id_course, 0) NOT IN (".implode(", ", array_keys($courses)).") ";
				else
					$whrExp = "1";
			
			break;
			case _ASSIGNMENT_EXISTS:
			
				$whrExp = "CONCAT(T.id_user, T.id_course) IN (SELECT CONCAT(id_user, id_entry) FROM %lms_assignment "
						 ."WHERE type_entry = 'course' AND id_user <> 0 AND id_entry <> 0 AND status IN ("._ASSN_STATUS_PREPARATION.", "._ASSN_STATUS_ACTIVE.")) ";
			
			break;
			case _ASSIGNMENT_DUPLICATE:
		
				$whrExp = " (SELECT COUNT(*) FROM %lms_assignment_temp	
								WHERE id_user = T.id_user AND id_course = T.id_course 				
									AND user_ins = T.user_ins AND id_org = T.id_org AND file_row < T.file_row)  > 0 ";
									
			break;			 
			case _ASSIGNMENT_NOT_FOUND:
			
				$whrExp = "IFNULL(CONCAT(T.id_user, T.id_course), '') NOT IN (SELECT CONCAT(id_user, id_entry) FROM %lms_assignment "
						 ."WHERE type_entry = 'course' AND status = "._ASSN_STATUS_ACTIVE.") ";
			
			break;
			case _USER_STATUS_SUSPEND:
			
				$whrExp = "T.id_user IN (SELECT idst FROM %adm_user WHERE valid = 0) ";
			
			break;
		}
		
		// Preparo la stringa SQL
		$query = "SELECT T.* , '".$type_check."' AS TRec "
				." FROM %lms_assignment_temp T "
				." WHERE T.user_ins = ".$id_user." AND T.id_org = ".$id_org." AND ". $whrExp
				." ORDER BY T.file_row";
				
	
		// Lancio la query
		$result = sql_query($query);
		
				
		while($row = sql_fetch_assoc($result))
		{
			// Restituisco la riga all'array di risposta
			$res[$row['id_assn_tmp']] = $row;
		}
		
		return $res;
	}
	
	
	public function insAssnTemp($arr_data, $operation) {
		//>> Inserisce i dati nella tabella delle assegnazioni temporanea
		//>> eliminando prima i precedenti record caricati dall'utente.
		//>> Restituisce null se l'inserimento completo non è andato a buon fine,
		//>> 0 se non ci sono dati da inserire o il numero di record inseriti.
		//>> $arr_data: dati da inserire. $operation: costante tipo operazione 
		
		// Recupero il manager
		$ca_man = $this->courseassn_man;


		// Elimino i record caricati in precedenza e preparo variabile di ritorno record inseriti
		// Non è ammesso operare contemporaneamente su ins e upd, quindi elimino tutto.
		if (($ca_man->delAssnTemp($this->id_user, $this->id_org)) !== false) $res = 0;
		
		
		if (count($arr_data) > 0 && $res === 0) {
			// Se ci sono dati e l'eliminazione è andata a buon fine, inserisco
		
			foreach ($arr_data as $index => $record) {
				
				// Aggiungo codice operazione
				$record['operation'] = $operation;
				
				// Inserisco record
				$affected = $ca_man->insAssn($record, true, ($res == 0));
				
				// Conteggio
				if($affected){
					$res += 1;
					
				}else{
					$res = $affected;
					break;
				}
			}
			
		}
		
		return $res;
			
	}
	
	
	public function getAssnTmpLoad($suspended = false) {
		//>> Restituisce le assegnazioni temporanee caricate per l'utente e organizzazione di lavoro
		//>> Se suspended = true restituisce solo quelle sospese, altrimenti quelle non sospese
		
		// Recupero il manager
		$ca_man = $this->courseassn_man;		
		
		
		// Lancio il metodo e restituisco il risultato
		$whrExp = (!$suspended ? 'suspended = 0' : 'suspended = 1');

		return $ca_man->getAssnTemp($this->id_user, $this->id_org, $whrExp);

	}
	
	
	public function getAssnTempInvId($type_checks) {
		//>> Restituisce un array con gli Id delle assegnazioni non valide della tabella di appoggio.
		//>> $type_checks è un array di costanti check
		
		$arr_res = array();
		

		// Recupero le assegnazioni non valide
		foreach ($type_checks as $check) {
			$arr_inv[$check] = $this->getAssnTempInvalid($check);
		}
							
		// Unisco le risposte in un unico array (in caso di stessa chiave, l'elemento di $arr_tmp non viene aggiunto)
		foreach ($arr_inv as $arr_tmp) {
			$arr_res = $arr_res + $arr_tmp;
		}
		
		// Recupero solo la chiave (id dell'assegnazione temporanea)
		$arr_res = array_keys($arr_res);
		
		return $arr_res;
		
	}
	
	
	public function insAssnFromTemp(&$ret_id) {
		//>> Inserisce le assegnazioni dalla tabella delle assegnazioni temporanee caricate
		//>> Tralascia le assegnazioni già eventualmente caricate (stesso utente, codice corso e stato assegnazione non aperta)
		//>> suspended in ogni caso esclude tutte le assegnazioni che non hanno passato il controllo.
		//>> ret_id riporta al chiamante gli id delle nuove assegnazioni inserite.
		
		$ret_id = array();
		$db = DbConn::getInstance();
		
		// Preparo la query
		$query = " INSERT INTO %lms_assignment ( "
				."			id_entry, type_entry, id_user, status, fav_location, user_ins, user_upd, desc_upd, id_manager, "
				."			date_ins, date_upd, description) "
				." SELECT t.id_course, 'course', t.id_user, "._ASSN_STATUS_ACTIVE.", t.fav_location, t.user_ins, t.user_ins, 'Assignment entered', t.id_manager, "
				." 		NOW(), NOW(), CONCAT(IFNULL(t.user_fname,''), ' ', t.user_lname , ' - ', t.course_code, ' - ', CURDATE()) "
				." FROM %lms_assignment_temp t "
				." 		LEFT JOIN %lms_assignment a ON (t.id_user = a.id_user AND t.id_course = a.id_entry AND a.type_entry = 'course') "
				." WHERE (a.id_assn IS NULL OR a.status NOT IN("._ASSN_STATUS_PREPARATION.", "._ASSN_STATUS_ACTIVE.")) AND t.suspended = 0 "
				."		AND t.id_user > 0 AND t.user_ins = '".$this->id_user."' AND id_org = '".$this->id_org."' AND operation = '"._OP_INS."'";


		// Lancio la query
		$res = $db->query($query);
		
		// Recupero il numero di inserimenti
		$retVal = $db->affected_rows($res);
		
		// Recupero gli id inseriti
		if ($retVal) {
			$res = $db->query("SELECT id_assn FROM %lms_assignment WHERE id_assn >= LAST_INSERT_ID()");
			
			while($row = sql_fetch_assoc($res))
				$ret_id[] = $row['id_assn'];
		}
		
		// Out
		return $retVal;
	}
	
	
	public function updAssnFromTemp() {
		//>> Aggiorna le assegnazioni in base alla tabella delle assegnazioni temporanee caricate.
		//>> Al momento è previsto solo l'aggiornamento del responsabile.
		//>> Tralascia le assegnazioni chiuse.
		
		// Preparo la query
		$query = " UPDATE %lms_assignment a "
				."		INNER JOIN %lms_assignment_temp t ON (a.id_user = t.id_user AND a.id_entry = t.id_course AND a.type_entry = 'course') "
				." SET a.id_manager = t.id_manager "
				." WHERE a.status = "._ASSN_STATUS_ACTIVE." AND t.suspended = 0 "
				."		AND t.id_user > 0 AND t.user_ins = '".$this->id_user."' AND id_org = '".$this->id_org."' AND operation = '"._OP_UPD."'";


		// Lancio la query e restituisco il numero di righe aggiornate
		$db = DbConn::getInstance();
		$res = $db->query($query);
		
		return $db->affected_rows($res);
	}
	
	
	public function updSuspendAssnTempInvalid($type_checks) {
		//>> Sospende le assegnazioni temporanee non valide.
		
		// Recupero la chiave delle assegnazioni non valide
		$arr_inv = $this->getAssnTempInvId($type_checks);
				
		if(count($arr_inv) > 0){
			// Preparo la query
			$query = "UPDATE %lms_assignment_temp SET suspended = 1 "
					."WHERE id_assn_tmp IN (".implode(', ', $arr_inv).")";
					

			// Lancio la query
			return sql_query($query);
		}
		
	}
	
	
	public function updCourseId() {
		//>> Aggiorna gli 'id_course' delle assegnazioni temporanee in fase di importazione

		// Preparo la query
		$query = "UPDATE %lms_assignment_temp a "
				."INNER JOIN %lms_course c ON a.course_code = c.code "
				."SET a.id_course = c.idCourse "
				."WHERE suspended = 0 AND user_ins = '".$this->id_user. "' AND id_org = '".$this->id_org."'";
				
		// Lancio la query
		return sql_query($query);		
		
	}
	
	
	public function updUserFromTemp() {
		//>> Aggiorna alcuni campi degli utenti assegnatari in base alle info della tabella temporanea
		//>> Tempo disponibile, sede e e-mail.
		
		// Query aggiornamento utente normale
		$query = "UPDATE %adm_user u "
				."INNER JOIN %lms_assignment_temp a ON u.idst = a.id_user "
				."SET u.email = a.user_email, u.time_availability = a.time_availability, u.job_location = a.job_location "
				."WHERE operation = '"._OP_INS."' AND a.user_ins = '".$this->id_user."'";
		
		$res = sql_query($query);		
		
		// Query aggiornamento utente manager
		$query = "UPDATE %adm_user u "
				."INNER JOIN %lms_assignment_temp a ON u.idst = a.id_manager "
				."SET u.email = a.manager_email "
				."WHERE operation = '"._OP_INS."' AND a.user_ins = '".$this->id_user."'";
	
		$res *= sql_query($query);
		
		return $res;
	}
	
	
	public function updUserAssnId() {
		//>> Aggiorna gli 'id_user' degli utenti in fase di importazione nella tabella delle assegnazioni temporanee
		
		// Preparo la query
		$query = "UPDATE %lms_assignment_temp a "
				."LEFT JOIN %adm_user u ON CONCAT('/', a.user_userid)= u.userid "
				."LEFT JOIN %adm_user m ON CONCAT('/', a.manager_userid) = m.userid "
				."SET a.id_user = u.idst, a.id_manager = m.idst "
				."WHERE a.id_assn_tmp > 0 AND suspended = 0 AND user_ins = '".$this->id_user. "' AND id_org = '".$this->id_org."'";

		// Lancio la query
		return sql_query($query);
	}
	
	
	public function getAssnYearMin() {
		//>> Restituisce l'anno di inserimento meno recente della tabella delle assegnazioni
		$query = "SELECT MIN(YEAR(date_ins)) AS year FROM %lms_assignment";

		
		list($res) = sql_fetch_row(sql_query($query));
		
		return $res;
	}
	
	
	public function checkIsSubscibed($id_assn) {
		//>> Restituisce se l'assegnazione ha un'iscrizione
		return $course_man->checkIsSubscibed($id_assn);
	}
	
	
	public function getNewUserAssn($type_user = 'user') {
		//>> Restituisce gli utenti delle assegnazioni temporanee (userid) non ancora presenti nel sistema ($type_user = 'user').
		//>> Oppure i manager non ancora presenti a sistema.
		

		// Controllo l'argomento
		
		if(!($type_user == 'user' || $type_user == 'manager')) return;
		
		// Preparo la stringa SQL
		$tus = $type_user;
		
		$fields = $tus."_userid AS userid, "
				. $tus."_fname  AS fname, "
				. $tus."_lname  AS lname, "
				. $tus."_email  AS email"
				. ($type_user == 'user' ? ", a.time_availability, a.job_location" : "");		

		
		$query = "SELECT DISTINCT a.".$fields.", '".$tus."' AS TRec "
				."FROM %lms_assignment_temp a "
				."	LEFT JOIN %adm_user u ON CONCAT('/', a.".$tus."_userid) = u.userid "
				."WHERE a.suspended = 0 AND a.user_ins = '".$this->id_user."' AND a.id_org = '".$this->id_org."' AND u.idst IS NULL "
				."	AND IFNULL(".$tus."_userid,'') <> '' AND IFNULL(".$tus."_lname,'') <> '' AND IFNULL(".$tus."_email,'') <> '' ";
		
		
		// Lancio la query
		$result = sql_query($query);
		
				
		while($row = sql_fetch_assoc($result))
		{
			// Restituisco la riga all'array di risposta
			$res[$row['userid']] = $row;
		}
		
		// Output
		return $res;		
		
	}
	
	
	public function getAssnTempManager() {
		//>> Recupera gli id dei manager delle assegnazioni temporanee
			
			$managers 	= array();
			
			// Recupero le assegnazioni
			$assn = $this->getAssnTmpLoad(false);
			
			// Recupero i manager
			foreach ($assn as $record) {
				
				if ($record['id_manager'])
					$managers[] = $record['id_manager'];
			}	
				
			return $managers;
	}
	
	
	public function assignManagerRole($arr_idst, $role_desc = '') {
		//>> Aggiunge gli utenti dell'array al ruolo passato in argomento

		require_once(_adm_.'/models/FunctionalrolesAdm.php');
		
		$f_man = new FunctionalrolesAdm();
		$retVal = false;
		
		
		// Recupero il ruolo se questo esiste
		if($role_desc != '') {
			$filter['text'] = $role_desc;
			$id_fncroles = $f_man->selectAllFunctionalRoles($filter);
		}
			
		if (count($id_fncroles) == 1) {
			
			$id_fncrole = $id_fncroles[0];
			
			// Recupero gli utenti del ruolo
			$members = $f_man->getMembers($id_fncrole);
					
			// Recupero gli utenti da aggiungere al ruolo
			$new_members = array_diff($arr_idst, $members);
			
			
			// Aggiungo i nuovi membri al gruppo
			foreach ($new_members as $idst)
				$retVal = $f_man->assignMembers($id_fncrole, array($idst));
		
		}
		
		return $retVal;
	}
	
	
	public function createUsers($users, $send_alert = true, $force_change = true) {
		//>> Crea nuovi utenti in base ai valori passati in argomento
		//>> e li aggiunge al ruolo se questo esiste
		//>> $users è un array con chiavi 'userid', 'fname', 'lname', 'email'
		
		require_once(_adm_.'/models/UsermanagementAdm.php');
		require_once(_base_.'/lib/lib.eventmanager.php');
		
		$u_man = new UsermanagementAdm();
		
		
		// Ciclo sugli utenti
		foreach($users as $user) {
						
			//creazione oggetto dati utente
			$userdata = new stdClass();
			$userdata->userid = 	addslashes(trim($user['userid']));
			$userdata->firstname = 	addslashes(trim($user['fname']));
			$userdata->lastname = 	addslashes(trim($user['lname']));
			$userdata->email = 		addslashes(trim($user['email']));
			$userdata->time_availability = strtolower(trim($user['time_availability']));
			$userdata->job_location = trim($user['job_location']);
			$userdata->password = substr("000".($this->uniord(mb_substr($user['lname'],0,1)) + 3).((($this->uniord(mb_substr($user['userid'],-1, 1)) + 1) + $this->uniord(mb_substr($user['lname'],0,1))) * $this->uniord(mb_substr($user['lname'],-1,1)) + strlen($user['email'])),-8,8);
			$userdata->force_change = (bool)$force_change;
			$userdata->level = ADMIN_GROUP_USER;
			

			// Controllo se l'utente già esiste
			if (!$u_man->checkUserid($userdata->userid)) {
				continue;
			}
			
			// Imposto organizzazione di appartenenza
			$folders = array($this->id_org => $this->id_org);
			
			// Creo utente
			$idst = $u_man->createUser($userdata, $folders);
			
			if (is_numeric($idst) && $idst>0) {
				// Se l'inserimento è riuscito
				// Invio alert se richiesto
				if($send_alert) {
					
					$e_msg = new EventMessageComposer();
					
					$array_subst = array('[url]' => Get::sett('url'), '[userid]' => $userdata->userid, '[password]' => $userdata->password);
					
					$e_msg->setSubjectLangText('email', '_REGISTERED_USER_SBJ', false);
					$e_msg->setBodyLangText('email', '_REGISTERED_USER_TEXT', $array_subst );
					$recipients = array($idst);

					createNewAlert('UserNew', 'directory', 'edit', '1', 'New user created', $recipients, $e_msg, true);
					
				}
			
				//Inserisco id utente in array di ritorno
				$res[] = $idst;
			}
		}
		
		//Output
		return $res;
	}
	
	
	public function getAssnId($year = false, $id_org = false, $filter_text = false) {
		//>> Restituisce tutti gli id delle assegnazioni in base ad alcuni criteri filtro
		
		$result = array();
				
		// Recupero id_org se non è passato in argomento
		$id_org = ($id_org ? $id_org : $this->id_org);
		
		// Formo espressione filtro 
		$whrExp = "idOrg = ".(int)$id_org;
		
		if($year)
			$whrExp .= " AND YEAR(date_ins) = ".(int)$year;
		
		if($filter_text)
			$whrExp .= " AND " . str_replace("[@filter_text]", $filter_text, $this->courseassn_man->getFilterExpression());
		
	
		// Lancio il metodo 
		$res = $this->courseassn_man->getAssn($whrExp);
		
		
		// Recupero solo id
		foreach ($res as $k => $row) {
			$result[] = $row['id_assn'];		
		}
		
		return $result;
	}
	
	
	public function getAssnById($arr_id_assn, $id_org = false) {
		//>> Restituisce le assegnazioni in base agli ID passati in argomento
		
		if (!is_array($arr_id_assn)) return false;
	
					
		//Formo espressione filtro su ID assegnazione
		$whrExp = "id_assn IN (".implode(",", $arr_id_assn).")";
		
		//Formo espressione filtro su organizzazione se passata in argomento
		$whrExp .= ($id_org ? " AND idOrg = ".$id_org: "");

	
		//Lancio il metodo 
		$res = $this->courseassn_man->getAssn($whrExp);
		
		//Output
		return $res;
	}
	

	public function getOwner($id_assn) {
		//>> Restituisce il proprietario dell'assegnazione (idst o id_user)
		
		$retVal = false;
		
		$res = $this->getAssnById(array($id_assn));
		
		if($res) 
			$retVal = reset($res)['id_user'];
			
		return $retVal;
	}


	public function isActiveAlert($className) {
		//>> Restituisce True se event manager è configurato per inviare un alert 
		//>> className è il nome della classe in core_event
		
		$query	= "SELECT permission "
				." FROM %adm_event_class as ec"
				." JOIN %adm_event_manager as em"
				." WHERE ec.idClass = em.idClass AND ec.class = '".$className."'" ;
		
		list($send_alert) = sql_fetch_row( sql_query($query) );
			
		return ($send_alert == "mandatory");
	}


	private function uniord($u) {
		//Restituisce il numero del carattere unicode passato in argomento
		
		$k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
		$k1 = ord(substr($k, 0, 1));
		$k2 = ord(substr($k, 1, 1));
		return $k2 * 256 + $k1;
	} 

}

?>
