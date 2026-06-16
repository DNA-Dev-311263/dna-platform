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


define('_GAP_STATUS_ACTIVE', 1);
define('_GAP_STATUS_CLOSED', 3);
define('_GAP_STATUS_CANCELED', 8);

class GapManager
{

	protected $lang;
	protected $acl_man;
	protected $courseassn_man;
	protected $logUserInfo;

	public function __construct(){
		
		require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.courseassn.php');
	
		$this->lang = DoceboLanguage::CreateInstance('admin_gap', 'lms');
		$this->acl_man = Docebo::user()->getAclManager();
		$this->courseassn_man = new CourseassnManager();
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
		
		return array(	_GAP_STATUS_ACTIVE 		=> $this->lang->def('_GAP_STATUS_ACTIVE', 'gap'),
						_GAP_STATUS_CLOSED		=> $this->lang->def('_GAP_STATUS_CLOSED', 'gap'),
						_GAP_STATUS_CANCELED	=> $this->lang->def('_GAP_STATUS_CANCELED', 'gap') );
	}


	public function delGap($id_gap){
		//>> Elimina il gap passato in argomento
		
		$query =	"DELETE FROM %lms_gap"
					." WHERE id_gap = ".(int)$id_gap;
		
		// Elimino
        $res = sql_query($query);
        
        // Resetto i test
        if ($res) $this->resetTest($id_gap);
                
		return $res;
	}
	
	
	public function cancelGap($id_gap, $reason) {
		//>> Annulla i gap passati in argomento
		
		$arr_gap = is_array($id_gap) ? $id_gap : array($id_gap);
		
		//Preparo query		
		$query = "UPDATE %lms_gap gap 
						SET gap.status = ". _GAP_STATUS_CANCELED.", date_upd = NOW(), user_upd = ".$this->getLogUser().", desc_upd = CONCAT('Canceled: ','".$reason."')
				  WHERE gap.status <> ". _GAP_STATUS_CLOSED." AND id_gap IN ('".implode("','", $arr_gap)."')";
				  
		// Annullo i gap
		$res = sql_query($query);
		
		// Resetto i test
		if ($res) {
			foreach($arr_gap as $id)		
					$this->resetTest($id);
		}
			
		//Out
		return $res;		
	}
	
	
	public function cancelActiveGap($year, $id_org) {
		//>> Annulla i gap in stato attivo per l'anno e l'organizzazione passati in argomento
		
		$res = false;
		
		//Preparo where
		$whrExp = "gap.status = ". _GAP_STATUS_ACTIVE." AND YEAR(date_ins) = ".(int)$year." AND idOrg = ".(int)$id_org;
		
		//Recupero gap
		$gap = $this->getGap($whrExp, false, false, false);

		//Aggiorno
		if($gap)
			$res = $this->cancelGap(array_column($gap, 'id_gap'), 'in bulk');

		//Out
		return $res;		
	}
	
	
	public function updGapCatalogueDeleted($id_catalogue){
		//>> Aggiorna il gap rimuovendo l'id del catalogo assegnato. Non rimuove il record di gap per storico.

		$query = '';
						
		$query = "UPDATE %lms_gap 
					SET id_catalogue = 0, status = "._GAP_STATUS_CANCELED.", date_upd = NOW(), 
						user_upd = ".$this->getLogUser().", desc_upd = 'Catalogue deleted'  
					WHERE id_catalogue = ".$id_catalogue. " AND status <> "._GAP_STATUS_CLOSED;
					
		//Lancio query
		return sql_query($query);
	}
	
	
	public function delGapUserDeleted($id_user, $conn = false){
		//>> Elimina tutti i gap dell'utente.

		$query = "DELETE FROM %lms_gap 
					WHERE id_gap > 0 AND id_user = ".(int)$id_user;
					
		//Lancio query
		return sql_query($query, $conn);
	}
	
	
	public function updGapStatusById($id_gap, $status){
		//>> Aggiorna lo stato del gap (modifica singola)
		
		$arrStatus = $this->getStatusForDropdown();
		$res = false;
		
		if(array_key_exists($status, $arrStatus)){
							
			$query = "UPDATE %lms_gap 
						SET status = ".$status.", date_upd = NOW(), 
							user_upd = ".$this->getLogUser().", desc_upd = 'Status changed'  
						WHERE id_gap = ".(int)$id_gap;
					
			// Aggiorno stato
			$res = sql_query($query);
		}
		
		if ($res) {
			// Se è un annullamento o una chiusura, resetto i test
			if($status == _GAP_STATUS_CANCELED || $status == _GAP_STATUS_CLOSED) {
										
				$this->resetTest($id_gap);
			}
		}
		
		// Out
		return $res;
	}
	
	
	public function updGapCataById($id_gap, $id_cata) {
		//>> Aggiorna il catalogo del gap
		
		$query = "UPDATE %lms_gap 
					SET id_catalogue = ".$id_cata.", date_upd = NOW(), 
						user_upd = ".$this->getLogUser().", desc_upd = 'Catalogue changed'  
					WHERE id_gap = ".(int)$id_gap;
				
		//Lancio query
		return sql_query($query);
	}
	
	
	public function updGapRqmtById($id_gap, $value){
		//>> Aggiorna il requirement del gap
				
		$query = "UPDATE %lms_gap 
					SET requirement = ".$value.", date_upd = NOW(), 
						user_upd = ".$this->getLogUser().", desc_upd = 'Requirement changed'  
					WHERE id_gap = ".(int)$id_gap;
				
		//Lancio query
		return sql_query($query);
	}
	
	
	public function getGapByUser($id_user, $arr_status = false) {
		//>> Restituisce i gap degli utenti passati in argomento.
		//>> Se non è valorizzato arr_status, vengono restituite solo i gap ancora aperti 
		//>> (non chiusi e non annullati)
		
		// Preparo array utenti
		$users = (is_array($id_user) ? $id_user : array($id_user));
		
		// Preparo where
		$whrExp = " id_user IN ('".implode("','", $users)."')";
		
		if($arr_status !== false) 
			$whrExp .= " AND gap.status IN (".implode(",", $arr_status).")";
		else
			$whrExp .= " AND gap.status NOT IN ("._GAP_STATUS_CLOSED.","._GAP_STATUS_CANCELED.")";
		
	
		//Restituisco assegnazioni
		return $this->getGap($whrExp);	
	}
	
	
	public function getGapNumber($id_org, $year = false, $where = false){
		//>> Restituisce il numero di gap per una data società e un dato anno
		
		//Formo la stringa where
		$whrExp = "idOrg = ".(int)$id_org;
		
		if($where) 
			$whrExp .= " AND (".$where.") ";
		
		if($year > 0) 
			$whrExp .= " AND YEAR(date_ins) = ".$year;	

		//lancio la query e recupero il numero di righe
		$query = $this->_getGapSqlString($whrExp);
		$res = sql_num_rows(sql_query($query));
		
		
		return $res;
	}

	
	private function _getGapSqlString($whrExp = false, $orderExp = false, $limitExp = false, $full = true){
		//>> Restituisce la stringa SQL per recuperare i gap
		//>> Gli utenti assegnatari devono essere dentro a un nodo e la query espone il codice del nodo di primo livello
		//>> Se l'argomento full è vero, vengono restituite anche le informazioni correlate
		
		//Preparo strigna Where
		if($whrExp !== false)
			$whrExp  = ' WHERE '. $whrExp;
		
		//Preparo stringa ordinamenti
		if(is_string($orderExp))
			$orderExp = ' ORDER BY '.$orderExp;

		
		//Preparo stringa limit
		if(is_string($limitExp))
			$limitExp = ' LIMIT '.$limitExp;
			

		//Preparo query
			
		if(!$full) {	
			$query = "	SELECT gap.*, org.idOrg  
						FROM %lms_gap gap
							JOIN %adm_group_members grp ON gap.id_user = grp.idstMember
							JOIN (select path, idst_oc from %adm_org_chart_tree) sorg ON grp.idst = sorg.idst_oc
							JOIN %adm_org_chart_tree org ON LEFT(sorg.path, 5+9*1) = org.path "
						.$whrExp . $orderExp . $limitExp;	
						
		} else {	
			$query = "	SELECT gap.*, org.idOrg,  org.code AS org_code, cata.name AS cata_name, assn.count_assn, 
							usr_u.firstname AS user_firstname, usr_u.lastname AS user_lastname, usr_u.userid AS user_userid, usr_u.valid AS user_valid, 
							usr_u.email AS user_email, usr_u.time_availability AS user_time_availability, usr_u.job_location AS user_job_location, 
							usr_m.firstname AS manager_firstname, usr_m.lastname AS manager_lastname, 
							usr_m.userid AS manager_userid, usr_m.email AS manager_email,
							usr_o.firstname AS modifier_firstname, usr_o.lastname AS modifier_lastname, 
							usr_l.firstname AS loader_firstname, usr_l.lastname AS loader_lastname, 
							CONCAT(usr_u.firstname,' ',usr_u.lastname) AS user_fullname, 
							CONCAT(usr_m.firstname,' ',usr_m.lastname) AS manager_fullname, 
							CONCAT(usr_o.firstname,' ',usr_o.lastname) AS modifier_fullname, 
							CONCAT(usr_l.firstname,' ',usr_l.lastname) AS loader_fullname
						FROM %lms_gap gap
							LEFT JOIN %adm_user usr_u ON gap.id_user = usr_u.idst
							LEFT JOIN %adm_user usr_o ON gap.user_upd = usr_o.idst
							LEFT JOIN %adm_user usr_m ON gap.id_manager = usr_m.idst
							LEFT JOIN %adm_user usr_l ON gap.user_ins = usr_l.idst
							LEFT JOIN 
								(SELECT id_gap, COUNT(id_assn) AS count_assn FROM %lms_assignment 
									WHERE status <> "._ASSN_STATUS_CANCELED." GROUP BY id_gap) assn ON gap.id_gap = assn.id_gap
							JOIN %lms_catalogue cata ON gap.id_catalogue = cata.idCatalogue
							JOIN %adm_group_members grp ON gap.id_user = grp.idstMember
							JOIN (SELECT path, idst_oc from %adm_org_chart_tree) sorg ON grp.idst = sorg.idst_oc
							JOIN %adm_org_chart_tree org ON LEFT(sorg.path, 5+9*1) = org.path "
						.$whrExp . $orderExp . $limitExp;
		}
		
		return $query;
	}
	
	
	public function getGap($whrExp = false, $orderExp = false, $limitExp = false, $full = true){
		//>> Restituisce i gap applicando le espressioni Where e Order By passate in argomento
		//>> Se l'argomento full è vero, vengono restituite anche le informazioni correlate
		
		$res = array();
		
		//Recupero la stringa SQL
		$query = $this->_getGapSqlString($whrExp, $orderExp, $limitExp, $full);

		//Lancio la query
		$result = sql_query($query);


		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_gap']] = $row;
		}
		
		return $res;
	}
	
	
	public function getGapByOrg($id_org, $status = false, $year_gap = false, $date_from = false, $date_to = false){
		//>> Restituisce i gap in base all'organizzazione, allo status, all'anno di gap e periodo di inserimento
		
		//Formo la stringa where
		$whrExp = "idOrg = ". (int) $id_org;

		if($status) 	$whrExp .= " AND gap.status = ".(int)$status ;
		if($year_gap)	$whrExp .= " AND gap.year_gap = ".(int)$year_gap ;
		if($date_from)	$whrExp .= " AND gap.date_ins >= '".$date_from."'";
		if($date_to)	$whrExp .= " AND gap.date_ins <= '".$date_to."'";
		
		return $this->getGap($whrExp);	
	}
	
	
	public function getGapGroupBy($functArr, $groupExp, $whrExp = false, $orderExp = false, $keyField = false) {
		//>> Esegue una query group by sulla tabella dei gap
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
		$query = $this->_getGapSqlString($whrExp);
		
		$query = " SELECT ".$groupExp . $field_calc
				." FROM (".$query.") AS view_gap "
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
	
	
	public function getGapTemp($id_user, $id_org, $whrExp = false){
		//>> Restituisce i gap temporanei applicando l'espressione Where passata in argomento
		
		$whrExp = (!$whrExp ? '' : 'AND '.$whrExp);
			
		//Recupero la stringa SQL
		$query = "SELECT * "
				."FROM %lms_gap_temp "
				."WHERE user_ins = '".$id_user."' AND id_org = '".$id_org."' ".$whrExp;
	
		//Lancio la query
		$result = sql_query($query);
		
		$res = array();
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_gap_tmp']] = $row;
		}
		
		return $res;
	}		

	
	public function delGapTemp($id_user, $id_org){
		//>> Elimina i record della tabella di appoggio relativi 
		//>> all'id utente che ha caricato i dati e all'organizzazione di lavoro

		$query = "DELETE FROM %lms_gap_temp "
				."WHERE user_ins = ".(int)$id_user." AND id_org = ".(int)$id_org;
				
		return  sql_query($query);
	}
	
	
	public function insGap($record, $is_tabletemp, $check_field = true){
		//>> Inserisce una riga di gap nella tabella dei gap (temporanea o effettiva)
		
		$res = array();
		$table_name = ($is_tabletemp ? '%lms_gap_temp' : '%lms_gap');
		
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
	
	
	public function getGapUndefUser($id_user, $date_from = false) {
		//>> Restituisce i gap non definiti per un utente passato in argomento
		
		//Preparo where
		$whrExp = "id_user = ".(int)$id_user." AND gap.status = "._GAP_STATUS_ACTIVE." AND IFNULL(count_assn, 0) < gap.requirement";
		
		if($date_from)	$whrExp .= " AND gap.date_ins >= '".$date_from."'";
		
		//Lancio il metodo
		return $this->getGap($whrExp);
	}
	

	public function getGapUndefined($id_org, $date_from = false) {
		//>> Restituisce i gap con il requirement inferiore al numero di assegnazioni (gap non definito completamente)
		
		//Preparo where
		$whrExp = "idOrg = ".(int)$id_org." AND gap.status = "._GAP_STATUS_ACTIVE." AND IFNULL(count_assn, 0) < gap.requirement";
		
		if($date_from)	$whrExp .= " AND gap.date_ins >= '".$date_from."'";
		
		//Lancio il metodo
		return $this->getGap($whrExp);
	}
	
	
	public function checkIsAssigned($id_gap) {
		//>> Restituisce se il gap ha assegnazioni
		$query = "SELECT id_assn FROM %lms_assignment WHERE id_gap = ".(int)id_gap;
		
		list($res) = sql_fetch_row(sql_query($query));
		
		return (bool)$res;
	}
	
	
	public function countAssn($id_gap, $status = false) {
		//>> Conta le assegnazioni sul gap passato in argomento
		//>> Se $status (array) non è valorizzato, vengono escluse le assegnazioni annullate
		
		//Lancio il metodo
		$res = $this->courseassn_man->getAssnByGap($id_gap, $status);
		
		//Out
		return (!$res ? 0 : count($res));
	}
	
	
	public function getCatalogByOrg($id_org, $no_duplicate = true) {
		//>> Restituisce i cataloghi dell'organizzazione.
		
		//Preparo where
		$whrExp = "ogc.idOrg = " . (int)$id_org;
		
		if($no_duplicate)
			$whrExp .= " AND ( 1 = (SELECT COUNT(*) FROM learning_catalogue WHERE name = ca.name) )";
		
		//Completo query
		$query = "SELECT ca.idCatalogue, ca.name, ca.hidden "
				."FROM %lms_catalogue ca "
				."	JOIN learning_catalogue_member ctm ON ca.idCatalogue = ctm.idCatalogue "
				."	JOIN core_org_chart_tree ogc ON (ctm.idst_member = ogc.idst_oc OR ctm.idst_member = ogc.idst_ocd) "
				."WHERE " . $whrExp;
				
		//Lancio la query
		$result = sql_query($query);
				
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['idCatalogue']] = $row;
		}
		
		return $res;
	}
	
	
	public function getSingleGap($id_gap) {
		//>> Restituisce il gap singolo con i valori di tabella
		
		// Preparo where
		$whrExp = "id_gap = ". (int)$id_gap;
		
		// Lancio il metodo
		$res = $this->getSimpleGap($whrExp);
		
		//Recupero la riga
		if ($res) $res = reset($res);
		
		//Out
		return $res;
	}
	
	
	public function getSimpleGap($whrExp) {
		//>> Restituisce i gap con i valori di tabella
		
		$res = array();
		
		// Preparo strigna Where e query
		$whrExp  = ($whrExp !== false ? $whrExp : '1');
		
		$query = "SELECT * FROM %lms_gap WHERE ".$whrExp;
				
		// Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_gap']] = $row;
		}
		
		return $res;
	}
	
	
	public function getGapById($id_gap, $id_org = false, $full = true) {
		//>> Restituisce i gap in base agli ID passati in argomento con informazioni ridotte o estese
		
		$arr_id_gap = (is_array($id_gap) ? $id_gap : array($id_gap));
		
		//Formo espressione filtro su ID assegnazione
		$whrExp = "gap.id_gap IN (".implode(",", $arr_id_gap).")";
		
		//Formo espressione filtro su organizzazione se passata in argomento
		$whrExp .= ($id_org ? " AND idOrg = ".$id_org: "");

	
		//Lancio il metodo 
		$res = $this->getGap($whrExp, false, false, $full);
		
		// Restituisco un array senza righe se la richiesta è per un solo gap
		if ($res && !is_array($id_gap))
			$res = reset($res);
		
		//Output
		return $res;
	}
	

	public function getValidCatalogue($id_org) {
		//>> Restituisce i cataloghi dell'organizzazione validi per i gap
		
		$valid = array();
		
		$cata = $this->getCatalogByOrg($id_org);
		$empty = $this->getEmptyCatalogue();
		
		$valid = array_diff_key($cata, $empty);
		
		return $valid;
	}
	
	
	public function getEmptyCatalogue() {
		//>> Restituisce i cataloghi che non contengono corsi 
		//>> di tipo assessment con iscrizione da gap
		//>> I corsi chiusi o cancellati vengono considerati inesistenti

		$res = array();
		$st_valid = CST_AVAILABLE.','.CST_EFFECTIVE;
		
		$query = "SELECT DISTINCT idCatalogue"
				." FROM %lms_catalogue"
				." WHERE  idCatalogue NOT IN"
				." (SELECT idCatalogue FROM  %lms_catalogue_entry cte"
				." 		JOIN %lms_course cor ON cte.idEntry = cor.idCourse"
				."		WHERE  type_of_entry = 'course' AND course_type = 'assessment' AND subscribe_method = 8 AND status IN (".$st_valid ."))";
		
		
		//Lancio la query
		$result = sql_query($query);
				
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['idCatalogue']] = $row;
		}
		
		return $res;
	}
	
	
	public function getCourseByGap($id_gap, $status = false, $check_date = false) {
		//>> Restituisce i corsi di assessment relativi a uno specifico gap
		//>> (solo quelli assessment a iscrizione da gap)
		//>> L'argomento opzionale $status è un array con i valori di stato corso
		//>> L'argomento opzionale id_cata è la chiave di un catalogo specifico
		//>> id_gap può essere un array di chiavi
		
		$courses = array();
		$arr_gap = is_array($id_gap) ? $id_gap : array($id_gap); 
		
		// Recupero i gap
		$whrExp = "gap.id_gap IN ('". implode("','", $arr_gap) ."')";
		
		$gaps = $this->getGap($whrExp);
		
		foreach ($gaps as $gap) {
			// Recupero i corsi
			$gap_crs =  $this->getCourseCatalogOrg($gap['idOrg'], $gap['id_catalogue'], $status, $check_date);
			
			// Aggiungo riferimento al gap
			foreach($gap_crs as &$course)
				$course['id_gap'] = $gap['id_gap'];
				
			// Aggiungo i corsi del gap ai corsi di output
			$courses += $gap_crs;	
		}

		return $courses;
	}
		
		
	public function getCourseCatalogOrg($id_org, $id_cata = false, $status = false, $check_date = false) {
		//>> Restituisce i corsi di assessment del catalogo assegnati all'organizzazione 
		//>> (solo quelli assessment a iscrizione da gap)
		//>> L'argomento opzionale $status è un array con i valori di stato corso
		//>> L'argomento opzionale id_cata è la chiave di un catalogo specifico
			
		$res = array();
		$whrExp = "";

		//Preparo where
		if($id_cata) {
			$whrExp .= " AND cte.idCatalogue = ". (int)$id_cata;
		}
		if(is_array($status)) {
			$whrExp .= " AND cor.status IN (" . implode(",", $status) . ") ";
		}
		if($check_date) {
			$whrExp .= " AND (DAYNAME(cor.date_begin) IS NULL OR cor.date_begin <= '".date('Y-m-d')."') 
						 AND (DAYNAME(cor.date_end) IS NULL OR cor.date_end >= '".date('Y-m-d')."')";
		}

		//Preparo la query
		$query = "SELECT cte.idCatalogue, cor.* "
				." FROM %lms_catalogue_entry cte"
				."	JOIN %lms_course cor ON cte.idEntry = cor.idCourse"
				."	JOIN learning_catalogue_member ctm ON cte.idCatalogue = ctm.idCatalogue"
				."	JOIN core_org_chart_tree ogc ON (ctm.idst_member = ogc.idst_oc OR ctm.idst_member = ogc.idst_ocd)"
				." WHERE ogc.idOrg = " . (int)$id_org . " AND cte.type_of_entry = 'course' AND cor.subscribe_method = 8"
				."		AND cor.course_type = 'assessment' " . $whrExp
				." ORDER BY cor.code";

						
		//Lancio la query
		$result = sql_query($query);
				
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['idCourse']] = $row;
		}
		
		return $res;
	}
	
	
	public function getGapSuspended($id_user) {
		//>> Restituisce i gap dell'utente con corsi già presenti in gap attivi
		
		$ass_dup = array();
		$gaps = array();
		$idx = 0;
		
		// Preparo la query per gli assessment presenti in due diversi cataloghi/gap
		$query = "SELECT idEntry AS id_course"
				." FROM %lms_gap g JOIN %lms_catalogue_entry ce ON g.id_catalogue = ce.idCatalogue"
				." WHERE type_of_entry = 'course' AND status = "._GAP_STATUS_ACTIVE." AND g.id_user = ".(int)$id_user
				." GROUP BY id_user, idEntry"
				." HAVING count(idEntry) > 1";
		
		// Lancio il metodo e preparo array
		$result = sql_query($query);
				
		while($row = sql_fetch_assoc($result))
			$ass_dup[] = $row['id_course'];


		// Se ci sono assessment duplicati, prendo i gap interessati
		if ($ass_dup) {
			
			$query = "SELECT g.id_gap"
					." FROM %lms_gap g JOIN %lms_catalogue_entry ce ON g.id_catalogue = ce.idCatalogue"
					." WHERE  type_of_entry = 'course' AND status = "._GAP_STATUS_ACTIVE
					."		AND id_user = ".(int)$id_user. " AND idEntry IN (". implode(",", $ass_dup ). ")"
					." ORDER BY date_ins, id_gap";
			
					
			// Eseguo
			$result = sql_query($query);
			
			// Recupero i gap che devono attendere la conclusione del primo
			while($row = sql_fetch_assoc($result))
			{
				if($idx > 0)
					$gaps[$row['id_gap']] = $row['id_gap'];
				
				$idx +=1;
			}
			
		}
		
		return $gaps;
	}
	
	
	public function insAssnFromGap($id_gap, $id_course, $user_ins) {
		//>> Inserisce un'assegnazione dal record di gap
		
		$res = false;
		$row_assn = array();
		$crs_man = new Man_Course();
		
		// Recupero il gap
		$gap = $this->getSingleGap($id_gap);
		
		if ($gap) {
			// Recupero il corso per controlli
			$course = $crs_man->getCourseInfo($id_course);
			
			// Recupero se assegnazione già esiste
			$assn_exists = $this->courseassn_man->checkAssnExists($gap['id_user'], $id_course);
			
			// Controlli
			$can_go = ($course && $gap['status'] == _GAP_STATUS_ACTIVE && $course['status'] == CST_EFFECTIVE && !$assn_exists);
			
			// Preparo riga da aggiungere se i controlli sono ok
			if ($can_go) {
				$row_assn['id_entry'] 		= $id_course;
				$row_assn['type_entry'] 	= 'course';
				$row_assn['id_user'] 		= $gap['id_user'];
				$row_assn['fav_location'] 	= $gap['fav_location'];
				$row_assn['description'] 	= $course['name'].' - '.$gap['description'];
				$row_assn['user_ins'] 		= $user_ins;
				$row_assn['user_upd'] 		= $user_ins;
				$row_assn['desc_upd'] 		= 'insert by gap';
				$row_assn['id_manager'] 	= $gap['id_manager'];
				$row_assn['status'] 		= 1;
				$row_assn['id_gap'] 		= $gap['id_gap'];	
				
				// Aggiungo riga di assegnazione
				$res = $this->courseassn_man->insAssn($row_assn, false);
			}
		}
		
		// Out
		return $res;
	}
	
	
	public function getRecentlyAssigned($id_user, $id_gap) {
		//>> Restituisce l'elenco dei corsi recentemnte assegnati in base alla data di gap.
		//>> Il controllo è effettuato sulle assegnazioni chiuse.
		//>> Se (anno gap corrente - anno gap assn) <=1, allora è recente.
		
		$res = array();
		$arr_course = is_array($id_course) ? $id_course : array($id_course);
		
		// Recupero il gap
		$gap = $this->getSingleGap($id_gap);
		
		// Esco se non lo trovo
		if(!$gap) return $res;
		
		// Recupero l'anno
		$year_gap = $gap['year_gap'];
		
		// Preparo query
		$query	= "SELECT a.id_entry, a.id_assn, g.id_gap, a.id_user, c.code, c.name"
				. " FROM %lms_assignment a"
				. " 	JOIN %lms_gap g ON a.id_gap = g.id_gap"
				. " 	JOIN %lms_course c ON a.id_entry = c.idCourse"
				. " WHERE a.type_entry = 'course' AND a.id_user = ".(int)$id_user." AND a.status IN ( "._ASSN_STATUS_CLOSED.") AND (".$year_gap." - g.year_gap) <= 1 ";
								
		// Eseguo
		$result = sql_query($query);
		
		// Recupero i corsi recentemente assegnati
		while($row = sql_fetch_assoc($result))
		{
			$res[ $row['id_entry'] ] = $row;
		}
		
		// Out
		return $res;
	}
	
	
	public function updGapStatusRecalc($id_gap = false) {
		//>> Aggiorna lo status dei gap ancora aperti ricalcolandolo sulla situazione del momento. 
		//>> id_gap è l'id di un singolo gap da aggiornare. Se non è passato, aggiorna
		//>> tutti i gap devono essere aggiornati.
		
		$gap = array();
		$retVal = 0;
		
		// Preparo Where
		if($id_gap)
			$wheExp = "gap.id_gap = ". (int)$id_gap ." AND gap.status <> "._GAP_STATUS_CANCELED;
		else
			$wheExp = "(gap.status = "._GAP_STATUS_ACTIVE." AND ifnull(assn_complete, 0) = gap.requirement) OR (gap.status = "._GAP_STATUS_CLOSED." AND ifnull(assn_complete, 0) < gap.requirement)";

		// Preparo query per recuperare i gap da aggiornare
		$query	= "SELECT  gap.id_gap, gap.status, gap.requirement, ifnull(assn_complete, 0) AS assn_complete"
				. " FROM learning_gap gap"
				. " LEFT JOIN (SELECT id_gap, count(id_assn) AS assn_complete FROM %lms_assignment WHERE status = "._ASSN_STATUS_CLOSED." GROUP BY id_gap) assn"
				. "		ON gap.id_gap = assn.id_gap"
				. " WHERE ". $wheExp;
				
		// Eseguo
		$result = sql_query($query);
					
		// Ciclo di aggiornamento
		while($gap = sql_fetch_assoc($result))
		{
			// Recupero il nuovo stato
			$newState = ($gap['requirement'] == $gap['assn_complete']) ? _GAP_STATUS_CLOSED : _GAP_STATUS_ACTIVE;
			
			// Aggiorno
			$res = $this->updGapStatusById($gap['id_gap'], $newState);
			
			//Conteggio
			if($res) $retVal += 1;
		}
		
		//Out
		return $retVal;
	}
	
	
	protected function getLoTest($id_course) {
		//>> Restituisce le chiavi dei learning object di tipo test
		//>> per uno specifico corso
		
		$query = "SELECT idOrg FROM %lms_organization WHERE objectType = 'test' AND idCourse = ". (int)$id_course; 
		
		//Lancio la query
		$result = sql_query($query);
				
		while($row = sql_fetch_assoc($result))
			$res[] = $row['idOrg'];
		
		//Out
		return $res;
	}
	
	
	protected function resetTest($id_gap) {
		//>> Elimina i punteggi e le statistiche conseguiti nel test
		//>> e rimuove l'iscrizione agli assessment per consentire un nuovo svolgimento.
		//>> $id_lo è idOrg del materiale didattico (test)
		//>> Le info sull'esito del test rimangono comunque salvate in learning_testtrack_times
		
		require_once(_lms_.'/models/CoursestatsLms.php');
		require_once(_lms_.'/lib/lib.subscribe.php');
		
		$retVal = false;
		$stats_model = new CoursestatsLms();
		$subs_man = new CourseSubscribe_Manager();
		
		//Info gap
		$gap = $this->getSingleGap($id_gap);
		
		//Controllo esistenza gap
		if (!$gap) return false;
	
		//Gap trovato, recupero i corsi
		$id_user = $gap['id_user'];
		$courses = $this->getCourseByGap($gap['id_gap']);
		
		foreach ($courses as $course) {
			$id_course = $course['idCourse'];
			
			// Elimino iscrizione
			$del = $subs_man->delUserFromCourse($id_user, $id_course);
			
			if ($del) {
				// Recupero i learning object del corso
				$arr_lo = $this->getLoTest($id_course);

				// Elimino le statistiche dell'oggetto
				foreach ($arr_lo as $lo)
					$stats_model->resetTrack($lo, $id_user);
			}
			// Calcolo risposta
			$retVal *= $del;
		}

		// Out
		return $retVal;
	}
	
	
	public function getAssessmentComplete($id_user) {
		//>> Restituisce gli assessment completati dall'utente
		
		$res = array();
			
		// Preparo e lancio query
		$query =	"SELECT c.idCourse, c.name, c.status"
					." FROM %lms_courseuser AS s"
					." JOIN %adm_user AS u ON s.idUser = u.idst"
					." JOIN %lms_course AS c ON s.idCourse = c.idCourse"
					." WHERE c.course_type = 'assessment' AND u.idst = ".(int)$id_user." AND s.status = 2";
					
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			// Restituisco la riga all'array di risposta
			$res[$row['idCourse']] = $row;
		}
		
		// Output
		return $res;
	}


	public function getFilterExpression() {
		//>> Restituisce una stringa where per le ricerche sulla query dei gap
		//>> Eseguire il replace del tag [@filter_text]
		
		return "(usr_u.userid LIKE '%[@filter_text]%' OR usr_u.firstname LIKE '%[@filter_text]%' OR usr_u.lastname LIKE '%[@filter_text]%')"; 
	}
	
}

?>
