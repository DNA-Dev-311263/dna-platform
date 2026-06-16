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


class CommcourseManager
{

	protected $acl_man;
	protected $date_man;
	protected $courseassn_man;
	protected $gap_man;

	public function __construct(){
		
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_lms_.'/lib/lib.gap.php');
		require_once(_lms_.'/lib/lib.courseassn.php');

		$this->lang = DoceboLanguage::CreateInstance('admin_date', 'lms');
		$this->acl_man = Docebo::user()->getAclManager();
		$this->date_man = New DateManager();
		$this->gap_man = New GapManager();
		$this->courseassn_man = New CourseassnManager();
		
	}


	public function __destruct(){
		
	}
	
	public function getCourseAvailable($id_org) {
		//>> Recupera i corsi confermati dell'azienda 

		$status = array(2);
		
		return $this->courseassn_man->getCourseCatalogOrg($id_org, $status);	
	}


	public function getCourseSummary($id_org, $type_transl) {
		//>> Recupera i corsi con le informazioni di repilogo (utenti assegnati, formati, ecc.)
		
		
		//Recupero i corsi
		$courses = $this->getCourseAvailable($id_org);		
		
		//Completo le informazioni
		$result = array();
		$rw = array();
		
		foreach ($courses as $id_course => $row)
		{	
			//Modifico la riga del corso		

			$course_type = $row['course_type'];
					
			$rw['code'] 			= $row['code'];
			$rw['name'] 			= $row['name'];
			$rw['idCourse'] 		= $row['idCourse'];
			$rw['course_virtual'] 	= $row['course_virtual'];
			$rw['course_type'] 		= $course_type;
			$rw['course_type_t'] 	= $type_transl[$course_type];		
			$rw['chk_cell'] 		= null;
			$rw['num_seat']			= null;
			$rw['num_assn'] 		= $this->courseassn_man->countAssnCourse($id_course, $id_org);
			$rw['num_edition'] 		= $this->_countEditionCourse($id_course , $course_type);
			$rw['num_subs'] 		= $this->_countSubsOpen($id_course , $course_type, $id_org);
			$rw['num_nosubs'] 		= $rw['num_assn'] - $rw['num_subs'];
				
			//Conto posti rimasti			
			if ($course_type == 'classroom')
				$rw['num_seat'] = $this->date_man->countSeatByCourse($id_course);
			
			
			//Aggiungo descrizione virtuale
			if ($rw['course_virtual'] == 1)
				$rw['course_type_t'] .=  ' '.strtolower($type_transl['virtual']);
			
			
			//Passo la riga all'array di output
			$result[] = $rw;
		}	

		//Out
		return $result;
	}
	
	
	public function getAssnForNewEdition($arr_id_course, $id_org, $show_location = false) {
		//>> Restituisce le assegnazioni degli utenti interessati alle nuove edizioni (utenti assegnatari senza iscrizioni valide)
		
		if (!is_array($arr_id_course)) return false;
		
		$result = array();
		$users = array();
		$locations = array();
		$class_list = '';

		foreach ($arr_id_course as $id_course) {
			
			//Recupero info corso
			$course = $this->_getCourseInfo($id_course);
			$course_type = $course['course_type'];
			
			//Recupero le edizioni disponibili
			$editions = $this->_getEditionCourse($id_course,  $course_type, true);
			
			if($editions) {
				
				//Recupero le sedi (se richiesto)
				if ($course_type == 'classroom' && $show_location) {
					
					foreach ($editions as $id_date) {
						//Unisco gli array delle sedi (stessa chiave presa una sola volta con operatore +)
						$locations = $locations + $this->date_man->getDateLocations($id_date); 
					}	

					$location_list	= implode("; ", $locations);
				}
				
				//Recupero gli utenti interessati
				$users = $this->courseassn_man->getAssnUsers($id_org, $id_course, false, true);
				
				
				//Se l'utente ha già un'iscrizione valida lo rimuovo		
				foreach ($users as $key => $user) {
					if (array_key_exists($user['id_edition'], $editions)) unset($users[$key]);
				}   
				
				
				//Preparo array di uscita
				if ($users) {
					
					$result[] = array (	'id_course' => $id_course,
										'course_name' => $course['name'], 
										'course_code' => $course['code'],
										'course_virtual' =>	$course['course_virtual'],
										'location' =>	$location_list,
										'users' => $users);
				}
			}	
		}
		
		return $result;	
	}
	
	
	public function getAssnActive($arr_id_course, $id_org, $date_from) {
		//>> Restituisce le informazioni relative alle nuove assegnazioni (aperte e successive a data indicata)
		//>> Esclude le assegnazioni provenienti da gap
		//>> dateFrom deve già essere nel formato yyyy-mm-dd
	
		if (!is_array($arr_id_course)) return false;
		
		$res = array();
		

		
		foreach ($arr_id_course as $id_course) {
		
			//Recupero le assegnazioni per lo specifico corso
			$whrExp = "assn.id_entry = ". $id_course ." AND org.idOrg = ". (int)$id_org
						." AND usr_u.valid = 1 AND assn.id_gap IS NULL AND assn.date_ins >= '". $date_from ."' AND assn.status = ". _ASSN_STATUS_ACTIVE;
		
		
			$assn = $this->courseassn_man->getAssn($whrExp);

			//Recupero le informazioni per l'array di ritorno			
			foreach ($assn as $row) {
				$res[$row['id_assn']] = array( 'course_name' 	=> $row['course_name'],
												'course_code' 	=> $row['course_code'],
												'userid' 		=> $row['user_userid'],
												'firstname' 	=> $row['user_firstname'],
												'lastname' 		=> $row['user_lastname'],
												'email' 		=> $row['user_email'],
												'id_manager'	=> $row['id_manager']);
			}
		
		}
					
		return $res;
	}
	
	
	private function _getCourseInfo($id_course) {
		//>> Restituisce le informazioni su uno specifico corso
		$query = "SELECT *
					FROM ".$GLOBALS['prefix_lms']."_course
					WHERE idCourse = ".$id_course;
			
		$res = sql_query($query);

		return sql_fetch_assoc($res);

	}
	
	
	private function _countEditionCourse($id_course,  $course_type, $coming = false) {
		//>> Conta le edizioni aperte sul corso passato in argomento
		//   Se coming è true, il conteggio riguarda solo quelle pianificate nel futuro.
		
		//Recupero le edizioni
		$editions = $this->_getEditionCourse($id_course,  $course_type, $coming);
		
		
		//Out
		return count($editions);		
	}
	
	
	private function _countSubsOpen($id_course, $course_type, $id_org) {
		//>> Restituisce il numero di iscrizioni, sulle edizioni aperte, del corso passato in argomento
		
		$count = 0;
		
		//Recupero le edizioni disponibili
		$editions = $this->_getEditionCourse($id_course,  $course_type, false);
		
		
		if ($editions) {
		
			//Recupero le assegnazioni per lo specifico corso
			$whrExp = "assn.id_entry = ". $id_course ." AND org.idOrg = ". (int)$id_org
						." AND assn.status = " . _ASSN_STATUS_ACTIVE . " AND id_edition IN (". implode(',', $editions). ")";
			
			$assn = $this->courseassn_man->getAssn($whrExp);
			
			//Conto le righe del recordset
			$count = count($assn);
		}
		
		//Out
		return $count;
	}
	
	
	private function _getEditionCourse($id_course,  $course_type, $coming = false) {
		//>> Restituisce le edizioni aperte (id) sul corso passato in argomento
		//   Se coming è true, il conteggio riguarda solo quelle pianificate nel futuro.
		
		$editions = array();
		$havingExp = ($coming === false ? "" : " HAVING MIN(dy.date_begin) > NOW()");
		
		if ($course_type == 'classroom') {
						
			$query = "SELECT dy.id_date As id_edition "
					." FROM %lms_course_date dt JOIN %lms_course_date_day dy ON dt.id_date = dy.id_date "
					." WHERE dy.deleted = 0 AND id_course = " . $id_course . " AND status = " ._DATE_STATUS_ACTIVE
					." GROUP BY dy.id_date "
					.  $havingExp;
					
		} elseif ($course_type == 'elearning') {
			
			$query = "SELECT id_edition "
					." FROM %lms_course_editions "
					." WHERE id_course = " . $id_course . " AND status = 2 " . $whrExp;
		
		}
		

		
		//Lancio la query		
		$result = sql_query($query);
		
		
		//Recupero i valori		
		while (list($id_ed) = sql_fetch_row($result)) {
			$editions[$id_ed] = $id_ed;
		}
		
		//Out
		return $editions;		
	}
	
	
	public function getGapUndefined($id_org, $date_from = false) {
		//>> Restituisce i gap con il requirement inferiore al numero di assegnazioni (gap non definito completamente)
		//>> Elimina eventuali gap su cataloghi senza test disponibili

		// Recupero i cataloghi vuoti
		$emptyCata = array_keys($this->gap_man->getEmptyCatalogue());
		
		// Recupero i gap
		$gap = $this->gap_man->getGapUndefined($id_org, $date_from);
		
		if($emptyCata && $gap) {
			// Recupero le chiavi dei gap di cataloghi validi
			$gap_cata	= array_column($gap, 'id_catalogue', 'id_gap');
			$gap_key	= array_diff($gap_cata, $emptyCata);
			
			// Recupero i gap validi
			$gap = array_intersect_key($gap, $gap_key);
		}
		
		return $gap;
	}
	
	
	public function getOrgInfoByUser($id_user, $lev_org_chart){
		//>> Restituisce le informazioni sul nodo organizzativo di appartenenza
		
		// Recupero il nodo organizzativo
		$retVal = $this->courseassn_man->getOrgInfoByUser($id_user, $lev_org_chart);
		
		// Out
		return $retVal;	
	}
	
	
	public function getOrgInfoByLevel($lev_org_chart = 1){
		//>> Restituisce le informazioni sui nodi organizzativi di un dato livello
		
		return $this->courseassn_man->getOrgInfoByLevel($lev_org_chart);
	}
	
	
	private function _checkDateString($dateString, $format = 'Y-m-d'){
		//>> Controlla la data stringa passata in argomento. 
		//	 Se il valore è nullo viene restituito stringa vuota, 
		//	 altrimenti viene restituita nel formato passato in argomento
		
		$res = "";
		
		if(!empty($dateString)) $res = date($format, strtotime($dateString));
		
		return $res;
	}
		
}

?>
