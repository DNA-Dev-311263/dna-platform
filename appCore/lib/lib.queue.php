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

use Formalms\lib\Get;

define('_QUEUE_STATUS_WAITING', 1);
define('_QUEUE_STATUS_STARTED', 2);
define('_QUEUE_STATUS_COMPLETE', 3);
define('_QUEUE_STATUS_INCOMPLETE', 4);


class QueueManager
{
	protected $lang;
	protected $acl_man;
	protected $logUserInfo;
	protected $max_attempt;
	protected $delay; 
	

	public function __construct(){
	
		require_once(_lms_.'/lib/lib.course.php');
	
		$this->lang = DoceboLanguage::CreateInstance('admin_queue', 'adm');
		$this->acl_man = Docebo::user()->getAclManager();
		$this->max_attempt = 3;
	}


	public function __destruct(){
		
	}
	
	
	public function getStatusForDropdown() {
		
		return array(	_QUEUE_STATUS_WAITING 	=> Lang::t('_QUEUE_STATUS_WAITING', 'queue'),
						_QUEUE_STATUS_STARTED	=> Lang::t('_QUEUE_STATUS_STARTED', 'queue'),
						_QUEUE_STATUS_COMPLETE	=> Lang::t('_QUEUE_STATUS_COMPLETE', 'queue'),
						_QUEUE_STATUS_INCOMPLETE	=> Lang::t('_QUEUE_STATUS_INCOMPLETE', 'queue')
					);
	}
	
	
	public function setMaxAttempt($value) {
		if ($value > 0)
			$this->max_attempt = $value;
	}
	
	
	public function setDelay($value) {
		//Imposta il ritardo di ogni run in secondi
		
		if (is_numeric($value) && $value >= 0)
			$this->delay = $value; 
	}
	
	
	public function getLogUser($infoType = false) {
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
	

	public function delQueue($id_queue){
		//>> Elimina la coda passata in argomento
		
		$query 	= "DELETE FROM %adm_queue"
				. " WHERE id_queue = ".(int)$id_queue;
		
		// Elimino la coda e le attività correlate per integrità referenziale
        $res = sql_query($query);
  
          
		return $res;
	}
	
	
	public function delOldQueue($num_days){
		//>> Elimina le code più vecchie di n. giorni
		
		// Preparo where per filtro periodo temporale
		$whrExp = "date_ins <= date_sub(now(), interval ".(int)$num_days." day)";
		
		// Recupero le code da eliminare
		$queue = $this->getSimpleQueue("email", $whrExp); 
		
		// Elimino le code e le attività correlate per integrità referenziale
		foreach($queue as $q)
			$res = $this->delQueue($q['id_queue']);

		// Out
		return $res;
	}
	
	
	public function countLastMailings($period) {
		//>> Conta il numero di invii nell'ultimo minuto o nell'ultima ora
		
		switch ($period) {
			case "m":
				$whrWxp = "date_attempt >= date_sub(now(), interval 1 minute)";
			break;
			case "h":
				$whrWxp = "date_attempt >= date_sub(now(), interval 1 hour)";
			break;
			case "hd":
				$whrWxp = "date_attempt >= date_sub(now(), interval 12 hour)";
			default:
				$whrWxp = 1;
		}
		
		$query	= "SELECT COUNT(p.name)"
				. " FROM %adm_queue_task t "
				. " 	JOIN %adm_queue_task_property p ON t.id_task = p.id_task"
				. "	WHERE t.complete = 1 AND t.task_type = 'email' AND p.name IN ('to','cc','bcc') AND ". $whrWxp;
						
		list($res) = sql_fetch_row(sql_query($query));

		return $res;
	}
	
	
	public function getTopTask($num_task, $task_type = false) {
		//>> Restituisce le attività non completate da eseguire (le prime in base a numTask)
		
		$res = array();
		$numTask = (int)$numTask;
		
		$query	= "SELECT q.id_queue, q.checkin_code, t.id_task"
				. " FROM %adm_queue q"
				. "		JOIN %adm_queue_task t ON q.id_queue = t.id_queue" 
				. " WHERE q.status IN ("._QUEUE_STATUS_WAITING.","._QUEUE_STATUS_STARTED.") AND t.complete = 0"
				. " ORDER BY id_queue, id_task"
				. " LIMIT ".$num_task;
				
		// Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_task']] = $row;
		}
		
		return $res;
	}
	
	
	public function getProperty($id_task) {
		//>> Recupera le proprietà di una o più attività
		//>> id_task può essere un array di id
		
		$res = array();
		$arr_task = is_array($id_task) ? $id_task : array($id_task);
		
		$query	= "SELECT *"
				. " FROM %adm_queue_task_property"
				. " WHERE id_task IN ('". implode("','", $arr_task). "')";
				
		// Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_property']] = $row;
		}
		// Out
		return $res;
	}
	
	
	public function isCheckOut($queue_type = false) {
		//>> Controlla se tutte le code sono in check-out

		$whrExp = !$queue_type ? "" : "AND queue_type = '".$queue_type."'";
		
		$query	= "SELECT COUNT(*) AS cnt FROM %adm_core_queue"
				. " WHERE checkin_code IS NOT NULL ".$whrExp ;
		
		list ($found) = sql_fetch_row(sql_query($query));
		
		return !(bool)$found;
	}
	
	
	public function checkinExists($checkin_code) {
		//>> Controlla se il codice check-in esiste ancora in tabella
		
		$query	= "SELECT id_queue FROM %adm_queue"
				. " WHERE checkin_code = '". $checkin_code ."'" ;
		
		$result = sql_query($query);
		
		return $result ? true : false;
	}
	
	
	public function checkQueue($id_queue, $queue_type = false) {
		//>> Controlla se l'id della coda esiste

		$whrExp = !$queue_type ? "" : "AND queue_type = '".$queue_type."'";
		
		$query	= "SELECT COUNT(*) AS cnt FROM %adm_queue"
				. " WHERE id_queue = '".$id_queue."' ".$whrExp ;
				
		list ($found) = sql_fetch_row(sql_query($query));
		
		return (bool)$found;
	}
	
	
	protected function sendMail($mailer, $mail_properties, &$err_info = false) {
		// Invia la mail in base alle proprietà passate in argomento

		$res = false;
		$id_task = 0;
		
		// Esco se non ci sono proprietà
		if (!$mail_properties) return $res;
		
		// Default argomenti
		$args = array ( "to" 		=> array(),
						"cc" 		=> array(),
						"bcc" 		=> array(),
						"sender"	=> "",
						"subject"	=> "",
						"body"		=> "",
						"param"		=> array(),
						"attachment" => false );


		// Ciclo recupero proprietà email
		foreach ($mail_properties as $prop) {
			
			$id_task = $prop['id_task'];
			$name	= &$prop['name'];
			$value	= &$prop['value'];
			
			if (!$value) continue;

			switch ($name) {
				case "to": case "cc": case "bcc":
					$args[$name][] = $value;
				break;
				case "param":
					$args[$name] = unserialize($value);	
				break;
				case "attachment":
					//decodifico la stringa, ma l'array lo tengo serialized
					$args[$name] = base64_decode($value); 
				break;
				default:
					$args[$name] = $value;
			}
		}

		// Preparo i params con le proprietà dell mail
		$new_params = 	array(	MAIL_REPLYTO => $args["sender"], 
								MAIL_RECIPIENTSCC => implode(" ", $args["cc"]),
								MAIL_RECIPIENTSBCC => implode(" ", $args["bcc"]) );
		
		// Aggiungo gli altri params salvati nel db (es. tipo di allegati)
		$new_params = 	array_merge ($new_params, $args['param'] );
		
		// Invio mail
		$res =  $mailer->SendFormaMail($args["sender"], $args["to"], $args["subject"], $args["body"], $args["attachment"], $new_params );
		
		// Recupero eventuali errori
		$err_info[ $id_task ] = $mailer->ErrorInfo;
		
		// Out
		return $res;
	}
	
	
	public function runTaskMail($checkin_code, $num_task, &$err_info = false) {
		//>> Esegue l'attività di invio mail
		
		require_once(_base_.'/lib/lib.mailer.php');
		
		$data = array();
		$count = 0;
		$is_checkin = false;
		
		// Recupero limiti di invio del server
		$limits['m']	= Get::cfg('mail_minute', 300);
		$limits['h']	= Get::cfg('mail_hour', 2000);
		$limits['hd']	= Get::cfg('mail_half_day', 12000);
		
		// Array risposte
		$output['sent']		= array("success" => true, 	"message" => "Sent emails ");
		$output['waiting']	= array("success" => false, "message" => "waiting");
		$output['limit_m'] 	= array("success" => false, "message" => "overlimit minute");
		$output['limit_h'] 	= array("success" => false, "message" => "overlimit hour");
		$output['limit_hd']	= array("success" => false, "message" => "overlimit half day");
		
		
		// Ritardo esecuzione
		if ($this->delay) sleep($this->delay);
		
		// Controllo se ci sono checkin
		if (!$this->isCheckOut('email')) return $output['waiting'];
		
		// Controllo limiti
		if ($this->countLastMailings("m") >= $limits['m']) 		return $output['limit_m'];
		if ($this->countLastMailings("h") >= $limits['h']) 		return $output['limit_h'];
		if ($this->countLastMailings("hd") >= $limits['hd'])	return $output['limit_hd'];
		
		// Instanzio mailer
		$mailer = FormaMailer::getInstance();
		
		// Recupero le email da inviare per questo run
		$tasks = $this->getTopTask($num_task, 'email');


		// Raggruppo le email per id coda
		foreach ($tasks as $task)
			$data[ $task['id_queue'] ][] = $task['id_task'];	
	 
	 
		// Ciclo di invio
		foreach ($data as $id_queue => $mails) {
			
			// Eseguo check-in
			$this->setCheckIn($id_queue, $checkin_code);

			foreach ($mails as $id) {
	
				// Controllo il checkin ad ogni ciclo
				$is_checkin = $this->checkinExists($checkin_code);
				
				// Esco se non c'è più il checkin
				if (!$is_checkin) break;
				
				// Recupero le proprietà di invio
				$props = $this->getProperty($id);
				
				// Invio
				$res = $this->sendMail($mailer, $props, $err_info);
				
				// Conteggio
				if ($res) $count += 1;
					
				// Aggiorno stato attività
				$this->updTaskAttempt($id, $res);
			}
			
			// Eseguo check-out
			if($is_checkin) $this->setCheckOut($id_queue, $checkin_code);
			
			// Ricalcolo stato coda
			$this->updStatusRecalc($id_queue);
		}
		// Recupero il conteggio
		$output['sent']['message'] .= $count;
		
		// Out
		return $output['sent'];
	}
	
	
	public function updTaskAttempt($id_task, $complete) {
		//>> Imposta l'attività a stato completato
		$res = false;
		$complete = (int)$complete;
							
		$query 	= "UPDATE %adm_queue_task"
				. "	SET attempt = attempt+1, date_attempt = NOW(), complete = ".$complete
				. " WHERE id_task = ".(int)$id_task;
				
		// Aggiorno stato
		$res = sql_query($query);
		
		return $res;			
	}
	
	
	public function setCheckIn($id_queue, $checkin_code) {
		//>> Imposta il codice di check-in

		$query	= "UPDATE %adm_queue"
				. " SET last_execution = NOW(), checkin_code = '". $checkin_code ."'"
				. " WHERE id_queue = ". (int)$id_queue;
		
		// Aggiorno
		return sql_query($query);
	}
	
	
	public function setCheckOut($id_queue, $checkin_code) {
		//>> Elimina il codice di check-in
		
		$query	= "UPDATE %adm_queue"
				. " SET checkin_code = NULL"
				. " WHERE id_queue = ". (int)$id_queue . " AND checkin_code = '".$checkin_code."'";
		
		// Aggiorno
		return sql_query($query);		
	}
	
	
	public function insMail($sender, $recipients, $subject, $body, $attachments = false, $params = false) {
		//>> Inserisce le mail da inviare con il processo della coda
		
		$res = false;
		$id_queue = $id_task = false;
		$rec = &$recipients;
		
		
		// Recupero id della coda
		if (isset($params[MAIL_QUEUE_INFO]['id_queue'])) {
			// Prendo id dai params
			$idq = $params[MAIL_QUEUE_INFO]['id_queue'];
			
			// Controllo validità id
			if ($this->checkQueue($idq, 'email')) $id_queue = $idq;
			
		} else {
			// Recupero il codice da attribuire alla coda
			$code = isset($params[MAIL_QUEUE_INFO]['caller']) ? $params[MAIL_QUEUE_INFO]['caller'] : "code_und";
			
			// Inserisco il record di coda
			$id_queue = $this->insQueue('email', $code);
		}
		
		// Formo le proprietà destinatari
		if (isset($rec["to"]) && $rec["to"])
			$objProp->to = $rec["to"];
			
		if (isset($rec["cc"]) && $rec["cc"])
			$objProp->cc = $rec["cc"];
			
		if (isset($rec["bcc"]) && $rec["bcc"] > 0)
			$objProp->bcc = $rec["bcc"];


		// Controllo presenza dati essenziali
		if ( ! ($id_queue && get_object_vars($objProp)) ) return $res;
			
		
		// Formo le altre proprietà della mail
		if ($sender) $objProp->sender = $sender;
			
		if ($subject) $objProp->subject = addslashes($subject);
			
		if ($body) $objProp->body = addslashes($body);
		
		if ($params) $objProp->param = serialize($params); 
		
		if (is_array($attachments)) $attachments = serialize($attachments);

		if ($attachments) $objProp->attachment = base64_encode($attachments);
		
		
		// Preparo query inserimento task
		$query	= "INSERT %adm_queue_task (id_queue, task_type)"
				. " VALUES ('".$id_queue."', 'email')";
	
		// Inserisco record attività
		if ( sql_query( $query ) ) {
			
			// Recupero id attività (email)
			$id_task = sql_insert_id();
			
			if($id_task) {
				// Recupero query proprietà
				$query = $this->getSqlInsProp($id_task, $objProp);
			
				// Inserisco proprietà
				$res = sql_query($query);
			}
		}
	
		//Out
		return $res;	
	}
	
	
	protected function getSqlInsProp($id_task, $objProp) {
		//>> Restituisce la query di inserimento delle proprietà
		
		$val_exp = array();
		$id_task = (int)$id_task;

		foreach ($objProp as $prop_name => $prop_val) {
	
			if (is_array($prop_val)) {
				
				foreach($prop_val as $val) 
					$val_exp[] = "(".$id_task.", '".$prop_name."', '".$val."')";
		
			} else {
				$val_exp[] = "(".$id_task.", '".$prop_name."', '".$prop_val."')";
			}
		}
		
		$query	= "INSERT INTO %adm_queue_task_property"
				. " (id_task, name, value)"
				. " VALUES ". implode(",", $val_exp);
				
		
		return $query;
	}
	
	
	public function insQueue($queue_type, $code) {
		//>> Inserisce il record di coda e restituisce il suo id
		
		$res = false;
		$id_user = Docebo::user()->getIdSt();
		$code = $this->_convertCode($code);
		
		// Preparo la query
		$query 	= "INSERT INTO %adm_queue (queue_type, code, date_ins, user_ins)"
				. " VALUES ('".$queue_type."', '".$code."', NOW(), ".$id_user.")";
				
		// Eseguo l'inserimento
		$result = sql_query( $query );
		
		// Recupero l'id del record inserito
		if ($result)
			$res = sql_insert_id();
		
		// Out
		return $res;
	}
	

	public function updStatusRecalc($id_queue) {
		//>> Ricalcola e aggiorna lo stato del record di coda
		
		$cmpl = 0;
		$pending = 0;
		$whrExp = "id_queue = ". (int)$id_queue;
		
		// Recupero le attività del record di coda
		$tasks = $this->getSimpleTask($whrExp);
		
		// Le conto
		$count = count($tasks);
		
		// Conteggio per stato invio
		foreach ($tasks as $task) {
			
			if ($task['complete'])
				$cmpl += 1;
			elseif ($task['attempt'] < $this->max_attempt)
				$pending += 1;
		}
			
		// Stabilisco lo stato della coda
		if ($count == $cmpl)
			$new_status = _QUEUE_STATUS_COMPLETE;
		elseif ($pending > 0)
			$new_status = _QUEUE_STATUS_STARTED;
		else
			$new_status = _QUEUE_STATUS_INCOMPLETE;
			
		// Aggiorno
		return $this->updQueueStatusById($id_queue, $new_status);
	}
	
	
	public function getQueueByTask($id_task) {
		//>> Restituisce un array con gli id dei record di coda 
		//>> relazionati alle attività in argomento
		
		$res = array();
		
		$arr_task = is_array($id_task) ? $id_task : array($id_task);
		
		// Preparo strigna Where e query
		$query = "SELECT DISTINCT id_queue FROM %adm_queue_task WHERE id_task IN ('". implode("','", $arr_task) ."')";
				
		// Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_queue']] = $row['id_queue'];
		}
		
		return $res;
	}
	
	
	public function getSimpleQueue($queue_type, $whrEp = false) {
		//>> Restituisce un array con gli id dei record di coda 
		//>> relazionati alle attività in argomento
		
		$res = array();
		
		if(!is_string($whrEp)) $whrEp = 1;
			
		// Preparo strigna Where e query
		$query 	= "SELECT * FROM %adm_queue"
				. " WHERE queue_type = '".$queue_type."' AND ".$whrEp ;
				
		// Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_queue']] = $row;
		}
		
		return $res;
	}
	
	
	public function getSimpleTask($whrExp, $orderBy = false) {
		//>> Restituisce le attività della coda come da tabella
		
		$res = array();
		
		// Preparo strigna Where e query
		$whrExp 	= ($whrExp !== false ? $whrExp : '1');
		$orderBy 	= ($orderBy !== false ? $orderBy : 'id_task');
		
		$query = "SELECT * FROM %adm_queue_task WHERE ". $whrExp ." ORDER BY ". $orderBy;
				
		// Lancio la query
		$result = sql_query($query);
		
		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[$row['id_task']] = $row;
		}
		
		return $res;
	}
		
		
	public function updQueueStatusById($id_queue, $status){
		//>> Aggiorna lo stato del record di coda (modifica singola)
		
		$arrStatus = $this->getStatusForDropdown();
		$res = false;
		
		if(array_key_exists($status, $arrStatus)){
							
			$query = "UPDATE %adm_queue
						SET status = ".$status."  
						WHERE id_queue = ".(int)$id_queue;
					
			// Aggiorno stato
			$res = sql_query($query);
		}
		
		return $res;
	}
	
	
	public function getTask($whrExp = false, $orderBy = false) {
		//>> Restituisce le attività con le informazioni correlate
		
		$res = array();
		
		$whrExp 	= ($whrExp ? 'AND '. $whrExp : '');
		$orderBy 	= ($orderBy ? $orderBy : 'q.date_ins DESC, t.date_attempt DESC');	
			
		// Preparo query
		$query	= "SELECT q.id_queue, q.code, q.queue_type, q.date_ins, q.user_ins, q.status, q.last_execution," 
				. " t.id_task, t.attempt, t.date_attempt, t.complete AS task_complete,"
				. " u.userid AS user_userid, CONCAT(u.firstname, ' ', u.lastname) AS user_fullname"
				. " FROM %adm_queue q"
				. " 	JOIN %adm_queue_task t ON q.id_queue = t.id_queue"
				. "		JOIN %adm_user u ON q.user_ins = u.idst"
				. " WHERE 1 ". $whrExp
				. " ORDER BY ". $orderBy;
				
				
		// Lancio la query
		$result = sql_query($query);

		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[ $row['id_task'] ] = $row;
		}
		
		// Out
		return $res;		
		
	}
	
	
	public function resetRunQueue() {
		//>> Elimina il codice di check-in presente nella tabella delle code
		//>> Utile per far ripartire le operazioni con un altro run
		
		$query	= "UPDATE %adm_queue SET checkin_code = NULL";
		
		// Aggiorno
		return sql_query($query);	
	}
	
	
	public function getRegister($whrExp = false, $orderBy = false) {
		//>> Recupera le informazioni per il registro della coda
		
		$res = array();
		
		$whrExp 	= ($whrExp ? 'AND '. $whrExp : '');
		$orderBy 	= ($orderBy ? $orderBy : 'q.date_ins DESC, q.id_queue DESC');
		
		// Preparo query
		$query	= "SELECT q.id_queue, q.code, q.queue_type, q.date_ins, q.user_ins, q.checkin_code, q.status, q.last_execution," 
				. " COUNT(t.id_task) AS count_task, SUM(t.complete) AS sum_complete,"
				. " u.userid AS user_userid, CONCAT(u.firstname, ' ', u.lastname) AS user_fullname"
				. " FROM %adm_queue q"
				. " 	JOIN %adm_queue_task t ON q.id_queue = t.id_queue"
				. "		LEFT JOIN %adm_user u ON q.user_ins = u.idst"
				. " WHERE 1 ". $whrExp
				. " GROUP BY q.id_queue"
				. " ORDER BY ". $orderBy;
				
		// Lancio la query
		$result = sql_query($query);

		while($row = sql_fetch_assoc($result))
		{
			//restituisco la riga all'array di risposta
			$res[] = $row;
		}
		
		// Out
		return $res;
	}


	public function getFilterExpression() {
		//>> Restituisce una stringa where per le ricerche sulla query dei gap
		//>> Eseguire il replace del tag [@filter_text]
		
		return "(usr_u.userid LIKE '%[@filter_text]%' OR usr_u.firstname LIKE '%[@filter_text]%' OR usr_u.lastname LIKE '%[@filter_text]%')"; 
	}
	
	
	private function _convertCode($code) {
		//>> Converte la stringa di testo passata come codice 
		//>> (tutto in minuscolo con undescore in sostituzione del camelCase
		
        $code = str_ireplace("Docebo", "", $code);
        $arr_chr = str_split($code);
        
        $doConv = false;

		foreach($arr_chr as &$chr)
		{
			if(!$doConv && !ctype_upper($chr))
				$doConv = true;
			
			if($doConv && ctype_upper($chr)){
			   $chr = "_".$chr;
			}
		}

		return strtolower(implode("", $arr_chr));
	}
	
	
	public function delTask($id_task) {
		//>> Elimina le attività in base al loro id
		//>> Di base non necessaria perché l'eliminazione della coda elimina per
		//>> integrità referenziale anche i dati correlati
		
		$arr_task = is_array($id_task) ? $id_task : array($id_task);
		
		$query	= "DELETE T, P"
				. " FROM %adm_queue_task T"
				. "		JOIN %adm_queue_task_property ON T.id_task = P.id_task"
				. " WHERE id_task IN ('". implode("','", $arr_task) ."')";
		
		// Elimino le mail
        $res = sql_query($query);
		
		// Out
		return $res;
	}
	
	
	public function delTaskByQueue($id_queue) {
		//>> Elimina le attività in base all'id della coda.
		//>> Di base non necessaria perché l'eliminazione della coda elimina per
		//>> integrità referenziale anche i dati correlati
		
		$arr_queue = is_array($id_queue) ? $id_queue : array($id_queue);
		
		$query	= "DELETE T, P"
				. " FROM %adm_queue_task T"
				. "		JOIN %adm_queue_task_property ON T.id_task = P.id_task"
				. " WHERE id_queue IN ('". implode("','", $arr_queue) ."')";
		
		// Elimino le attività e le sue proprietà
        $res = sql_query($query);
        
		// Out
		return $res;
	}
	
}

?>
