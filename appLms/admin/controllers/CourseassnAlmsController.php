<?php defined("IN_FORMA") or die("Direct access is forbidden");

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt      		| 
|	By ABR     																|
\ ======================================================================== */

use Formalms\lib\Get;


class CourseassnAlmsController extends AlmsController {
	public $name = 'courseassn';

	protected $json;
	protected $acl_man;

	protected $data;

	protected $permissions;

	protected $base_link_course;
	protected $base_link_edition;
	protected $base_link_courseassn;
	protected $user_level;
	protected $id_user;
	protected $id_org_user;
	protected $model;
	protected $import_checks;
	
	
	public function init(){
		
		checkPerm('view', false, 'courseassn', 'lms');
		
		require_once(_base_.'/lib/lib.json.php');
		require_once(_base_.'/lib/lib.mailer.php');
		require_once(_adm_.'/models/UsermanagementAdm.php');

		$this->json = new Services_JSON();
		$this->acl_man =& Docebo::user()->getAclManager();

		$this->base_link_course = 'alms/course';
		$this->base_link_edition = 'alms/edition';
		$this->base_link_courseassn = 'alms/courseassn';
		
		$this->user_level = Docebo::user()->getUserLevelId();
		$this->id_user = Docebo::user()->getIdSt();
		
		$this->model = new CourseassnAlms();
		$org_us = $this->model->getOrgInfoByUser($this->id_user);
		$this->id_org_user = (int)$org_us['idOrg_parent'];
		
	
		//Recupero i permessi dell'utente
		$this->permissions = array(
			'view'	=> checkPerm('view', true, 'courseassn', 'lms'),
			'mod'	=> checkPerm('mod', true, 'courseassn', 'lms'),
		);
		
		//Imposto i controlli di importazione
		$this->import_checks = array(
			_OP_INS	=> array(_MISSING_INFO, _ORG_NOT_MATCH, _COURSE_NOT_REG, _ASSIGNMENT_EXISTS, _ASSIGNMENT_DUPLICATE, _USER_STATUS_SUSPEND),
			_OP_UPD	=> array(_MISSING_INFO, _ORG_NOT_MATCH, _COURSE_NOT_REG, _ASSIGNMENT_DUPLICATE, _ASSIGNMENT_NOT_FOUND)
		);
	}
	
	
	protected function _renderInvalid($error_code){
		
			$this->render('invalid', array(
				'message' => $this->_getMessage($error_code),
				'back_url' => 'index.php?r='.$this->base_link_courseassn.'/show'
			));
			return;
	}

	protected function _getMessage($code){
		$message = "";
		switch ($code){
			case "NO_PERMISSION": $message = "You don't have the required permission";
				break;
			case "PARAM_ERROR": $message = "You don&#96;t have the correct configurations";
				break;
			case "ERROR": $message = "Errors occurred";
				break;
			case "FILE_MISSING": $message = "You did not select the file";
				break;
			case "NO_DATA": $message = "There are no data to upload";
				break;
			case "USER_STATUS_SUSPEND": $message = Lang::t('_USER_STATUS_SUSPEND', 'standard');
				break;
			case "SUCCESS": $message = Lang::t('_OPERATION_SUCCESSFUL', 'standard');
				break;
			case "NO_UPDATE": $message = Lang::t('_OPERATION_NO_UPDATES', 'standard');
				break;
			case "FAILURE": $message = Lang::t('_OPERATION_FAILURE', 'standard');
				break;	
		}
		return $message;
	}
        
	protected function _getBackLink(){
			return getBackUi('index.php?r='.$this->base_link_courseassn.'/show', Lang::t('_BACK', 'standard'));
	}
	
	protected function isUserGodAdmin(){
		//>> Restituisce true se l'utente corrente è un super amministratore
		if($this->user_level == ADMIN_GROUP_GODADMIN)
			return true;
	}
	

	protected function show(){
		//>> Apre la view delle assegnazioni
		
		$result_wizard = Get::req('result', DOTY_MIXED, false);
		$model = &$this->model;
		
		//Recupero l'organizzazione di partenza

		if($this->isUserGodAdmin()){
			// Superadmin

			if($result_wizard) {
				// Se si arriva da un caricamento delle assegnazioni (dal wizard)
				$id_org = Get::req('id_org', DOTY_INT, 0);;
				
			}else{
				// altrimenti recupero la prima organizzazione di primo livello
				$org = $model->getOrgInfoByLevel();
				
				$id_org = (count($org) > 0 ? $org[0]['idOrg'] : 0);
			}	

		}else{
			// Admin normale, recupero l'organizzazione di appartenenza dell'utente
			$id_org = $this->id_org_user;
		}			
	
		
		//Istanzio il modello di classe con l'id dell'organizzazione di lavoro e dell'utente
		$model = new CourseassnAlms($id_org, $this->id_user);
		
		//Librerie
		Util::get_js(Get::rel_path('base').'/lib/js_utils.js', true, true);
		Util::get_js(Get::rel_path('lms').'/admin/views/courseassn/courseassn.js', true, true);
		
		
		//Messaggi dall'upload
		if (stripos("failure", $result_wizard)) 
				UIFeedback::error($this->_getMessage($result_wizard));	
						
		elseif ($result_wizard)
				UIFeedback::info($this->_getMessage($result_wizard));
						
						
		//Invio i parametri di preparazione della view prima del caricamento dati
		//Nel caso si voglia aumentare il numero di colonne condivise, aumentare il numero passato a 'num_var_fields' 
		$this->render('show', array(
			'back_link' => $this->_getBackLink(),
			'model' => $model,
			'permissions' => $this->permissions,
			'base_link_course' => $this->base_link_course,
			'base_link_edition' => $this->base_link_edition,
			'base_link_courseassn' => $this->base_link_courseassn,
			'num_var_fields' => 2,
			'fields' => $this->getTableInfo(),
			'status_list_js' => $this->getJsArrayStatus(),
			'is_godadmin' => $this->isUserGodAdmin()
		));
		
	}
	
	protected function getTableInfo(){
		//>> Recupera array di 
		//>> campi/colonna da rappresentare nella tabella della view
		//>> chiavi da leggere dal set di dati
		
		//Campi in colonna dedicata (statica)
		$status_list_js = $this->getJsArrayStatus();
		
		$fields['static_fields'] = array(
			array('key' => 'date_ins', 'label' => Lang::t('_REGISTER_DATE', 'standard'), 'sortable' => true),
			array('key' => 'user_userid', 'label' => Lang::t('_USERNAME', 'standard'), 'sortable' => true),
			array('key' => 'user_fullname', 'label' => Lang::t('_USER_NAME', 'courseassn'), 'sortable' => true),
			array('key' => 'course_fullname', 'label' => Lang::t('_COURSE_NAME', 'standard'), 'sortable' => true),
			array('key' => 'course_type', 'label' => Lang::t('_COURSE_TYPE', 'course'), 'sortable' => true),
			array('key' => 'status', 'label' => Lang::t('_STATUS', 'standard'), 'sortable' => true, 'formatter' => 'Courseassn.statusFormatter','editor' => 'new YAHOO.widget.DropdownCellEditor({dropdownOptions:'.$status_list_js.'})'),
			array('key' => 'user_subscribed', 'label' => Lang::t('_USER_STATUS_SUBS', 'standard'), 'sortable' => true),
			array('key' => 'user_time_availability', 'label' => Lang::t('_TIME_AVAILABILITY', 'courseassn'), 'sortable' => true),
			
		);

		//Campi in colonna condivisa (dinamica)
		$fields['dyn_fields'] = array(
			array('key' => 'date_upd', 'label' => Lang::t('_UPDATE_DATE', 'standard'), 'sortable' => true, 'selected_on_col' => 1),
			array('key' => 'modifier_fullname', 'label' => Lang::t('_USER_UPDATE', 'courseassn'), 'selected_on_col' => 2),
			array('key' => 'desc_upd', 'label' => Lang::t('_LAST_OPERATION', 'courseassn'), 'sortable' => true,'selected_on_col' => 3),
			array('key' => 'loader_fullname', 'label' => Lang::t('_USER_INSERT', 'courseassn')),
			array('key' => 'manager_fullname', 'label' => Lang::t('_MANAGER_ASSN', 'courseassn'), 'sortable' => true),
			array('key' => 'manager_userid', 'label' => Lang::t('_MANAGER_ASSN_USERID', 'courseassn'), 'sortable' => true),
			array('key' => 'course_virtual', 'label' => Lang::t('_VIRTUAL', 'course'), 'sortable' => true),
			array('key' => 'description', 'label' => Lang::t('_DESCRIPTION', 'standard')),
			array('key' => 'user_job_location', 'label' => Lang::t('_JOB_LOCATION', 'courseassn'), 'sortable' => false, 'selected_on_col' => 0),
			array('key' => 'fav_location', 'label' => Lang::t('_FAV_LOCATION', 'courseassn'), 'sortable' => true),
			array('key' => 'org_code', 'label' => Lang::t('_ORGANIZATION', 'courseassn'), 'sortable' => false),
			array('key' => 'id_gap', 'label' => 'ID gap', 'sortable' => true)
		);
		
		//Elenco chiavi da leggere nel dataTable (widget) dall'array dei dati (vedi getcourseassnlist)
		//Si possono elencare anche i campi non visibili come gli id. Possono servire per messaggi o comandi javascript (recuperati tramite getData).
		//I campi raggruppati in colonna unica non serve elencarli perché verranno letti da una chiave condivisa ('_dyn_field_*').
		//Può essere utile elencarli se occorrono a messaggi di conferma (es. 'description' per conferma su eliminazione, vedi param 'delDisplayField').

		$fields['field_keys'] = array('id', 'id_user', 'id_entry', 'id_edition', 'date_ins', 'user_fullname', 'user_userid',
									 'course_fullname', 'course_type', 'user_subscribed', 'fav_location', 'status',
									 'user_time_availability', 'user_job_location', 'description', 'del'
									);
		//Output
		return $fields;
	}
	
	
	protected function getCourseassnList(){		
		//>> Restituisce le assegnazioni. Chiamata da view -> ajax.adm_server.php 
		
		// Info filter
		$op = Get::req('op', DOTY_MIXED, false);
		$year = Get::req('year', DOTY_INT, false);
		$id_org = Get::req('id_org', DOTY_INT, 0);
		$filter_text = Get::req('filter_text', DOTY_STRING, '');
		$id_fncrole_ref = Get::req('id_fncrole_ref', DOTY_INT, false);

		
		// Controllo se il datatable vuole eseguire un'operazione diversa da il loading
		if ($op == 'selectall') {
			echo $this->_getAssnIdJson($id_org, $year, $filter_text);
			return;
		}
		
		
		// Procedo con le operazioni di loading
		
		
		// Datatable info
		$start_index	= Get::req('startIndex', DOTY_INT, 0);
		$results		= Get::req('results', DOTY_MIXED, Get::sett('visuItem', 25));
		$sort			= Get::req('sort', DOTY_MIXED, 'date_ins');
		$dir			= Get::req('dir', DOTY_MIXED, 'desc');
		$dyn_fields     = Get::req('_dyn_field', DOTY_MIXED, array());
		
		
		// Controllo se è possibile utilizzare idOrg richiesto (admin normale può sare solo il suo)
		if(!$this->isUserGodAdmin()) $id_org = $this->id_org_user;
		
		
		// Sistemo il codice del campo sort se la richiesta di ordinamento proviene da una colonna dyn (arriva la chiave della colonna e devo leggere il nome del campo contenuto)
		if (stristr($sort, '_dyn_field_') !== false){
			$index = str_replace('_dyn_field_', '', $sort);
			$sort = $dyn_fields[(int)$index];
		}
		
		//Istanzio modello
		$model = new CourseassnAlms($id_org, $id_user);
		
		//Recupero numero totale di assegnazioni in base ai criteri
		$total_assn = $model->getAssnNumber($year, $id_fncrole_ref, false, $filter_text);
		
		// Recupero i dati per la pagina
		$arr_data = $model->loadAssn($year, $id_fncrole_ref, $start_index, $results, $sort, $dir, $filter_text);
		
		// Scelgo e formatto i dati
		$arr_result = array();
		
		foreach ($arr_data as $row){
			
			// formattazioni date (date_upd verrà utilizzata nella colonna unica)
			$row['date_ins'] = Format::datetimeToString($row['date_ins'], 'date');
			$row['date_upd'] = Format::datetimeToString($row['date_upd'], 'datetime');
			
			// formattazione userid (lo prendo relativo, senza "/")
			$row['user_userid'] = $this->acl_man->relativeId($row['user_userid']);
			$row['manager_userid'] = $this->acl_man->relativeId($row['manager_userid']);
			
			// valori dei campi in colonna singola (passo anche description. Sebbene sia in colonna condivisa, mi occorre per conferma messaggio di eliminazione)
			$record = array(
							'id' => $row['id_assn'],
							'id_user' => $row['id_user'],
							'id_edition' => $row['id_edition'],
							'date_ins' => $row['date_ins'],
							'user_fullname' => $row['user_fullname'],
							'user_userid' => $row['user_userid'],
							'course_fullname' => $row['course_fullname'],
							'course_type' => $row['course_type'],
							'status' => $row['status'],
							'user_subscribed' => $row['user_subscribed'],
							'fav_location' => $row['fav_location'],
							'user_time_availability' => $row['user_time_availability'],
							'user_job_location' => $row['user_job_location'],
							'description' => $row['description'],
							'del' => 'ajax.adm_server.php?r=alms/courseassn/del&amp;id_assn='.$row['id_assn']
							);
			
			// valori dei campi selezionati nelle colonne condivise 				
			foreach ($dyn_fields as $index => $field_key){
	
				$content = "".( isset($row[$field_key]) ? $row[$field_key] : "");
				$record['_dyn_field_'.$index] = $content;
			}
			
			// passo il record all'array di ritorno				
			$arr_result[] = $record;
		}
		
		// Passo i dati alla view	
		$result = array(	'totalRecords' => $total_assn,
							'startIndex' => $start_index,
							'sort' => $sort,
							'dir' => $dir,
							'rowsPerPage' => $results,
							'results' => count($arr_result),
							'records' => $arr_result,
						);

		$this->data = $this->json->encode($result);
		
		echo $this->data;
	}


	public function edit(){
		//>> Aggiorna il dato dell'assegnazione (campo singolo)
		
		$output = array();
		
		// Update info
		$id_assn = Get::req('id_assn', DOTY_INT, 0);
		$col = Get::req('col', DOTY_STRING, '');
		$new_value = Get::req('new_value', DOTY_STRING, '');
		$old_value = Get::req('old_value', DOTY_STRING, '');
		
		// Recupero l'utente
		$id_user = $this->model->getOwner($id_assn);
		$u_info = $this->acl_man->getUser($id_user, false);

		// Controlli
		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'), 'undo'=> $undo);
			echo $this->json->encode($output);
			return;
			
		}elseif ($u_info[ACL_INFO_VALID] == '0' && $new_value != _ASSN_STATUS_CANCELED){
			$output = array('success' => false, 'message' => $this->_getMessage('USER_STATUS_SUSPEND'), 'undo'=> $undo);
			echo $this->json->encode($output);
			return;	
		}

		// Modifico
		if ($new_value === $old_value){
			// Stesso valore, ok
			$output = array('success' => true);
				
		} else {
			switch ($col){
				case 'status': 
				
					if ($this->model->updAssnStatusById($id_assn, $new_value))
						$output = array('success' => true);
					else
						$output = array('success' => false);
					break;
				
				default: 
					$output = array('success' => false, 'message' => 'field not editable');	
			}
		}
		
		//Out
		echo $this->json->encode($output);
	}


	public function del(){
		
		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}

		if(Get::cfg('demo_mode'))
			die('Cannot del assn during demo mode.');

		$id_assn = Get::req('id_assn', DOTY_INT, 0);

		$res = array('success' => $this->model->delAssn($id_assn));

		$this->data = $this->json->encode($res);

		echo $this->data;
	}
	
	
	public function cancelActive() {
		//>> Annulla le assegnazioni ancora aperte (chiamata da ajax)
		
		$output = array();
		$id_org = Get::req('id_org', DOTY_INT, 0);
		$year = Get::req('year', DOTY_INT, 0);
		
		//Recupero il modello
		$model = &$this->model;
		$model = new CourseassnAlms($id_org, $this->id_user);

		//Controllo permessi
		if ( !($this->permissions['mod'] && ($this->isUserGodAdmin() || $this->id_org_user == $id_org)) ){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}
		
		//Aggiorno
		$res = $model->cancelActiveAssn($year, $id_org);
		
		//Out
		$output = array('success' => $res);
		$this->data = $this->json->encode($output);
	
		echo $this->data;
	}
	
	
	public function multidel(){
		//>> Eliminazione multipla di assegnazioni

		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}

		$assignments = Get::req('assignments', DOTY_STRING, "");
		$output = array();
		
		if ($assignments == ''){
			$output = array('success' => true, 'count' => 0, 'total' => 0);
		} else {
			$list_assn = explode(',', $assignments);
			$count = 0;
			$total = count($list_assn);
			$deleted = array();
			
			foreach ($list_assn as $id_assn){
				
				if ($this->model->delAssn($id_assn)){
					$count++;
					$deleted[] = $id_assn;
				}
			}
			$output = array('success' => true, 'count' => $count, 'total' => $total, 'deleted' => $deleted);
		}

		$this->data = $this->json->encode($output);
		echo $this->data;
	}
	
	private function _importAssnFile($file_path, $operation){
		//>> Inserisce i dati del file nella tabella temporanea
		//>> $file_path: percorso file, 
		//>> $operation: ins o upd (file per inserimento, file per aggiornamento)
		
		$arr_data = array();
		$headers = array();
		$id_org = $this->model->getIdOrg();
			
		// Recupero setting intestazioni file
		$param_name = ($mode == 'ins' ? 'courseassn_fileinsert_header' : 'courseassn_fileupdate_header');
		$param_exp = Get::sett($param_name);
		
		
		$headers_couple  = explode(";", $param_exp);
		
		foreach($headers_couple as $value){
			$hd = explode("=", $value);
			// chiave nome campo file atteso, valore nome campo tabella temp
			$headers[trim($hd[0])] = trim($hd[1]);
		}
		
		// Se risultano non corretti esco
		if (count($headers) < 11 && count($headers) > 13){
			return 'PARAM_ERROR';
		}
		
	
		// Carico i dati del file nella tabella di appoggio
		if ($file_path){
			
			require_once(_base_.'/lib/lib.simplexlsx.php');
			
			// Leggo il file
			if ( $xlsx = SimpleXLSX::parse($file_path) ){
				
				list( $cols, ) = $xlsx->dimension();
		
				//ciclo sulle righe
				foreach ($xlsx->rows() as $r => $row){
					if ($r == 0){
						//recupero posizione campi previsti
						for ($c = 0; $c < $cols; $c ++){
							
							if(isset($headers[$row[$c]]))
								$headers[$c] = $headers[$row[$c]];
						}
						
					}else {
						
						//recupero la riga dati
						$record = array();
						
						for ($c = 0; $c < $cols; $c ++){
							
							if(isset($headers[$c]))
								$record[$headers[$c]] = trim($row[$c]);
								
						}


						if($record && implode("",$record) != ''){

							// Se la riga non è vuota 
							// aggiungo l'id dell'operatore, dell'organizzazione di lavoro e il numero riga all'array
							// e passo il record all'array finale
							
							$record['id_org'] = $id_org;
							$record['user_ins'] = $this->id_user;
							$record['file_row'] = $r + 1;
							
							$arr_data[$r] = $record;
					
						}
					}						
				}
					
			} else {
				// Errore di lettura
				return SimpleXLSX::parse_error();
				
			}
												
			// Inserisco i dati nella tabella
			return $this->model->insAssnTemp($arr_data, $operation);

		}
		  
	}
	
	
	private function _getDescError($record, $type_check) {
		//>> Restituisce la descrizione dell'errore nel processo di importazione (step 2)
		
		$retVal = false;
		
		switch($type_check){
		
			case _MISSING_INFO:
				$retVal =  Lang::t('_ROW_MISSING_INFO', 'courseassn'). "&emsp;(". $record['user_lname']." ".$record['course_code'].")";
			
			break;
			case _ORG_NOT_MATCH:
				$retVal = str_ireplace('[org_code]',$record['org_code'], Lang::t('_ORG_NOT_MATCH', 'courseassn'));
				
			break;
			case _COURSE_NOT_REG:
				$retVal = str_ireplace('[course_code]',$record['course_code'], Lang::t('_COURSE_NOT_REG', 'courseassn'));
				
			break;
			case _ASSIGNMENT_DUPLICATE:
				$retVal = str_ireplace($anchors, $values, Lang::t('_ASSIGNMENT_DUPLICATE', 'courseassn')). "&emsp;(". $record['user_lname']." ".$record['course_code'].")";
				
			break;
			case _ASSIGNMENT_EXISTS:
					$retVal = str_ireplace($anchors, $values, Lang::t('_ASSIGNMENT_EXISTS', 'courseassn')). "&emsp;(". $record['user_lname']." ".$record['course_code'].")";
					
			break;
			case _ASSIGNMENT_NOT_FOUND:
					$retVal = str_ireplace($anchors, $values, Lang::t('_ASSIGNMENT_NOT_FOUND', 'courseassn')). "&emsp;(". $record['user_lname']." ".$record['course_code'].")";
			
			break;
			case _USER_STATUS_SUSPEND:
					$retVal = str_ireplace($anchors, $values, Lang::t('_USER_STATUS_SUSPEND', 'standard')). "&emsp;(". $record['user_lname']." ".$record['user_userid'].")";
			
			break;
			
		}
		
		// Out
		return $retVal;
		
	}

	protected function getJsArrayStatus(){
		//>> Recupera i nomi di stato: per javascript

		$first = true;
		$output = '[';
		$list = $this->model->getStatusForDropdown();
		foreach ($list as $id_status => $status_translation){
			if ($first)
				$first = false; else
				$output .= ', ';
			$output .= '{"value":' . $this->json->encode($id_status) . ',"label":' . $this->json->encode($status_translation) . '}';
		}
		$output .= ']';
		return $output;

	}
	
	public function getInvalidList(){
		//>> Controlla le assegnazioni temporanee caricate per inserimento o aggiornamento.
		//>> Chiamata dalla view nello step di controllo (ajax)
		//>> Se riscontra errori, restituisce un array alla view con keys: 'check', 'row', 'desc', 'severity'
		
		$arr_inv = array();
		
		// Info filter
		$id_org = Get::req('id_org', DOTY_INT, 0);
		$operation = Get::req('op', DOTY_STRING, "");
		
		// Recupero i controlli da effettuare
		$operation = strtoupper($operation);
		$type_checks = $this->import_checks[$operation];
		
		// Datatable info
		$start_index	= Get::req('startIndex', DOTY_INT, 0);
		$results		= Get::req('results', DOTY_MIXED, Get::sett('visuItem', 25));
		
		//Istanzio modello di classe con organizzazione di lavoro
		$this->model = new CourseassnAlms($id_org, $this->id_user);
		$model = &$this->model;
		
		// Ciclo controlli
		
		foreach ($type_checks as $type_check) {
			
			// Lancio il controllo
			$result = $model->getAssnTempInvalid($type_check);
			
			
			// Preparo array errori
			foreach ($result as $key => $record){

				$arr_inv[] 	= 	array(	'check' => $record['TRec'],
										'row'   => $record['file_row'],
										'desc'  => $this->_getDescError($record, $type_check),
										'severity' => 1
								);
			}			
		}
		
		
		// Trovo il totale dei record anomali
		$totalRecords = count($arr_inv);
		
		
		// Preparo l'array di ritorno (parziale in base alla pagina)
		$arr_result = array_slice($arr_inv, $start_index, $results);
		
		
		// Passo i dati alla view	
		$result = array(	'totalRecords'	=> $totalRecords,
							'startIndex'	=> $start_index,
							'rowsPerPage' 	=> $results,
							'fields'		=> $fields,
							'results'		=> count($arr_result),
							'records'		=> $arr_result
						);
							
											
		// Out
		$this->data = $this->json->encode($result);
		
		echo $this->data;
		
	}
	
		
	public function updateAssnWizard(){
		//>> Aggiorna le assegnazioni da file Excel *.xlsx (usata per aggiornare i responsabili assegnazione)
		
		// Recupero modello di classe e id organizzazione
		$id_org = Get::req('id_org', DOTY_INT, 0);
		
		// Recupero il nome dell'organizzazione
		$u_man = new UsermanagementAdm();
				
		$org_names = $u_man->getAllFolderNames();
		$org_name = $org_names[$id_org];
		
		// Recupero controlli di aggiornamento
		$type_checks = $this->import_checks[_OP_UPD];		
		
		// Controllo permessi di importazione.

		if (!($this->permissions['mod'] && 
			 ($this->isUserGodAdmin() || $id_org = $this->id_org_user))){
			// Procedo solo se ho il permesso di modificare e 
			// sono un superadmin o utente dell'organizzazione di lavoro
			
			$this->_renderInvalid("NO_PERMISSION");
			return;
		}
		
		//Istanzio modello di classe con organizzazione di lavoro
		$this->model = new CourseassnAlms($id_org, $this->id_user);
		$model = &$this->model;
		
		//Recupero lo step di avanzamento
		$step = Get::req('step', DOTY_INT, 1);

		if (isset($_POST['next']))
			$step++;

		if (isset($_POST['prev']))
			$step--;

		if (isset($_POST['undo']))
			$step = 0;
			
		switch($step){
			
			case 0:
				// Annullo l'importazione e riporto l'utente alla pagina delle assegnazioni
				$post_undo['id_org'] = $id_org;
							
				$this->show();
			
			break;
			
			case 1:
				// -- Selezione file --
				
				// Preparo i parametri
				$params = array('id_org' => $id_org,
								'org_name' => $org_name
								);

				// Passo alla view
				$this->render('update_xlsx_step_1', $params);
				
			break;
			
			case 2:
			
				// -- Caricamento -- 
				// (se non si proviene da un 'torna indietro')			
				if (!isset($_POST['prev'])) {
					
					// Percorso tmp file dal form
					$file = $_FILES['file_import'];
					
					// Controllo se il file è stato selezionato
					if (!$file['name']){
						$this->_renderInvalid("FILE_MISSING");
						return;
					}
					
					
					// Importo il file nella tabella temporanea
					$res_import = $this->_importAssnFile($file['tmp_name'], _OP_UPD);


					if ($res_import == 'PARAM_ERROR'){
						// Se il setting delle intestazioni non è corretto mi fermo
						$this->_renderInvalid("PARAM_ERROR");
						return;				
						
					} elseif (!is_numeric($res_import)){
						// Se ci sono errori mi fermo
						$this->_renderInvalid("ERROR");
						return;	
						
					}elseif ($res_import === 0){
						// Se non ci sono dati inseriti mi fermo
						$this->_renderInvalid("NO_DATA");
						return;	
						
					}elseif ($res_import > 0){
						// Caricamento avvenuto
						// Aggiorno gli id degli utenti e dei corsi già presenti
						$model->updUserAssnId();
						$model->updCourseId();
					}
				}	
					
				// Info colonne per la view
				$fields['field_keys'] = array('check', 'row', 'desc', 'severity');
	
				$fields['view_fields'] = array(
								array('key' => 'check', 'label' => Lang::t('_TYPOLOGY', 'standard'), 'sortable' => false),
								array('key' => 'row', 'label' => Lang::t('_FILE_ROW', 'standard'), 'sortable' => false),
								array('key' => 'desc', 'label' => Lang::t('_DESCRIPTION', 'standard'), 'sortable' => false));
				
				$params = array('id_org' => $id_org, 'org_name' => $org_name, 'base_link_courseassn' => $this->base_link_courseassn, 'fields' => $fields);
				
				
				// Passo alla view del secondo step (controlli)
				$this->render('update_xlsx_step_2', $params);
				
			break;
			
			case 3:
				// -- Conferma --	
				
				$num_load = count($model->getAssnTmpLoad());
				$num_invalid = count($model->getAssnTempInvId($type_checks));
				$num_valid = $num_load - $num_invalid;
				$num_new_manager = count($model->getNewUserAssn('manager'));
				
				// Preparo i parametri
				$params = array('id_org' => $id_org,
								'org_name' => $org_name,
								'num_load' => $num_load,
								'num_invalid' => $num_invalid,
								'num_valid' => $num_valid,
								'num_new_manager' => $num_new_manager,
								'base_link_courseassn' => $this->base_link_courseassn
								);

				// Passo alla view
				$this->render('update_xlsx_step_3', $params);		
			
				
			break;
			case 4:
				// -- Importazione complessiva dei dati --
				
				// Richiesta alert per nuovi utenti
				$chk_send = (bool)$_POST['chk_send'];
				
				// 1. Aggiorno a stato sospeso le assegnzioni non valide
				$model->updSuspendAssnTempInvalid($type_checks);
			
				// 2. Inserisco i nuovi utenti manager
				$users = $model->getNewUserAssn('manager');
				$model->createUsers($users, $chk_send);
				
				// 5. Aggiorno gli 'id' per i nuovi utenti
				$model->updUserAssnId();
				
				// 6. Aggiungo gli utenti manager al gruppo, se necessario
				$model->assignManagerRole($model->getAssnTempManager(), 'manager_assignment');
				
				// 7. Aggiorno le assegnazioni
				$a = $model->updAssnFromTemp();
				
				
				// 8. Apro la view delle assegnazioni (show) con parametri per messaggio di conferma.
				if 		($a > 0)	$res = 'SUCCESS';		
				elseif	($a == 0)	$res = 'NO_UPDATE';
				else 				$res = 'FAILURE';
				
				Util::jump_to('index.php?r='.$this->base_link_courseassn.'/show&id_org='.$id_org.'&result='.$res);
		
			break;
		}
		
	}
	
	
	public function insertAssnWizard(){
		//>> Importa le assegnazioni da file Excel *.xlsx
		
		// Recupero modello di classe e id organizzazione
		$id_org = Get::req('id_org', DOTY_INT, 0);
		
		//Recupero il nome dell'organizzazione
		$u_man = new UsermanagementAdm();
				
		$org_names = $u_man->getAllFolderNames();
		$org_name = $org_names[$id_org];
		
		// Recupero controlli di inserimento
		$type_checks = $this->import_checks[_OP_INS];	

			
		// Controllo permessi di importazione.

		if (!($this->permissions['mod'] && 
			 ($this->isUserGodAdmin() || $id_org = $this->id_org_user))){
			// Procedo solo se ho il permesso di aggiungere e 
			// sono un superadmin o utente dell'organizzazione di lavoro
			
			$this->_renderInvalid("NO_PERMISSION");
			return;
		}
		
		//Istanzio modello di classe con organizzazione di lavoro
		$this->model = new CourseassnAlms($id_org, $this->id_user);
		$model = &$this->model;
		
		
		//Recupero lo step di avanzamento
		$step = Get::req('step', DOTY_INT, 1);

		if (isset($_POST['next']))
			$step++;

		if (isset($_POST['prev']))
			$step--;

		if (isset($_POST['undo']))
			$step = 0;
			
		switch($step){
			
			case 0:
				// Annullo l'importazione e riporto l'utente alla pagina delle assegnazioni
				$post_undo['id_org'] = $id_org;
				
				$this->show($post_undo);
			
			break;
			
			case 1:
				// -- Selezione file --
				
				// Preparo i parametri
				$params = array('id_org' => $id_org,
								'org_name' => $org_name
								);

				// Passo alla view
				$this->render('insert_xlsx_step_1', $params);
				
			break;
	
			case 2:
			
				// -- Caricamento -- 
				// (se non si proviene da un 'torna indietro')			
				if (!isset($_POST['prev'])) {
					
					// Percorso tmp file dal form
					$file = $_FILES['file_import'];
					
					// Controllo se il file è stato selezionato
					if (!$file['name']){
						$this->_renderInvalid("FILE_MISSING");
						return;
					}
					
					// Importo il file nella tabella temporanea
					$res_import = $this->_importAssnFile($file['tmp_name'], _OP_INS);
					
					
					if ($res_import == 'PARAM_ERROR'){
						// Se il setting delle intestazioni non è corretto mi fermo
						$this->_renderInvalid("PARAM_ERROR");
						return;				
						
					} elseif (!is_numeric($res_import)){
						// Se ci sono errori mi fermo
						$this->_renderInvalid("ERROR");
						return;	
						
					}elseif ($res_import === 0){
						// Se non ci sono dati inseriti mi fermo
						$this->_renderInvalid("NO_DATA");
						return;	
						
					}elseif ($res_import > 0){
						// Caricamento avvenuto
						// Aggiorno gli id degli utenti e dei corsi già presenti
						$model->updUserAssnId();
						$model->updCourseId();
					}
				}	
					
				// Info colonne per la view
				$fields['field_keys'] = array('check', 'row', 'desc', 'severity');
	
				$fields['view_fields'] = array(
								array('key' => 'check', 'label' => Lang::t('_TYPOLOGY', 'standard'), 'sortable' => false),
								array('key' => 'row', 'label' => Lang::t('_FILE_ROW', 'courseassn'), 'sortable' => false),
								array('key' => 'desc', 'label' => Lang::t('_DESCRIPTION', 'standard'), 'sortable' => false));
				
				$params = array('id_org' => $id_org, 'org_name' => $org_name, 'base_link_courseassn' => $this->base_link_courseassn, 'fields' => $fields);
				
				
				// Passo alla view del secondo step (controlli)
				$this->render('insert_xlsx_step_2', $params);
				
			break;
			
			case 3:
				// -- Conferma --	
							
				$num_load = count($model->getAssnTmpLoad());
				$num_invalid = count($model->getAssnTempInvId($type_checks));
				$num_valid = $num_load - $num_invalid;
				$num_new_user = count($model->getNewUserAssn('user'));
				$num_new_manager = count($model->getNewUserAssn('manager'));
				$send_alert = $this->getSendAlertMode();
				
				// Preparo i parametri
				$params = array('id_org' => $id_org,
								'org_name' => $org_name,
								'num_load' => $num_load,
								'num_invalid' => $num_invalid,
								'num_valid' => $num_valid,
								'num_new_user' => $num_new_user,
								'num_new_manager' => $num_new_manager,
								'send_alert' => $send_alert,
								'base_link_courseassn' => $this->base_link_courseassn
								);

				// Passo alla view
				$this->render('insert_xlsx_step_3', $params);		
			
				
			break;
			
			case 4:
				// -- Importazione complessiva dei dati --
				
				// Richiesta alert per utenti
				$chk_send = (bool)$_POST['chk_send'];
				
				// 1. Aggiorno a stato sospeso le assegnzioni non valide
				$model->updSuspendAssnTempInvalid($type_checks);

				// 2. Aggiorno i dati dei vecchi utenti	
				$model->updUserFromTemp();	
				
				// 3. Inserisco i nuovi utenti
				$users = $model->getNewUserAssn('user');
				$model->createUsers($users, false);
			
				// 4. Inserisco i nuovi utenti manager
				$users = $model->getNewUserAssn('manager');
				$model->createUsers($users, false);	
			
				// 5. Aggiorno gli 'id' per i nuovi utenti
				$model->updUserAssnId();
				
				// 6. Aggiungo gli utenti manager al gruppo, se necessario
				$model->assignManagerRole($model->getAssnTempManager(), 'manager_assignment');
				
				// 7. Inserisco le assegnazioni
				$a = $model->insAssnFromTemp($ret_id);
				
				// 8. Invio alert
				if ($chk_send && $a) $this->_sendNewAssnAlert($ret_id);
				
				// 9. Apro la view delle assegnazioni (show) con messaggio di conferma.
				$res = ($a > 0 ? 'SUCCESS' : 'FAILURE');
				
				Util::jump_to('index.php?r='.$this->base_link_courseassn.'/show&id_org='.$id_org.'&result='.$res);;
		
			break;
			
		}
	
	}
	
	
	protected function _formatCsvValue($value, $delimiter) {
		$formatted_value = str_replace($delimiter, '\\'.$delimiter, $value);
		return $delimiter.$formatted_value.$delimiter;
	}
	
	
	public function csvexport() {
		//>> Chiamata da form nascosto per esportazioni CSV
		
		$operation = Get::req('operation', DOTY_STRING, "");
		
		switch ($operation) {
			case 'export_assn':
				$this->_exportAssn();
				break;
			case 'export_assn_active':
				$this->_exportAssnActive();
				break;
		}
	}
	

	private function _exportAssn() {
		//>> Esporta le assegnazioni in formato CSV
		
		// Controllo permessi
		if (!$this->permissions['view']) Util::jump_to('index.php?r='.$this->base_link_courseassn.'/show');

		require_once(_base_.'/lib/lib.download.php');
	
		$fields = $this->getTableInfo();
		$assignments = Get::req('data', DOTY_STRING, "");
		$separator = ';';
		$delimiter = '"';
		$line_end = "\r\n";

		$output = "";
		
		
		// Recupero intestazioni (etichette e nomi campo query)

		$head = array();
		
		foreach ($fields['static_fields'] as $element) {
			$head['key'][] = $element['key'];
			$head['label'][] = $this->_formatCsvValue($element['label'], $delimiter);
		}
		
		foreach ($fields['dyn_fields'] as $element) {
			$head['key'][] = $element['key'];
			$head['label'][] = $this->_formatCsvValue($element['label'], $delimiter);
		}

		//Preparo prima riga csv di output
		$output .= implode($separator, $head['label']).$line_end;
			

		if ($assignments != "") {
			// Recupero gli id selezionati
			$arr_assn = explode(',', $assignments);
			$arr_assn = array_unique($arr_assn);
			
			// Carico le estrazioni in base agli ID passati dal form di chiamata
			$details = $this->model->getAssnById($arr_assn, $this->id_org);
			
			// Preparo le righe per il csv di output
			if (is_array($details)) {
				
				foreach ($details as $id_assn => $detail) {
					$row = array();
					
					foreach ($head['key'] as $key) {
						
						// Recupero il dato
						$row[$key] = $detail[$key];
						
						// Formattazioni
						if (strpos($key, "userid") !== false)
							// Formatto userid (lo prendo relativo, senza "/")
							$row[$key] = $this->acl_man->relativeId($row[$key]);
							
						elseif (strpos($key, "date") !== false && (bool)strtotime($row[$key]))
							// Formatto date
							$row[$key] = Format::datetimeToString($row[$key], 'date');
					}


					// Formatto e preparo la riga
					$csv_row = array();
					foreach ($row as $k => $row_data) {
								
						$csv_row[] = $this->_formatCsvValue($row_data, $delimiter);
					}

					$output .= implode($separator, $csv_row).$line_end;
				}
			}
			
		}
		
		
		// Lancio il download del file;
		sendStrAsFile($output, 'assn_export_'.date("Ymd").'.csv');
	}
	
	
	private function _exportAssnActive() {
		//>> Esporta in formato xlsx le assegnazioni aperte
		
		require_once(_base_.'/lib/lib.xlsxwriter.php');
		require_once(_base_.'/lib/lib.download.php' );
					
		// Recupero i parametri inviati in formato json nel campo del form
		$json = Get::req('data', DOTY_STRING, "");
		$json = stripslashes($json);
		
		// Decodifico i parametri in oggetto php
		$post = json_decode($json);
		
		// Recupero le assegnazioni aperte
		$assignments = $this->model->getAssnActive($post->id_org);
		
		
		// Recupero setting intestazioni file
		$sett_exp = Get::sett('courseassn_fileexport_header');
		
		// Recupero intestazioni
		$headers_couple  = explode(";", $sett_exp);
		
		
		$xls_header = array();
		$fields = array();
		
		foreach($headers_couple as $value){
			$hd = explode("=", $value);
			
			$hd[0] = trim($hd[0]);
			$hd[1] = trim($hd[1]);
			
			// Definisco campi da esportare
			$fields[$hd[1]] = $hd[0];
			
			// Definisco colonne excel
			if (strripos($hd[1],"date") !== false) {
				$xls_header[$hd[0]] = 'DD/MM/YYYY';
			} else {
				$xls_header[$hd[0]] = 'string';
			}
		}
		
		// Preparo il file
		$writer = new XLSXWriter();
		
		
		// Scrivo intestazioni
		$styles = array( 'font'=>'Arial','font-size'=>10,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'freeze_rows'=>1);
		
		$writer->writeSheetHeader('Sheet1', $xls_header, $styles);
		
		
		// Scrivo righe con scelta campi
		foreach ($assignments as $row) {
			
			foreach ($fields as $key => $name) {
					
				if (array_key_exists($key, $row)) {
					
					// Recupero il dato
					$new_row[$key] = $row[$key];
					
					// Formatto userid (lo prendo relativo, senza "/")
					if (strripos($key, "userid") !== false)
						$new_row[$key] = $this->acl_man->relativeId($new_row[$key]);
								
				}
			}
			
			// Scrivo la riga
			$writer->writeSheetRow('Sheet1', $new_row );
		}
	
		// Lancio il download del file;
		sendStrAsFile($writer->writeToString(), 'assn_active_'.date("Ymd").'.xlsx');
		
	}
	
	
	protected function getSendAlertMode() {
		//>> Restituisce se inviare una notifica e in che modo
		//>> 0 nessuna notifica, 1 invio diretto, 2 invio con coda
		$res = 0;
		
		$queue_on = ( Get::sett('mail_queue') == 'on' );
		$event_active = $this->model->isActiveAlert('UserCourseAssigned');
		
		if ($event_active)
			$res = $queue_on ? 2 : 1;
		
		return $res;
	}
	
	
	private function _getAssnIdJson($id_org, $year, $filter_text) {
		//>> Restituisce gli id delle assegnazioni in formato json in base ai criteri passati in argomento
		 
		$res = $this->model->getAssnId($year, $id_org, $filter_text);
		
		return $this->json->encode($res);
	}
	
	
	private function _sendNewAssnAlert($arr_id_assn) {
		//>> Invia le e-mail con le notifica di una nuova assegnazione
		
		$mailer = FormaMailer::getInstance();
		$model = &$this->model;
		$count = 0;
		
		// Imposto id coda, se attiva
		$mailer->setNewQueue('ImportAssignment');
		
		// Indirizzo from e url sito
		$from_address = Get::sett('sender_event');
		$url =  Get::sett('url');
		
		// Testi mail
		$subject_model = Lang::t('_NOTICE_ASSN_SUBJECT', 'email');
		$body_model = Lang::t('_NOTICE_ASSN_HTML', 'email');
		
		
		// Recupero le assegnazioni
		$assn_info = $model->getAssnById($arr_id_assn);
		
		foreach($assn_info as $info) {

			// Mail utente
			$to_address = $info['user_email'];
				
			
			// Sostituisco il tag corso nell'oggetto
			$subject = str_replace('[course]', $info['course_name'], $subject_model);


			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => $url,
									'[sender_event]' => $from_address,
									'[course]' => $info['course_name'],
									'[code]' => $info['course_code'],
									'[firstname]' => $info['user_firstname'],
									'[lastname]' => $info['user_lastname']
								);
			
			// Sostituisco i tag					
			$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
			
			
			// Invio
			//$mailer->SendFormaMail($args["sender"], $args["to"], $args["subject"], $args["body"], $args["attachment"], $new_params );
			$mailer->SendMail($from_address, [$to_address], $subject, $body, false, 
								array(MAIL_REPLYTO => $from_address));
			
					
			// Contatore
			$count +=1;		
		}
		
		return  $count;	
	}
	

}



?>
