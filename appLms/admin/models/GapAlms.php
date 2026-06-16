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

define('_MISSING_INFO', "MISSING_INFO");
define('_ORG_NOT_MATCH', "ORG_NOT_MATCH");
define('_CATALOG_NOT_REG', "CATALOG_NOT_REG");
define('_CATALOG_EMPTY', "CATALOG_EMPTY");
define('_GAP_EXISTS', "GAP_EXISTS");
define('_GAP_DUPLICATE', "GAP_DUPLICATE");
define('_GAP_NOT_FOUND', "GAP_NOT_FOUND");
define('_USER_STATUS_SUSPEND', "USER_STATUS_SUSPEND");

define('_OP_INS', "INS");
define('_OP_UPD', "UPD");

Class GapAlms extends Model
{
	protected $gap_man;
	protected $courseassn_man;
	protected $acl_man;
	protected $id_org;
	protected $id_user;


	public function __construct($id_org = 0, $id_user = 0) {
		
		require_once(_lms_.'/lib/lib.courseassn.php');
		require_once(_lms_.'/lib/lib.gap.php');
		require_once(_lms_.'/lib/lib.proposal.php');

		$this->acl_man =& Docebo::user()->getAclManager();
		
		$this->gap_man = new GapManager();  				// manager gap
		$this->courseassn_man = new CourseassnManager();  	// manager corsi assegnazioni
		
		//>> $id_user è l'id dell'operatore che sta caricando/visualizzando i dati.
		//>> $id_org l'organizzazione di lavoro (se è godadmin può non essere la sua).
		
		$this->id_user = $id_user;
		$this->id_org = $id_org;
		
	}


	protected function _getStatusOpen($outArray = false) {
		//>> Restituisce gli stati del gap considerato ancora aperto
		//>> Al momento, l'unico stato è 'ACTIVE'. Utile se in futuro si inseriscono altri stati (es. in corso, in chiusura ecc.)
		
		if($outArray)
			return array(_GAP_STATUS_ACTIVE);
		else
			return _GAP_STATUS_ACTIVE;
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
	
	public function loadGap($year, $start_index, $results, $sort, $dir, $filter_text = false){	
		//>> Restituisce i gap
		
		$orderExp = false;
		$limitExp = false;
	
		//Formo espressione filtro
		$whrExp = "idOrg = ".(int)$this->id_org;
		
		if($year > 0) 
			$whrExp .= " AND YEAR(date_ins) = ".$year;
			
		if ($filter_text)
			$whrExp .= " AND (usr_u.userid LIKE '%".$filter_text."%' OR usr_u.firstname LIKE '%".$filter_text."%' OR usr_u.lastname LIKE '%".$filter_text."%') ";
			
			
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
				
			case 'cata_name':
				$orderExp = $sort." ".$dir.", date_ins ".$dir.", user_fullname, status ".$dir;
			break;
			
			case 'status':
				$orderExp = $sort." ".$dir.", count_assn, user_fullname ".$dir;
			break;
			
			case 'count_assn':
				$orderExp = $sort." ".$dir.", requirement, status ";
			break;
			
			default:
				$orderExp = $sort." ".$dir.", status, id_gap";
		}

		//Formo espressione limit
		$limitExp = ($start_index === false ? '' : $start_index.", ".$results);
		
		
		//Lancio il metodo 
		$res = $this->gap_man->getGap($whrExp, $orderExp, $limitExp);

		//Output
		return $res;
	}
	
	
	public function getGapActive($id_org, $year = false) {
		//>> Restituisce i gap aperti di una data organizzazione
		
		//Argomenti di chiamata
		$status = _GAP_STATUS_ACTIVE;
			
		//Lancio il metodo 
		$res = $this->gap_man->getGapByOrg($id_org, $status, (int)$year);

		//Output
		return $res;
	}
	
	
	public function getOrgForDropdown(){
		//>> Usata per restituire le organizzazioni da inserire in combo
		
		$res = array();
		$org = $this->getOrgInfoByLevel();
			
		foreach($org as $k => $row) 
			$res[$row['idOrg']] = $row['code'];
		
		return $res;	
	}
	
	
	public function getCataForDropdown(){
		//>> Usata per restituire i cataloghi dell'organizzazione validi per i gap
		
		$res = array();
		$cata = $this->gap_man->getValidCatalogue($this->id_org);
		
		foreach($cata as $k => $row) 
			$res[$k] = $row['name'];

		return $res;
	}
	
	
	public function getStatusForDropdown(){
		//>> Usata per restituire gli stati dei gap
		
		return $this->gap_man->getStatusForDropdown();
	}
	
	
	public function getAssnStatusForDropdown(){
		//>> Usata per restituire gli stati delle assegnazioni
		
		return $this->courseassn_man->getStatusForDropdown();
	}
	
	
	public function getCourseCatalogOrg() {
		//>> Restituisce i corsi assessment del catalogo assegnati all'organizzazione

		// Lancio il metodo del manager
		$retVal = $this->gap_man->getCourseCatalogOrg($this->id_org);
		
		// Out
		return $retVal;
	}
	
	
	public function getCatalogByOrg() {
		//>> Restituisce i cataloghi dell'organizzazione (li esclude se sono duplicati).
		
		$retVal = $this->gap_man->getCatalogByOrg($this->id_org);
		
		//Out
		return $retVal;
	}


	public function delGap($id_gap){
		//>> Elimina il gap e le assegnazioni collegate
		
		// Recupero le assegnazioni collegate
		$assn = $this->getAssnByGap($id_gap, 'all_status', 'id_assn');
		
		// Elimino il gap
		$retVal = $this->gap_man->delGap($id_gap);
		
		// Elimino le assegnazioni
		if($assn) {
			$this->courseassn_man->delAssn($assn, false);
		}
		
		// Out
		return $retVal;
	}
	

	public function cancelActiveGap($year, $id_org = false) {
		//>> Annulla i gap in stato attivo (stato iniziale) e le assegnazioni collegate
		
		$res = false;
		
		// Recupero id_org se non è passato in argomento
		$id_org = ($id_org ? $id_org : $this->id_org);
		
		// Trovo gap attivi
		$gaps = $this->getGapActive($id_org, $year);
		
		if($gaps) {
			// Recupero solo id
			$gaps = array_keys($gaps);
		
			// Annullo i gap attivi
			$res = $this->gap_man->cancelActiveGap($year, $id_org);
			
			// Annullo assegnazioni collegate
			if($res) $this->courseassn_man->cancelActiveAssn($year, $id_org, $gaps, false);
		}
		
		// Out
		return $res ;
	}
	
	
	public function cancelGapByUser($users, $canc_assn = false, $reason = 'user suspended') {
		//>> Annulla i gap aperti dell'utente e le sue assegnazioni aperte
		
		$res = false;
		
		// Recupero gap attivi
		$gap = $this->gap_man->getGapByUser($users);
		
		if($gap) {
			// Prendo solo gli id
			$gap = array_keys($gap);
			
			// Annullo gap
			$res = $this->gap_man->cancelGap($gap, $reason);
		
			// Annullo assegnazioni (se richiesto)
			if($canc_assn) {
				$assn = $this->getAssnByGap($gap, array(_ASSN_STATUS_ACTIVE, _ASSN_STATUS_PREPARATION), 'id_assn');
				if($assn)
					$this->courseassn_man->cancelAssn($assn, $reason, false);
			}	
		}
			
		// Out
		return $res;
	}
	
	
	public function updGapStatusById($id_gap, $status){
		//>> Aggiorna lo stato del gap 
		
		// Aggiorno il gap
		$res = $this->gap_man->updGapStatusById($id_gap, $status);
		
		// Se è un annullamento, annullo anche le assegnazioni
		if($status == _GAP_STATUS_CANCELED) {
			
			$assn = $this->courseassn_man->getAssnByGap($id_gap);

			if($assn) {
				$id_assn = array_column($assn, 'id_assn');
				$this->courseassn_man->cancelAssn($id_assn, 'gap canceled', false);
			}
		}
		
		return $res;
	}
	
	
	public function updGapRqmtById($id_gap, $value){
		//>> Aggiorna il valore del requirement del gap (num. corsi)

		$res = $this->gap_man->updGapRqmtById($id_gap, $value);
		
		//Out
		return $res;
	}
	
	
	public function updGapCataById($id_gap, $id_cata){
		//>> Aggiorna il catalogo del gap

		$res = $this->gap_man->updGapCataById($id_gap, $id_cata);
		
		//Out
		return $res;
	}
	
	
	public function countAssn($id_gap, $active = true, $close = false) {
		//>> Conta le assegnazioni in base agli argomenti passati
		
		$status = array();
		
		if($active)
			$status[] = _ASSN_STATUS_ACTIVE;
			
		if($close)
			$status[] = _ASSN_STATUS_CLOSE;
			
		$res = $this->gap_man->countAssn($id_gap, $status);
		
		return $res;	
	}
	
	
	public function cataIsAdmitted($id_gap, $id_cata_new) {
		//>> Controlla se il cambiamento del nuovo catalogo è ammesso
		//>> Restituisce true se il catalogo è ammesso, un codice testuale se non lo è.
		
		$id_org = 0;
		$gap_man = &$this->gap_man;
		$proposal_man = new ProposalManager();
		
		// Recupero utente del gap
		$id_user = $this->getOwner($id_gap);
	
		// Recupero i gap attivi dell'utente
		$gaps = $this->gap_man->getGapByUser($id_user);
		
		// Recupero info gap da modificare
		$gap_info = isset($gaps[$id_gap]) ? $gaps[$id_gap] : false;
		
		
		//! Controllo se il gap è attivo
		if (!$gap_info) return '_STATUS';
		
		//! Controllo se il catalogo è duplicato
		foreach ($gaps as $gap) {
			
			if ($id_gap == $gap['id_gap'])
					$id_org = $gap['idOrg'];
			
			elseif ($gap['id_catalogue'] ==  $id_cata_new)
					return '_CATALOGUE';
		}
		
		//! Controllo se gli assessment conclusi sono presenti nel nuovo catalogo
		$act_ass 		= $gap_man->getCourseCatalogOrg($id_org, $gap_info['id_catalogue']);
		$new_ass 		= $gap_man->getCourseCatalogOrg($id_org, $id_cata_new);
		
		$act_ass_compl 	= array_intersect_key($act_ass, $gap_man->getAssessmentComplete($id_user));
		
		foreach ($act_ass_compl as $ass) {
			if (!array_key_exists($ass['idCourse'], $new_ass))
				return '_ASSESSMENT';
		}

		//! Controllo le eventuali assegnazioni
		$assn = $this->getAssnByGap($id_gap, false, 'keys');
		
		if ($assn) {
			// Se ci sono assegnazioni, controllo che gli assessment del nuovo catalogo 
			// contengano i corsi già assegnati (le proposte degli assessment possono essere modificate)
		
			$new_ass_id = array_keys($new_ass);
			$courses = $proposal_man->getProposalKeys($new_ass_id, 'id_course');

			foreach ($assn as $row) {
				if ( !in_array($row['id_entry'], $courses) )
					return '_ASSIGNMENTS';
			}
		}
		
		//! Ok
		return true;	
	}
		
	
	public function getGapNumber($year = false, $id_org = false, $filter_text = false) {
		//>> Restituisce il numero di gap in base ai criteri in argomento
		
		$whrExp = false;
		
		// Recupero id_org se non è passato in argomento
		$id_org = ($id_org ? $id_org : $this->id_org);
		
		// Formo espressione where se è in corso una ricerca
		if($filter_text)
			$whrExp = str_replace("[@filter_text]", $filter_text, $this->gap_man->getFilterExpression());
		
		// Recupero il numero di gap in base agli argomenti
		$retVal = $this->gap_man->getGapNumber($id_org, $year, $filter_text);
		
		// Out
		return $retVal;	
	}
	
	
	public function getAssnByGap($id_gap, $status = false, $info_mode = 'compact') {
		//>> Restituisce le informazioni sulle assegnazioni di uno specifico gap
		
		$res = array();
		$full_info = ($info_mode == 'compact' || $info_mode == 'full');
		
		//Recupero le assegnazioni
		$assn =  $this->courseassn_man->getAssnByGap($id_gap, $status, $full_info);
		
		//Esco se non ci sono valori
		if (!$assn) return $res;
		
		switch ($info_mode) {
			case 'compact':
				$col_select = array('id_assn', 'date_ins', 'course_code', 'course_name', 'status', 'user_userid');
				
				foreach($assn as $id => $row) {
					$res[] = array_intersect_key($row, array_flip($col_select));
				}
				
				break;
				
			case 'keys':
			case 'full':
				$res = $assn;
				
				break;
				
			default:
				$res = array_column($assn, $info_mode);
		}
		//Out
		return $res;
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
	

	public function getGapTempInvalid($type_check){
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
						."	IFNULL(org_code,'') = '' OR  IFNULL(catalogue_name,'') = '' OR NOT (requirement > 0)"
						."	) ";
			
			break;
			case _ORG_NOT_MATCH:
			
				$whrExp = "org_code NOT IN (SELECT code FROM %adm_org_chart_tree WHERE lev = 1 AND idOrg = ".$id_org.") ";
			
			break;
			case _CATALOG_NOT_REG:
				
				$catalogue = $this->getCatalogByOrg($id_org);	
				
				if(count($catalogue) > 0)
					$whrExp = "IFNULL(id_catalogue, 0) NOT IN (".implode(", ", array_keys($catalogue)).") ";
				else
					$whrExp = "1";
					
			break;
			case _CATALOG_EMPTY:
				
				$catalogue = $this->getEmptyCatalogue();	
				
				if(count($catalogue) > 0)
					$whrExp = "IFNULL(id_catalogue, 0) IN (".implode(", ", array_keys($catalogue)).") ";
				else
					$whrExp = "1";

			break;
			case _GAP_EXISTS:
				
				$whrExp = "T.id_user IN (SELECT id_user FROM %lms_gap "
						 ."		WHERE id_user <> 0 AND status IN (".$this->_getStatusOpen().")) ";
				/* 
				$whrExp = "CONCAT(T.id_user, T.id_catalogue) IN (SELECT CONCAT(id_user, id_catalogue) FROM %lms_gap "
						 ."		WHERE id_user <> 0 AND status IN (".$this->_getStatusOpen().")) ";
				*/
				// E' consentito un gap aperto per utente. Il where commentato ammette più gap (utente/catalogo)
			break;
			case _GAP_DUPLICATE:
				
				$whrExp = " (SELECT COUNT(*) FROM %lms_gap_temp	
								WHERE id_user = T.id_user 			
									AND user_ins = T.user_ins AND id_org = T.id_org AND file_row < T.file_row)  > 0 ";
				/*
				$whrExp = " (SELECT COUNT(*) FROM %lms_gap_temp	
								WHERE id_user = T.id_user AND id_catalogue = T.id_catalogue 				
									AND user_ins = T.user_ins AND id_org = T.id_org AND file_row < T.file_row)  > 0 ";
				*/		
			break;			 
			case _GAP_NOT_FOUND:
			
				$whrExp = "IFNULL(CONCAT(T.id_user, T.id_catalogue), '') NOT IN (SELECT CONCAT(id_user, id_catalogue) FROM %lms_gap "
						 ."WHERE status IN (".$this->_getStatusOpen().")";
			
			break;
			case _USER_STATUS_SUSPEND:
			
				$whrExp = "T.id_user IN (SELECT idst FROM %adm_user WHERE valid = 0) ";
			
			break;
		}
		
		// Preparo la stringa SQL
		$query = "SELECT T.* , '".$type_check."' AS TRec "
				." FROM %lms_gap_temp T "
				." WHERE T.user_ins = ".$id_user." AND T.id_org = ".$id_org." AND ". $whrExp
				." ORDER BY T.file_row";
				
				
		// Lancio la query
		$result = sql_query($query);
		
				
		while($row = sql_fetch_assoc($result))
		{
			// Restituisco la riga all'array di risposta
			$res[$row['id_gap_tmp']] = $row;
		}
		
		return $res;
	}
	
	
	public function insGapTemp($arr_data, $operation) {
		//>> Inserisce i dati nella tabella dei gap temporanea
		//>> eliminando prima i precedenti record caricati dall'utente.
		//>> Restituisce null se l'inserimento completo non è andato a buon fine,
		//>> 0 se non ci sono dati da inserire o il numero di record inseriti.
		//>> $arr_data: dati da inserire. $operation: costante tipo operazione 
		
		// Recupero il manager
		$gap_man = &$this->gap_man;


		// Elimino i record caricati in precedenza e preparo variabile di ritorno record inseriti
		// Non è ammesso operare contemporaneamente su ins e upd, quindi elimino tutto.
		if (($gap_man->delGapTemp($this->id_user, $this->id_org)) !== false) $res = 0;
		
		
		if (count($arr_data) > 0 && $res === 0) {
			// Se ci sono dati e l'eliminazione è andata a buon fine, inserisco
		
			foreach ($arr_data as $index => $record) {
				
				// Aggiungo codice operazione
				$record['operation'] = $operation;
				
				// Inserisco record
				$affected = $gap_man->insGap($record, true, ($res == 0));
				
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
	
	
	public function getGapTmpLoad($suspended = false) {
		//>> Restituisce i gap temporanei caricati per l'utente e organizzazione di lavoro
		//>> Se suspended = true restituisce solo quelli sospesi, altrimenti quelli non sospesi
		
		// Recupero il manager
		$gap_man = &$this->gap_man;		
		
		// Lancio il metodo e restituisco il risultato
		$whrExp = (!$suspended ? 'suspended = 0' : 'suspended = 1');

		return $gap_man->getGapTemp($this->id_user, $this->id_org, $whrExp);

	}
	
	
	public function getGapTempInvId($type_checks) {
		//>> Restituisce un array con gli Id dei gap non validi della tabella di appoggio.
		//>> $type_checks è un array di costanti check
		
		$arr_res = array();
		

		// Recupero i gap non validi
		foreach ($type_checks as $check) {
			$arr_inv[$check] = $this->getGapTempInvalid($check);
		}
							
		// Unisco le risposte in un unico array (in caso di stessa chiave, l'elemento di $arr_tmp non viene aggiunto)
		foreach ($arr_inv as $arr_tmp) {
			$arr_res = $arr_res + $arr_tmp;
		}
		
		// Recupero solo la chiave (id del gap temporaneo)
		$arr_res = array_keys($arr_res);
		
		return $arr_res;
		
	}
	
	
	public function insGapFromTemp(&$ret_id) {
		//>> Inserisce i gap dalla tabella dei gap temporanei caricati.
		//>> Tralascia i gap già eventualmente caricati (stesso utente, nome catalogo e stato gap aperto)
		//>> ret_id riporta al chiamante gli id delle nuove assegnazioni inserite.
		
		$ret_id = array();
		$db = DbConn::getInstance();
		
		// Preparo la query
		$query = " INSERT INTO %lms_gap ( "
				."			id_catalogue, id_user, requirement, status, fav_location, user_ins, user_upd, desc_upd, id_manager, "
				."			year_gap, date_ins, date_upd, description) "
				." SELECT t.id_catalogue, t.id_user, t.requirement, "._GAP_STATUS_ACTIVE.", t.fav_location, t.user_ins, t.user_ins, 'Gap entered', t.id_manager, "
				." 			YEAR(NOW()), NOW(), NOW(), CONCAT(IFNULL(t.user_fname,''), ' ', t.user_lname , ' - ', t.catalogue_name, ' - ', CURDATE()) "
				." FROM %lms_gap_temp t "
				." 		LEFT JOIN %lms_gap g ON (t.id_user = g.id_user AND t.id_catalogue = g.id_catalogue) "
				." WHERE (g.id_gap IS NULL OR g.status NOT IN(".$this->_getStatusOpen().")) AND t.suspended = 0 "
				."		AND t.id_user > 0 AND t.user_ins = '".$this->id_user."' AND id_org = '".$this->id_org."' AND operation = '"._OP_INS."'";


		// Lancio la query
		$res = $db->query($query);
		
		// Recupero il numero di inserimenti
		$retVal = $db->affected_rows($res);
		
		// Recupero gli id inseriti
		if ($retVal) {
			$res = $db->query("SELECT id_gap FROM %lms_gap WHERE id_gap >= LAST_INSERT_ID()");
			
			while($row = sql_fetch_assoc($res))
				$ret_id[] = $row['id_gap'];
		}
		
		// Out
		return $retVal;
	}
	
	
	public function updGapFromTemp() {
		//>> Aggiorna i gap in base alla tabella dei gap temporanei caricati.
		//>> Al momento è previsto solo l'aggiornamento del responsabile.
		//>> Tralascia i gap chiusi.

		// Preparo la query
		$query = " UPDATE %lms_gap g "
				."		INNER JOIN %lms_gap_temp t ON (g.id_user = t.id_user AND g.id_catalogue = t.id_catalogue) "
				." SET g.id_manager = t.id_manager "
				." WHERE g.status IN (".$this->_getStatusOpen().") AND t.suspended = 0 "
				."		AND t.id_user > 0 AND t.user_ins = '".$this->id_user."' AND id_org = '".$this->id_org."' AND operation = '"._OP_UPD."'";
				
				
		// Lancio la query e restituisco il numero di righe aggiornate
		$db = DbConn::getInstance();
		$res = $db->query($query);
		
		return $db->affected_rows($res);
	}
	
	
	public function updAssnManager($from_temp = false) {
		//>> Aggiorna le assegnazioni aperte (manager) dei gap aperti
		
		// Preparo where
		if($from_temp)
			$whrExp = " AND EXISTS (SELECT * FROM %lms_gap_temp t 
									WHERE suspended = 0 AND g.id_user = t.id_user AND g.id_catalogue = t.id_catalogue) ";
	
		// Preparo la query
		$query = " UPDATE %lms_assignment a "
				."		INNER JOIN %lms_gap g ON a.id_gap = g.id_gap "
				." SET a.id_manager = g.id_manager "
				." WHERE a.id_assn > 0 AND a.status = "._ASSN_STATUS_ACTIVE." AND g.status IN (".$this->_getStatusOpen().") "
				.  $whrExp;
	
		
		// Lancio la query e restituisco il numero di righe aggiornate
		$db = DbConn::getInstance();
		$res = $db->query($query);
		
		return $db->affected_rows($res);
	}
	
	
	public function updSuspendGapTempInvalid($type_checks) {
		//>> Sospende i gap temporanei non validi.
		
		// Recupero la chiave dei gap non validi
		$arr_inv = $this->getGapTempInvId($type_checks);
				
		if(count($arr_inv) > 0){
			// Preparo la query
			$query = "UPDATE %lms_gap_temp SET suspended = 1 "
					."WHERE id_gap_tmp IN (".implode(', ', $arr_inv).")";
					

			// Lancio la query
			return sql_query($query);
		}
	}
	
	
	public function updCatalogueId() {
		//>> Aggiorna gli 'id_catalogue' delle assegnazioni temporanee in fase di importazione

		// Preparo la query
		$query = "UPDATE %lms_gap_temp g "
				."INNER JOIN %lms_catalogue ca ON g.catalogue_name = ca.name "
				."SET g.id_catalogue = ca.idCatalogue "
				."WHERE suspended = 0 AND user_ins = '".$this->id_user. "' AND id_org = '".$this->id_org."'";
				
		// Lancio la query
		return sql_query($query);		
		
	}
	
	
	public function updUserFromTemp() {
		//>> Aggiorna alcuni campi degli utenti assegnatari del gap in base alle info della tabella temporanea
		//>> Tempo disponibile, sede e e-mail.
		
		// Query aggiornamento utente normale
		$query = "UPDATE %adm_user u "
				."INNER JOIN %lms_gap_temp g ON u.idst = g.id_user "
				."SET u.email = g.user_email, u.time_availability = g.time_availability, u.job_location = g.job_location "
				."WHERE operation = '"._OP_INS."' AND g.user_ins = '".$this->id_user."'";
		
		$res = sql_query($query);		
		
		// Query aggiornamento utente manager
		$query = "UPDATE %adm_user u "
				."INNER JOIN %lms_gap_temp a ON u.idst = g.id_manager "
				."SET u.email = g.manager_email "
				."WHERE operation = '"._OP_INS."' AND g.user_ins = '".$this->id_user."'";
	
		$res *= sql_query($query);
		
		return $res;
	}
	
	
	public function updUserGapId() {
		//>> Aggiorna gli 'id_user' degli utenti in fase di importazione nella tabella dei gap temporanei
		
		// Preparo la query
		$query = "UPDATE %lms_gap_temp g "
				."LEFT JOIN %adm_user u ON CONCAT('/', g.user_userid)= u.userid "
				."LEFT JOIN %adm_user m ON CONCAT('/', g.manager_userid) = m.userid "
				."SET g.id_user = u.idst, g.id_manager = m.idst "
				."WHERE g.id_gap_tmp > 0 AND suspended = 0 AND user_ins = '".$this->id_user. "' AND id_org = '".$this->id_org."'";

		// Lancio la query
		return sql_query($query);
	}
	
	
	public function getGapYearMin() {
		//>> Restituisce l'anno di inserimento meno recente della tabella delle assegnazioni
		$query = "SELECT MIN(YEAR(date_ins)) AS year FROM %lms_gap";

		
		list($res) = sql_fetch_row(sql_query($query));
		
		return $res;
	}
	
	
	public function checkIsSubscibed($id_assn) {
		//>> Restituisce se l'assegnazione ha un'iscrizione
		return $course_man->checkIsSubscibed($id_assn);
	}
	
	
	public function getNewUserGap($type_user = 'user') {
		//>> Restituisce gli utenti dei gap temporanei (userid) non ancora presenti nel sistema ($type_user = 'user').
		//>> Oppure i manager non ancora presenti a sistema.
		

		// Controllo l'argomento
		
		if(!($type_user == 'user' || $type_user == 'manager')) return;
		
		// Preparo la stringa SQL
		$tus = $type_user;
		
		$fields = $tus."_userid AS userid, "
				. $tus."_fname  AS fname, "
				. $tus."_lname  AS lname, "
				. $tus."_email  AS email"
				. ($type_user == 'user' ? ", g.time_availability, g.job_location" : "");		

		
		$query = "SELECT DISTINCT g.".$fields.", '".$tus."' AS TRec "
				."FROM %lms_gap_temp g "
				."	LEFT JOIN %adm_user u ON CONCAT('/', g.".$tus."_userid) = u.userid "
				."WHERE g.suspended = 0 AND g.user_ins = '".$this->id_user."' AND g.id_org = '".$this->id_org."' AND u.idst IS NULL "
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
	
	
	public function getGapTempManager() {
		//>> Recupera gli id dei manager dei gap temporanei
			
			$managers 	= array();
			
			// Recupero i gap
			$gap = $this->getGapTmpLoad(false);
			
			// Recupero i manager
			foreach ($gap as $record) {
				
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
	
	
	public function getGapId($year = false, $id_org = false, $filter_text = false) {
		//>> Restituisce tutti gli id dei gap in base ad alcuni criteri filtro
		
		$result = array();
				
		// Recupero id_org se non è passato in argomento
		$id_org = ($id_org ? $id_org : $this->id_org);
		
		// Formo espressione filtro 
		$whrExp = "idOrg = ".(int)$id_org;
		
		if($year)
			$whrExp .= " AND YEAR(date_ins) = ".(int)$year;
		
		if($filter_text)
			$whrExp .= " AND " . str_replace("[@filter_text]", $filter_text, $this->gap_man->getFilterExpression());
	
		// Lancio il metodo 
		$res = $this->gap_man->getGap($whrExp, false, false, false);
		
		// Recupero solo id
		foreach ($res as $k => $row) {
			$result[] = $row['id_gap'];		
		}
		
		return $result;
	}
	
	
	public function getGapById($id_gap, $id_org = false, $full = true) {
		//>> Restituisce i gap in base agli ID passati in argomento
		
		return $this->gap_man->getGapById($id_gap, $id_org, $full);
	}
	
	
	public function getEmptyCatalogue() {
		return $this->gap_man->getEmptyCatalogue();
		
	}


	public function getOwner($id_gap) {
		//>> Restituisce il proprietario del gap (idst o id_user)
		
		$retVal = false;
		
		$res = $this->gap_man->getSingleGap($id_gap);
		
		if($res)
			$retVal = $res['id_user'];
			
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
	
	
    public function getUserInfo($idst, $info_type) {
		//>> Restituisce le informazioni dell'utente in base al suo id
		//	 $info_type deve contenere costanti come ACL_INFO_EMAIL
		
		$retVal = false;
		$acl_manager = $this->acl_man;
		
		$u_info = $acl_manager->getUser($idst, false);
		
		if ( $u_info ) 
			$retVal = $u_info[$info_type];

		return $retVal;
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
