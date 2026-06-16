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


define('_TYPE_MANAGED', 'managed');
define('_TYPE_UNMANAGED', 'unmanaged');


define('_RES_SUCCESS', 1);
define('_RES_FAIL', 0);


class TasklogManager
{
	protected $lang;
	

	public function __construct(){
	
	}


	public function __destruct(){
		
	}
	

	public function delLog($id_log){
		//>> Elimina il log passato in argomento
		
		$query 	= "DELETE FROM %adm_task_log"
				. " WHERE id_log = ".(int)$id_log;

        $res = sql_query($query);
          
		return $res;
	}
	
	
	public function startLog($task_name, $task_type, $date_begin = false, $note = '') {
		//>> Inserisce il log aperto
		
		$id_log = false;
		
		// Recupero la data di inizio
		if (!$date_begin)
			!$date_begin = (New DateTime())->format('Y-m-d H:i:s');
	
		// Preparo query inserimento
		$query	= "INSERT %adm_task_log (task_name, task_type, date_begin, note)"
				. " VALUES ('".$task_name."', '".$task_type."', '".$date_begin."', '".$note."')";
	
		// Inserisco record log
		if ( sql_query( $query ) ) {
			
			// Recupero id log
			$id_log = sql_insert_id();
			
		}
	
		//Out
		return $id_log;	
	}
	
	
	public function endLog($id_log, $result, $date_end = false, $note = '') {
		//>> Chiude il log
		
		$res = false;
		
		// Recupero la data di inizio
		if (!$date_end)
			!$date_end = (New datetime())->format('Y-m-d H:i:s');
	
		// Preparo query inserimento
		$query	= "UPDATE %adm_task_log SET result = ". (int)$result .", date_end = '".$date_end."', note = '".$note."' 
					WHERE id_log = ".(int)$id_log;
	
		// Inserisco record log
		$res = sql_query( $query );
			
		//Out
		return $res;	
	}
		
		
	public function lastOperation($look_behind_date = false, $task_name = false, $task_type = false, $result = false) {
		//>> Recupera l'ultima operazione precedente a una certa data (<=)
		
		$where = '';
		$res = array();
		
		// Sistemo data di ricerca all'indietro
		if (!$look_behind_date)
			$look_behind_date = (new DateTime)->format('Y-m-d');
		
		// Preparo where		
		if (!$task_name)
			$where .= " AND task_name = '". $task_name. "'";
			
		if (!$task_type)
			$where .= " AND task_type = '". $task_type. "'";
			
		if ($result !== false)
			$where .= " AND result = ". (int)$result;
			
			
		// Stringa SQL per ottenere l'ultiimo id
		$query = "SELECT MAX(id_log) AS id_log FROM %adm_task_log 
					WHERE date_begin <= '". $look_behind_date ."' ". $where;
						
												
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero l'ultima operazione
		if($result) {
			// Riga ultimo id
			$row = sql_fetch_assoc($result);
			
			// Recupero l'operazione
			$res = $this->getSingleLog($row['id_log']);
		}
		
		// Out
		return $res;
				
	}
	
	
	public function getLog($where = false){
		//>> Restituisce i log in base all'espressione where passata in argomento
	
		$res = array();

		if (!$where)
			$where .= "WHERE ".$where;
		
		//Recupero la stringa SQL
		$query = "SELECT * FROM %adm_task_log ".$where;
						
			
		//Lancio la query
		$result = sql_query($query);
		
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_log']] = $row;
		}
		
		//Out
		return $res;
	}
	
	
	function checkFirstRecord($task_name, $task_type, $ins_not_exists = false) {
		//>> Controlla l'esistenza del record di inizializzazione.
		//   Se non è presente e l'argomento è true, lo inserisce
		
		$exists = $this->countLog($task_name, $task_type) > 0;
		
		if (!$exists && $ins_not_exists) {
			
			$date = date_create("1900-01-01");
			$date = $date->format('Y-m-d H:i:s');
	
			// Preparo query inserimento
			$query	= "INSERT %adm_task_log (task_name, task_type, date_begin,  date_end, result, note)"
					. " VALUES ('".$task_name."', '".$task_type."', '".$date."', '".$date."', "._RES_SUCCESS.", 'initialization')";
	
			// Inserisco record log
			$exists = sql_query( $query );	
		}
		
		// Out
		return $exists;
	
	}
	
	
	public function countLog($task_name, $task_type, $where = false){
		//>> Conta i log in base al nome attività e al where passato in argomento
				
		// Sistemo Where
		$where = (!$where ? '' : ' AND '.$where);
			
		//Recupero la stringa SQL
		$query = "SELECT COUNT(*) As total FROM %adm_task_log 
					WHERE task_name = '".$task_name."' AND task_type = '".$task_type."' ".$where;
			
		
		// Lancio la query
		$result = sql_query($query);
		
		// Recupero la riga con il conteggio
		$row = sql_fetch_assoc($result);
		
		// Out
		return $row['total'];
	}
	
	
	public function getSingleLog($id_log) {
		//>> Restituisce un singolo log in base al suo id
		
		$res = array();
		
		//Recupero la stringa SQL
		$query = "SELECT * FROM %adm_task_log WHERE id_log = ".(int)$id_log;
							
		//Lancio la query
		$result = sql_query($query);
		
		//Recupero la riga
		if ($result)
			$res = sql_fetch_assoc($result);
		
		//Out
		return $res;
	}
	
}

?>
