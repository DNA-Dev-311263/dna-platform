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


class ProposalManager
{

	protected $lang;
	protected $acl_man;
	protected $logUserInfo;
	protected $course_man;

	public function __construct(){
		
		require_once(_lms_.'/lib/lib.course.php');
				
		$this->lang =& DoceboLanguage::CreateInstance('admin_proposal', 'lms');
		$this->acl_man =& Docebo::user()->getAclManager();
		$this->course_man = new Man_Course();
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
	
	
	private function _getUnusedCourseSql($id_proponent, $whrExp = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce la stringa sql per il recupero dei corsi non ancora aggiunti
		
		$st = array(CST_PREPARATION, CST_AVAILABLE, CST_EFFECTIVE);
		
		// Preparo strigna Where
		if($whrExp !== false)
			$whrExp  = ' AND ('. $whrExp .')';
		
		// Ordinamenti
		if(is_string($orderExp))
			$orderExp = ' ORDER BY '.$orderExp;
		
		// Limit
		if(is_string($limitExp))
			$limitExp = ' LIMIT '.$limitExp;
		
		// Preparo query
		$query 	= "SELECT c.idCourse AS id, c.idCourse, c.code, c.name, c.course_type, c.status, c.idCategory, REPLACE(ca.path, '/root/', '') AS category" 
				. "	FROM %lms_course c"  	
				. "	LEFT JOIN %lms_category ca ON c.idCategory = ca.idCategory" 
				."	WHERE ((c.course_type = 'elearning' AND course_edition = 1) OR c.course_type = 'classroom')"
				."		AND subscribe_method = 9"	
				."		AND status IN (".implode(",", $st).")"
				."		AND c.idCourse NOT IN (SELECT id_course FROM %lms_course_proposal WHERE id_proponent = ".(int)$id_proponent.") "
				. 	$whrExp . $orderExp . $limitExp;
					
		return $query;	
	}
	
	
	public function countUnusedCourses($id_proponent, $id_category = false, $whrExp = false){
		//>> Restituisce il numero di corsi non aggiunti per i criteri passati in argomento
		
		// Preparo espressione where
		if(!$whrExp) 
			$whrExp = "1";
		
		if($id_category) {
			$cat = $this->course_man->getCategoryDescendants($id_category);
			if($cat) $whrExp .= " AND c.idCategory IN (" . implode(",", $cat) . ")";
		}
		
		// Recupero la query
		$query = $this->_getUnusedCourseSql($id_proponent, $whrExp, $orderExp, $limitExp);
		
				
		//lancio la query e recupero il numero di righe
		$res = sql_num_rows(sql_query($query));	
		
		return $res;
	}
	
		
	public function getUnusedCourses($id_proponent, $id_category = false, $whrExp = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce le informazioni di base dei corsi selezionabili

		// Preparo espressione where
		if(!$whrExp) 
			$whrExp = "1";
		
		if($id_category) {
			$cat = $this->course_man->getCategoryDescendants($id_category);
			if($cat) $whrExp .= " AND c.idCategory IN (" . implode(",", $cat) . ")";
		}
		
		// Recupero la query
		$query = $this->_getUnusedCourseSql($id_proponent, $whrExp, $orderExp, $limitExp);
		
				
		//Lancio query
		$result = sql_query($query);


		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['idCourse']] = $row;
		}
		
		//Out
		return $res;
	}
	
	
	public function getProposalNumber($id_proponent) {
		//>> Restituisce il numero di corsi proposti in base al corso proponente
		
		$query =	"SELECT COUNT(*)"
					." FROM %lms_course_proposal"
					." WHERE id_proponent = " .(int)$id_proponent;

		list($res) = sql_fetch_row(sql_query($query));

		return $res;
		
	}
		
			
	public function getProposal($whrExp = false, $orderExp = false, $limitExp = false) {
		//>> Restituisce l'elenco delle proposte
		
		$res = array();
		
		//Preparo strigna Where
		if($whrExp !== false)
			$whrExp  = ' AND '. $whrExp;
		
		//La completo con ordinamenti
		if(is_string($orderExp))
			$orderExp = ' ORDER BY '.$orderExp;
		
		//La completo con limit
		if(is_string($limitExp))
			$limitExp = ' LIMIT '.$limitExp;

		//Preparo Query
				
		$query = "SELECT p.id_proposal AS id, p.id_proposal, p.id_proponent, p.id_course, p.from_score, p.to_score,"
				." c.code, c.name, c.course_type, c.status, c.date_begin, c.date_end, c.create_date, c.box_description, c.description,"
				." ca.idCategory, ca.path, REPLACE(ca.path, '/root/', '') AS category "
				." FROM %lms_course_proposal p "
				."	JOIN %lms_course c ON p.id_course = c.idCourse "
				."	JOIN %lms_category ca  ON c.idCategory = ca.idCategory "
				." WHERE 1 " . $whrExp. $orderExp . $limitExp;
				
		
		//Lancio query
		$result = sql_query($query);

		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_proposal']] = $row;
		}
		
		//Out
		return $res;	
	}
	
	
	public function updProposalScore($id_proposal, $from_score = false, $to_score = false){
		//>> Aggiorna i punteggi soglia per la proposta corsi
		
		$query = '';
		$retVal = false;
		$set_from = $set_to = '';
		
		//Preparo stringa SET
		if(is_numeric($from_score))
			$set_from = "p.from_score = ".$from_score;
				
		if(is_numeric($to_score ))
			$set_to =  ($from_score ? ", " : "") . "p.to_score = ".$to_score;
		
		
		//Termino e lancio query se i valori sono validi
		if($set_from || $set_to){
			
			$query = "UPDATE %lms_course_proposal p
						SET ". $set_from . $set_to. " 
						WHERE id_proposal = " . (int)$id_proposal;
						
			//Lancio query
			$retVal = sql_query($query);
		}
		
		//Out
		return $retVal;
	}
	
	
	public function insProposal($id_proponent, $id_course) {
		//>> inserisce i corsi come proposte del corso proponent
		//>> id_course può essere un array di id corso

		$retVal = false;
		$cnt = 0;
		$query = "";
		$select = "";
		
		$courses = is_array($id_course) ? $id_course : array($id_course);
		
		//Formo la select con i corsi da inserire
		foreach ($courses as $course) {
			$select .= ($cnt == 0 ? "" : "UNION ");
			$select .= "SELECT " . (int)$id_proponent.", " . (int)$course . PHP_EOL;
			
			$cnt += 1;
		}
		
		if($select) {
			//Formo la insert into
			$query =  " INSERT INTO %lms_course_proposal (id_proponent, id_course) "
						. PHP_EOL . $select;
						
			//Lancio query
			$retVal = sql_query($query);
		}
		
        //Out        
		return $retVal;
	}
	
	public function delAllProposal($id_proponent) {
		//>> Elimina tutte le proposte di un proponent
		
		$query =	"DELETE FROM %lms_course_proposal"
					." WHERE id_proponent = ".(int)$id_proponent;

        $res = sql_query($query);
                
		return $res;
	}


	public function delProposal($id_proposal) {
		//>> Elimina la proposta passata in argomento
		
		$query =	"DELETE FROM %lms_course_proposal"
					." WHERE id_proposal = ".(int)$id_proposal;

        $res = sql_query($query);
                
		return $res;
	}
	
	
	public function getProposalKeys($id_proponent, $key = false){
		//>> Restituisce le chiavi delle proposte in base al proponente passato in rgomento
		//>> id_proponent può essere un array di id
		
		$res = array();
		
		// Preparo proponent
		$arr_pnt = is_array($id_proponent) ? $id_proponent : array($id_proponent);
		
		// Preparo query
		$query = "SELECT p.id_proposal, p.id_proponent, p.id_course "
				." FROM %lms_course_proposal p "
				." WHERE id_proponent IN ('" . implode("','", $arr_pnt) ."')";
		
		//Lancio query
		$result = sql_query($query);

		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_proposal']] = $row;
		}
		
		//Se è richiesta una specifica colonna, restituisco i suoi valori univoci
		if($key && $res) {
			$res = array_column($res, $key);
			$res = array_unique($res);
		}
		
		//Out
		return $res;			
	}
	
	
	public function getFilterExpression() {
		//>> Restituisce una stringa where per le ricerche sulla query dei corsi non aggiunti
		//>> Eseguire il replace del tag [@filter_text]
		
		return "(c.code LIKE '%[@filter_text]%' OR c.name LIKE '%[@filter_text]%')"; 
	}

}

?>
