<?php

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

use FormaLms\lib\Get;


class GapAlmsController extends AlmsController {
	public $name = 'gap';

	protected $json;
	protected $acl_man;

	protected $data;

	protected $permissions;

	protected $base_link_gap;
	protected $user_level;
	protected $id_user;
	protected $id_org_user;
	protected $model;
	protected $import_checks;


	public function init(){
		
		die("Modulo non migrato"); // Istruzione temporanea
		
		checkPerm('view', false, 'gap', 'lms');
		
		require_once(_base_.'/lib/lib.json.php');
		require_once(_base_.'/lib/lib.mailer.php');
		require_once(_adm_.'/models/UsermanagementAdm.php');

		$this->json = new Services_JSON();
		$this->acl_man =& Docebo::user()->getAclManager();

		$this->base_link_gap = 'alms/gap';
		
		$this->user_level = Docebo::user()->getUserLevelId();
		$this->id_user = Docebo::user()->getIdSt();
		
		$this->model = new GapAlms();
		$org_us = $this->model->getOrgInfoByUser($this->id_user);
		$this->id_org_user = (int)$org_us['idOrg_parent'];
		
		
		//Recupero i permessi dell'utente
		$this->permissions = array(
			'view'	=> checkPerm('view', true, 'gap', 'lms'),
			'mod'	=> checkPerm('mod', true, 'gap', 'lms'),
		);
		
		//Imposto i controlli di importazione
		$this->import_checks = array(
			_OP_INS	=> array(_MISSING_INFO, _ORG_NOT_MATCH, _CATALOG_NOT_REG, _CATALOG_EMPTY, _GAP_EXISTS, _GAP_DUPLICATE, _USER_STATUS_SUSPEND),
			_OP_UPD	=> array(_MISSING_INFO, _ORG_NOT_MATCH, _CATALOG_NOT_REG, _CATALOG_EMPTY, _GAP_DUPLICATE, _GAP_NOT_FOUND)
		);
	}
	
	
	protected function _renderInvalid($error_code){
		
			$this->render('invalid', array(
				'message' => $this->_getMessage($error_code),
				'back_url' => 'index.php?r='.$this->base_link_gap.'/show'
			));
			return;
	}


	protected function _getMessage($code){
		$message = "";
		
		switch ($code){
			case "NO_PERMISSION": $message = "You don&#96;t have the required permission";
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
			case "GAP_NOT_FOUND": $message = Lang::t('_GAP_NOT_FOUND', 'gap');
				break;
			case "INVALID_CHANGE": $message = Lang::t('_INVALID_CHANGE', 'standard');
				break;
			case "FAILURE": $message = Lang::t('_OPERATION_FAILURE', 'standard');
				break;	
		}
		return $message;
	}
        
        
	protected function _getBackLink(){
			return getBackUi('index.php?r='.$this->base_link_gap.'/show', Lang::t('_BACK', 'standard'));
	}
	
	
	protected function isUserGodAdmin(){
		//>> Restituisce true se l'utente corrente è un super amministratore
		if($this->user_level == ADMIN_GROUP_GODADMIN)
			return true;
	}
	
	
	protected function show(){
		//>> Apre la view dei gap
		
		$result_wizard = Get::req('result', DOTY_MIXED, false);
		$model = &$this->model;

		//Recupero l'organizzazione di partenza
		
		if($this->isUserGodAdmin()){
			// Superadmin

			if($result_wizard) {
				// Se si arriva da un caricamento dei gap (dal wizard)
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
		$model = new GapAlms($id_org, $this->id_user);
		
		//Librerie
		Util::get_js(Get::rel_path('base').'/lib/js_utils.js', true, true);
		Util::get_js(Get::rel_path('lms').'/admin/views/gap/gap.js', true, true);
		
		
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
			'base_link_gap' => $this->base_link_gap,
			'num_var_fields' => 2,
			'fields' => $this->getTableInfo(),
			'status_list_js' => $this->_getJsArrayStatus(),
			'cata_list_js' => $this->_getJsArrayCatalogue(),
			'is_godadmin' => $this->isUserGodAdmin()
		));
		
	}
	
	protected function getTableInfo(){
		//>> Recupera array di 
		//>> campi/colonna da rappresentare nella tabella della view
		//>> chiavi da leggere dal set di dati
		
		//Campi in colonna dedicata (statica)
		$status_list_js = $this->_getJsArrayStatus();
		$cata_list_js = $this->_getJsArrayCatalogue();
		
		$fields['static_fields'] = array(
			array('key' => 'date_ins', 'label' => Lang::t('_REGISTER_DATE', 'standard'), 'sortable' => true),
			array('key' => 'user_userid', 'label' => Lang::t('_USERNAME', 'standard'), 'sortable' => true),
			array('key' => 'user_fullname', 'label' => Lang::t('_USER_NAME', 'courseassn'), 'sortable' => true),
			array('key' => 'requirement', 'label' => Lang::t('_REQ_COURSE', 'gap'), 'sortable' => true, 'className' => 'align-center'),
			array('key' => 'id_catalogue', 'label' => Lang::t('_GAP_CATALOGUE', 'gap'), 'sortable' => true, 'formatter' => 'Gap.cataFormatter'),
			array('key' => 'status', 'label' => Lang::t('_STATUS', 'standard'), 'sortable' => true, 'formatter' => 'Gap.statusFormatter'),
			array('key' => 'count_assn', 'label' => Lang::t('_ASSN_BY_GAP', 'gap'), 'sortable' => true, 'className' => 'align-center', 'formatter' => 'Gap.aslinkFormatter'),
			array('key' => 'user_time_availability', 'label' => Lang::t('_TIME_AVAILABILITY', 'courseassn'), 'sortable' => true)
		);

		//Campi in colonna condivisa (dinamica)
		$fields['dyn_fields'] = array(
			array('key' => 'date_upd', 'label' => Lang::t('_UPDATE_DATE', 'standard'), 'sortable' => true, 'selected_on_col' => 1),
			array('key' => 'modifier_fullname', 'label' => Lang::t('_USER_UPDATE', 'courseassn'), 'selected_on_col' => 2),
			array('key' => 'desc_upd', 'label' => Lang::t('_LAST_OPERATION', 'courseassn'), 'sortable' => true,'selected_on_col' => 3),
			array('key' => 'loader_fullname', 'label' => Lang::t('_USER_INSERT', 'courseassn')),
			array('key' => 'manager_fullname', 'label' => Lang::t('_MANAGER_ASSN', 'courseassn'), 'sortable' => true),
			array('key' => 'manager_userid', 'label' => Lang::t('_MANAGER_ASSN_USERID', 'courseassn'), 'sortable' => true),
			array('key' => 'description', 'label' => Lang::t('_DESCRIPTION', 'standard')),
			array('key' => 'user_job_location', 'label' => Lang::t('_JOB_LOCATION', 'courseassn'), 'sortable' => false, 'selected_on_col' => 0),
			array('key' => 'fav_location', 'label' => Lang::t('_FAV_LOCATION', 'courseassn'), 'sortable' => true),
			array('key' => 'org_code', 'label' => Lang::t('_ORGANIZATION', 'courseassn'), 'sortable' => false),
			array('key' => 'id_gap', 'label' => 'ID', 'sortable' => true)
		);
		
		//Imposto editor sui campi statici (3 requirement, 4 catalogo, 5 status)
		if ($this->permissions['mod']){
			$fields['static_fields'][3]['editor'] = 'new YAHOO.widget.TextboxCellEditor({validator:Gap.rqmtValidator, asyncSubmitter:Gap.colSave})';
			$fields['static_fields'][4]['editor'] = 'new YAHOO.widget.DropdownCellEditor({dropdownOptions:'.$cata_list_js.', asyncSubmitter:Gap.colSave})';
			$fields['static_fields'][5]['editor'] = 'new YAHOO.widget.DropdownCellEditor({dropdownOptions:'.$status_list_js.', asyncSubmitter:Gap.colSave})';
		}
		
		//Elenco chiavi da leggere nel dataTable (widget) dall'array dei dati (vedi getGapList)
		//Si possono elencare anche i campi non visibili come gli id. Possono servire per messaggi o comandi javascript (recuperati tramite getData).
		//I campi raggruppati in colonna unica non serve elencarli perché verranno letti da una chiave condivisa ('_dyn_field_*').
		//Può essere utile elencarli se occorrono a messaggi di conferma (es. 'description' per conferma su eliminazione, vedi param 'delDisplayField').

		$fields['field_keys'] = array(	'id', 'id_user', 'date_ins', 'user_fullname', 'user_userid', 'requirement', 'id_catalogue', 
										'cata_name', 'status', 'year_gap', 'count_assn', 'fav_location', 
										'user_time_availability', 'description', 'del'
								);
								
		//Output
		return $fields;
	}
	
	
	protected function getGapList(){		
		//>> Restituisce i gap. Chiamata da view -> ajax.adm_server.php 
		
		// Info filter
		$op = Get::req('op', DOTY_MIXED, false);
		$year = Get::req('year', DOTY_INT, false);
		$id_org = Get::req('id_org', DOTY_INT, 0);
		$filter_text = Get::req('filter_text', DOTY_STRING, '');

		
		// Controllo se il datatable vuole eseguire un'operazione diversa da il loading
		if ($op == 'selectall') {
			echo $this->_getGapIdJson($id_org, $year, $filter_text);
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
		$model = new GapAlms($id_org, $id_user);
		
		//Recupero numero totale di assegnazioni in base ai criteri
		$total_gap= $model->getGapNumber($year, false, $filter_text);
		
		
		// Recupero i dati per la pagina
		$arr_data = $model->loadGap($year, $start_index, $results, $sort, $dir, $filter_text);
		
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
							'id' => $row['id_gap'],
							'id_user' => $row['id_user'],
							'id_catalogue' => $row['id_catalogue'],
							'date_ins' => $row['date_ins'],
							'user_fullname' => $row['user_fullname'],
							'user_userid' => $row['user_userid'],
							'cata_name' => $row['cata_name'],
							'requirement' => $row['requirement'],
							'status' => $row['status'],
							'year_gap' => $row['year_gap'],
							'fav_location' => $row['fav_location'],
							'user_time_availability' => $row['user_time_availability'],
							'description' => $row['description'],
							'count_assn' => $row['count_assn'],
							'del' => 'ajax.adm_server.php?r=alms/gap/del&amp;id_gap='.$row['id_gap']
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
		$result = array(	'totalRecords' => $total_gap,
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
		
		// Update info
		$id_gap = Get::req('id_gap', DOTY_INT, 0);
		$new_value = Get::req('new_value', DOTY_STRING, '');
		$col = Get::req('col', DOTY_STRING, '');
		
		$model = $this->model;
		$res = array('success' => false);
		
		// Recupero l'utente
		$id_user = $model->getOwner($id_gap);
		$u_info = $this->acl_man->getUser($id_user, false);

		// Controlli
		if (!$this->permissions['mod']){
			$res['message'] = $this->_getMessage('NO_PERMISSION');
			echo $this->json->encode($res);
			return;
			
		}elseif ($u_info[ACL_INFO_VALID] == '0' && $new_value != _GAP_STATUS_CANCELED){
			$res['message'] = $this->_getMessage('USER_STATUS_SUSPEND');
			echo $this->json->encode($res);
			return;	
		}
		
		//Recupero informazioni sul gap per controlli
		$info = $model->getGapById($id_gap);
			
		// Operazioni
		switch ($col){
			case 'status': 
				// Modifico
				$res['success'] = $model->updGapStatusById($id_gap, $new_value);

			break;
				
			case 'requirement':
				//Modifico se passano i controlli
				if (!$info) {
					$res['message'] =  $this->_getMessage('GAP_NOT_FOUND');
					
				} elseif ($new_value < $info['count_assn'] || $info['status'] != 1) {
					$res['message'] = $this->_getMessage('INVALID_CHANGE');
					
				} else {
					$res['success'] = $model->updGapRqmtById($id_gap, $new_value);	
				}
			break;
			
			case 'id_catalogue':
				//Modifico se passano i controlli
				if (!$info) {
					$res['message'] =  $this->_getMessage('GAP_NOT_FOUND');
				} else {
					$resAdmitted = $model->cataIsAdmitted($id_gap, $new_value);
					
					if ($resAdmitted !== true) {
						$res['message'] = $this->_getMessage('INVALID_CHANGE') . " (". Lang::t($resAdmitted, 'standard') .")";
					} else {
						$res['success'] = $model->updGapCataById($id_gap, $new_value);	
					}
				}			
			break;
		}
		
		//Info di ritorno aggiornte per datatable
		$res['data'] = $model->getGapById($id_gap);
		
		//Out
		echo $this->json->encode($res);	
	
	}


	public function del(){
		//>> Eliminazione di un singolo gap
		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}

		if(Get::cfg('demo_mode'))
			die('Cannot del assn during demo mode.');

		$id_gap = Get::req('id_gap', DOTY_INT, 0);

		$res = array('success' => $this->model->delGap($id_gap));

		$this->data = $this->json->encode($res);

		echo $this->data;
	}
	
	
	public function cancelActive() {
		//>> Annulla i gap ancora aperti (chiamata da ajax)
		
		$output = array();
		$id_org = Get::req('id_org', DOTY_INT, 0);
		$year = Get::req('year', DOTY_INT, 0);
		
		//Recupero il modello
		$model = &$this->model;
		$model = new GapAlms($id_org, $this->id_user);

		//Controllo permessi
		if ( !($this->permissions['mod'] && ($this->isUserGodAdmin() || $this->id_org_user == $id_org)) ){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}
		
		//Aggiorno
		$res = $model->cancelActiveGap($year, $id_org);
		
		//Out
		$output = array('success' => $res);
		$this->data = $this->json->encode($output);
	
		echo $this->data;
	}
	
	
	public function multidel(){
		//>> Eliminazione multipla di gap

		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}

		$gaps = Get::req('gaps', DOTY_STRING, "");
		$output = array();
		
		if ($gaps == ''){
			$output = array('success' => true, 'count' => 0, 'total' => 0);
		} else {
			$list_gap = explode(',', $gaps);
			$count = 0;
			$total = count($list_gap);
			$deleted = array();
			
			foreach ($list_gap as $id_gap){
				
				if ($this->model->delGap($id_gap)){
					$count++;
					$deleted[] = $id_gap;
				}
			}
			$output = array('success' => true, 'count' => $count, 'total' => $total, 'deleted' => $deleted);
		}

		$this->data = $this->json->encode($output);
		echo $this->data;
	}
	
	
	protected function _getJsArrayCatalogue(){
		//>> Recupera i cataloghi: per javascript

		$first = true;
		$output = '[';
		$list = $this->model->getCataForDropdown();
		foreach ($list as $id_catalogue => $name){
			if ($first)
				$first = false; else
				$output .= ', ';
			$output .= '{"value":' . $this->json->encode($id_catalogue) . ',"label":' . $this->json->encode($name) . '}';
		}
		$output .= ']';
		return $output;
	}
	
	
	protected function _getJsArrayStatus(){
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
	
	
	private function _importGapFile($file_path, $operation){
		//>> Inserisce i dati del file nella tabella temporanea
		//>> $file_path: percorso file, 
		//>> $operation: ins o upd (file per inserimento, file per aggiornamento)
		
		$arr_data = array();
		$headers = array();
		$id_org = $this->model->getIdOrg();
			
		// Recupero setting intestazioni file
		$param_name = ($mode == 'ins' ? 'gap_fileinsert_header' : 'gap_fileupdate_header');
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
			return $this->model->insGapTemp($arr_data, $operation);

		}
		  
	}
	
	
	private function _getDescError($record, $type_check) {
		//>> Restituisce la descrizione dell'errore nel processo di importazione (step 2)
		
		$retVal = false;
		
		switch($type_check){
		
			case _MISSING_INFO:
				$retVal =  Lang::t('_ROW_MISSING_INFO', 'gap'). "&emsp;(".$record['user_userid']." ".$record['user_lname']." ".$record['catalogue_name'].")";
			
			break;
			case _ORG_NOT_MATCH:
				$retVal = str_ireplace('[org_code]',$record['org_code'], Lang::t('_ORG_NOT_MATCH', 'gap'));
				
			break;
			case _CATALOG_NOT_REG:
				$retVal = str_ireplace('[catalogue_name]',$record['catalogue_name'], Lang::t('_CATALOG_NOT_REG', 'gap'));	
					
			break;
			case _CATALOG_EMPTY:
				$retVal = str_ireplace('[catalogue_name]',$record['catalogue_name'], Lang::t('_CATALOG_EMPTY', 'gap'));	
					
			break;
			case _GAP_DUPLICATE:
				$retVal = str_ireplace($anchors, $values, Lang::t('_GAP_DUPLICATE', 'gap')). "&emsp;(". $record['user_lname']." ".$record['catalogue_name'].")";
				
			break;
			case _GAP_EXISTS:
				$retVal = str_ireplace($anchors, $values, Lang::t('_GAP_EXISTS', 'gap')). "&emsp;(". $record['user_lname']." ".$record['catalogue_name'].")";
					
			break;
			case _GAP_NOT_FOUND:
					$retVal = str_ireplace($anchors, $values, Lang::t('_GAP_NOT_FOUND', 'gap')). "&emsp;(". $record['user_lname']." ".$record['catalogue_name'].")";
			
			break;
			case _USER_STATUS_SUSPEND:
					$retVal = str_ireplace($anchors, $values, Lang::t('_USER_STATUS_SUSPEND', 'standard')). "&emsp;(". $record['user_lname']." ".$record['user_userid'].")";
			
			break;
			
		}
		
		// Out
		return $retVal;
		
	}
	
	
	public function getInvalidList(){
		//>> Controlla i gap temporanei caricati per inserimento o aggiornamento.
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
		$this->model = new GapAlms($id_org, $this->id_user);
		$model = &$this->model;

		// Ciclo controlli
		
		foreach ($type_checks as $type_check) {

			// Lancio il controllo
			$result = $model->getGapTempInvalid($type_check);
			
			
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
	
		
	public function updateGapWizard(){
		//>> Aggiorna le assegnazioni da file Excel *.xlsx (usata per aggiornare i responsabili di gap e assegnazioni)
		
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
		$this->model = new GapAlms($id_org, $this->id_user);
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
					$res_import = $this->_importGapFile($file['tmp_name'], _OP_UPD);


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
						$model->updUserGapId();
						$model->updCatalogueId();
					}
				}	
					
				// Info colonne per la view
				$fields['field_keys'] = array('check', 'row', 'desc', 'severity');
	
				$fields['view_fields'] = array(
								array('key' => 'check', 'label' => Lang::t('_TYPOLOGY', 'standard'), 'sortable' => false),
								array('key' => 'row', 'label' => Lang::t('_FILE_ROW', 'courseassn'), 'sortable' => false),
								array('key' => 'desc', 'label' => Lang::t('_DESCRIPTION', 'standard'), 'sortable' => false));
				
				$params = array('id_org' => $id_org, 'org_name' => $org_name, 'base_link_gap' => $this->base_link_gap, 'fields' => $fields);
				
				
				// Passo alla view del secondo step (controlli)
				$this->render('update_xlsx_step_2', $params);
				
			break;
			
			case 3:
				// -- Conferma --	
				
				$num_load = count($model->getGapTmpLoad());
				$num_invalid = count($model->getGapTempInvId($type_checks));
				$num_valid = $num_load - $num_invalid;
				$num_new_manager = count($model->getNewUserGap('manager'));
				
				// Preparo i parametri
				$params = array('id_org' => $id_org,
								'org_name' => $org_name,
								'num_load' => $num_load,
								'num_invalid' => $num_invalid,
								'num_valid' => $num_valid,
								'num_new_manager' => $num_new_manager,
								'base_link_gap' => $this->base_link_gap
								);

				// Passo alla view
				$this->render('update_xlsx_step_3', $params);		
			
				
			break;
			case 4:
				// -- Importazione complessiva dei dati --
				
				// Richiesta alert per nuovi utenti
				$chk_send = (bool)$_POST['chk_send'];
				
				// 1. Aggiorno a stato sospeso i gap non validi
				$model->updSuspendGapTempInvalid($type_checks);
			
				// 2. Inserisco i nuovi utenti manager
				$users = $model->getNewUserGap('manager');
				$model->createUsers($users, $chk_send);
				
				// 5. Aggiorno gli 'id' per i nuovi utenti
				$model->updUserGapId();
				
				// 6. Aggiungo gli utenti manager al gruppo, se necessario
				$model->assignManagerRole($model->getGapTempManager(), 'manager_assignment');
				
				// 7. Aggiorno i gap
				$g = $model->updGapFromTemp();

				
				// 8. Aggiorno le assegnazioni collegate ai gap
				$a = $model->updAssnManager();
		

				// 9. Apro la view dei gap (show) con parametri per messaggio di conferma.
				if 		($g + $a > 0)	$res = 'SUCCESS';		
				elseif	($g + $a == 0)	$res = 'NO_UPDATE';
				else 					$res = 'FAILURE';
				
				Util::jump_to('index.php?r='.$this->base_link_gap.'/show&id_org='.$id_org.'&result='.$res);
		
			break;
		}
		
	}
	
	
	public function insertGapWizard(){
		//>> Importa i gap da file Excel *.xlsx
		
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
		$this->model = new GapAlms($id_org, $this->id_user);
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
				// Annullo l'importazione e riporto l'utente alla pagina deli gap
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
					$res_import = $this->_importGapFile($file['tmp_name'], _OP_INS);
					
					
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
						// Aggiorno gli id degli utenti e dei cataloghi già presenti
						$model->updUserGapId();
						$model->updCatalogueId();
					}
				}	
					
				// Info colonne per la view
				$fields['field_keys'] = array('check', 'row', 'desc', 'severity');
	
				$fields['view_fields'] = array(
								array('key' => 'check', 'label' => Lang::t('_TYPOLOGY', 'standard'), 'sortable' => false),
								array('key' => 'row', 'label' => Lang::t('_FILE_ROW', 'standard'), 'sortable' => false),
								array('key' => 'desc', 'label' => Lang::t('_DESCRIPTION', 'standard'), 'sortable' => false));
				
				$params = array('id_org' => $id_org, 'org_name' => $org_name, 'base_link_gap' => $this->base_link_gap, 'fields' => $fields);
				
				
				// Passo alla view del secondo step (controlli)
				$this->render('insert_xlsx_step_2', $params);
				
			break;
			
			case 3:
				// -- Conferma --	
							
				$num_load = count($model->getGapTmpLoad());
				$num_invalid = count($model->getGapTempInvId($type_checks));
				$num_valid = $num_load - $num_invalid;
				$num_new_user = count($model->getNewUserGap('user'));
				$num_new_manager = count($model->getNewUserGap('manager'));
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
								'base_link_gap' => $this->base_link_gap
								);

				// Passo alla view
				$this->render('insert_xlsx_step_3', $params);		
			
				
			break;
			
			case 4:
				// -- Importazione complessiva dei dati --
				
				// Richiesta alert per utenti
				$chk_send = (bool)$_POST['chk_send'];
				
				// 1. Aggiorno a stato sospeso le assegnzioni non valide
				$model->updSuspendGapTempInvalid($type_checks);

				// 2. Aggiorno i dati dei vecchi utenti	
				$model->updUserFromTemp();	
				
				// 3. Inserisco i nuovi utenti
				$users = $model->getNewUserGap('user');
				$model->createUsers($users, false);
			
				// 4. Inserisco i nuovi utenti manager
				$users = $model->getNewUserGap('manager');
				$model->createUsers($users, false);	
			
				// 5. Aggiorno gli 'id' per i nuovi utenti
				$model->updUserGapId();
				
				// 6. Aggiungo gli utenti manager al gruppo, se necessario
				$model->assignManagerRole($model->getGapTempManager(), 'manager_assignment');
				
				// 7. Inserisco i gap
				$g = $model->insGapFromTemp($ret_id);
				
				// 8. Invio alert
				if ($chk_send && $g) $this->_sendNewGapAlert($ret_id);
				
				// 9. Apro la view delle assegnazioni (show) con messaggio di conferma.
				$res = ($g > 0 ? 'SUCCESS' : 'FAILURE');
				
				Util::jump_to('index.php?r='.$this->base_link_gap.'/show&id_org='.$id_org.'&result='.$res);
		
			break;
			
		}
	
	}
	
	public function getAssnInfo(){
		//>> Restituisce le informazioni sulle assegnazioni di uno specifico gap (usata da ajax)
		
		$id_gap = Get::req('id_gap', DOTY_INT, 0);
		$res = array('success' => true);
		
			
		//Controllo permessi
		if (!$this->permissions['view']){
			$res['success'] = false;
			$res['message'] = $this->_getMessage('NO_PERMISSION');
			echo $this->json->encode($res);
			return;
		}
		
		//Recupero le assegnazioni
		$assn = $this->model->getAssnByGap($id_gap);

		
		if(!$assn) {
			$res['count'] = 0;
			
		} else {
			//Recupero stati assegnazioni
			$st_list = $this->model->getAssnStatusForDropdown();
		
			//Formattazioni 
			foreach($assn as &$row) {
				$row['date_ins'] =  Format::datetimeToString($row['date_ins'], 'date');
				$row['status'] = $st_list[$row['status']];
			}
			
			//Preparo output
			$res['count'] = count($assn);
			$res['fields'] = array(	['key'=>'date_ins', 'label'=>Lang::t('_REGISTER_DATE', 'standard')], 
									['key'=>'course_code', 'label'=>Lang::t('_COURSE_CODE', 'standard')], 
									['key'=>'course_name', 'label'=>Lang::t('_COURSE_NAME', 'standard')], 
									['key'=>'status', 'label'=>Lang::t('_STATUS', 'standard')] );
				
			$res['data'] = $assn;
		}
		
		//Out
		echo $this->json->encode($res);
		
	}
	
	
	protected function _formatCsvValue($value, $delimiter) {
		$formatted_value = str_replace($delimiter, '\\'.$delimiter, $value);
		return $delimiter.$formatted_value.$delimiter;
	}
	
	
	public function csvexport() {
		//>> Chiamata da form nascosto per esportazioni CSV
		
		$operation = Get::req('operation', DOTY_STRING, "");
		
		switch ($operation) {
			case 'export_gap':
				$this->_exportGap();
				break;
			case 'export_gap_active':
				$this->_exportGapActive();
				break;
		}
	}
	

	private function _exportGap() {
		//>> Esporta le assegnazioni in formato CSV
		
		// Controllo permessi
		if (!$this->permissions['view']) Util::jump_to('index.php?r='.$this->base_link_gap.'/show');

		require_once(_base_.'/lib/lib.download.php');
	
		$fields = $this->getTableInfo();
		$gaps = Get::req('data', DOTY_STRING, "");
		$separator = ';';
		$delimiter = '"';
		$line_end = "\r\n";

		$output = "";
		
		
		// Recupero intestazioni (etichette e nomi campo query)

		$head = array();
		
		foreach ($fields['static_fields'] as $element) {

			$key = $element['key'];
			
			if($key == 'id_catalogue') $key = 'cata_name';
			
			$head['key'][] = $key;
			$head['label'][] = $this->_formatCsvValue($element['label'], $delimiter);
		}
		
		foreach ($fields['dyn_fields'] as $element) {
			$head['key'][] = $element['key'];
			$head['label'][] = $this->_formatCsvValue($element['label'], $delimiter);
		}

		//Preparo prima riga csv di output
		$output .= implode($separator, $head['label']).$line_end;
			

		if ($gaps != "") {
			
			// Recupero gli id selezionati
			$arr_gap = explode(',', $gaps);
			$arr_gap = array_unique($arr_gap);
			
			// Carico le estrazioni in base agli ID passati dal form di chiamata
			$details = $this->model->getGapById($arr_gap, $this->id_org);
			
			// Preparo le righe per il csv di output
			if (is_array($details)) {
				
				foreach ($details as $id_gap => $detail) {
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
		sendStrAsFile($output, 'gap_export_'.date("Ymd").'.csv');
	}
	
	
	private function _exportGapActive() {
		//>> Esporta in formato xlsx le assegnazioni aperte
		
		require_once(_base_.'/lib/lib.xlsxwriter.php');
		require_once(_base_.'/lib/lib.download.php' );
					
		// Recupero i parametri inviati in formato json nel campo del form
		$json = Get::req('data', DOTY_STRING, "");
		$json = stripslashes($json);
		
		// Decodifico i parametri in oggetto php
		$post = json_decode($json);
		
		// Recupero le assegnazioni aperte
		$gaps = $this->model->getGapActive($post->id_org);
		
		
		// Recupero setting intestazioni file
		$sett_exp = Get::sett('gap_fileexport_header');
		
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
		foreach ($gaps as $row) {
			
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
		sendStrAsFile($writer->writeToString(), 'gap_active_'.date("Ymd").'.xlsx');
		
	}
	
	
	protected function getSendAlertMode() {
		//>> Restituisce se inviare una notifica e in che modo
		//>> 0 nessuna notifica, 1 invio diretto, 2 invio con coda
		$res = 0;
		
		$queue_on = ( Get::sett('mail_queue') == 'on' );
		$event_active = $this->model->isActiveAlert('UserGapInserted');
		
		if ($event_active)
			$res = $queue_on ? 2 : 1;
		
		return $res;
	}
	
	
	private function _getGapIdJson($id_org, $year, $filter_text) {
		//>> Restituisce gli id delle assegnazioni in formato json in base ai criteri passati in argomento
		 
		$res = $this->model->getGapId($year, $id_org, $filter_text);
		
		return $this->json->encode($res);
	}
	

	private function _sendNewGapAlert($arr_id_gap) {
		//>> Invia una notifica agli utenti 
		//>> Non utilizzo EventMessageComposer per alleggerire il processo di invio massivo
		
		$mailer = DoceboMailer::getInstance();
		$model = &$this->model;
		$count = 0;
		$valid_man = array();
		
		// Impsto la coda, se è attiva
		$mailer->setNewQueue('ImportGap');
		
		// Indirizzo from e url sito
		$from_address = Get::sett('sender_event');
		$url =  Get::sett('url');
		
		// Testi mail
		$subject_model = Lang::t('_NOTICE_GAP_SUBJECT', 'email');
		$body_model = Lang::t('_NOTICE_GAP_HTML', 'email');
		
		
		// Recupero i gap
		$gap_info = $model->getGapById($arr_id_gap);
		
		foreach($gap_info as $info) {

			// Recupero mail utente (lo stato dell'utente è controllato dal wizard)
			$to_address = $info['user_email'];
			
			
			// Recupero mail responsabile, se è attivo
			$idm = $info['id_manager'];
	
			if ( !array_key_exists($idm, $valid_man) ) 
				$valid_man[$idm] = $model->getUserInfo($idm, ACL_INFO_VALID );
				
			$cc_address = ( $valid_man[$idm] == '1'  ? $info['manager_email'] : false );
		
			
			// Sostituisco il tag corso nell'oggetto
			$subject = str_replace('[catalogue]', $info['cata_name'], $subject_model);


			// Preparo array per sostituzione tag della comunicazione
			$array_subst = array(	'[url]' => $url,
									'[catalogue]' => $info['cata_name'],
									'[requirement]' => $info['requirement'],
									'[firstname]' => $info['user_firstname'],
									'[lastname]' => $info['user_lastname']
								);
			
			// Sostituisco i tag					
			$body = str_replace(array_keys($array_subst), array_values($array_subst), $body_model);
			
			
			// Invio
			$mailer->SendMail($from_address, $to_address, $subject, $body, false, 
								array(MAIL_REPLYTO => $from_address, MAIL_RECIPIENTSCC => $cc_address));
			
					
			// Contatore
			$count +=1;		
		}
		
		return  $count;	
	}

}



?>
