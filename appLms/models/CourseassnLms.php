<?php defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt   			|
| 	BKO: integrazioni by ABR									            |
\ ======================================================================== */

use Formalms\lib\Get;

class CourseassnLms extends Model {

	protected $_t_order = false;
	protected $courseassn_man;
	protected $date_man;
	protected $edition_man;
	protected $course_man;
	protected $idst_user;
	
	public function  __construct(){
		require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.courseassn.php');
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_lms_.'/lib/lib.edition.php');
		
		$this->course_man = new Man_Course();
		$this->courseassn_man = new CourseassnManager();
		$this->date_man = new DateManager();
		$this->edition_man = new EditionManager();
		$this->idst_user = Docebo::user()->getIdSt();
	}
	
	
	public function chkEditionIsOpen($id_edition, $id_course, $typeOfCourse = false){
		//Controlla se l'edizione di un corso è aperta

		If(!$typeOfCourse){
			//Se non è passato in argomento il tipo di corso lo cerco
			$cinfo = $this->course_man->getCourseInfo($id_course);
			$typeOfCourse = $cinfo['course_type'];
		}
		
		if($typeOfCourse == 'classroom'){
			//edizione classroom
			$id_no = $this->date_man->getNotConfirmetDateForCourse($id_course);	
		
		}elseif($typeOfCourse == 'elearning'){
			//edizione e-learning
			$id_no = $this->edition_man->getNotConfirmetEditionForCourse($id_course);
		}
		
		if(!in_array($id_edition, $id_no)){
				return true;
		}	
		
	}

	
	public function getCourseEditionAvailable($id_course, $typeOfCourse, $id_user = false){
		//Restituisce gli id delle edizioni valide (sia classroom sia elearning)
		//Se non ci sono edizioni disponibili, restituisce un array vuoto.
		
		$retVal = array();
		
		// Se l'utente non è passato in argomento, recupero quello di istanza
		if (!is_numeric($id_user)) $id_user = $this->id_user;
			
			
		
		if ($typeOfCourse == 'classroom'){
			
			$classrooms = $this->date_man->getCourseDate($id_course, false);
			

			if (count($classrooms) > 0){
				
				 // get data/edition for which the user is already enrolled
				$user_classroom = $this->date_man->getUserDateForCourse($id_user, $id_course);
				// get data/edition non valid: cancelled, finished, in preparation
				$classroom_not_confirmed = $this->date_man->getNotConfirmetDateForCourse($id_course);
				// recupero le date già occupate completamente
				$classroom_full = $this->date_man->getDateAlreadyFull($id_course);
								
				$date_id = array();
				
				// all the available data/edition for a course
				foreach ($classrooms as $classroom_info)
					$date_id[] = $classroom_info['id_date'];
            
				reset($classrooms);
				
				
				// remove the data in which the user is subscribed or the classroom not confirmed
				$control = array_diff($date_id, $user_classroom, $classroom_not_confirmed, $classroom_full);
				
				$retVal = $control;
				 
			}
			
		}elseif($typeOfCourse == 'elearning'){
			
			$editions = $this->edition_man->getEditionAvailableForCourse($id_user, $id_course);
			
			if (count($editions) > 0){
				$retVal = $editions;
			}
		}
		
		return $retVal;
		
	}


	/**
	 * This function return the correct order to use when you wish to diplay the a
	 * course list for the user.
	 * @param <array> $t_name the table name to use as a prefix for the field, if false is passed no prefix will e used
	 *							we need a prefix for the course user rows and a prefix for the course table
	 *							array('u', 'c')
	 * @return <string> the order to use in a ORDER BY clausole
	 */
	protected function _resolveOrder($t_name = array('', '')){
		// read order for the course from database
		if($this->_t_order == false){

			$t_order = Get::sett('tablist_mycourses', false);
			if($t_order != false){

				$arr_order_course = explode(',', $t_order);
				$arr_temp = array();
				foreach($arr_order_course as $key=>$value){

					switch ($value){
						case 'status': $arr_temp[] = ' ?u.status '; break;
						case 'code': $arr_temp[] = ' ?c.code '; break;
						case 'name': $arr_temp[] = ' ?c.name '; break;
					}
				}
				$t_order = implode(', ', $arr_temp);
			} else {

				$t_order = '?u.status, ?c.name';
			}
			// save a class copy of the resolved list
			$this->_t_order = $t_order;
		}
		foreach($t_name as $key=>$value){
			if($value != '') $t_name[$key] = $value.'.';
		}
		return str_replace(array('?u.', '?c.'), $t_name ,$this->_t_order);
	}


	public function compileWhere($conditions, $params){

		if(!is_array($conditions)) return "1";

		$where = array();
		$find = array_keys($params);
		foreach($conditions as $key=>$value){

			$where[] = str_replace($find, $params, $value);
		}
		return implode(" AND ", $where);
	}


	public function findAll($conditions, $params){

		//$conditions[] = ' c.course_type = ":course_type" ';
		//$params[':course_type'] = 'elearning';

		$db = DbConn::getInstance();
		
		//$id_user = Docebo::user()->getIdst();
		$whrExp = $this->compileWhere($conditions, $params);
		
        $query = "SELECT c.idCourse, c.course_type, c.idCategory, c.code, c.name, c.description, c.box_description, c.difficult, c.status AS course_status, c.level_show_user, c.course_edition, "
				."    c.max_num_subscribe, c.create_date, c.direct_play, c.img_othermaterial, "
				."    c.course_demo, c.use_logo_in_courselist, c.img_course, c.lang_code, c.course_virtual, "
				."	  c.course_vote, c.hour_end , c.date_begin, c.date_end, c.valid_time, c.show_result, c.userStatusOp, "
				."    c.auto_unsubscribe, c.unsubscribe_date_limit, a.id_edition, cu.date_inscr, "
				."    cu.date_first_access, cu.date_complete, cu.waiting, IFNULL(cu.status/cu.status, 0) AS TRec, "
				."	  CASE WHEN (a.id_edition IS NULL OR cu.status IS NULL) THEN -1 ELSE cu.status END AS user_status, "
				."	  CASE WHEN (a.id_edition IS NULL OR cu.level IS NULL) THEN -1 ELSE cu.level END AS level "
				." FROM %lms_course AS c LEFT JOIN %lms_assignment AS a ON (c.idCourse = a.id_entry) "
				."		LEFT JOIN %lms_courseuser AS cu ON (a.id_entry = cu.idCourse AND a.id_user = cu.idUser) "
				." WHERE  a.status = "._ASSN_STATUS_ACTIVE ." AND a.type_entry = 'course' AND c.subscribe_method = 9 AND c.status = 2 AND " .$whrExp
				." ORDER BY TRec ASC, ".$this->_resolveOrder(array('cu', 'c'));
				
						
        /* ABR DEBUG ///-----------------------------
         
       
         Util::fdebug($miaVariabile);
       
         Il codice seguente può essere evitato usando la funzione introdotta nella classe Util
        ----------------------------------------------
		require_once(_lms_.'/lib/lib.courseassn.php');
		$dbgMan = new CourseassnManager;
		$dbgMan->fdebug($query);
		
		//debug variabile in stringa
		ob_start();
		var_dump($variabile);
		$r = ob_get_clean();
		$dbgMan->fdebug($r);
		
		//DEBUG MSG DEV
		if (!ini_get('display_errors')) {
			ini_set('display_errors', '1');
		}
		//---------------------------------------- */

		$rs = $db->query($query);

		$result = array();
		$courses = array();
		while($data = $db->fetch_assoc($rs)){

			$data['enrolled'] = 0;
			$data['numof_waiting'] = 0;
			$data['first_lo_type'] = FALSE;


            //** name category
            $data['nameCategory'] = $this->getCategory($data['idCategory']);

            $courses[] = $data['idCourse'];
			$result[$data['idCourse']] = $data;
		}

		if (!empty($courses)){
			// find subscriptions
			$re_enrolled = $db->query(
				"SELECT c.idCourse, COUNT(*) as numof_associated, SUM(waiting) as numof_waiting"
				." FROM %lms_course AS c "
				." JOIN %lms_courseuser AS cu ON (c.idCourse = cu.idCourse) "
				." WHERE c.idCourse IN (".implode(',', $courses).") "
				." GROUP BY c.idCourse"
			);
			while($data = $db->fetch_assoc($re_enrolled)){

				$result[$data['idCourse']]['enrolled'] = $data['numof_associated'] - $data['numof_waiting'];
				$result[$data['idCourse']]['numof_waiting'] = $data['numof_waiting'];
			}


            #3562 Grifo multimedia - LR
            $query_lo = "select org.idOrg, org.idCourse, org.objectType from (SELECT o.idOrg, o.idCourse, o.objectType 
                          FROM %lms_organization AS o WHERE o.objectType != '' AND o.idCourse IN (".implode(',', $courses).") ORDER BY o.path) as org 
                          GROUP BY org.idCourse ";



			// find first LO type
			$re_firstlo = $db->query($query_lo);
			while($data = $db->fetch_assoc($re_firstlo)){
				$result[$data['idCourse']]['first_lo_type'] = $data['objectType'];
			}
		}

		return $result;
	}


	public function getFilterYears($id_user){
		$output = array(0 => Lang::t("_ALL_YEARS", 'course'));
		$db = DbConn::getInstance();

        $query = "SELECT DISTINCT YEAR(cu.date_inscr) AS inscr_year "
            ." FROM %lms_courseuser AS cu "
            ." WHERE cu.idUser = ".(int)$id_user
            ." ORDER BY inscr_year ASC";


		$res = $db->query($query);
		if ($res && $db->num_rows($res) > 0){
			while (list($inscr_year) = $db->fetch_row($res)){
                if ($inscr_year== 0){
                    $output['no-data'] = Lang::t('_NO_COURSE_DATA', 'course');
                } else {    
				    $output[$inscr_year] = $inscr_year;
                }    
			}
		}
		return $output;
	}


    //** Calculates the course states **
    public function getFilterStatusCourse($id_user){
		
     
        $output['all'] = Lang::t('_ASSIGNMENTS_OPEN', 'courseassn'); 
        
     
        $db = DbConn::getInstance();


        $query = "SELECT DISTINCT IFNULL(cu.status, -1) AS status_course "
            ." FROM %lms_assignment AS a "
            ." 	LEFT JOIN %lms_courseuser AS cu ON (a.id_entry = cu.idCourse AND a.id_user = cu.idUser) "
            ." WHERE a.type_entry = 'course' AND a.status = 1 AND a.id_user = ".(int)$id_user."  "
            ." ORDER BY 1";

            
        $res = $db->query($query);
        if ($res && $db->num_rows($res) > 0){
            while (list($status_course) = $db->fetch_row($res)){
                if($status_course==-1) $str_status_course =  Lang::t('_USER_STATUS_NOTSUBSCRIBED', 'standard');
                if($status_course==0) $str_status_course =  Lang::t('_NEW', 'course');
                if($status_course==1) $str_status_course =  Lang::t('_USER_STATUS_BEGIN', 'course');
                if($status_course==2) $str_status_course =  Lang::t('_COMPLETED', 'course');;
                
                if($status_course>=-1) $output[$status_course] = $str_status_course;
            }
        }
        return $output;
    }    
    

    // LR: list category of subscription
    public function getListCategory($idUser, $completePath = true){
        $db = DbConn::getInstance();
        
        $query = "SELECT idCategory,path from %lms_category where idcategory 
							IN (
								SELECT distinct idCategory 
								FROM %lms_course as c JOIN %lms_assignment as a ON c.idCourse = a.id_entry
								WHERE a.type_entry = 'course' AND a.id_user=".$idUser." 
								)";

        $res = $db->query($query);
        if ($res && $db->num_rows($res) > 0){
            $output[0] = Lang::t('_ALL_CATEGORIES', 'standard');
            while (list($idCategory, $path) = $db->fetch_row($res)){
                if($completePath){
                    $category = str_replace('/root/','',$path);
                }
                else {
                    $category = explode('/',$path);
                }
                $output[$idCategory] = $category[count($category)-1];
            }
        } else {
            $output[0] = Lang::t('_NO_CATEGORY', 'standard');
        }
        return $output;


    }


    private function getCategory($idCat){
		
        $db = DbConn::getInstance();
        $query = "select path from %lms_category where idCategory=".$idCat;
        $res = $db->query($query);
        $path = "";
        
        if ($res && $db->num_rows($res) > 0){
            list($path) = $db->fetch_row($res);
        }
        
        return $path;
    }
    
    
    /**
	 * Conta le assegnazioni aperte sdell'utente corrente
	 */
    public function countAssnCurrentUser()
    {
		return $this->courseassn_man->countAssnUser($this->idst_user);
		
	}
    
    
	public function getUserOpenEditionInfo($id_user){
		//>> Restituisce informazioni su tutte le edizioni alle quali l'utente è assegnato e iscritto 
		//>> Non utilizzata
		
		$res = array();
		$assn_man = $this->courseassn_man;
		
		// Recupero tutte le assegnazioni aperte con iscrizione
		$whrExp = "id_edition IS NOT NULL AND assn.status = "._ASSN_STATUS_ACTIVE." AND crs.status = ".CST_EFFECTIVE." AND id_user = ".(int)$id_user;
		
		$user_assn = $this->courseassn_man->getAssnId($whrExp);
		
		
		// Recupero le edizioni trascorse o non valide dell'utente
		$status_invalid = array(_DATE_STATUS_CANCELLED, _DATE_STATUS_FINISHED, _DATE_STATUS_PREPARATION);
		$u_past_edition['classroom'] = $assn_man->getUserPastEdition($id_user, 'classroom', $status_invalid);
		
		$status_invalid = array(CST_PREPARATION, CST_CONCLUDED, CST_CANCELLED);
		$u_past_edition['elearning'] = $assn_man->getUserPastEdition($id_user, 'elearning', $status_invalid);
		
		
		// Preparo array di ritorno
		foreach($user_assn as $id_assn => $row){
			
			// Salto edizione chiusa o del passato
			$type = $row['course_type'];
			
			if (!empty($u_past_edition[$type])){
				if (array_key_exists ($row['id_edition'], $u_past_edition[$type])) continue;
			}
		
			// Recupero edizione/corso per assegnazione
			$res[$id_assn] = $this->getCourseEditionInfo($row['id_entry'], $row['id_edition']);
		}
		
		// Output
		return $res;
	
	}
    

	public function getCourseEditionInfo($id_course, $id_edition = false){
		// ABR: restituisce tre array con le informazioni del corso e dell'eventuale edizione passata in argomento e delle info aggiuntive
		
		$resTmp = array();
		$res = array('course', 'course_cfield', 'edition', 'more_info_edition', 'course_editions');
		
		
		//recupero le informazioni del corso
		$res['course'] = $this->course_man->getCourseMoreInfo($id_course);
			
		//recupero le informazioni dei campi custom
		$res['course_cfield'] =  $this->course_man->getCourseCustomFields($id_course, true);
		
		//metto il tipo corso in una variabile di appoggio
		$typeOfCourse = $res['course']['course_type'];
		
		if($id_edition > 0){
					
			switch($typeOfCourse){
				case 'classroom':
				
					$ed_arr[0] = $id_edition;
					
					//recupero le info dell'edizione
					$res['edition'] = $this->date_man->getCourseEdition($id_course, false, false, 'date_begin', false, $ed_arr)[0];
								
					//recupero il docente se è stato assegnato
					$res['more_info_edition']['teacher'] = $this->date_man->getDateTeachers($id_edition);
					
					//recupero il nome delle aule dell'edizione (separate da ",")
					//$resTmp = array('classroom' => $this->date_man->getDateClassrooms($id_edition));
					//$res['more_info_edition'] = array_merge($res['more_info_edition'], $resTmp);
					
					//recupero le date dell'edizione con aula per ogni giorno
					$resTmp = array('days' => $this->date_man->getDateDayDateDetails($id_edition));
					$res['more_info_edition'] = array_merge($res['more_info_edition'], $resTmp);
					
				break;
				
				case 'elearning':
					
					//recupero le info dell'edizione
					$res['edition'] = $this->edition_man->getEditionInfo($id_edition);
	
					//recupero il docente se è stato assegnato
					$res['more_info_edition']['teacher']  = $this->edition_man->getEditionTeachers($id_edition);
					
				break;
			}
			
		}else{
			
			//se non c'è l'edizione in argomento, recupero tutte le edizioni disponibili per l'utente e le date
			switch($typeOfCourse){
				case 'classroom':
					
					//recupero le info sull'edizione (date)
					$id_dates = $this->getCourseEditionAvailable($id_course, $typeOfCourse);					
					$res['course_editions'] = $this->date_man->getCourseEdition($id_course, false, false, false, false, $id_dates);
					
					//recupero i giorni dell'edizione
					$days = $this->date_man->getDateDayForDates($id_dates);
					
					//formo un unico array di output
					foreach($res['course_editions'] as $key => $val){
						$res['course_editions'][$key]['days'] = $days[$val['id_date']]; 
					}
				

				break;
				
				case 'elearning':
					//recupero le info sull'edizione
					$id_editions = $this->getCourseEditionAvailable($id_course, $typeOfCourse);
					$res['course_editions'] = $this->edition_man->getEditionsInfo($id_editions);
				break;
			}	
			
		}
		
		//out
		return $res;
		
	}
	
	
	
}
