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

class UnsubscriptionLms extends Model
{
    protected $_t_order = false;
    protected $course_man = false;

    public function __construct()
    {
		require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_lms_.'/lib/lib.edition.php');
		//require_once(_lms_.'/admin/models/SubscriptionAlms.php'); //Non serve, il model è già accessibile
		
		$this->course_man = new Man_Course();
		
        parent::__construct();
    }

	/**
	 * Controlla se l'edizione / corso consente la disiscrizione ed è entro la data massima
	 * 0 non ammessa, -1 con moderazione ma fuori data limite, -2 libera fuori data limite
	 * 1 con moderazione ammessa, 2 libera ammessa.
	 */
	public function selfUnsubsAllowed($id_course, $id_date = false) {

		
		$date_man = new DateManager();
		
		//Recupero il corso
		$course = $this->course_man->getCourseInfo($id_course);
	
		//Controlli
		if ($course['course_type'] = 'classroom' && $id_date) {
			//classroom 
			$res = $date_man->checkSelfUnsubsDate($id_date);	
			$retVal = $res[$id_date];
			
		
			
		} else {
			//e-learning
			$res = $this->course_man->checkSelfUnsubsCourse($id_course);
			$retVal = $res[$id_course];
		}
		
		//Out
		return $retVal;
	}
	
	
	/**
	 * Controlla se l'utente può disiscriversi dal corso / edizione
	 * Restituisce 0 se la disiscrizione non è ammessa, 1 se è ammessa, 3 se è già stata richiesta
	 */
	private function checkUserUnsubscribe($id_user, $id_course, $id_edition = false, $id_date = false, $pending = false) {
		//>> Controlla se l'utente può disiscriversi dal corso / edizione
		//>> Restituisce 0 se la disiscrizione non è ammessa, 1 se è ammessa, 3 se è già stata richiesta
		
		// Controllo se la disiscrizione è già stata richiesta
		if(!$pending) $pending = array();
		
		foreach($pending as $obj) {
			//La proprietà res_id può valere idCourse, id_edition, id_date a seconda della proprietà r_type (course, edition, classroom)
			
			$res_id = ($id_date ? $id_date : ($id_edition ? $id_edition : $id_course));
			
			if ($obj->user_id = $id_user && $obj->idCourse == $id_course && $obj->res_id == $res_id) {
				//Trovata richiesta: esco
				return 3;
			}
		}
			
		// Recupero se il corso / edizione consente la disiscrizione
		$allowed = $this->selfUnsubsAllowed($id_course, $id_date);
		
		// Out
		return ($allowed > 0 ? 1 : 0);
	}


	/**
	 * 	restituisce un array di tre array con le informazioni del corso, dell'eventuale edizione e delle info aggiuntive
	 *	$res[] = array('course', 'edition', 'more_info');
	 */
	public function getValidSubs($id_user){

		$course_man = $this->course_man;
		$courseuser_man = new Man_CourseUser();
		$smodel = new SubscriptionAlms();
		$subs_info = array();
		$pending = array();
		$res = array();
		$subscr = false;
		$now = (new DateTime())->format('Y-m-d H:i:s');
		
		// Recupero le categorie
		$categories = $course_man->getCategoriesInfo();
		
		//Recupero le eventuali disiscrizioni in pending dell'utente
		$pending = $smodel->getUnsubscribeRequestsList(array(), array('user_q' => 'idst = '.$id_user));

		
		// Recupero le iscrizioni in stato aperto (array ordinato per data di iscrizione decrescente)
		$user_status = array(_CUS_SUBSCRIBED, _CUS_BEGIN);
		$courses = $courseuser_man->getUserCourses($id_user, false, CST_EFFECTIVE, $user_status, "date_inscr");


		// Ciclo sulle iscrizioni
		foreach ($courses as $id_course => $course) {
			
			$k = $id_course;
			$idCat = $course['idCategory'];
			$subscr = false;
			
			// Recupero la categoria
			if ( isset($categories[$idCat]) )
				$course['category'] = $categories[$idCat]['path'];
			
			// Selezione dati differenziata in base al tipo corso
			switch ($course['course_type']) {
				case 'classroom':
					$date_man = new DateManager();
					
					// Ottengo le date dell'utente
					$dates = $date_man->getUserDateForCourse($id_user, $id_course);
					
					// Recupero informazioni
					$dates = $date_man->getDatesInfo($dates, array(_DATE_STATUS_ACTIVE), false);
								
					// Prendo la prima data nel futuro
					foreach ($dates as $id_date => $date) {
					
						if(strcmp($date['date_begin'], $now) > 0) {
							$subscr = $date;
							break;
						}	
					}
						
					// Preparo l'array di uscita
					if ($subscr) {
						$id_date = $subscr['id_date'];
						
						$res[$k]['course'] = $course;
						$res[$k]['edition'] = $subscr;
						$res[$k]['more_info'] = array();
						
						//recupero il docente se è stato assegnato
						$res[$k]['more_info']['teacher'] = $date_man->getDateTeachers($id_date);
					
						//recupero le date dell'edizione con aula per ogni giorno
						$resTmp = array('days' => $date_man->getDateDayDateDetails($id_date));
						$res[$k]['more_info'] = array_merge($res[$k]['more_info'], $resTmp);	
						
						// Recupero se cancellabile
						$res[$k]['more_info']['allow_unsubscribe'] = $this->checkUserUnsubscribe($id_user, $id_course, false, $id_date, $pending);			
					}
					break;
					
				case 'elearning':
					if ($course['course_edition'] == 1) {
						//Elearning a edizioni
						$ed_man = new EditionManager();
						
						// Ottengo le edizioni dell'utente
						$editions = $ed_man->getUserEdition($id_user, CST_EFFECTIVE, $id_course);
					
						// Recupero informazioni
						$editions = $ed_man->getEditionsInfo($editions);
						
						// Prendo l'edizione solo se è senza data o non è già finita
						foreach ($editions as $id_edition => $edition) {
							if(strcmp($edition['date_end'], $now) > 0 || $edition['date_begin'] == '0000-00-00') {
								$subscr = $edition;
								break;
							}	
						}
							 
						 // Preparo l'array di uscita
						if ($subscr) {
							$id_edition = $subscr['id_edition'];
							
							$res[$k]['course'] = $course;
							$res[$k]['edition'] = $subscr;
	
							//recupero il docente se è stato assegnato
							$res[$k]['more_info']['teacher'] = $ed_man->getEditionTeachers($id_edition);
							
							// Recupero se cancellabile
							$res[$k]['more_info']['allow_unsubscribe'] = $this->checkUserUnsubscribe($id_user, $id_course, $id_edition, false, $pending);		
						}
			
					} else {
						//Elearning normale
						
						if(strcmp($course['date_end'], $now) > 0 || $course['date_begin'] == '0000-00-00') {
							// Preparo l'array di uscita se non ho superato la data limite
							
							$course['date_begin'] = $course['date_begin']." ".$course['hour_begin'].":00";
							
							$res[$k]['course'] = $course;
							$res[$k]['more_info']['teacher'] = $course_man->getCourseTeachers($id_course);
							$res[$k]['more_info']['allow_unsubscribe'] = $this->checkUserUnsubscribe($id_user, $id_course, false, false, $pending);		
						}
					}
					break;
			}	//switch
		}	//foreach
		
			
		//Out
		return $res;
	}
	
}
