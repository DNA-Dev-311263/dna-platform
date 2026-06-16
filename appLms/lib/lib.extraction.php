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


class ExtractionManager
{

	protected $acl_man;
	protected $date_man;
	protected $courseassn_man;

	public function __construct(){
		
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_lms_.'/lib/lib.fund.php');
		require_once(_lms_.'/lib/lib.courseassn.php');
		require_once _adm_ .'/lib/lib.field.php';

		$this->lang = DoceboLanguage::CreateInstance('extraction', 'lms');
		$this->acl_man = Docebo::user()->getAclManager();
		$this->date_man = New DateManager();
		$this->courseassn_man = New CourseassnManager();
	}


	public function __destruct(){
		
	}
	
		
	public function getCalendarCostGroup($date_begin, $date_end, $status, $id_org = false, $id_group = false) {
		//>> Metodo per l'estrazione CalendarCostGroup. 
		//	 Gli importi e il numero di iscrizioni vengono riportate in relazione agli utenti di un gruppo.
		
		$res = array();
		$status_list = $this->getStatusDateDropdown();
				
		//Recupero la stringa SQL
		$query = $this->_getCalendarSql($date_begin, $date_end, $status, $id_org, $id_group, array(9));
				
		//Lancio la query
		$result = sql_query($query);
		
		
		//Recupero il record solo se è del corso aziendale o eseguo esportazione generalizzata
		while($row = sql_fetch_assoc($result))
		{		
				$row['inizio_edizione']	=	$this->_checkDateString($row['inizio_edizione']);
				$row['fine_edizione'] 	=	$this->_checkDateString($row['fine_edizione']);
				$row['stato_edizione'] 	=	$status_list[$row['stato_edizione']];
				$pct					=	$row['utenti_iscritti'] / $row['iscrizioni_complessive'];
				$pct					=	is_nan($pct) ? 0 : $pct;
				$row['percentuale']				=	number_format($pct * 100, 2, ',', '.').'%';
				$row['fattura']			=	number_format($row['costo'] * $pct, 2, ',', '.');
				$row['costo'] 			=	number_format($row['costo'], 2, ',', '.');
							
				$res[] = $row;
		}
		
		//Out
		return $res;
	}
	
	
	public function getCourseActiveUsers($date_begin, $date_end, $id_org = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce gli utenti attivi nei corsi nell'intervallo passato in argomento
		//	 Le date devono essere stringhe già formattate yyyy-mm-dd	
		
		$res = array();
		
		// Recupero gli utenti dell'azienda (idst)
		$users = $this->acl_man->getUsersByOrg($id_org);
		
		//Preparo stringa Where sottoquery di selezione
		$whrExp  = "DATE(dateAttempt) >= '". $date_begin . "' AND  DATE(dateAttempt) <= '". $date_end . "' AND idUser IN ('" .implode("','", $users). "')";	
		
		// Formo la query
		$query = "SELECT u.userid AS cid_utente, u.lastname AS cognome_utente, u.firstname AS nome_utente, u.email, c.name AS nome_corso, o.title AS oggetto_didattico, t.dateAttempt AS data_accesso "
				."	FROM (SELECT idUser, MAX(idTrack) AS idAttempt FROM %lms_commontrack WHERE " .$whrExp. " GROUP BY idUser) AS last "
				."		JOIN %adm_user u ON last.idUser = u.idst "
				."		JOIN %lms_commontrack t ON t.idTrack = last.idAttempt AND t.idUser = last.idUser "
				."		LEFT JOIN %lms_organization o ON o.idOrg = t.idReference "
				."		LEFT JOIN %lms_course c ON o.idCourse = c.idCourse "
				."	ORDER BY userid desc";
				

		// Lancio la query
		$result = sql_query($query);
		
		
		// Recupero il record
		while($row = sql_fetch_assoc($result))
		{
			// Sistemazione valori
			$row['cid_utente']	=  str_replace('/', '', $row['cid_utente']);
			$row['data_accesso'] =  $this->_checkDateString($row['data_accesso']);

			// Memorizzo riga
			$res[] = $row;	
		}
		
		//Out
		return $res;	
		
	}
	
	
	public function getCoursepathProgress($date_begin, $date_end, $id_org, $lang_custom_fields = false) {
		//>> Metodo per l'estrazione dell'avanzamento su percorso formativo
		
		$users = array();
		$custom_fields = array();
		$field_values = array();
		$res = array();
		
		
		// Recupero i campi personalizzati
		if ( is_array($lang_custom_fields) ) {
			$custom_fields = $this->getOrgFieldList($id_org, $lang_custom_fields);
		}
		
		// Recupero gli utenti dell'azienda
		$users = $this->acl_man->getUsersByOrg($id_org);
		
		// Recupero solo percorsi del catalogo)
		$cpath = $this->getCoursepathCatalogOrg($id_org);
		
		// Recupero i valori dei campi utente
		if ( $users && $custom_fields ) {
			$fl = new FieldList();
			//$result[$id_user][$id_field]
			$field_values = $fl->getUsersFieldEntryData( $users, array_keys($custom_fields), true );
		}
		
		// Formo la query
		$query =	"SELECT cp.id_path, cp.path_code as codice_percorso, cp.path_name as nome_percorso, ncs.course_count as corsi, "
					."  cpu.idUser as id_utente, u.userid as username, u.firstname as nome, u.lastname as cognome, u.email, u.lastenter as ultimo_accesso, cpu.date_assign as data_iscrizione, "
					."	IF(ncs.course_count = 0, 0, cpu.course_completed/ncs.course_count*100) AS avanzamento "
					."	FROM %lms_coursepath AS cp "
					."		JOIN (SELECT COUNT(*) AS course_count, id_path FROM %lms_coursepath_courses GROUP BY id_path) AS ncs ON ncs.id_path = cp.id_path "
					."		JOIN %lms_coursepath_user AS cpu ON cpu.id_path = cp.id_path "
					."		JOIN %adm_user AS u ON cpu.idUser = u.idst "
					."	WHERE DATE(cpu.date_assign) >= '". $date_begin . "' AND  DATE(cpu.date_assign) <= '". $date_end . "' AND cp.id_path IN ('" .implode("','", array_keys($cpath)). "') ";
		
		// Lancio la query
		$result = sql_query($query);
		

		// Recupero il record solo se l'utente è dell'organizzazione
		while($row = sql_fetch_assoc($result))
		{
			if( in_array($row['id_utente'], $users) ) {
				
				// Aggiungo campi utente personalizzati alla riga
				foreach ($custom_fields as $id_field => $field) {
					$row[ $field ] = isset( $field_values[$row['id_utente']][$id_field] ) ? $field_values[$row['id_utente']][$id_field] : '';
				}
				
				// Sistemazione valori
				$row['avanzamento'] = round($row['avanzamento']);
				$row['username']	=  str_replace('/', '', $row['username']);
				$row['ultimo_accesso'] =  $this->_checkDateString($row['ultimo_accesso']);
				$row['data_iscrizione'] =  $this->_checkDateString($row['data_iscrizione']);
				
				if (isset($row['Data di nascita'])) $row['Data di nascita'] = $this->_checkDateString($row['Data di nascita']);

				// Memorizzo riga
				$res[] = $row;
			}		
		}
		
		//Out
		return $res;		
	}
	
	
	public function getCalendarCost($date_begin, $date_end, $status, $id_org = false) {
		//>> Metodo per l'estrazione CalendarCost (è la stessa estrazione del CalendarLearning)
		$res = $this->getCalendarLearning($date_begin, $date_end, $status, $id_org);
	
		
		//Out
		return $res;
	}
	
	
	public function getCalendarLearningDay($date_begin, $date_end, $status, $id_org = false) {
		//>> Metodo per l'estrazione CalendarLearning

		$res = array();
		$status_list = $this->getStatusDateDropdown();
		
		//Recupero la stringa SQL
		$query = $this->_getCalendarDaySql($date_begin, $date_end, $status, $id_org);

		//Lancio la query
		$result = sql_query($query);

		//Recupero il record solo 
		while($row = sql_fetch_assoc($result))
		{		
				$row['inizio_edizione']	=  $this->_checkDateString($row['inizio_edizione']);
				$row['fine_edizione']	=  $this->_checkDateString($row['fine_edizione']);
				$row['inizio_lezione'] 	=  $this->_checkDateString($row['inizio_lezione'], 'Y-m-d H:i');
				$row['fine_lezione'] 	=  $this->_checkDateString($row['fine_lezione'], 'Y-m-d H:i');
				$row['stato_edizione'] 	=  $status_list[$row['stato_edizione']];
				
				$res[] = $row;
		}
		
		//Out
		return $res;
	}
	

	public function getCalendarLearning($date_begin, $date_end, $status, $id_org = false) {
		//>> Metodo per l'estrazione CalendarLearning
	
		$res = array();
		$status_list = $this->getStatusDateDropdown();
		
		//Recupero la stringa SQL
		$query = $this->_getCalendarSql($date_begin, $date_end, $status, $id_org, false, array(9));
		
		//Lancio la query
		$result = sql_query($query);

		
		//Recupero il record 
		while($row = sql_fetch_assoc($result))
		{		
				$row['inizio_edizione']	=  $this->_checkDateString($row['inizio_edizione']);
				$row['fine_edizione'] 	=  $this->_checkDateString($row['fine_edizione']);
				$row['stato_edizione'] 	=  $status_list[$row['stato_edizione']];
				$row['costo'] 			=  number_format($row['costo'], 2, ',', '.');
				
				$res[] = $row;
		}
		
		//Out
		return $res;
	}
	
	
	public function getNoShowUser($date_begin, $date_end, $status, $id_org = false) {
		//>> Metodo per l'estrazione NoShowUser
		
		$users = array();
		$res = array();
		
		//Recupero le descrizioni degli stati
		$st_assn_list = $this->getStatusAssnDropdown();
		$st_assn_list[''] = '';

		//Recupero gli utenti dell'azienda se passata in argomento
		if($id_org){
			$users = $this->acl_man->getUsersByOrg($id_org);
		}
		
		//Recupero la stringa SQL
		$query = $this->_getNoShowSql($date_begin, $date_end, $status);

		
		//Lancio la query
		$result = sql_query($query);

		
		//Recupero il record solo se l'assegnazione è di un utente della specifica azienda o eseguo esportazione generalizzata
		while($row = sql_fetch_assoc($result))
		{			
			if(!$id_org || in_array($row['id_utente'], $users)) {
				
				// Adeguo alcuni campi del report
				$row['cid_utente']			=  str_replace('/', '', $row['cid_utente']);		       
				$row['ultima_edizione']	=  $this->_checkDateString($row['ultima_edizione']);
				$row['stato_assegnazione'] 	=  $st_assn_list[$row['stato_assegnazione']];
				
				// Recupero la riga modificata
				$res[] = $row;
			}
		}
		
		//Out
		return $res;
	}
	
	
	public function getUserAssnStatus($date_begin, $date_end, $status, $id_org = false) {
		//>> Metodo per l'estrazione UserAssnStatus
		
		$users = array();
		$res = array();
		
		//Recupero le descrizioni degli stati
		$st_assn_list = $this->getStatusAssnDropdown();
		$st_date_list = $this->getStatusDateDropdown();
		
		//Recupero gli utenti dell'azienda se passata in argomento
		if($id_org){
			$users = $this->acl_man->getUsersByOrg($id_org);
		}
		
		//Recupero la stringa SQL
		$query = $this->_getUserAssnSql($date_begin, $date_end, $status);

		
		//Lancio la query
		$result = sql_query($query);

		
		//Recupero il record solo se l'assegnazione è di un utente della specifica azienda o eseguo esportazione generalizzata
		while($row = sql_fetch_assoc($result))
		{			
			if(!$id_org || in_array($row['id_utente'], $users)) {
				
				// Adeguo alcuni campi del report
				$row['cid_utente']			=  str_replace('/', '', $row['cid_utente']);		       
				$row['data_assegnazione']	=  $this->_checkDateString($row['data_assegnazione']);
				$row['inizio_edizione']		=  $this->_checkDateString($row['inizio_edizione']);
				$row['fine_edizione'] 		=  $this->_checkDateString($row['fine_edizione']);
				$row['data_iscrizione'] 	=  $this->_checkDateString($row['data_iscrizione']);
				$row['fine_partecipazione'] =  $this->_checkDateString($row['fine_partecipazione']);
				$row['stato_assegnazione'] 	=  $st_assn_list[$row['stato_assegnazione']];
				$row['stato_edizione'] 		=  $st_date_list[$row['stato_edizione']];
				
				// Recupero la riga modificata
				$res[] = $row;
			}
		}
		
		//Out
		return $res;
	}
	
	
	private function _checkDateString($dateString, $format = 'Y-m-d'){
		//>> Controlla la data stringa passata in argomento. 
		//	 Se il valore è nullo viene restituito stringa vuota, 
		//	 altrimenti viene restituita nel formato passato in argomento
		
		$res = "";
		
		if(!empty($dateString)) $res = date($format, strtotime($dateString));
		
		return $res;
		
	}

	
	private function _getUserAssnSql($date_begin, $date_end, $status = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce la stringa SQL per il recupero delle assegnazioni con edizioni collegate
		//	 Le date devono essere stringhe già formattate yyyy-mm-dd	
		
		//Preparo stringa Where
		$whrExp  = "DATE(A.date_ins) >= '". $date_begin . "' AND  DATE(A.date_ins) <= '". $date_end . "'";
			
		if($status)
			$whrExp  .= " AND  A.status = ".(int)$status;
		
		//Preparo stringa ordinamenti
		if(is_string($orderExp))
			$orderExp = ' ORDER BY '.$orderExp;
		
		//Preparo stringa limit
		if(is_string($limitExp))
			$limitExp = ' LIMIT '.$limitExp;
			
		$query = "	SELECT	A.date_ins AS data_assegnazione, U.idst AS id_utente, U.userid AS cid_utente, LOWER(U.email) AS email, U.lastname AS cognome, U.firstname AS nome, 
							C.code AS codice_corso, C.name AS nome_corso, DT.code AS codice_edizione, DT.status AS stato_edizione, DY.dt_begin AS inizio_edizione, 
							DY.dt_end AS fine_edizione, DU.date_subscription AS data_iscrizione, IF(A.status=2,DY.dt_end,'') AS fine_partecipazione, A.status AS stato_assegnazione
					FROM 	%lms_assignment A JOIN
							%adm_user U ON A.id_user = U.idst JOIN
							%lms_course C ON (A.id_entry = C.idCourse AND A.type_entry = 'course') LEFT JOIN
							%lms_course_date DT ON (A.id_edition = DT.id_date) LEFT JOIN
							%lms_course_date_user DU ON (A.id_user = DU.id_user AND DT.id_date = DU.id_date) LEFT JOIN
							(SELECT id_date, MIN(date_begin) AS dt_begin, MAX(date_end) AS dt_end FROM %lms_course_date_day WHERE deleted = 0 GROUP BY id_date) AS DY ON DT.id_date = DY.id_date LEFT JOIN
							(SELECT id_date, id_user, MAX(day) AS dt_last_presence FROM %lms_course_date_presence WHERE presence = 1 GROUP BY id_date, id_user) AS DP ON (DT.id_date = DP.id_date AND U.idst = DP.id_user)
					WHERE 	". $whrExp . $orderExp . $limitExp;
					
		return $query;
		
	}
	
	
	private function _getNoShowSql($date_begin, $date_end, $status = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce la stringa SQL per il recupero degli utenti no-show
		//	 Le date devono essere stringhe già formattate yyyy-mm-dd
		
		$havingExp = false;
		
		//Preparo stringa Where
		$whrExp  = " AND DATE(dt_begin) >= '". $date_begin . "' AND  DATE(dt_end) <= '". $date_end . "'";
		
		//Preparo stringa Having
		if($status)
			$havingExp = ' HAVING A.status = '.(int)$status;
		
		//Preparo stringa ordinamenti
		if(is_string($orderExp))
			$orderExp = ' ORDER BY '.$orderExp;
		
		//Preparo stringa limit
		if(is_string($limitExp))
			$limitExp = ' LIMIT '.$limitExp;
			
			
		$query = "	SELECT	U.idst AS id_utente, U.userid AS cid_utente, LOWER(U.email) AS email, U.lastname AS cognome, U.firstname AS nome,
							C.code AS codice_corso, C.name AS nome_corso, MAX(dt_begin) AS ultima_edizione, A.status AS stato_assegnazione, COUNT(DT.id_date) AS no_show
					FROM	%lms_course_date DT JOIN
							%lms_course C ON DT.id_course = C.idCourse JOIN
							%lms_course_date_user DU ON DT.id_date = DU.id_date JOIN
							%adm_user U ON DU.id_user = U.idst JOIN
							(SELECT id_date, MIN(date_begin) AS dt_begin, MAX(date_end) AS dt_end FROM %lms_course_date_day WHERE deleted = 0 GROUP BY id_date) AS DY 
								ON DT.id_date = DY.id_date JOIN
							(SELECT id_date, id_user FROM %lms_course_date_presence GROUP BY id_date, id_user HAVING  SUM(presence) = 0) AS NS 
								ON (DU.id_user = NS.id_user AND DU.id_date = NS.id_date) LEFT JOIN
							(SELECT MAX(id_assn) AS id_assn, id_entry, id_user FROM %lms_assignment WHERE type_entry = 'course' GROUP BY id_entry, id_user) LA
								ON (DT.id_course = LA.id_entry AND DU.id_user = LA.id_user) LEFT JOIN
							%lms_assignment A ON LA.id_assn = A.id_assn 
					WHERE 	U.valid = 1 AND DT.status = " . _DATE_STATUS_FINISHED . $whrExp . " 
					GROUP BY U.idst, U.userid, U.email, U.lastname, U.firstname, C.code, C.name, A.status "
					
					. $havingExp . $orderExp . $limitExp;

		return $query;
			
	}
	
	
	private function _getCalendarDaySql($date_begin, $date_end, $status = false, $id_org = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce la stringa SQL per il recupero del calendario formativo in giornate di lezione
		//	 Le date devono essere stringhe già formattate yyyy-mm-dd	
		
		//Preparo stringa Where query principale
		$whrExp  = "DATE(DY.date_begin) >= '". $date_begin . "' AND  DATE(DY.date_begin) <= '". $date_end . "'";		
		
		if($status !== false)
			$whrExp  .= " AND  DT.status = ".(int)$status;
			
		if($id_org){
			$courses = $this->getCourseCatalogOrg($id_org, false, $subscribe_method);
			$whrExp  .=  " AND C.idCourse IN ('" .implode("','", array_keys($courses)). "') ";
		}
		
		//Preparo stringa ordinamenti
		if(is_string($orderExp))
			$orderExp = " ORDER BY ".$orderExp;
		
		//Preparo stringa limit
		if(is_string($limitExp))
			$limitExp = " LIMIT ".$limitExp;
			
		//Formo la query	
		$query = "	SELECT 	C.idCourse AS id_corso, C.code AS codice_corso, C.name AS nome_corso, IF(C.course_virtual=1,'TEAMS','AULA') AS tipo_erogazione, C.course_type AS tipo_corso,
							DT.code AS codice_edizione, DT.status AS stato_edizione, DYG.dt_begin AS inizio_edizione, DYG.dt_days AS lezioni, DY.date_begin AS inizio_lezione, DY.date_end AS fine_lezione, DY.id_day + 1 AS num_lezione,
							S.num_subs AS utenti_iscritti, T.teacher AS docente
					FROM 	%lms_course C LEFT JOIN 
							%lms_course_date AS DT ON C.idCourse = DT.id_course LEFT JOIN
							%lms_course_date_day AS DY ON (DT.id_date = DY.id_date AND DY.deleted = 0) LEFT JOIN
							(SELECT id_date, COUNT(id_day) AS dt_days, MIN(date_begin) AS dt_begin, MAX(date_end) AS dt_end FROM %lms_course_date_day WHERE deleted = 0 GROUP BY id_date) AS DYG ON DT.id_date = DYG.id_date LEFT JOIN
							(SELECT du.id_date, GROUP_CONCAT(u.idst) AS id_teacher, GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname)) AS teacher
							 FROM 	%lms_course_date_user du JOIN
									%lms_course_date dt ON du.id_date = dt.id_date JOIN
									%lms_courseuser cu ON (du.id_user = cu.idUser AND dt.id_course = cu.idCourse AND cu.level = 6) JOIN
									%adm_user u ON cu.idUser = u.idst
							 GROUP BY du.id_date) T ON DT.id_date = T.id_date LEFT JOIN 
							(SELECT COUNT(id_user) AS num_subs, du.id_date 
							 FROM %lms_course_date_user  AS du JOIN %lms_course_date dt ON du.id_date = dt.id_date 
                             WHERE id_user IN (SELECT idUser FROM %lms_courseuser WHERE level = 3 AND idCourse = dt.id_course) GROUP BY id_date) AS S ON DT.id_date = S.id_date 
					WHERE C.course_type = 'classroom' AND " .$whrExp . $orderExp . $limitExp;
					

		return $query;
	}
	
	
	private function _getCalendarSql($date_begin, $date_end, $status = false, $id_org = false, $id_group = false, $subscribe_method = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce la stringa SQL per il recupero del calendario formativo
		//	 Le date devono essere stringhe già formattate yyyy-mm-dd	
		
		//Preparo stringa Where query principale
		$whrExp  = "DATE(dt_begin) >= '". $date_begin . "' AND  DATE(dt_end) <= '". $date_end . "'";
		
		if($status !== false)
			$whrExp  .= " AND  DT.status = ".(int)$status;
			
		if($id_org){
			$courses = $this->getCourseCatalogOrg($id_org, false, $subscribe_method);
			$whrExp  .=  " AND C.idCourse IN ('" .implode("','", array_keys($courses)). "') ";
		}
		
		if($subscribe_method){
			$whrExp  .=  " AND C.subscribe_method IN ('" .implode("','", $subscribe_method). "') ";
		}
		
		//Preparo stringa Where utenti per sottoquery
		$whrExpUser = "1";
		
		if($id_group) {
			$users = $this->acl_man->getGroupAllUser($id_group);
			$whrExpUser = " id_user IN ('" .implode("','", $users). "') ";
		}
		
		
		//Preparo stringa ordinamenti
		if(is_string($orderExp))
			$orderExp = " ORDER BY ".$orderExp;
		
		//Preparo stringa limit
		if(is_string($limitExp))
			$limitExp = " LIMIT ".$limitExp;
			
			
		//Formo la query	
		$query = "	SELECT 	C.idCourse AS id_corso, C.code AS codice_corso, C.name AS nome_corso, IF(C.course_virtual=1,'TEAMS','AULA') AS tipo_erogazione, C.course_type AS tipo_corso,
							DT.code AS codice_edizione, DT.internal_note AS note_interne, DT.status AS stato_edizione, DT.max_par AS max_iscrizioni, DT.price AS costo, DY.dt_begin AS inizio_edizione, DY.dt_end AS fine_edizione,
							S.num_user AS utenti_iscritti, A.num_complete AS utenti_formati, IF(DT.status <> 1, NULL, S.num_user - IFNULL(A.num_complete, 0)) AS utenti_assenti, TS.num_subs AS iscrizioni_complessive, T.teacher AS docente
					FROM 	%lms_course C LEFT JOIN 
							%lms_course_date AS DT ON C.idCourse = DT.id_course LEFT JOIN
							(SELECT id_date, MIN(date_begin) AS dt_begin, MAX(date_end) AS dt_end FROM %lms_course_date_day WHERE deleted = 0 GROUP BY id_date) AS DY ON DT.id_date = DY.id_date LEFT JOIN
							(SELECT id_entry, id_edition, SUM(IF(status = 2,1,0)) AS num_complete  
							 FROM %lms_assignment 
							 WHERE type_entry = 'course' AND " .$whrExpUser. "
							 GROUP BY id_entry, id_edition) AS A ON DT.id_course = A.id_entry AND DT.id_date = A.id_edition LEFT JOIN
							(SELECT du.id_date, GROUP_CONCAT(u.idst) AS id_teacher, GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname)) AS teacher
							 FROM 	%lms_course_date_user du JOIN
									%lms_course_date dt ON du.id_date = dt.id_date JOIN
									%lms_courseuser cu ON (du.id_user = cu.idUser AND dt.id_course = cu.idCourse AND cu.level = 6) JOIN
									%adm_user u ON cu.idUser = u.idst
							 GROUP BY du.id_date) T ON DT.id_date = T.id_date LEFT JOIN 
							(SELECT COUNT(id_user) AS num_user, du.id_date 
							 FROM %lms_course_date_user  AS du JOIN %lms_course_date dt ON du.id_date = dt.id_date 
                             WHERE " .$whrExpUser. " AND id_user IN (SELECT idUser FROM %lms_courseuser WHERE level = 3 AND idCourse = dt.id_course) GROUP BY id_date) AS S ON DT.id_date = S.id_date LEFT JOIN
							(SELECT COUNT(id_user) AS num_subs, du.id_date 
							 FROM %lms_course_date_user  AS du JOIN %lms_course_date dt ON du.id_date = dt.id_date 
                             WHERE id_user IN (SELECT idUser FROM %lms_courseuser WHERE level = 3 AND idCourse = dt.id_course) GROUP BY id_date) AS TS ON DT.id_date = TS.id_date 
					WHERE C.course_type = 'classroom' AND " .$whrExp . $orderExp . $limitExp;
					
					//N.B. 	Il conteggio degli iscritti è indipendente dalle assegnazioni e il docente viene escluso in base al ruolo nel corso.
		Util::fdebug($query);
		
		return $query;
		
	}
	
	
	public function getCourseuserComplete($id_org, $date_from, $date_to = false) {
		//>> Restituisce gli utenti di una specifica azienda che hanno completato un corso in un determinato periodo
		//>> Il metodo è utilizzato da job di estrazione
		
		$res = array();
		
		// Preparo Query
		$where = !$date_to ? "" : " AND date_complete <= '". $date_to ."'";
		
		$query_users = $this->acl_man->getSqlUsersByOrg($id_org);
		
		$query = "	SELECT c.code AS course_code, c.name AS course_name,
						u.userid AS user_userid, u.firstname AS user_firstname, u.lastname AS user_lastname, u.email AS user_email,
						cu.date_inscr, cu.date_complete, cu.level, cu.edition_id 
					FROM %lms_courseuser cu 
						JOIN %adm_user u ON cu.idUser = u.idst
						JOIN %lms_course c ON cu.idCourse = c.idCourse 
					WHERE u.idst IN (".$query_users.") AND cu.status = "._CUS_END." AND date_complete >= '".$date_from."' ".$where;
					  
					
		// Eseguo query
		$result = sql_query($query);		
		
		
		// Preparo array di uscita
		while($row = sql_fetch_assoc($result)) 
		{
			$res[] = $row;
		}
		
		// Out
		return $res;
	}
	
	
	public function getCourseCatalogOrg($id_org, $status = false, $subscribe_method = false) {
		//>> Restituisce i corsi del catalogo assegnati all'organizzazione 
		//>> L'argomento opzionale $status è un array con i valori di stato corso
		//>> L'argomento opzionale $subscribe_method è un array con i valori della modalità di iscrizione
		
		require_once(_lms_.'/lib/lib.course.php');
				
		$course_man = new Man_Course();
		
		return $course_man->getCourseCatalogOrg($id_org, $status, $subscribe_method);
	}
	

	public function getCoursepathCatalogOrg($id_org, $subscribe_method = false) {
		//>> Restituisce i percorsi del catalogo assegnati all'organizzazione 
		//>> L'argomento opzionale $subscribe_method è un array con i valori della modalità di iscrizione
		
		require_once(_lms_.'/lib/lib.coursepath.php');
				
		$path_man = new CoursePath_Manager();
		
		return $path_man->getCoursepathCatalogOrg($id_org, $subscribe_method);
	}
	

	public function getStatusDateDropdown() {
		// Restituisce gli stati delle edizioni classroom
		return $this->date_man->getStatusForDropdown();
		
	}
	
	
	public function getStatusAssnDropdown() {
		// Restituisce gli stati delle assegnazioni
		return $this->courseassn_man->getStatusForDropdown();
	}
	
	/**
	 * Restituisce i campi personalizzati dell'utente assegnati nell'organigramma
	 */
	public function getOrgFieldList($id_org, $lang_filter_fields = false) {
		
        $fl = new FieldList();
        $acl_man = $this->acl_man;
        
        $filter_fields = array();
        $result = array();
        
        // Recupero campi organizzazione
		$org_fields = $fl->getFieldsFromIdst([$acl_man->getGroupST('oc_' . $id_org)]);
		
		// Recupero campi selezionati se esistono
		if ( is_array( $lang_filter_fields ) ) {
			$filter_fields = $fl->getFieldIdsFromTranslations( $lang_filter_fields );
		}
		
		// Restituisco tutti i campi o solo quelli richiesti
		foreach ($org_fields as $field) {
			if ( !$lang_filter_fields || in_array( $field[ FIELD_INFO_ID ], $filter_fields) )
				$result[ $field[ FIELD_INFO_ID ] ] = $field[ FIELD_INFO_TRANSLATION ];
		}
		
		// Out
		return $result;
	}
		
}

?>
