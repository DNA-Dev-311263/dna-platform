<?php
/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 *
 * from docebo 4.0.5 CE 2008-2012 (c) docebo
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * By ABR
 */

defined('IN_FORMA') or exit('Direct access is forbidden.');

require_once _base_ . '/lib/lib.json.php';

$op = FormaLms\lib\Get::req('op', DOTY_ALPHANUM, '');

switch ($op) {
    case 'htmlpage_complete':
    
		require_once _lms_ . '/class.module/track.htmlpage.php';

        $json = new Services_JSON();
        $idUser = getLogUserId();
        $idOrg = FormaLms\lib\Get::req('idItem', DOTY_INT, 0);
        $progress = FormaLms\lib\Get::req('progress', DOTY_INT, 0);
        $output = ['success'=>false, 'message'=>''];
        
        // Controllo permessi
        if (!checkPerm('view', true, 'organization')) return $output;
        
        // Recupero sessione
        $session = \FormaLms\lib\Session\SessionManager::getInstance()->getSession();
        
        
        // Trovo id pagina
        $query = "SELECT idResource, idCourse FROM %lms_organization WHERE idOrg = " . $idOrg;
        list($idPage, $idCourse) = sql_fetch_row(sql_query($query));
        
        
        if ( $idPage ) {

			// Controllo validità progress
			$query = "SELECT videoComplete FROM %lms_htmlpage WHERE idPage = " . $idPage;
			list($videoComplete) = sql_fetch_row(sql_query($query));
			
			if ($progress >= $videoComplete) {
				
				// Trovo idTrack da aggiornare
				list($exist, $idTrack) = Track_Htmlpage::getIdTrack($idOrg, $idUser, $idPage, false);
				
				// Aggiorno il record di track (deve già esistere)
				if ( $exist && !$session->get('track_saved_'.$idTrack) ) {
					
					$ti = new Track_Htmlpage($idTrack);
					$ti->setDate(date('Y-m-d H:i:s'));
					$ti->status = 'completed';
					$res = $ti->update();

					// Salvo stato in sessione per evitare aggiornamenti ripetuti
					if ($res) {
						$session->set('track_saved_'.$idTrack, 'completed');
						$session->save();
					}				
					
					// Info di uscita
					$output = ['success'=>$res, 'message'=>($res ? 'completed' : 'error')];
					
				} else {
					$output['message'] = 'track not exists or alredy saved';
				}
				
			} else {
				$output['message'] = 'incorrect progress';
			}			
			
		} else {
			$output['message'] = 'resource not found';
		}
    
		// Out
        aout($json->encode($output));
        
     break;
}

?>



