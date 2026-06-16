<?php

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|	By ABR                                                                  |
\ ======================================================================== */

define("IN_FORMA", true);
define("_deeppath_", '../');
require(dirname(__FILE__).'/../base.php');

ini_set('display_errors', 'On');

// start buffer
ob_start();

// initialize
require(_base_.'/lib/lib.bootstrap.php');		
Boot::init(BOOT_DATETIME);

// not a pagewriter but something similar
$GLOBALS['operation_result'] = '';
if(!function_exists("docebo_out")) {
	function docebo_cout($string) { $GLOBALS['operation_result'] .= $string; }
}

require_once(_plugins_.'/TmsMeeting/Features/appLms/lib/lib.meeting.php');
require_once(_plugins_.'/TmsMeeting/Features/appLms/admin/models/TmsmeetingAlms.php');
require_once(_adm_.'/lib/lib.tasklog.php');
require_once(_lib_ . '/lib.pluginmanager.php');

$task_status = "on";
$task_name = "Tmsmeeting";	

$log_man = new TasklogManager();
$meeting_man = new TmsMeetingManager();

$log_res = array();


if (($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']))
	docebo_cout('PEMISSION DENIED');


elseif (	$task_status == "on" && 
			$log_man->checkFirstRecord($task_name, _TYPE_UNMANAGED, true) &&
			PluginManager::is_plugin_active('TmsMeeting')
		) {
	// Procedo se lo stato è attivo, è presente il record di inizializzazione, è attivo il plugin
	
	docebo_cout('STARTING TMSMEETING CRON, ');
		
	$now = new DateTime();
	$last_op = false;

	// Inizio operazione
	$start = $now->format('Y-m-d H:i:s');
	
	// Inserisco il log di avvio
	$id_log = $log_man->startLog($task_name, _TYPE_UNMANAGED, $start);
	
	
	// Recupero l'ultima operazione di successo (almeno una ci deve essere, vedi record di inizializzazione)
	$res =  $log_man->lastOperation($start, $task_name, 'unmanaged', _RES_SUCCESS);
	$last_op = new DateTime($res['date_begin']);

	// Il primo time di estrazione è l'ultima operazione eseguita con successo
	$date_from = $last_op->format('Y-m-d H:i:s');
	
	// L'ultimo time di estrazione è il giorno/ora di avvio della procedura
	$date_to = $start;
	
	// Inserisco i report dei partecipanti per gli eventi con fine compresa tra le date del periodo
	$result = (new TmsmeetingAlms)->insAttendanceReport($date_from, $date_to, $elab, $err_select);
	
	
	// Preparo il log
	if ($result)
		$log_res = array('success' => _RES_SUCCESS, 'message' => $elab." processed meetings");		
	else
		$log_res = array('success' => _RES_FAIL, 'message' => "import failed".(isset($err_select) && $err_select ? ', Teams: '.$err_select : ''));


	// Chiudo il log
	$log_man->endLog($id_log, $log_res['success'], false, $log_res['message']);
	
	// Messaggio di uscita
	docebo_cout('RESULT: '. $log_res['message']);
}
	

	
// finalize
Boot::finalize();

// remove all the echo
ob_clean();

// Print out the page
echo $GLOBALS['operation_result'];

// flush buffer
ob_end_flush();




