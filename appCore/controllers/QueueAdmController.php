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
|   Dev by ABR																|        
\ ======================================================================== */
Use Formalms\lib\Get;

class QueueAdmController extends AdmController {
	
	public $name = 'queue';
	protected $model;
	protected $json;
	protected $acl_man;
	protected $base_link_queue;
	protected $user_level;
	protected $id_user;
	protected $id_org_user;
	protected $permissions;


	public function init(){

		checkPerm('view', false, 'queue', 'framework');
	
		$this->base_link_queue = 'adm/queue';
		
		$this->user_level = Docebo::user()->getUserLevelId();
		$this->id_user = Docebo::user()->getIdSt();
		
		$this->model = new QueueAdm();
		$this->json = new Services_JSON();
		$this->acl_man = Docebo::user()->getAclManager();
		
		$org_us = $this->model->getOrgInfoByUser($this->id_user);
		$this->id_org_user = (int)$org_us['idOrg_parent'];
	
		//Recupero i permessi dell'utente
		$this->permissions = array(
			'view'	=> checkPerm('view', true, 'queue', 'framework'),
			'mod'	=> checkPerm('mod', true, 'queue', 'framework'),
			'del'	=> checkPerm('del', true, 'queue', 'framework')
		);
	}
	
	
	private function _getMessage($code){
		$message = "";
		
		switch ($code){
			case "NO_PERMISSION": $message = "You don&#96;t have the required permission";
				break;
			case "FAILURE": $message = Lang::t('_OPERATION_FAILURE', 'standard');
				break;	
		}
		return $message;
	}
        
	
	protected function isUserGodAdmin(){
		//>> Restituisce true se l'utente corrente è un super amministratore
		if($this->user_level == ADMIN_GROUP_GODADMIN)
			return true;
	}
	
	
	public function getRegisterList() {		
		//>> Restituisce l'elenco delle mail in coda in formato Json. Usata per chiamate Ajax
		
		$result = array();
		$model = new QueueAdm();
		$can_go = true;
		
		// Controllo permessi
		if (!$this->permissions['view']) $can_go = false;
		
		
		if ($can_go) {
			// Recupero i criteri di selezione
			$post 	= $this->getPostData();

			// Recupero traduzioni stato
			$st = $model->getStatusForDropdown();
		
			// Recupero le mail
			$result = $model->getRegister($post->date_from, $post->status);
			
			// Formattazioni e nuovi campi
			foreach ($result as &$row) {
				$row['chk_cell'] = null;
				$row['status'] = $st[ $row['status'] ];
				$row['user_userid'] = $this->acl_man->relativeId($row['user_userid']);
				$row['date_ins'] = Format::datetimeToString($row['date_ins'], 'datetime');
				$row['last_execution'] = Format::datetimeToString($row['last_execution'], 'datetime');
			}
		}
		
		// Dati per dataTables
		$data = $this->json->encode($result);
		echo '{"data":'.$data.'}';
	}


	public function show() {
		
		$model = &$this->model;
	
		// Istanzio il modello di classe con l'id dell'utente
		$model = new QueueAdm($this->id_user);
		
		//Librerie
		Util::get_js(Get::rel_path('base').'/lib/js_utils.js', true, true);
		Util::get_js(Get::rel_path('adm').'/views/queue/queue.js', true, true);
		
		// Apro la vista
		$this->render('show', array(
			'model' => $model,
			'permissions' => $this->permissions,
			'base_link_queue' => $this->base_link_queue,
			'is_godadmin' => $this->isUserGodAdmin(),
			'tableInfo' => $model->getTableInfo()
		));

	}
	
	
	public function multidel(){
		//>> Eliminazione multipla di mail

		if (!$this->permissions['del']){
			$output = array('success' => false, 'message' => $this->_getMessage('NO_PERMISSION'));
			echo $this->json->encode($output);
			return;
		}

		$queue = Get::req('queue', DOTY_STRING, "");
		$output = array();

		
		if ($queue == ''){
			$output = array('success' => true, 'count' => 0, 'total' => 0);
		} else {
			$list_queue = explode(',', $queue);
		
			$total = count($list_queue);
			$deleted = $this->model->delQueue($list_queue);
			
			$output = array('success' => (bool)$deleted, 'total' => $total, 'deleted' => $deleted);
		}

		echo $this->json->encode($output);
	}
	
	
	protected function restart() {
		//>> Annulla il run della coda (via ajax)
		
		$output['success'] = false;
		
		if ($this->permissions['mod']){
			
			$output['success'] = $this->model->resetRunQueue();
		}
		
		echo $this->json->encode($output);
	}


	public function getPostData() {
		//>> Restituisce i parametri di chiamata della pagina
			
		$objReq = New StdClass();
	
		$objReq->date_from		= Get::req('date_from', DOTY_STRING, "");
		$objReq->status			= Get::req('status', DOTY_INT, 0);
		$objReq->queue_list		= Get::req('queue_list', DOTY_STRING, "");
		$objReq->q_string 		= $_SERVER['QUERY_STRING'];
		$objReq->method 		= $_SERVER['REQUEST_METHOD']; 
		
		return $objReq;
	}
	
	
	protected function exportTaskDetail() {
		//>> Esporta in formato xlsx le attività selezionate
		
		require_once(_base_.'/lib/lib.xlsxwriter.php');
		require_once(_base_.'/lib/lib.download.php' );
		
		$model = $this->model;
						
		// Recupero i parametri inviati in formato json nel campo del form
		$selList = Get::req('data', DOTY_STRING, "");
		
		// Recupero gli id selezionati
		$arr_queue = explode(',', $selList);

		// Recupero le assegnazioni aperte
		$tasks = $model->getTaskByQueue($arr_queue);
		
		// Esco se non ci sono attività o non si hanno i permessi
		if (!$tasks || !$this->permissions['view'])	Util::jump_to('index.php?r='.$this->base_link_queue.'/show');

		// Recupero informazioni tabella di esportazione
		$tab_info = $model->getTableDetailInfo();
		
		// Intestazioni
		$columns = array_column($tab_info, 'label', 'key');
		$formats = array_column($tab_info, 'format', 'key');
		$fields = array_keys($columns);
		

		foreach($fields as $key){
			
			// Definisco colonne excel (lascio che sia Excel a formattare)
			if (isset($formats[$key]) && $formats[$key] == 'datetime' ) {
				$xls_header[ $columns[$key] ] = 'DD/MM/YYYY hh:mm:ss';
			} else {
				$xls_header[ $columns[$key] ] = 'string';
			}
		}
		
		// Preparo il file
		$writer = new XLSXWriter();	
		
		// Scrivo intestazioni
		$styles = array( 'font'=>'Arial','font-size'=>10,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'freeze_rows'=>1);
		
		$writer->writeSheetHeader('Sheet1', $xls_header, $styles);
		
		
		// Scrivo righe con scelta campi
		foreach ($tasks as $row) {
			
			foreach ($fields as $key) {
					
				if (array_key_exists($key, $row)) {
					
					// Recupero il dato
					$new_row[$key] = $row[$key];
					
					if (strripos($key, "userid") !== false)
						$new_row[$key] = $this->acl_man->relativeId($new_row[$key]);
								
				}
			}
			
			// Scrivo la riga
			$writer->writeSheetRow('Sheet1', $new_row );
		}
	
		// Lancio il download del file;
		sendStrAsFile($writer->writeToString(), 'task_'.date("Ymd").'.xlsx');
		
	}
	

}

?>
