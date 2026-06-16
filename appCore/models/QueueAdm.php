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

class QueueAdm extends Model
{
	protected $queue_man;
	protected $id_user;
	
		
    public function __construct($id_user = 0)
    {
		require_once(_adm_.'/lib/lib.queue.php');
		require_once(_lms_.'/lib/lib.courseassn.php');
		
		$this->queue_man = new QueueManager();  
		$this->id_user = $id_user;
	}


	public function getPerm() {
        return array('view' => 'standard/view.png', 'mod' => 'standard/mod.png', 'del' => 'standard/del.png' );
	}
	
	
	public function getStatusForDropdown($with_all = true) {
		//>> Restituisce la lista degli stati più la voce tutti
		
		$list = $this->queue_man->getStatusForDropdown();
		
		if ($with_all) 
			$list[-1] = Lang::t('_ALL', 'standard');
		
		return $list;
	}
	
	
	public function delQueue($id_queue) {
		//>> Elimina una o più code
		//>> id_queue può essere un array di id

		$arr_queue = is_array($id_queue) ? $id_queue : array($id_queue);
		$deleted = 0;
		
		// Elimino
		foreach ($arr_queue as $id) {
			if ( $this->queue_man->delQueue($id) ) $deleted++;
		}
		
		// Out
		return $deleted;	
	}
	
	
	public function getTaskByQueue($arr_queue) {
		//>> Restituisce le informazioni complete delle attività
		//>> arr_queue è un array di id_queue
		
		$res = array();
		$q_man = &$this->queue_man;
		$whrExp = "q.id_queue IN ('". implode("','", $arr_queue). "')";
		
		// Recupero le attività
		$task_info = $q_man->getTask($whrExp);
		
		// Recupero le proprietà
		$prop_info = $q_man->getProperty(array_keys($task_info));
		
		// Proprietà da esportare
		$prop_admit = array('to', 'subject');
		
		// Aggiungo proprietà a riga attività
		foreach ($prop_info as $prop) {
			
			$id = $prop['id_task'];
			$name = $prop['name'];
			
			if ( in_array($name, $prop_admit) )
				$task_info[$id][$name] = $prop['value'];
		}
		// Out
		return $task_info;
	}


	public function getRegister($date_from, $status = -1) {
		//>> Restituisce le email della coda a partire da una data di partenza
	
		$date_from 	= Format::dateDb($date_from, 'date');
		
		$whrExp = "q.date_ins >= '". $date_from. "'";
		
		if ($status >= 0)
			$whrExp .= " AND q.status = ". (int)$status;
		
		return $this->queue_man->getRegister($whrExp);
	}
	
	
	public function resetRunQueue() {
		//>> Annulla l'esecuzione del run corrente
		 return $this->queue_man->resetRunQueue();
	}
	
	
	public function getOrgForDropdown(){
		//>> Usata per restituire le organizzazioni da inserire in combo
		
		$res = array();
		$org = $this->getOrgInfoByLevel();
			
		foreach($org as $k => $row) 
			$res[$row['idOrg']] = $row['code'];
		
		return $res;	
	}
	
	
	public function getOrgInfoByUser($id_user, $lev_org_chart = 1){
		//>> Restituisce le informazioni sul nodo organizzativo di appartenenza dell'utente
		
		$ca_man = new CourseassnManager();
		
		// Recupero il nodo organizzativo
		$retVal = $ca_man->getOrgInfoByUser($id_user, $lev_org_chart);
		
		// Out
		return $retVal;	
	}
	
	
	public function getOrgInfoByLevel($lev_org_chart = 1){
		//>> Restituisce le informazioni sui nodi organizzativi di un dato livello

		$ca_man = new CourseassnManager();
		return $ca_man->getOrgInfoByLevel($lev_org_chart);
	}
	
	
	public function getTableInfo() {
		//>> Restituisce un array contenente le informazioni sulla tabella del registro
		
		$col[] = array('key'=> 'chk_cell', 'label' => Lang::t('_SELECT', 'standard'));
		$col[] = array('key'=> 'date_ins', 'label' => Lang::t('_CREATION_DATE', 'standard'));
		$col[] = array('key'=> 'code', 'label' => Lang::t('_CODE', 'standard'));
		$col[] = array('key'=> 'queue_type', 'label' => Lang::t('_TYPE', 'standard'));
		$col[] = array('key'=> 'user_userid', 'label' => Lang::t('_AUTHOR_USERID', 'queue'));
		$col[] = array('key'=> 'user_fullname', 'label' => Lang::t('_AUTHOR_NAME', 'queue'));
		$col[] = array('key'=> 'last_execution', 'label' => Lang::t('_LAST_EXEC', 'queue'));
		$col[] = array('key'=> 'status', 'label' => Lang::t('_STATUS_QUEUE', 'queue'));
		$col[] = array('key'=> 'count_task', 'label' => Lang::t('_TOT_TASK', 'queue'));
		$col[] = array('key'=> 'sum_complete', 'label' => Lang::t('_TOT_TASK_COMPLETE', 'queue'));

		return $col;
	}
	
	
	public function getTableDetailInfo() {
		//>> Restituisce un array contenente le informazioni sulla tabella di esportazione dettagli
		
		$col[] = array('key'=> 'id_queue', 'label' => Lang::t('_QUEUE', 'standard'));
		$col[] = array('key'=> 'date_ins', 'label' => Lang::t('_CREATION_DATE', 'standard'), 'format' => 'datetime');
		$col[] = array('key'=> 'code', 'label' => Lang::t('_CODE', 'standard'));
		$col[] = array('key'=> 'queue_type', 'label' =>  Lang::t('_TYPE', 'standard'));
		$col[] = array('key'=> 'user_userid', 'label' => Lang::t('_AUTHOR_USERID', 'queue'));
		$col[] = array('key'=> 'user_fullname', 'label' => Lang::t('_AUTHOR_NAME', 'queue'));
		$col[] = array('key'=> 'last_execution', 'label' => Lang::t('_LAST_EXEC', 'queue'), 'format' => 'datetime');
		$col[] = array('key'=> 'id_task', 'label' => Lang::t('_TASK', 'queue'));
		$col[] = array('key'=> 'attempt', 'label' => Lang::t('_ATTEMPT', 'queue'));
		$col[] = array('key'=> 'date_attempt', 'label' => Lang::t('_ATTEMPT_DATE', 'queue'), 'format' => 'datetime');
		$col[] = array('key'=> 'task_complete', 'label' => Lang::t('_TASK_COMPLETE', 'queue'));
		$col[] = array('key'=> 'to', 'label' => Lang::t('_PROP_TO', 'queue'));
		$col[] = array('key'=> 'subject', 'label' => Lang::t('_PROP_SUBJECT', 'queue'));

		return $col;
	}			
	
}

?>
