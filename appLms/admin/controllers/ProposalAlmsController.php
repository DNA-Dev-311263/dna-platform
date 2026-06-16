<?php defined("IN_FORMA") or die("Direct access is forbidden");

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|   By ABR                                                                  |
\ ======================================================================== */

Class ProposalAlmsController extends AlmsController {

	protected $json;
	protected $acl_man;
	protected $model;

	protected $data;
	protected $permissions;

	protected $base_link_course;
	protected $base_link_proposal;


	public function init() {

		checkPerm('view', false, 'course', 'lms');
		require_once(_base_.'/lib/lib.json.php');
		
		$id_proponent = Get::req('id_proponent', DOTY_INT, 0);
		
		$this->json = new Services_JSON();
		$this->acl_man =& Docebo::user()->getAclManager();
		$this->model = new ProposalAlms($id_proponent);

		$this->base_link_course = 'alms/course';
		$this->base_link_proposal = 'alms/proposal';

		$this->permissions = array(
			'view'				=> checkPerm('view', true, 'course', 'lms'),
			'mod'				=> checkPerm('mod', true, 'course', 'lms')
		);
	}
	

	private function _getCourseIdJson($id_category, $filter_text) {
		//>> Restituisce gli id dei corsi non aggiunti in formato json
		
		$res = $this->model->getUnusedIdCourses($id_category, $filter_text);
		return $this->json->encode($res);
	}

	
	protected function getJsArrayStatus(){
		//>> Recupera i nomi di stato: per javascript
		
		$conds = array();
		$status_js = "";
		
		$list = $this->model->getCourseStatusForDropdown();
		
		foreach ($list as $id_status => $name_status) {
			$conds[] = 'status_'.$id_status.': "'.str_replace('"', '\\'.'"', $name_status).'"';
		}
		
		if (!empty($conds)) {
			$status_js = implode(','."\n", $conds);
		}
		
		return $status_js;
	}
	

	protected function show() {
		
		//Query string
		$id_proponent = Get::req('id_proponent', DOTY_INT, 0);
		$result_message = Get::req('result', DOTY_MIXED, false);

		$model = $this->model = new ProposalAlms($id_proponent);

		// Eventuali messaggi
		switch ($result_message) {
			case 'ok_ins': UIFeedback::info(Lang::t('_OPERATION_SUCCESSFUL', 'standard')); break;
			case 'err_ins': UIFeedback::error(Lang::t('_OPERATION_FAILURE', 'standard')); break;
		}

		// Aggiungo libreria js
		Util::get_js(Get::rel_path('lms').'/admin/views/proposal/proposal.js', true, true);
		
		// Visualizzo vista
		$this->render('show', array(
			'model' => $model,
			'permissions' => $this->permissions,
			'base_link_course' => $this->base_link_course,
			'base_link_proposal' => $this->base_link_proposal,
			'fields'=> $this->getViewFields('show'),
			'status_list_js' => $this->getJsArrayStatus()
		));
	}
	
	
	public function getViewFields($view) {
		//>> Restituisce i nomi campo per le tabelle delle viste
		$fields = '';
		
		if($view == 'show')
			$fields = array('id', 'id_proponent', 'id_course', 'idCategory', 'code', 'name', 
							'course_type', 'status', 'from_score', 'to_score', 'category', 'date_begin', 'date_end', 'del');
						
		elseif($view == 'addlist')
			$fields = array('id', 'code', 'name', 'course_type', 'status', 'idCategory', 'category');
			
			
		return $fields;
	}
	
	
	public function getCourseList() {
		//>> Recupera i corsi non ancora aggiunti alla proposta
		
		// Info filter
		$op = Get::req('op', DOTY_MIXED, false);
		$id_category = Get::req('id_category', DOTY_INT, 0);
		$filter_text = Get::req('filter_text', DOTY_MIXED, false);

		$start_index	= Get::req('startIndex', DOTY_INT, 0);
		$results		= Get::req('results', DOTY_MIXED, Get::sett('visuItem', 25));
		$sort			= Get::req('sort', DOTY_MIXED, 'userid');
		$dir			= Get::req('dir', DOTY_MIXED, 'asc');


		// Controllo permessi
		if (!$this->permissions['mod']) exit;
		
		//Alias model
		$model = &$this->model;
		
		// Controllo se il datatable vuole eseguire un'operazione diversa da il loading
		if ($op == 'selectall') {
			echo $this->_getCourseIdJson($id_category, $filter_text);
			return;
		}
			
		//Recupero i dati											
		$total_course = $model->totalUnusedCourse($id_category, $filter_text);
		$courses = $model->loadUnusedCourse($id_category, $start_index, $results, $sort, $dir, $filter_text);
	
		//Prendo solo le righe senza chiave id
		$courses = array_values($courses);
		
		//Preparo l'array di output
		$result =	array(
						'totalRecords' => $total_course,
						'startIndex' => $start_index,
						'sort' => $sort,
						'dir' => $dir,
						'rowsPerPage' => $results,
						'results' => count($courses),
						'records' => $courses
					);

		$this->data = $this->json->encode($result);

		echo $this->data;
		
	}


	public function getProposalList() {

		//Datatable info
		$start_index	= Get::req('startIndex', DOTY_INT, 0);
		$results		= Get::req('results', DOTY_MIXED, Get::sett('visuItem', 25));
		$sort			= Get::req('sort', DOTY_MIXED, 'userid');
		$dir			= Get::req('dir', DOTY_MIXED, 'asc');
		
		//Alias model
		$model = &$this->model;
		
		// Controllo permessi
		if (!$this->permissions['view']) exit;
		
		//Recupero i dati
		$total_proposal = $model->totalCourseProposal();
		$proposal = $model->loadCourseProposal($start_index, $results, $sort, $dir);
		
		//Preparo l'array di output
		$fields = $this->getViewFields('show');
		
		$record = array();
		$arr_data = array();
		
		foreach($proposal as $row) {
			
			//Formattazioni
			$row['date_begin'] 	= Format::datetimeToString($row['date_begin'], 'date', ''); 
			$row['date_end'] 	= Format::datetimeToString($row['date_end'], 'date', '');
			
			//Seleziono dal recordset di origine i campi necessari 
			foreach($fields as $fld)
			{
				if(array_key_exists($fld, $row))
						$record[$fld] = $row[$fld];
			}
			
			//Aggiunte
			$record['del'] = "ajax.adm_server.php?r=alms/proposal/del&amp;id_proposal=".$row['id_proposal'];
			
			//Passo il record all'array di output
			$arr_data[] = $record;
		}
	
		$result =	array(
						'totalRecords' => $total_proposal,
						'startIndex' => $start_index,
						'sort' => $sort,
						'dir' => $dir,
						'rowsPerPage' => $results,
						'results' => count($arr_data),
						'records' => $arr_data
					);

		$this->data = $this->json->encode($result);

		echo $this->data;
	}
	
	
	public function del(){
		//>> Elimina la proposta in base al suo id o tutte le proposte di un proponent
		
		// Tipo operazione
		$op = Get::req('op', DOTY_STRING, '');

		// Controllo permessi
		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => 'no permission');
			echo $this->json->encode($output);
			return;
		}
		
		if ($op == 'all') {
			//Elimino tutto	
			$id_proponent = Get::req('id_proponent', DOTY_INT, 0);
			
			$model = new ProposalAlms($id_proponent);
			$res = $model->delProposal();
			
		} else {
			//Elimino la singola proposta
			$id_proposal = Get::req('id_proposal', DOTY_INT, 0);
			$res = $this->model->delProposal($id_proposal);
		}

		// Risposta
		$this->data = $this->json->encode(array('success' => $res));

		echo $this->data;
		
	}


	public function edit(){
		//>> Aggiorna il dato della proposta (campo singolo)
		
		$output = array();
		
		// Update info
		$id_proposal = Get::req('id_proposal', DOTY_INT, 0);
		$col = Get::req('col', DOTY_STRING, '');
		$new_value = Get::req('new_value', DOTY_STRING, '');
		
		
		// Controlli
		if (!$this->permissions['mod']){
			$output = array('success' => false, 'message' => 'no permission');
			echo $this->json->encode($output);
			return;
		}

		// Modifico
		switch ($col){
			case 'from_score': 
			
				$res = $this->model->updProposalFromScore($id_proposal, $new_value);
				$output = array('success' => $res);
				break;
				
			case 'to_score': 
			
				$res = $this->model->updProposalToScore($id_proposal, $new_value);
				$output = array('success' => $res);
				break;
			
			default: 
				$output = array('success' => false, 'message' => 'field not editable');	
		}

		//Out
		echo $this->json->encode($output);
	}
	
	
	public function addProposal() {
		//>> Propone la lista dei corsi per aggiungere le nuove proposte del corso proponente		
		
		//Alias model
		$model = &$this->model;
					
		//Proponent info
		$id_proponent = $model->getIdProponent();
		
		
		if($_POST) {
			//Inserisco le proposte
			
			if (!$this->permissions['mod']) exit;
			
			$course_list = Get::req('courses', DOTY_STRING, '');
			$res = $model->insProposal($course_list);
		
			$res = ($res ? 'ok_ins' : 'err_ins');
	
			Util::jump_to('index.php?r='.$this->base_link_proposal.'/show&id_proponent='.$id_proponent.'&result='.$res);
			
		} else {
			//Apro la view per la scelta dei corsi
			
			Util::get_js(Get::rel_path('lms').'/admin/views/proposal/addlist.js', true, true);
			
			$this->render('addlist', array(
				'model' => $model,
				'permissions' => $this->permissions,
				'base_link_course' => $this->base_link_course,
				'base_link_proposal' => $this->base_link_proposal,
				'fields'=> $this->getViewFields('addlist'),
				'status_list_js' => $this->getJsArrayStatus(),
				'root_name' => Lang::t('_CATEGORY', 'admin_course_managment')
			));
		}
			
	}
	
	
	public function gettreedata() {
		//>> Restituisce le info per il tree delle categorie
	
		$treecat = new Categorytree();
		$command = Get::req('command', DOTY_ALPHANUM, "");
		
		switch ($command)
		{
			case "expand":
				$node_id = Get::req('node_id', DOTY_INT, 0);
				$initial = Get::req('initial', DOTY_INT, 0);

				$db = DbConn::getInstance();
				$result = array();
				if ($initial==1)
				{
							
					$folders = $treecat->getOpenedFolders();
					$result = array();

					$ref =& $result;
					foreach ($folders as $folder)
					{
						if ($folder > 0)
						{
							for ($i=0; $i<count($ref); $i++)
							{
								if ($ref[$i]['node']['id'] == $folder)
								{
									$ref[$i]['children'] = array();
									$ref =& $ref[$i]['children'];
									break;
								}
							}
						}

						$childrens = $treecat->getJoinedChildrensById($folder);		
						$cat_unused= $this->model->getCatUnusedCourses();
						
						while (list($id_category, $idParent, $path, $lev, $left, $right, $associated_courses) = $db->fetch_row($childrens))
						{
							//Salto la categoria se non comprende un corso ammesso all'imporazione
							if(!in_array($id_category, $cat_unused)) continue;
							
							$is_leaf = ($right-$left) == 1;

							$ref[] = array(
								'node' => array(
									'id' => $id_category,
									'label' => end(explode('/', $path)),
									'is_leaf' => $is_leaf,
									'count_content' => (int)(($right-$left-1)/2),
									'options' => false));
						}
					}

				}
				else
				{ //not initial selection, just an opened folder
					$re = $treecat->getJoinedChildrensById($node_id);
					while (list($id_category, $idParent, $path, $lev, $left, $right, $associated_courses) = $db->fetch_row($re))
					{
						$is_leaf = ($right-$left) == 1;

						$result[] = array(
							'id' => $id_category,
							'label' => end(explode('/', $path)),
							'is_leaf' => $is_leaf,
							'count_content' => (int)(($right-$left-1)/2),
							'options' => false); 
					}
				}

				$output = array('success'=>true, 'nodes'=>$result, 'initial'=>($initial==1));
				echo $this->json->encode($output);
				
			break;

			case "set_selected_node":
				//$id_node = Get::req('node_id', DOTY_INT, -1);
				//if ($id_node >= 0) $this->_setSessionTreeData('id_category', $id_node);
			break;
			//invalid command
			default: {}
		}
	}


}
?>
