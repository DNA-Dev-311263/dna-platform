<?php 
defined("IN_FORMA") or die("Direct access is forbidden");


/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|   By ABR                                                                  |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
\ ======================================================================== */

use FormaLms\lib\Get;

class TmsmeetingAlmsController extends AlmsController {
    
    const mod_name = 'TmsMeeting';
    const LMS_PATH = _plugins_.'/TmsMeeting/Features/appLms/admin';

    protected $model;
    protected $json;
    protected $user_level;
    protected $link, $link_subs, $link_course, $link_classroom;
    	

    public function init() {
		
        // Includo model per chiamate non via url
        require_once (self::LMS_PATH. '/models/TmsmeetingAlms.php');
        require_once(_base_ . '/lib/lib.json.php');
        
        // Istanzio model;
        $this->model = new TmsmeetingAlms();
        
        // Json
        $this->json = new Services_JSON();
        
        // Livello utente corrente
        $this->user_level = Docebo::user()->getUserLevelId();
        
        // Link per Jump
        $this->link				= 'alms/tmsmeeting';
        $this->link_subs		= 'alms/subscription';
		$this->link_course		= 'alms/course';
		$this->link_classroom	= 'alms/classroom';
        
    }


	/**
	 * Restituisce il path delle view
	 */
	public function viewPath() {
		return self::LMS_PATH. '/views';
    }
    
    
    /**
     * Restituisce true se l'utente corrente è un amministratore
     */
 	protected function isUserAdmin(){

		if($this->user_level == ADMIN_GROUP_GODADMIN || $this->user_level == ADMIN_GROUP_ADMIN )
			return true;
	}
    
    
	/**
	 * Controlla se la chiave segreta è valida, se non lo è segnala all'utetne l'impossibilità di procedere
	 */
    private function checkClientSecret() {
		
		if ( !$this->model->isClientSecretValid() && $this->isUserAdmin()) {
			// Salto alla pagina di segnalazione
			Util::jump_to('index.php?r='.$this->link.'/invalidSecret');	
		}
	}
	
	/**
	 * Pagina di segnalazione chiave segreta non valida
	 */
    public function invalidSecret() {
		$authUrl = Get::sett('tmsmeeting.auth_url', '');
		$this->render('invalid_secret', ['auth_url' => $authUrl]);
	}
	
	
	/**
	 * Controlla che la chiave segreta sia valida al caricamento delle edizioni
	 */
	public function onEditionList($event) {
		
		$this->checkClientSecret();
	}
    
    
    /**
     * Gestione evento all'eliminazione del corso
     */
    public function onCourseDeleted($event) {
	
		$model = &$this->model;
		
		// Recupero id del corso eliminato
		$args = $event->getArguments();
		$id_course = $args['id_course'];
			
		// Procedo con eliminazione dei meeting se il corso è stato pianificato
		if ( $id_course && $model->isMeetingCourse($id_course, false) ) {
						
			// Elimino meeting
			$model->deleteCourseEditionMeeting($id_course);
		}
	} 
	
	
	/**
	 * Gestione evento modifica iscrizione
	 * 
	 */
	public function onSubscriptionChanged($event) {
		
		$res = true;
	
		// Recupero il modello
		$model = &$this->model;

		// Recupero argomenti evento
		$id_course	= $event->getArguments()['id_course'];
		$id_date	= $event->getArguments()['id_edition'];
		$users 		= $event->getArguments()['users'];
		$action 	= $event->getArguments()['action'];
		$action_details = $event->getArguments()['action_details'];
		
		
	require_once(_lms_.'/lib/lib.courseassn.php');

		// Controllo che sia un corso di meeting
		if ( !$model->isMeetingCourse($id_course) ) return false;
		
		switch($action)
		{
			case _EVENT_SUBSCRIPTION_INSERT:	

				$res = $model->insertInvitees($id_date, array_keys($users));
				break;
				
			case _EVENT_SUBSCRIPTION_UPDATE:

				if ( !$this->checkUpdStatus($action_details) ) return false;
				
				$res = $model->updateInvitees($id_course, $users, $action_details);
				break;
				
			case _EVENT_SUBSCRIPTION_DELETE:
				$res = $model->deleteInvitees($id_date, array_keys($users));
				break;
		}
		
		// Errori (li restituisco solo se la richiesta proviene dal backend)
		if (!$res && $this->checkFromModule($action_details, 'alms'))
			Util::jump_to('index.php?r='.$this->link_subs.'/show&id_course='.$id_course.'&id_date='.$id_date.'&err=_OPERATION_FAILURE');
		
	}
	
	
	private function checkUpdStatus($action_details) {
		//>> Restituisce se è un cambio di stato utente nel corso
		return array_key_exists('status', $action_details);
	}
	
	private function checkUpdLevel($action_details) {
		//>> Restituisce se è un cambio di livello utente
		return array_key_exists('level', $action_details);
	}
	
	
	private function checkFromModule($action_details, $module) {
		//>> Restituisce true se il modulo di chiamata è quello passato in argomento
		return (array_key_exists('modActFrom', $action_details) && $action_details['modActFrom'] == $module);
	}
	
	public function onEditionChanged($event) {
		//>> Gestione evento modifica edizione
		
		$res = true;
		
		// Recupero il model
		$model = &$this->model;

		// Recupero ID corso
		$id_course = $event->getArguments()['id_course'];
		
		// Recupero altri argomenti evento
		$id_date	 	= $event->getArguments()['id_edition'];
		$action 	 	= $event->getArguments()['action'];
		$action_details = $event->getArguments()['action_details'];
		
		// Determino azione e lancio metodo
		switch ($action)
		{
			case _EVENT_EDITION_DAY_BEFORE_UPDATE:
				// Controllo la pianificazione. Gli orari di inizio devono essere precedenti a quelli di fine
				if ( !$model->checkSchedule($action_details) ) 
					exit( $this->json->encode(['success' => false, 'message' => Lang::t('_TMSMEETING.SCHEDULE_ERROR', 'classroom')]) );
					
				break;
				
			case _EVENT_EDITION_DAY_AFTER_UPDATE:
				// Gestisco i meeting in base alle variazioni di date
				
				if ( !$model->isVirtualCourse($id_course) ) return false;
				
				$res = $model->manageEventMeetings($id_date);
				break;
				
			case _EVENT_EDITION_UPDATE:
				// Si verifica al cambiamento dei dati anagrafici
						
				if ( !$model->isVirtualCourse($id_course) ) return false;
				
				$res = $model->manageUpdateEdition($id_date, $action_details);
				break;
				
			case _EVENT_EDITION_DELETE:
				// Si verifica all'eliminazione dell'edizione	
				
				if ( !$model->isMeetingCourse($id_course) ) return false;
				
				$res = $model->deleteEditionMeetings($id_date);
				break;
		}
		
		// Errori
		if ($res === false)
			//Util::jump_to('index.php?r='.$this->link_classroom.'/classroom&id_course='.$id_course.'&result=error&err_key=_TMSMEETING.SCHD_ERROR');
			exit( $this->json->encode( ['success' => false, 'message' => Lang::t('_TMSMEETING.SYNC_ERROR', 'classroom')] ) );
	}
	
	
	public function getModel() {
		// Restituisce il model
		return $this->model;
	}


    public function ExportToFile() {
		//Esporta i dati di fruizione in un file Excel o Pdf
		
		ini_set('display_errors', '1');
	
		$type = Get::req('type', DOTY_STRING, "");
		$id_date = Get::req('id_date', DOTY_INT, "");
		$id_org = Get::req('id_org', DOTY_INT, "");
		$list = Get::req('list', DOTY_STRING, "");
		
		// Controllo permessi
		checkPerm('view', false, 'extraction', 'lms');
	
		if ($list == "participant" && $type == "pdf")
		{
			$this->printAttendeePdf($id_date, $id_org);
			
		} elseif ($list == "participant" && $type == "xls")
		{
			$this->printAttendeeXls($id_date, $id_org);
		}

    }
       
    
	private function getPdf($html, $name, $img = false, $orientation = 'L', $download = true, $facs_simile = false, $for_saving = false, $hr_pgbreak = true) {
		//>> Prepara e invia al download il pdf in base al codice html passato in argomento
		
		require_once(_base_.'/lib/pdf/lib.pdf.php');
		
		// PDF($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = "UTF-8", $margins = false, enable_footer = false, $auto_pgbreak = false)

		$pdf = new PDF($orientation, 'mm', 'A4', true, 'UTF-8', array(12, 12, 12), true, true);
	
		if($for_saving)
			return $pdf->getPdf($html, $name, $img, $download, $facs_simile, $for_saving, $hr_pgbreak);
		else
			$pdf->getPdf($html, $name, $img, $download, $facs_simile, $for_saving, $hr_pgbreak);
	}
	
	
	private function transField($text_key) {
		//>> Restituisce il codice di campo tradotto se presente la traduzione
		
		$key = '_EXCT_F_'.strtoupper($text_key);
		$tmp_name = Lang::t($key, 'extraction', false, false, str_replace("_", " ", ucfirst($text_key)));
		
		return $tmp_name;
	}
    
    
    private function getAttendeeHtml($id_date, $id_org) {
		//>> Recupera la lista dei partecipanti in fomrmato html
		
		// Recupero dati edizioni
		$edition_days = $this->model->getEditionDayInfo($id_date);
		
		
		//$template = file_get_contents('/path/to/file');
		$section = 0;
		$html = "";
			
		foreach($edition_days as $day) {
			$section++;
			
			// Recupero presenze partecipanti
			$attendees = $this->model->getTmsAttendeeList($id_date, $day['id'], $id_org);
			
			// Interruzione di pagina
			if ($section > 1) $html .= "<hr>";
				
			// Tabella giornata di lezione
			$html .= '<div style="line-height:40px; font-size:20px; color:#034d91;">'.Lang::t('_TMSMEETING.EXCT_PARTICIPANTS', 'extraction').'</div>';

			$html .= '<table name="tbl_edition" cellspacing="1" cellpadding="1" style="border-top: 1px solid #034d91;">
							<tr style="line-height:200%;">
								<td width="20%">'.$this->transField('nome_edizione').':</td>
								<td width="30%">'.$day['edition_name'].'</td>
								<td width="20%">'.$this->transField('data_incontro').':</td>
								<td width="30%">'.Format::date($day['date_begin']).'</td>
							</tr>';
				
			$fcnt = 0;
			
			// Campi custom finanziamento
			foreach($day['fund_fields'] as $k => $v) {
				$fcnt++;
				$odd = ($fcnt % 2 != 0);

				if ($odd) $html .= '<tr style="line-height:200%;">';
					$html .= '			<td width="20%">'.$k.':</td><td width="30%">'.$v.'</td>';
				if (!$odd) $html .= '</tr>';	
			}
			
			// Chiusura seconda colonna se i campi custom sono dispari
			if (isset($odd) && $odd) 
					$html .= '			<td width="30%">&nbsp;</td></tr>';

			$html .= '</table>';
			$html .= '<br/>';
				
			// Tabella partecipanti
			if ($attendees) {
				
				$p = current($attendees)[0];
				$user_field = (isset($p['USERIDSF']) ? 'USERIDSF' : Lang::t('_USERNAME', 'standard'));
				
				$html .= '<table name="tbl_participant" width="100%">';
				$html .= '	<thead>';
				$html .= '		<tr nobr="true" style="background-color:#034d91;color:white;">';
				$html .= '			<th style="width:9%;">'.$user_field.'</th>';
				$html .= '			<th style="width:14%;">'.Lang::t('_FIRSTNAME', 'standard').'</th>';
				$html .= '			<th style="width:14%;">'.Lang::t('_LASTNAME', 'standard').'</th>';
				$html .= '			<th style="width:8%;">'.Lang::t('_LEVEL', 'standard').'</th>';
				$html .= '			<th style="width:24%;">'.Lang::t('_EMAIL', 'standard').'</th>';
				$html .= '			<th style="width:8%;">'.Lang::t('_TIME_ENTRY', 'standard').'</th>';
				$html .= '			<th style="width:8%;">'.Lang::t('_TIME_EXIT', 'standard').'</th>';
				$html .= '			<th style="width:5%;">'.Lang::t('_MINUTES', 'standard').'</th>';
				$html .= '		</tr>';
				$html .= '	</thead>';
				
				$html .= '	<tbody>';	
				$rwcnt = 0;
				
				foreach ($attendees as $presence) {
					foreach ($presence as $p) {

						$rwcnt++;
						$bg_color = ($rwcnt % 2 != 0) ? '#FFFFFF' : '#F0F4FF';
						$minutes = ($p['durationInSeconds']/60).($p['alert_day'] ? '*' : ''); 		// aggiungo asterisco se l'orario di collegamento non è del giorno pianificato
							
						$html .= '	<tr nobr="true" style="line-height:200%;">';
						$html .= '		<td style="background-color:'.$bg_color.'; width:9%;">'.(isset($p['USERIDSF']) ? $p['USERIDSF'] : $p['userid']).'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:14%;">'.$p['firstname'].'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:14%;">'.$p['lastname'].'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:8%;" >'.$p['level_t'].'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:24%;">'.$p['email'].'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:8%;">'.$p['joinDateTime'].'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:8%;">'.$p['leaveDateTime'].'</td>';
						$html .= '		<td style="background-color:'.$bg_color.'; width:5%;" align="right">'.$minutes.'</td>';
						$html .= '	</tr>';							
					}				
				}
				
				$html .= '	</tbody>';		
				$html .= '</table>';
				
			} else {
				$html .= '<span style="color:red; font-weight:bold;">'.Lang::t('_NO_DATA', 'standard').'</span>';
			}
		}
			
		$html .= "";
		
		//echo $html;
		//$html, $name, $img = false, $orientation = 'P', $download = true, $facs_simile = false, $for_saving = false
		
		// Out
		return $html;	
	}
	
	
	private function getAttendeeXlsx ($id_date, $id_org) {
		//>> Restituisce una stringa per la creazione del file dei partecipanti in formato xlsx
		
		require_once(_base_.'/lib/lib.xlsxwriter.php');
	
		// Recupero dati edizioni
		$edition_days = $this->model->getEditionDayInfo($id_date);
		
		// Istanzio classe scrittura excel
		$writer = new XLSXWriter();
		
		// Colonne foglio
		$col_options = array('font'=>'Arial','font-size'=>10, 'widths'=>[25,25,25,15,40,15,10,10,10], 'freeze_rows'=>10, 'suppress_row'=>true);
		$col_type = array('c1'=>'string', 'c2'=>'string', 'c3'=>'string', 'c4'=>'string', 'c5'=>'string', 'c6'=>'string', 'c7'=>'string', 'c8'=>'string', 'c9'=>'integer');
		
		
		// Stile intestazione
		$style_h = array('font-style'=>'bold', 'color'=>'#fff', 'fill'=>'#034d91', 'halign'=>'center', 'border'=>'left,right,top,bottom');
		
		
		foreach($edition_days as $day) {
			
			$date_begin = Format::date($day['date_begin']);
			$sheet_name = substr($date_begin, 0, 10);
			
			// Imposto colonne
			$writer->writeSheetHeader($sheet_name, $col_type, $col_options);
			
			
			// Intestazione foglio report
			$writer->writeSheetRow($sheet_name, array($this->transField('nome_edizione'), $day['edition_name']), ['font-style'=>'bold', 'fill'=>'#DDDDDD']);
			$writer->writeSheetRow($sheet_name, array($this->transField('data_incontro'), $date_begin));
							
			// Campi custom finanziamento
			foreach($day['fund_fields'] as $k => $v) {
				$writer->writeSheetRow($sheet_name, array($k, $v));
			}
			
			// Riga vuota
			$writer->writeSheetRow($sheet_name, ['']);

			// Recupero presenze partecipanti
			$attendees = $this->model->getTmsAttendeeList($id_date, $day['id'], $id_org);
			
			if ($attendees) {
				
				// Intestazione tabella presenze
				$p = current($attendees)[0];
				$user_field = (isset($p['USERIDSF']) ? 'USERIDSF' : Lang::t('_USERNAME', 'standard'));
				
				$header = 	array(
								$user_field,
								Lang::t('_FIRSTNAME', 'standard'),
								Lang::t('_LASTNAME', 'standard'),
								Lang::t('_LEVEL', 'standard'),
								Lang::t('_EMAIL', 'standard'),
								Lang::t('_TIME_ENTRY', 'standard'),
								Lang::t('_TIME_EXIT', 'standard'),
								Lang::t('_MINUTES', 'standard'),
							);
							
				$writer->writeSheetRow($sheet_name, $header, $style_h, ['string']);
				
				// Dati presenza
				foreach ($attendees as $presence) {
					foreach ($presence as $p) {
						
						$userid = isset($p['USERIDSF']) ? $p['USERIDSF'] : $p['userid'];					// identificativo utente
						$minutes = ($p['durationInSeconds']/60).($p['alert_day'] ? '*' : ''); 				// aggiungo asterisco se l'orario di collegamento non è del giorno pianificato
						
						$row = 	array (
									$userid,
									$p['firstname'],
									$p['lastname'],
									$p['level_t'],
									$p['email'],
									$p['joinDateTime'],
									$p['leaveDateTime'],
									$minutes
								);
								
						$writer->writeSheetRow($sheet_name, $row);							
					}				
				}

			} else {
				// Non ci sono dati di presenza
				$writer->writeSheetRow($sheet_name, [Lang::t('_NO_DATA', 'standard')], ['font-style'=>'bold', 'color'=>'#ff0000']);
			}
		}
		
		// Restituisco la stringa per la creazione del file
		return $writer->writeToString();	
	}
  
  
 	private function printAttendeeXls($id_date, $id_org) {
		//>> Stampa la lista dei partecipanti dei meeting di un'edizione in un file Excel (xlsx)
		
		require_once(_base_.'/lib/lib.download.php' );
		
		$filename = "users_virtual_edition.xlsx";
		
		// Recupero il contenuto del file
		$xlsx_string = $this->getAttendeeXlsx($id_date, $id_org);
		
		// Lancio il download del file
		sendStrAsFile($xlsx_string, $filename);
	}
	  
    
    private function printAttendeePdf($id_date, $id_org) {
		//>> Stampa la lista dei partecipanti dei meeting di un'edizione in pdf

		$filename = "users_virtual_edition.pdf";
		
		// Recupero codice html del report
		$html = $this->getAttendeeHtml($id_date, $id_org);
		
		// Download del pdf
		$this->getPdf($html, $filename);
	}
	
	
	public function saveParticipantPdf($id_date, $id_org) {
		//>> Salva il file PDF nella cartella files/appLms/meeting (non usata)
		
		// Recupero nome edizione
		$ed_name = $this->model->getEditionName($id_date);
		
		// Preparo dati di salvataggio
		$path = _base_."/files/appLms/meeting/";
		$filename = $ed_name."_".$id_org."UVE".$id_date.".pdf";
		
		// Recupero codice html del report
		$html = $this->getAttendeeHtml($id_date, $id_org);
		
		// Recupero il pdf in stringa
		$pdf_string = $this->getPdf($html, $filename, false, 'L', false, false, true, true);
		
		// Salvo il file (FALSE se fallisce la scrittura)
		$res = file_put_contents($path.'/'.$filename, $pdf_string);
		
		// Restituisco il nome del file
		return ($res ? $filename : false);
	}
	
		
    private function printAttendeeXls_likepdf($id_date, $id_org) {
		//>> Stampa la lista dei partecipanti dei meeting in xls con il layout del pdf (non più usata)
		
		require_once(_base_.'/lib/lib.download.php');
		
		$filename ="users_virtual_edition.xls";
		
		// Recupero codice html del report
		$html = $this->getAttendeeHtml($id_date, $id_org);
		
		// Invio per il download
		sendStrAsFile($html, $filename);
	}


    public function show() {
		echo "Non implementato";
    }
 
}
