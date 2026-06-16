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
\ ======================================================================== */

class CommcourseAlms extends Model {

	protected $commcourse_man;
	protected $acl_man;
	protected $id_org;
	protected $id_user;


	public function __construct($id_org = 0, $id_user = 0) {
		
		require_once(_lms_.'/lib/lib.commcourse.php');

		$this->acl_man =& Docebo::user()->getAclManager();
		
		$this->commcourse_man = new CommcourseManager();  		//manager comunicazione corsi

		//>> $id_user è l'id dell'operatore che sta visualizzando i dati.
		//>> $id_org l'organizzazione di lavoro (se è godadmin può non essere la sua).
		
		$this->id_user = $id_user;
		$this->id_org = $id_org;
		
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
	
	
	public function getPerm(){
		//>> Usata per restituire i check dei permessi su profilo amministratori
		
		return array(
			'view' => 'standard/view.png'
		);
	}
	
	public function getIdOrg(){
		
		return $this->id_org;
	}
	
	public function getList($all = false){
		//>> Restituisce l'elenco delle comunicazioni disponibili
		//	 La chiave dell'array è il codice della comunicazione

		$retVal = array();
		//$retVal['ReminderGapAssn'] 	= Lang::t('_REMINDER_GAP_ASSN', 'commcourse');  //Per il momento, sospeso
		$retVal['NewEdition'] 		= Lang::t('_NEW_EDITION', 'commcourse');
		$retVal['ReminderSubs'] 	= Lang::t('_REMINDER_SUBS', 'commcourse');
		$retVal['NoticeAssn'] 		= Lang::t('_NOTICE_ASSN', 'commcourse');   
		
		if($all === true) {
			// Report ammessi solo per godadmin
			//...
		}
		
		return $retVal;
	}


	public function getTableInfo($code) {
		//>> Restituisce un oggetto contenente le informazioni sulla tabella di riepilogo corsi e i default di pagina
		
		$objInfo = new stdClass();
		
		$objInfo->columns = array();
		$objInfo->defaults = array();
		$objInfo->code = $code;
		
		
		$fields	= array('chk_cell', 'code', 'name', 'course_type_t', 'num_assn', 'num_nosubs', 'num_edition', 'num_seat');
		
		switch ($code)
		{
			case 'ReminderGapAssn': 
				$objInfo->defaults = array('date_from' => Format::date(date("Y-01-01"), 'date'));
			break;
			case 'NewEdition':
				$objInfo->columns = $this->_getColumnInfo($fields);
			break;
			case 'ReminderSubs':
				$objInfo->columns = $this->_getColumnInfo($fields);
			break;
			case 'NoticeAssn':
				$objInfo->columns = $this->_getColumnInfo($fields);
				$objInfo->defaults = array('date_from' => Format::date(date("Y-m-d"), 'date'));
			break;
		}
		
		return $objInfo;
	}			
				
	
	public function getCourseSummary($id_org) {
		//>> Recupera la lista dei corsi

		//Traduzione tipi corso
		$type_transl = array('elearning' 	=> Lang::t('_COURSE_TYPE_ELEARNING', 'course'), 
							 'classroom' 	=> Lang::t('_CLASSROOM', 'classroom'), 
							 'virtual' 		=> Lang::t('_VIRTUAL', 'course'));
		
		// Eseguo
		return $this->commcourse_man->getCourseSummary($id_org, $type_transl);
		
	}
	
	
	public function getAssnForNewEdition($course_list, $id_org, $show_location = false) {
		//>> Restituisce le assegnazioni degli utenti interessati alle nuove edizioni (utenti assegnatari senza iscrizioni valide)
		
		$res = array();
		
		//Preparo array corsi
		$arr_id_course = $this->_listToArray($course_list);
		
		//Chiamo il metodo
		return $this->commcourse_man->getAssnForNewEdition($arr_id_course, $id_org, $show_location);
	}
	
	
	public function getGapUndefined($id_org, $date_from) {
		//>> Restituisce i gap con il requirement inferiore al numero di assegnazioni (gap non definito completamente)
		//>> Esclude i gap collegati a cataloghi vuoti
		
		$date_from 	= Format::dateDb($date_from, 'date');
		
		return $this->commcourse_man->getGapUndefined($id_org, $date_from);
	}
	
	
	public function getAssnActive($course_list, $id_org, $date_from) {
		//>> Restituisce le informazioni relative alle nuove assegnazioni (aperte e successive a data indicata)
		
		$res = array();
		$date_from 	= Format::dateDb($date_from, 'date');
		
		//Preparo array corsi
		$arr_id_course = $this->_listToArray($course_list);
							
		//Chiamo il metodo
		return $this->commcourse_man->getAssnActive($arr_id_course, $id_org, $date_from);

	}
	
	
	public function getCommCourseInfo($operation, $course_list, $id_org, $params = false) {
		//>> Chiama il metodo relativo all'operazione in argomento
		
		$manager =  $this->commcourse_man;
		$res = array();
				
				
		//Preparo array corsi
		$arr_id_course = $this->_listToArray($course_list);

		
		switch ($operation)
		{
			case 'new_edition':
				$res = $manager->getAssnForNewEdition($arr_id_course, $id_org, true);
				break;
				
			case 'reminder_subs':
				$res = $manager->getAssnForNewEdition($arr_id_course, $id_org, false);
				break;
				
			case 'notice_assn':
			
				if (isset($params['date_from'])) {
					
					$date_from 	= Format::dateDb($params['date_from'], 'date');
					$res = $manager->getAssnActive($arr_id_course, $id_org, $date_from);
				}			
		}
		
		return $res;
	}

	
	public function getOrgInfoByUser($id_user = false, $lev_org_chart = 1){
		//>> Restituisce le informazioni sul nodo organizzativo di appartenenza
		
		// Recupero id_user se non è passato in argomento
		$id_user = ($id_user ? $id_user : $this->id_user);
		
		// Recupero il nodo organizzativo
		$retVal = $this->commcourse_man->getOrgInfoByUser($id_user, $lev_org_chart);
		
		// Out
		return $retVal;	
	}
	
	
	public function getOrgForDropdown(){
		//>> Usata per restituire le organizzazioni da inserire in combo
		
		$res = array();
		$org = $this->commcourse_man->getOrgInfoByLevel();
			
		foreach($org as $k => $v) 
			$res[$v['idOrg']] = $v['code'];
		
		return $res;	
	}
	
	
	public function getOrgInfoByLevel($lev_org_chart = 1){
		//>> Restituisce le informazioni sui nodi organizzativi di un dato livello
		
		return $this->commcourse_man->getOrgInfoByLevel($lev_org_chart);
	}
	
	
	private function _listToArray($list_string, $conv_int = true) {
		//>> Restituisce un array  formato dagli elementi della stringa divisi da ","
		$arr_item = array();
		$arr_temp = explode(",", $list_string);
		
		//Controllo i valori della lista
		foreach ($arr_temp as $item) {
			$arr_item[] =  (!$conv_int ? trim($item) : (int)trim($item));
		}
		
		return $arr_item;	
	}
	
	
	private function _getColumnInfo($fields) {
		//>> Restituisce un array contenente i campi della tabella con nome tecnico e descrizione)
		
		$res = array();
		
		foreach ($fields as $val) {
			
			switch ($val) {
				
				case 'chk_cell':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_SELECT', 'standard')); break;
					
				case 'code':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_COURSE_CODE', 'standard')); break;
					
				case 'name':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_COURSE_NAME', 'standard')); break;
					
				case 'course_type_t':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_COURSE_TYPE', 'course')); break;

				case 'num_assn':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_ASSIGNMENTS', 'standard')); break;
					
				case 'num_nosubs':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_NOT_SUBSCRIBED', 'commcourse')); break;
				
				case 'num_edition':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_EDITION_AVAILABLE', 'commcourse')); break;
					
				case 'num_seat':
					$res[$val] = array('key'=> $val, 'label' => Lang::t('_SEAT_AVAILABLE', 'commcourse')); break;

				default:				
			}	
		}
		
		return $res;
	}
	
}
