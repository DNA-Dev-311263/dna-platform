<?php defined("IN_FORMA") or die("Direct access is forbidden");

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|	BKO: by ABR													|
\ ======================================================================== */

Class ExtractionAlms extends Model
{
	
	protected $tms_model;
	protected $extraction_man;
	protected $courseassn_man;
	protected $acl_man;
	protected $id_org;
	protected $id_user;


	public function __construct($id_org = 0, $id_user = 0) {
	
		require_once(_lms_.'/lib/lib.extraction.php');
		require_once(_lms_.'/lib/lib.courseassn.php');
		require_once(_base_.'/lib/lib.pluginmanager.php');

		$this->acl_man = Docebo::user()->getAclManager();
		
		$this->extraction_man = new ExtractionManager();  		//manager dell'estrazione dati
		$this->courseassn_man = new CourseassnManager();  		//manager delle assegnazioni
		
		//>> $id_user è l'id dell'operatore che sta estraendo/visualizzando i dati.
		//>> $id_org l'organizzazione di lavoro (se è godadmin può non essere la sua).
		
		$this->id_user = $id_user;
		$this->id_org = $id_org;
		
		// Recupero il model del plugin tmsmeeting se è attivo
		if ($tms_controller = PluginManager::get_feature('alms', 'tmsmeeting')) {
			$this->tms_model = $tms_controller->getModel();
		}

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
	

	public function getList($all = false) {
		//>> Restituisce l'elenco delle estrazioni disponibili
		//	 La chiave dell'array è il codice del report

		$retVal = array();
		$retVal['CalendarLearning'] = Lang::t('_EXCT_CAL_LEARNING_TITLE', 'extraction');			//richiede gestione assegnazioni
		$retVal['CalendarLearningDay'] = Lang::t('_EXCT_CAL_LEARNING_DAY_TITLE', 'extraction');
		$retVal['CourseActiveUsers'] = Lang::t('_EXCT_CRS_ACTIVE_USERS_TITLE', 'extraction');
		$retVal['CoursepathProgress'] = Lang::t('_EXCT_COURSEPATH_PROGRESS_TITLE', 'extraction');
		
		$retVal['UserAssnStatus'] 	= Lang::t('_EXCT_USERASSN_STATUS_TITLE', 'extraction'); 		//richiede gestione assegnazioni
		$retVal['NoShowUser'] 		= Lang::t('_EXCT_USER_NOSHOW_TITLE', 'extraction'); 			//richiede gestione assegnazioni
		
		// Report da plugin TmsMeeting
		if ($this->tms_model) {
			
			$if = $this->tms_model->getListInfo('TmsEditionList');
			$retVal[ $if['code'] ] = $if['title'];
			
			$if = $this->tms_model->getListInfo('TmsMeetingList');
			$retVal[ $if['code'] ] = $if['title'];
		}

		// Report ammessi solo per godadmin
		if($all === true) {
			$retVal['CalendarCost'] = Lang::t('_EXCT_CAL_COST_TITLE', 'extraction');				//richiede gestione assegnazioni
			$retVal['CalendarCostGroup'] = Lang::t('_EXCT_CAL_COST_GROUP_TITLE', 'extraction');		//richiede gestione assegnazioni
		}
		
		return $retVal;
	}
	
	
	/**
	 * Restituisce un oggetto contenente le proprietà del report di esportazione
	 */
	public function getProps($code) {

		$objInfo = new stdClass();
		$objInfo->code = $code;
		
		switch ($code)
		{
			case 'CoursepathProgress':
			
				$dtFrom = date("Y-m-d", strtotime(date("Y-01-01")));
				$dtTo	= date("Y-m-d");
										 	
				$objInfo->title 			= Lang::t('_EXCT_COURSEPATH_PROGRESS_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','ctm_field');
																		
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'ctm_field' => false );							
				break;

			case 'CourseActiveUsers':
			
				$dtFrom = date("Y-m-d", strtotime("first day of previous month", strtotime("now")));
				$dtTo	= date("Y-m-d", strtotime("last day of previous month", strtotime("now")));
										 	
				$objInfo->title 			= Lang::t('_EXCT_CRS_ACTIVE_USERS_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org');
																		
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'));							
				break;
					
			case 'CalendarLearning':
				
				$dtFrom = date("Y-m-d", strtotime("first day of previous month"));
				$dtTo	= date("Y-m-d");
														 	
				$objInfo->title 			= Lang::t('_EXCT_CAL_LEARNING_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','status');
									
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'status'	=> 'all');
						
				break;
				
			case 'CalendarLearningDay':
				
				$dtFrom = date("Y-m-d", strtotime("first day of previous month"));
				$dtTo	= date("Y-m-d");
				
				$objInfo->title 			= Lang::t('_EXCT_CAL_LEARNING_DAY_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','status');
													
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'status'	=> 'all');							
				break;
				
			case 'UserAssnStatus':
				
				$dtFrom = date("Y-m-d", strtotime(date("Y-01-01")));
				$dtTo	= date("Y-m-d");
				
				$objInfo->title = Lang::t('_EXCT_USERASSN_STATUS_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','status');

				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'status'	=> 'all');	
				
				break;
				
			case 'CalendarCost':
				
				$dtFrom = date("Y-m-d", strtotime("first day of previous month"));
				$dtTo	= date("Y-m-d");
				
				$objInfo->title 			= Lang::t('_EXCT_CAL_COST_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','status');
													
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'status'	=> 'all');							
				break;
				
			case 'CalendarCostGroup':
				
				$dtFrom = date("Y-m-d", strtotime("first day of previous month"));
				$dtTo	= date("Y-m-d");
				
				$objInfo->title 			= Lang::t('_EXCT_CAL_COST_GROUP_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','status','group');
													
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'group'		=> 'all',
													'status'	=> 'all' );							
				break;
				
			case 'NoShowUser':
				
				$dtFrom = date("Y-m-d", strtotime(date("Y-01-01")));
				$dtTo	= date("Y-m-d");
				
				$objInfo->title 			= Lang::t('_EXCT_USER_NOSHOW_TITLE', 'extraction');
				$objInfo->filterAllowed 	= array('date','org','status');
									
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'status'	=> 'all');							
				break;
			
			case 'TmsEditionList':
			case 'TmsMeetingList':
			
				$info = $this->tms_model->getListInfo($code);
				
				$dtFrom = date("Y-m-d", strtotime("first day of previous month"));
				$dtTo	= date("Y-m-d", strtotime("last day of next month"));

				$objInfo->title 			= $info['title'];
				$objInfo->filterAllowed 	= array('date','org','status');
					
				$objInfo->defaults			= array('date_from' => Format::date($dtFrom, 'date'),
													'date_to' 	=> Format::date($dtTo, 'date'),
													'status'	=> 'all');							
				break;
		}
		
		return $objInfo;
		
	}
	
	
	/**
	 * Restituisce i campi custom inseribili nel report (nome tradotto)
	 */
	protected function getLangCustomFields($exct_code) {
		
		$lang_fields = array();
		
		switch ($exct_code)
		{
			case 'CoursepathProgress':
				$lang_fields = 	array(	'Città', 'CAP', 'Provincia', 'Telefono', 'Cellulare', 'Luogo di nascita', 'Data di nascita', 'Codice fiscale', 'Professione', 
										'Disciplina', 'Inquadramento professionale', 'Numero iscrizione albo', 'Invitato da', 'Farmacia', 'Indirizzo completo farmacia'
								);
				break;
		}		
		
		return $lang_fields;
	}
	
	
	public function getTableInfo($code, $full_fields = false, $id_org = 0) {
		//>> Restituisce un oggetto contenente le informazioni campi utili alla creazione della tabella di esportazione
		
		$objInfo = new stdClass();
		$objInfo->code = $code;
		
		// Recupero id_org
		if (! $id_org ) $id_org = $this->id_org;
		
		switch ($code)
		{
			case 'CoursepathProgress':

				$fields = array('codice_percorso', 'nome_percorso', 'corsi', 'username', 'cognome', 'nome', 'email', 'ultimo_accesso', 'data_iscrizione', 'avanzamento');
													
				if ($full_fields) {
					
					foreach ($this->getLangCustomFields($code) as $fld) {
						$fields[] = $fld;
					}
				}
						
				break;
				
			case 'CourseActiveUsers':
									
				$fields = array('cid_utente', 'cognome_utente', 'nome_utente', 'email', 'nome_corso', 'oggetto_didattico', 'data_accesso');						
									
				break;
			
			case 'CalendarLearning':
				
				$fields = array('codice_corso', 'nome_corso', 'tipo_erogazione', 'codice_edizione', 'max_iscrizioni', 
													'stato_edizione', 'docente', 'note_interne', 'inizio_edizione', 'fine_edizione', 
													'utenti_iscritti', 'utenti_formati', 'utenti_assenti');
									
				break;
				
			case 'CalendarLearningDay':
			
				$fields = array('codice_corso', 'nome_corso', 'tipo_erogazione', 'codice_edizione', 
													'stato_edizione', 'inizio_edizione',  'utenti_iscritti', 'docente', 
													'num_lezione', 'inizio_lezione', 'fine_lezione');
																		
				break;
				
			case 'UserAssnStatus':
				
				$fields = array('data_assegnazione', 'cid_utente', 'cognome', 'nome', 'email',
													'codice_corso', 'nome_corso', 'codice_edizione', 'inizio_edizione', 'fine_edizione', 
													'data_iscrizione', 'stato_edizione', 'stato_assegnazione');
				
				
				break;
				
			case 'CalendarCost':

				$fields = array('codice_corso', 'nome_corso', 'tipo_erogazione', 'max_iscrizioni', 'codice_edizione', 
													'costo', 'stato_edizione', 'docente', 'note_interne', 'inizio_edizione', 'fine_edizione', 
													'utenti_iscritti');						
				break;
				
			case 'CalendarCostGroup':
				
				$fields = array('codice_corso', 'nome_corso', 'tipo_erogazione', 'codice_edizione', 
													'stato_edizione', 'docente', 'inizio_edizione', 'fine_edizione', 'costo', 'utenti_iscritti', 
													'percentuale', 'fattura');				
				break;
				
			case 'NoShowUser':
				
				$fields = array('cid_utente', 'cognome', 'nome', 'email',
													'codice_corso', 'nome_corso', 'ultima_edizione', 'stato_assegnazione', 'no_show');
					
				break;
			
			case 'TmsEditionList':
			case 'TmsMeetingList':
			
				$info = $this->tms_model->getListInfo($code);
				$fields = $info['header'];
					
				break;
		}
		
		$objInfo->columns			= $this->_getColumnInfo($fields);
		$objInfo->fields			= $fields;
		$objInfo->count 			= count($fields);
		
		return $objInfo;
	}
	
	
	private function _getColumnInfo($fields){
		//>> Restituisce un array contenente i campi della tabella con nome tecnico e descrizione) 
		//>> (la descrizione è il nome tecnico se non ci sono le traduzioni nella sezione lingue)
		
		$res = array();
		
		foreach ($fields as $val) {
			
			$text_key 	= '_EXCT_F_'.strtoupper($val);
			$tmp_name	= Lang::t($text_key, 'extraction');

			if ($tmp_name == trim(strtolower(str_replace('_', " ", $text_key)))) {
				//Non c'è traduzione, utilizzo il nome tecnico
				$res[$val] = array('key' => $val, 'label' => $val);
			} else {
				//C'è traduzione, la recupero
				$res[$val] = array('key' => $val, 'label' => $tmp_name);
			}
		}
		
		return $res;
	}
		
	public function getExtraction($args) {
		//>> Lancia l'estrazione in base al codice passato in argomento
	
		$res = array();
		
		// Recupero il codice
		$exct_code = $args->exct_code;
		
		// Recupero il metodo
		$method = 'get'.$exct_code;
		
		// Porto lo stato a false se la combo passa 'all'
		$args->status = ($args->status == 'all' ? false : $args->status);
		
		// Porto il gruppo a false se la combo passa 'all'
		$args->id_group = ($args->id_group == 'all' ? false : $args->id_group);
		
		
		// Eseguo
		switch ($exct_code)
		{
			case 'CoursepathProgress':
				$lang_custom_fields = $this->getLangCustomFields($exct_code);
				$lang_custom_fields = $args->ctm_field ? $lang_custom_fields : false;

				$res = $this->extraction_man->$method($args->date_from, $args->date_to, $args->id_org, $lang_custom_fields);
				
				break;
				
			case 'CourseActiveUsers':
				$res = $this->extraction_man->$method($args->date_from, $args->date_to, $args->id_org);
				
				break;			
			
			case 'CalendarLearning':
			case 'CalendarLearningDay':
			case 'UserAssnStatus':
			case 'NoShowUser':
			case 'CalendarCost':
				$res = $this->extraction_man->$method($args->date_from, $args->date_to, $args->status, $args->id_org);
				
				break;
				
			case 'CalendarCostGroup':
				$res = $this->extraction_man->$method($args->date_from, $args->date_to, $args->status, $args->id_org, $args->id_group);
				
				break;
			
			case 'TmsEditionList':
			case 'TmsMeetingList':
				$res = $this->tms_model->$method($args->date_from, $args->date_to, $args->status, $args->id_org);
				
				break;
		}
		
		// Out
		return $res;
		
	}
	
	public function getOrgForDropdown(){
		//>> Usata per restituire le organizzazioni da inserire in combo
		
		$res = array();
		$org = $this->courseassn_man->getOrgInfoByLevel();
			
		foreach($org as $k => $v) 
			$res[$v['idOrg']] = $v['code'];
		
		return $res;	
	}
	
	
	public function getGroupForDropdown(){
		//>> Usata per restituire i gruppi da inserire in combo
						
		// Aggiungo l'item seleziona tutto
		$res = array('all' => Lang::t('_SELECT_ALL', 'standard'));
		

		// Recupero i gruppi
		$groups = $this->acl_man->getAllGroupsId(false, false, false);
			
		foreach($groups as $idst => $row) 
			$res[$idst] = $row['groupid'];
			
		// Out
		return $res;	
	}
	
	
	public function getStatusForDropdown($exct_code, $add_all = true){
		//>> Usata per restituire gli stati da inserire in combo
		
		$res = array();
		
		// Recupero gli stati
		switch ($exct_code)
		{
			case 'CalendarLearning':
				$res = $this->extraction_man->getStatusDateDropdown();
				
				break;
				
			case 'CalendarLearningDay':
				$res = $this->extraction_man->getStatusDateDropdown();
				
				break;
				
			case 'UserAssnStatus':
				$res = $this->extraction_man->getStatusAssnDropdown();
				
				// rimuovo stato assegnazione in preparazione
				unset($res[5]);
				
				break;
				
			case 'NoShowUser':
				$res = $this->extraction_man->getStatusAssnDropdown();
				
				// rimuovo stato assegnazione in preparazione
				unset($res[5]);
				
				break;
			
			case 'CalendarCost':
				$res = $this->extraction_man->getStatusDateDropdown();
				
				break;		
			
			case 'CalendarCostGroup':
				$res = $this->extraction_man->getStatusDateDropdown();
				
				break;
				
			case 'TmsEditionList':
			case 'TmsMeetingList':
				$res = $this->extraction_man->getStatusDateDropdown();

				break;
		}
		
		// Aggiungo l'item seleziona tutto
		if ($add_all) $res = array('all' => Lang::t('_SELECT_ALL', 'standard')) + $res;
		
		// Out
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
	
	
	public function argsObject() {
		//>> Restituisce un oggetto per il passaggio degli argomenti dei metodi di estrazione
		
		$objReq = New StdClass();

		$objReq->id_org 	= -1;
		$objReq->date_from	= "";
		$objReq->date_to	= "";
		$objReq->exct_code	= "";
		$objReq->status 	= false;
		$objReq->id_group 	= false;
		$objReq->ctm_field	= 0;

		return $objReq;	
	}
	
	
}

?>
