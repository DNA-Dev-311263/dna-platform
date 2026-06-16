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
\ ======================================================================== */

define("IN_FORMA", true);
define("_deeppath_", '../');
require(dirname(__FILE__).'/../base.php');

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
function encryptParam() { return crypt($_SERVER["HTTP_HOST"],'hf'); }

// do something
	
	$p = $_GET["p"];
	$queue_on	= ( Formalms\lib\Get::sett('mail_queue') == 'on' );
	$folder_log = dirname(__FILE__)."/log";
	$file_log	= $folder_log."/qmail_".date('Y-m-d').".txt";
	$old_point	= 21;
	
	
	if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'] || $p != encryptParam())
		docebo_cout('PEMISSION DENIED');
		if ($p == "74741")
			docebo_cout("<br/>".encryptParam()); //Per leggere codice di lancio in fase di configurazione
	
	elseif (!$queue_on)
		docebo_cout('QUEUE NOT ACTIVE');
		
	else {
		docebo_cout('STARTING QUEUE CRON: ');
		
		require_once(_adm_.'/lib/lib.queue.php'); 
		$queue_man = new QueueManager();
		
		// Pulizia notturna (controlli effettuati nei primi run della una di notte)
		if (date('H') == 1 && date('i') < 15) {
			// Elimino code
			$queue_man->delOldQueue($old_point);
			
			// Elimino file di log
			$arr_file = glob($folder_log."/*.txt");
			$now = time();
			
			foreach ($arr_file as $file) {
				if ($now - filectime($file) >= 60 * 60 * 24 * $old_point)
					unlink($file);
			}
		}
		
		// Tempo di attesa del run
		$queue_man->setDelay(4);
		
		// Preparo il codice di check-in
		$checkin_code = date("YmdHi");
		
		// Invio (err_info è un array di errori mail, utile per debug)
		$res = $queue_man->runTaskMail($checkin_code, 200, $err_info);

		// Log
		$log = $checkin_code.';'.(int)$res["success"].';"'.$res["message"].'"'. PHP_EOL;
		
		$myfile = file_put_contents($file_log, $log, FILE_APPEND | LOCK_EX);
		
		// Out
		docebo_cout($res["message"]);
	}

// finalize
Boot::finalize();

// remove all the echo
ob_clean();

// Print out the page
echo $GLOBALS['operation_result'];

// flush buffer
ob_end_flush();
