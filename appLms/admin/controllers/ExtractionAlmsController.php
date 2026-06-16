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

Use FormaLms\lib\Get;

class ExtractionAlmsController extends AlmsController {
	public $name = 'extraction';

	protected $json;
	protected $acl_man;

	protected $data;

	protected $permissions;
	protected $base_link_extraction;

	protected $id_user;
	protected $id_org_user;
	protected $model;

	public function init(){
		
		require_once(_base_.'/lib/lib.json.php');
		require_once(_adm_.'/models/UsermanagementAdm.php');
		
		$this->base_link_extraction = 'alms/extraction';
		$this->model 	= new ExtractionAlms();
		$this->json 	= new Services_JSON();
		
		$this->acl_man 		=& Docebo::user()->getAclManager();
		$this->id_user 		= Docebo::user()->getIdSt();
		
		$org_us 			= $this->model->getOrgInfoByUser($this->id_user);
		$this->id_org_user 	= (int)$org_us['idOrg_parent'];
		
		//Recupero i permessi dell'utente
		$this->permissions = array(
			'view'			=> checkPerm('view', true, 'extraction', 'lms')
		);

	}
	

	public function getFieldNames($result_query) {
		//>> Restituisce un array contenente i nomi di campo della query (array risultati) passata in argomento
		// NON USATA
		
		$count = sql_num_fields($result_query);
		$field_names = array();

		   for ($i = 0; $i < $count ; $i++ ) {
			   $field_names[] = sql_field_name($result_query, $i);
		   }

		return $field_names;
	}
	
	
	protected function show($msg = "") {
		//>> Apre la view delle estrazioni
				
		$model 		 = &$this->model;
		$table_info = false;
		$id_org		 = 0;
		$is_godadmin = $this->_isUserGodAdmin();
		$exct_code = '';
		
		// Recupero eventuali filtri di chiamata
		$postData = $this->getPostData();
		
		// Preparo informazioni di tabella
		
		switch ($postData->mode)
		{
			case 0:
				// mode = 0: prima apertura
				$exct_code = 'CalendarLearning';
				
				if(!$is_godadmin ){
					// Admin normale, recupero l'organizzazione di appartenenza dell'utente
					$id_org = $this->id_org_user;
		
				}else{
					// Superadmin, recupero l'organizzazione di appartenenza dell'utente
					$org 	= $model->getOrgInfoByLevel();
					$id_org = (count($org) > 0 ? $org[0]['idOrg'] : 0);
				}
				
				break;
			case 1:
				// mode = 1: esportazione su file csv
				// Passo da qui solo quando non ci sono dati da esportare in un file
				
				$postData->result = Lang::t('_NO_CONTENT', 'standard');
				$exct_code	= $postData->exct_code;
				$id_org 	= $postData->id_org;
	
				break;
			case 2:
				// mode = 2: caricamento dati su tabella
				$exct_code	= $postData->exct_code;
				$id_org 	= $postData->id_org;
				$table_info = $model->getTableInfo($exct_code, $postData->ctm_field, $id_org);
		}
		
		//Istanzio il modello di classe con l'id dell'organizzazione di lavoro e dell'utente
		$model = new ExtractionAlms($id_org, $this->id_user);
		
		//Inserisco le librerie javascript utili alla view
		Util::get_js(Get::rel_path('base').'/lib/js_utils.js', true, true);
							
		//Invio i parametri di preparazione della view
		$this->render('show', array(
			'model' => $model,
			'msg'	=> $msg,
			'permissions' => $this->permissions,
			'list' => $model->getList($is_godadmin),
			'is_godadmin' => $is_godadmin,
			'base_link_extraction' => $this->base_link_extraction,
			'postData' => $postData,
			'props'		=> $model->getProps($exct_code),
			'tableInfo' => $table_info
		));
		
	}
	
	public function getExtractionJson() {		
		//>> Esegue l'estrazione. Usata per chiamate Ajax
		
		$result = array();
		
		// Controllo permessi
		if (!$this->permissions['view']) exit;

		// Recupero i criteri di estrazione
		$post = $this->getPostData();
		
		// Estraggo i dati
		$args = $this->argsPrepare($post);
	
		$result = $this->model->getExtraction($args);
		
		// Trasformo i dati in json
		$this->data = $this->json->encode($result);
		
		// Preparo oggetto per dataTables
		$this->data = '{"data":'.$this->data.'}';
	
		// Out
		echo $this->data;
	}
	
	public function getParamJson() {
		//>> Esegue recupero informazioni su parametri di estrazione. Usata per chiamate Ajax
	
		$code = Get::req('exct_code', DOTY_STRING, "");
		$props = $this->model->getProps($code);
		
		$params = new stdClass;
		$params->defaults = $props->defaults;
		$params->filterAllowed = $props->filterAllowed;
		$params->statusList = array();
		$params->groupList = array();
		
		if (in_array("status", $params->filterAllowed)) 
			$params->statusList = $this->model->getStatusForDropdown($code);
		
		if (in_array("group", $params->filterAllowed)) 
			$params->groupList = $this->model->getGroupForDropdown();

		// Trasformo i dati in json
		$this->data = $this->json->encode($params);
	
		// Out
		echo $this->data;
		
	}
	
	private function argsPrepare($postData) {
		//>> Prepara l'oggetto con gli argomenti per la chiamata delle estrazioni
		
		// Recupero l'oggetto dal modello
		$args = $this->model->argsObject();
		
		// Passo i parametri
		$args->exct_code = $postData->exct_code;
		$args->status = $postData->status;
		$args->id_group = $postData->id_group;
		$args->ctm_field = $postData->ctm_field;
		
		// Sistemo le date per il db
		$args->date_from = Format::dateDb($postData->date_from, 'date');
		$args->date_to 	= Format::dateDb($postData->date_to, 'date');
		

		// Controllo se l'utente può visualizzare l'azienda passata in post (-1 è un valore inesistente)
		if(!$this->_isUserGodAdmin() && $postData->id_org != $this->id_org_user) 
			$args->id_org = -1;
		else
			$args->id_org = $postData->id_org;
			
		// Out
		return $args;
	}
	
	
	public function getPostData() {
		//>> Restituisce i parametri di chiamata della pagina
				
		$objReq = New StdClass();
		
		$objReq->mode		= Get::req('mode', DOTY_INT, 0);
		$objReq->id_org 	= Get::req('id_org', DOTY_INT, false);
		$objReq->date_from	= Get::req('date_from', DOTY_STRING, "");
		$objReq->date_to	= Get::req('date_to', DOTY_STRING, "");
		$objReq->exct_code	= Get::req('exct_code', DOTY_STRING, "");
		$objReq->status 	= Get::req('status', DOTY_MIXED, false);
		$objReq->ctm_field 	= Get::req('ctm_field', DOTY_INT, 0);
		$objReq->id_group 	= Get::req('id_group', DOTY_MIXED, false);
		
		$objReq->q_string = $_SERVER['QUERY_STRING'];	
		
		return $objReq;
	}
	
	
	protected function _formatCsvValue($value, $delimiter) {
		//>> Prepara il valore da esportare per il file csv (Inserisce il delimitatore)
				
		$formatted_value = strip_tags($value);
		$formatted_value = str_replace($delimiter, '\\'.$delimiter, $formatted_value);
		
		return $delimiter.$formatted_value.$delimiter;
	}

	public function getExtractionCsv() {
		//>> Esporta i dati in un file csv scaricabile
		
		require_once(_base_.'/lib/lib.download.php');
		
		$head = array();
		$data = array();
		$row = array();
	
		// Controllo permessi
		if (!$this->permissions['view']) exit;
		
		// Recupero filtri di chiamata
		$post = $this->getPostData();

		// Setting csv
		$separator = ';';
		$delimiter = '"';
		$line_end = "\r\n";
		$output = "";
		
		// Recupero info tabella
		$props = $this->model->getProps($post->exct_code);
		$tableInfo = $this->model->getTableInfo($post->exct_code, $post->ctm_field, $post->id_org);
		
		// Preparo intestazioni
		foreach ($tableInfo->columns as $field_info) {
			
			$head[] = $this->_formatCsvValue($field_info['label'], $delimiter);
		}
		
		// Preparo la prima riga csv
		$output .= implode($separator, $head).$line_end;
		
		// Estraggo i dati
		$args = $this->argsPrepare($post);
	
		$data = $this->model->getExtraction($args);
		
		// Se non ci sono righe torno alla pagina iniziale e non esporto file csv
		if(count($data) == 0) {$this->show(); return;}


		// Esporto il file	
		// Se ci sono record, preparo le righe csv (ciclo per riga e per colonna)
		foreach ($data as $row) {
			
			// Reset array dati riga
			$csv_row = array();
			
			// Prelevo solo i campi di esportazione nell'ordine indicato in tableInfo
			foreach ($tableInfo->columns as $field_info) {
				
					$fld = $field_info['key'];
					$csv_row[] = $this->_formatCsvValue($row[$fld], $delimiter);
			}
			// Formo riga csv
			$output .= implode($separator, $csv_row).$line_end;
		}
		
		// Scrivo il file
		sendStrAsFile($output, $post->exct_code.'_'.date("Ymd").'.csv');
		
	}
	

	private function _isUserGodAdmin(){
		//>> Restituisce true se l'utente corrente è un super amministratore
	
		$res = (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN);
		return $res;
	}

}



?>
