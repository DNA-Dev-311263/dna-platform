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
|   BKO: integrazioni by ABR			    					            |
\ ======================================================================== */

use Formalms\lib\Get;

class CourseassnLmsController extends LmsController {

	public $name = 'courseassn';
	protected $_mvc_name = 'courseassn';


	public $ustatus = [];
	public $cstatus = [];

	public $levels = [];

	public $path_course = '';
	public $info = [];
	
	protected $_default_action = 'show';
	protected $base_link_courseassn;
	protected $json;
	protected $id_user;
	protected $model;
	
	
	public function init() {
		
		YuiLib::load('base,tabview');

		if(!isset($_SESSION['id_common_label']))
			$_SESSION['id_common_label'] = -1;

		require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.subscribe.php');
		require_once(_lms_.'/lib/lib.levels.php');
		require_once(_base_.'/lib/lib.json.php');
		
		$this->cstatus = [
			CST_PREPARATION => '_CST_PREPARATION',
			CST_AVAILABLE 	=> '_CST_AVAILABLE',
			CST_EFFECTIVE 	=> '_CST_CONFIRMED',
			CST_CONCLUDED 	=> '_CST_CONCLUDED',
			CST_CANCELLED 	=> '_CST_CANCELLED',
        ];

		$this->ustatus = [
			//_CUS_RESERVED 		=> '_T_USER_STATUS_RESERVED',
			_CUS_WAITING_LIST 	=> '_WAITING_USERS',
			_CUS_CONFIRMED 		=> '_T_USER_STATUS_CONFIRMED',

			_CUS_SUBSCRIBED 	=> '_T_USER_STATUS_SUBS',
			_CUS_BEGIN 			=> '_T_USER_STATUS_BEGIN',
			_CUS_END 			=> '_T_USER_STATUS_END'
        ];
        
        $this->id_user = (int)Docebo::user()->getId();
		$this->levels = CourseLevel::getLevels();
		$this->path_course = $GLOBALS['where_files_relative'].'/appLms/'.Get::sett('pathcourse').'/';
		$this->base_link_courseassn = 'lms/courseassn';
		
		$this->model = new CourseassnLms();

		$upd = new UpdatesLms();
		$this->info = $upd->courseUpdates();
		$this->json = new Services_JSON();
	}


	protected function _getBackLink(){
		//>> Restituisce il link di ritorno per la vista
		return getBackUi('index.php?r='.$this->base_link_courseassn.'/show', Lang::t('_BACK_TO_ASSN', 'courseassn'));
	}
	
	
    /**
     * Restituisce lo stato di accesso per il box corso da passare alla view
     */
    private function _getStateAccess($course) {
		
	    $state_access = 9;


		if ( $course['user_status']  == -1)
        //Utente non iscritto
			$state_access = count($course['editions_available']) > 0 ? 1 : 8;

		elseif (! $course['edition_valid'] )
        //Iscrizione su assegnazione non valida
			$state_access = count($course['editions_available']) > 0 ? 2 : 8;

		elseif ( $course['canEnter'] )
        //L'utente può entrare
			$state_access = 3;
			
		
		return $state_access;
	}
	
	
	/**
	 * Sistema e aggiunge le informazioni utili alla preesntazione del corso sulla pagina
	 * $course deve conterere almeno le informazioni native del corso
	 * @param array $course
	 */
	protected function _getCourseParsedData( $course ) {
		
			$model = $this->model;

            $course = CourseLms::getCourseParsedData($course);
            $course['courseBoxEnabled'] = CourseLms::isBoxEnabledForElearningAndClassroomInElearning($course);
		
            $course['courseBoxEnabled'] = CourseLms::isBoxEnabledForElearningAndClassroomInElearning($course);
            
            
			// Aggiungo elementi con valore di default
			$course['edition_valid'] = false;
			$course['editions_available'] = array();
			
			// Aggiungo elemento controllo accessibilità
            //$course['can_enter'] = Man_Course::canEnterCourse($course);

			// Imposto validità edizione per eventuale re-iscrizione
			if ($course['id_edition'])
				$course['edition_valid'] = $model->chkEditionIsOpen($course['id_edition'], $course['idCourse'], $course['course_type']);

			// Imposto eidizioni disponibili se necessario
			if (!$course['edition_valid'])
				$course['editions_available'] = $model->getCourseEditionAvailable($course['idCourse'], $course['course_type']);
				

			// Traduzione livello ad-hoc
			if ($course['level'] == -1)
				$course['level_text'] = Lang::t('_USER_STATUS_NOTSUBSCRIBED', 'standard');
				
				
			// Stato per accesso
			$course['state_access'] = $this->_getStateAccess($course);
            
            return $course;
	}
	
	protected function _getClassDisplayInfo($courses) {
		
		require_once(_lms_.'/models/ClassroomLms.php');
		
        $model = new ClassroomLms();
        $class_info = $model->getUserEditionsInfo($this->id_user, $courses);
        if (empty ($class_info)) return [];

        $dm =new DateManager();
        $status_arr =$dm->getStatusForDropdown();

        $output = [];
        /** @var int $id_course @var array $classrooms */
        foreach ($class_info as $id_course => $classrooms) {
            $output[$id_course] = [];
            foreach ($classrooms as $id_classroom => $classroom) {
                if (!isset($output[$id_course][$id_classroom])) {
                    $output[$id_course][$id_classroom] = new stdClass();
                    $output[$id_course][$id_classroom]->code = $classroom->code;
                    $output[$id_course][$id_classroom]->name = $classroom->name;
                    $output[$id_course][$id_classroom]->location = $classroom->location;
                    $output[$id_course][$id_classroom]->enrolled = $classroom->enrolled;
                    $output[$id_course][$id_classroom]->status = $status_arr[$classroom->status];
                    $output[$id_course][$id_classroom]->date_min = $classroom->date_min;
                    $output[$id_course][$id_classroom]->date_max = $classroom->date_max;

                    if (property_exists($classroom, 'date_info')) {
                        $output[$id_course][$id_classroom]->date_info =$classroom->date_info; // (array)
                    }
                    else {
                        $output[$id_course][$id_classroom]->date_info =false;
                    }
                }

                if (!property_exists($output[$id_course][$id_classroom], 'start_date')) $output[$id_course][$id_classroom]->start_date = $classroom->date_begin;
                if (!property_exists($output[$id_course][$id_classroom], 'end_date')) $output[$id_course][$id_classroom]->end_date = $classroom->date_end;
                if ($classroom->date_end > $output[$id_course][$id_classroom]->end_date) $output[$id_course][$id_classroom]->end_date = $classroom->date_end;
                if ($classroom->date_begin < $output[$id_course][$id_classroom]->start_date) $output[$id_course][$id_classroom]->start_date = $classroom->date_begin;
            }
        }


        return $output;
    }
    
    private function _concatDayInfo($date_begin, $date_end, $classroom, $date_format = "date"){
		//Prepara una stringa per la scrittura delle info dei giorni di lezione
		
		$val = array();
		$res = '';
		
		$val['date'] = Format::datetimeToString($date_begin, $date_format);
		$val['start'] = Format::datetimeToString($date_begin, 'time');
		$val['finish'] = Format::datetimeToString($date_end, 'time');

		$res .= $val['date'];	
		$res .= (isset($val['start']) ? ', '.$val['start'] : '');
		$res .= (isset($val['start']) && isset($val['finish']) ? ' - '.$val['finish'] : '');
		$res .= ($classroom && $classroom != ' - ' ? ', '.$classroom : '');
		
		return $res;
	}

	public function fieldsTask() {
		$level = Docebo::user()->getUserLevelId();
		if (Get::sett('request_mandatory_fields_compilation', 'off') === 'on' && $level !== ADMIN_GROUP_GODADMIN) {
			require_once(_adm_.'/lib/lib.field.php');
			$fl = new FieldList();
			$res = $fl->storeFieldsForUser($this->id_user);
		}
		Util::jump_to('index.php?r=elearning/show');
	}

	public function showTask() {
	
		$model = $this->model;
        
        // update behavior for on_usercourse_empty: applies only after login
		if(Get::sett('on_usercourse_empty') === 'on' && !$_SESSION['logged_in'])
		{
			$conditions_t = [
				'cu.iduser = :id_user'
            ];

			$params_t = [
				':id_user' => $this->id_user
            ];

			$cp_courses = $model->getUserCoursePathCourses($this->id_user);
			if (!empty($cp_courses))
			{
				$conditions_t[] = 'cu.idCourse NOT IN (' .implode(',', $cp_courses). ')';
			}
            
			$courselist_t = $model->findAll($conditions_t, $params_t);

			if(empty($courselist_t))
				Util::jump_to('index.php?r=lms/catalog/show&op=unregistercourse');
		}


        $block_list = [];
        $tb_label = (Get::sett('use_course_label', false) == 'off' ? false : true);
        if (!$tb_label) {
            $this->session->set('id_common_label', 0);
            $this->session->save();
        } else {
            $id_common_label = Get::req('id_common_label', DOTY_INT, -1);
            $this->session->set('id_common_label', $id_common_label);
            $block_list['labels'] = true;
        }

        if ($tb_label) {
            require_once _lms_ . '/admin/models/LabelAlms.php';
            $label_model = new LabelAlms();
            $user_label = $label_model->getLabelForUser(Docebo::user()->getId());
            $this->render('_tabs_block', ['block_list' => $block_list, 'use_label' => $tb_label, 'label' => $user_label, 'current_label' => $id_common_label]);
        } else {
            $this->render('_tabs_block', ['block_list' => $block_list, 'use_label' => $tb_label]);
        }
		
		
		// add feedback:
		// - feedback_type: [err|inf] display error feedback or info feedback
		// - feedback_code: translation code of message
		// - feedback_extra: extrainfo concat at end message
		$feedback_code=Get::req('feedback_code', DOTY_STRING, '');
		$feedback_type=Get::req('feedback_type', DOTY_STRING, '');
		$feedback_extra=Get::req('feedback_extra', DOTY_STRING, '');
		switch($feedback_type){
			case 'err':
				$msg = Lang::t($feedback_code, 'login'). ' ' .$feedback_extra;
				UIFeedback::error($msg);
				break;
			case 'inf':
				$msg = Lang::t($feedback_code, 'login'). ' ' .$feedback_extra;
				UIFeedback::info($msg);
				break;
		}	
	
	}

	public function newTask() {
		/*
		$model = $this->model;

		$filter_text = Get::req('filter_text', DOTY_STRING, '');
		$filter_year = Get::req('filter_year', DOTY_INT, 0);
        $filter_type = Get::req('filter_type', DOTY_STRING, '');
        $filter_cat = Get::req('filter_cat', DOTY_STRING, '');
        

		$conditions = [
			'cu.iduser = :id_user',
			'cu.status = :status'
        ];

		$params = [
			':id_user' => (int)Docebo::user()->getId(),
			':status' => _CUS_SUBSCRIBED
        ];

		if (!empty($filter_text)) {
			$conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
			$params[':keyword'] = $filter_text;
		}

		if (!empty($filter_year)) {
			$conditions[] = "(cu.date_inscr >= ':year-00-00 00:00:00' AND cu.date_inscr <= ':year-12-31 23:59:59')";
			$params[':year'] = $filter_year;
		}


       if (!empty($filter_cat)) {
            $conditions[] = '(c.idCategory in (' .$filter_cat. ') )';
        }         
        
        // filtro per tipo corso elearning
        if (empty($filter_type) || $filter_type === 'elearning' || $filter_type === 'all') {
            $courselist = $model->findAll($conditions, $params);
            $filter_type = empty($filter_type) ? 'elearning': $filter_type;            
        }        
        
		//check courses accessibility
		foreach ($courselist as $key => $courseListItem ){
            $courselist[$key]['can_enter'] = Man_Course::canEnterCourse($courselist[$key]);
            $courselist[$key]['course_type'] = 'elearning';
		}


  // CLASSROOM
        $modelClassroom = new ClassroomLms();

        $filter_text = Get::req('filter_text', DOTY_STRING, '');
        $filter_year = Get::req('filter_year', DOTY_INT, 0);

        $conditions = [
            'cu.iduser = :id_user',
            'cu.status = :status'
        ];

        $params = [
            ':id_user' => (int)Docebo::user()->getId(),
            ':status' => _CUS_SUBSCRIBED
        ];

        if (!empty($filter_text)) {
            $conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
            $params[':keyword'] = $filter_text;
        }

        
       if (!empty($filter_cat)) {
            $conditions[] = '(c.idCategory in (' .$filter_cat. ') )';
        }          
        
        if (!empty($filter_year)) {
            $clist = $modelClassroom->getUserCoursesByYear(Docebo::user()->getId(), $filter_year);
            if ($clist !== false) {
                $conditions[] = 'cu.idCourse IN (' .implode(',', $clist). ')';
            }
        }
        
        $cp_courses = $modelClassroom->getUserCoursePathCourses( Docebo::user()->getIdst() );
        if (!empty($cp_courses)) {
            $conditions[] = 'cu.idCourse NOT IN (' .implode(',', $cp_courses). ')';
        }

        if ($filter_type === 'classroom' || $filter_type === 'all') {
            $courselistClassroom = $modelClassroom->findAll($conditions, $params);
        }


        //check courses accessibility
        $keys = [];
        foreach ($courselistClassroom as $key => $courselistClassroomItem ){
            $courselistClassroom[$key]['can_enter'] = Man_Course::canEnterCourse($courselistClassroom[$key]);
            $keys[] = $key;
        }
        // fine classroom


		require_once(_lms_.'/lib/lib.middlearea.php');
		$ma = new Man_MiddleArea();
		$this->render('courselist', [
			'path_course' => $this->path_course,
			'courselist' => $courselist,
			'use_label' => $ma->currentCanAccessObj('tb_label'),
			'keyword' => $filter_text  ,
            'display_info' => $this->_getClassDisplayInfo($keys),
            'courselistClassroom' => $courselistClassroom ,
            'course_state' => "new_task" ,
            'filter_type' => $filter_type
        ]);
        
        */
	}
	
	
	/**
	 * Metodo chiamato nel caso si scelga le assegnazioni come home
	 */
	public function home() {
		
		if ($this->model->countAssnCurrentUser() > 0 ) {
			// Se ci sono assegnazioni procedo a visualizzare la pagina
			$this->showTask();
		} else {
			// Altrimenti salto alla pagina "I miei corsi"
			Util::jump_to('index.php?r=lms/elearning/show');
		}
		
	}

	public function allTask() {
	
        // COURSEASSN
        
        $model = $this->model;
        $id_user = $this->id_user;

		$filter_text = Get::req('filter_text', DOTY_STRING, '');
        $filter_type = '' .Get::req('filter_type', DOTY_STRING, '');
        $filter_cat = Get::req('filter_cat', DOTY_STRING, '');
        $filter_year = Get::req('filter_year', DOTY_STRING, 0);
        $filter_status = Get::req('filter_status', DOTY_STRING, '');
        
  
        $conditions = [
            'a.id_user = :id_user'
        ];

        $params = [
            ':id_user' => $id_user
        ];


		if (!empty($filter_text)) {
			$conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
			$params[':keyword'] = $filter_text;
		}

		if (!empty($filter_year)) {
            $str_cond_year = '';
			$conditions[] = "(a.date_ins >= ':year-01-01 00:00:00' AND a.date_ins <= ':year-12-31 23:59:59')";
			$params[':year'] = $filter_year;
		}

        if (!empty($filter_cat) && $filter_cat != '0') {
            $conditions[] = "(c.idCategory in (:filter_category) )";
            $params[':filter_category'] = $filter_cat;
        }                                                                     
        
        // ABR: status assign-course all open, without subscription, new course ecc.
        
        if ( $filter_status !== '' && $filter_status !== 'all') {
  
  			switch ($filter_status) {
				case -1:
					//no subscrition
					$conditions[] = '(a.id_edition IS Null)';
					break;
				default:
					//new, started ecc.
					$conditions[] = '(cu.status in (' .$filter_status. ') )';
			}
        } 
 
        // course type: elearning, all, classroom 
        if ($filter_type != 'all') {
           $conditions[] = "c.course_type = ':course_type'";
           $params[':course_type'] = $filter_type;
        }
        
        
        $courselist = $model->findAll($conditions, $params);  
        
        
        //Per ogni corso aggiungo informazioni
        foreach ($courselist as $k => $course_info) {
			 
            $courselist[$k] = $this->_getCourseParsedData($course_info);
        }         
        
        
		switch ($filter_type) {
		case 'elearning':
			$ft = Lang::t('_ELEARNING', 'catalogue');
			break;
		case 'classroom':
			$ft = Lang::t('_CLASSROOM_COURSE', 'cart');
			break;
		case 'all':
			$ft = Lang::t('_ALL_COURSES', 'standard');
			break;
		default:
			break;
        }
         
        // Manager Middle area
		require_once(_lms_.'/lib/lib.middlearea.php');
		$ma = new Man_MiddleArea();
		
		// Libreria della vista
		$js_tag = Util::get_js(Get::rel_path('lms') . '/views/courseassn/courseassn.js', true, false);
		

		$this->render('courselist', [
			'js_tag' => $js_tag,
			'path_course' => $this->path_course,
			'courselist' => $courselist,
			'use_label' => $ma->currentCanAccessObj('tb_label'),
			'keyword' => $filter_text,
			'ustatus' => $this->ustatus,
			'levels' => $this->levels,
            'display_info' => $this->_getClassDisplayInfo($keys),
            'stato_corso' => 'all_task',
            'filter_type' => $ft,
            'base_link_courseassn' => $this->base_link_courseassn
        ]);
	}
	

	
	/**
	 * Restituisce solo il box con i dati del corso aggiornati
	 * Chiamata via Ajax
	 */
	public function reloadCourseBox() {
		
		$id_course = FormaLms\lib\Get::req('id_course', DOTY_INT, 0);
		
		$conditions[] = 'c.idCourse = ":id" ';
		$params[':id'] = $id_course;
		
		$course_array = $this->model->findAll($conditions, $params);

		if ( $course_array ) {
			
			$course = reset($course_array);
			$course = $this->_getCourseParsedData($course);
			
			echo $this->render('single-box', ['course' => $course], true);
			
		} else {
			echo '<div class="alert alert-danger">Invalid course ID</div>';
		}
		
	}


	/**
	 * This implies the skill gap analysis :| well, a first implementation will be done based on
	 * required over acquired skill and proposing courses that will give, the required competences.
	 * If this implementation will require too much time i will wait for more information and pospone the implementation
	 */
	public function suggested() {

		$competence_needed = Docebo::user()->requiredCompetences();

		$model = $this->model;
		$courselist = $model->findAll([
			'cu.iduser = :id_user',
			'comp.id_competence IN (:competence_list)'
        ], [
			':id_user' => $this->id_user,
			':competence_list' => $competence_needed
        ], ['LEFT JOIN %lms_competence AS comp ON ( .... ) ']);

		$this->render('courselist', [
			'path_course' => $this->path_course,
			'courselist' => $courselist
        ]);
	}
	

	/*-----------------   Ajax   -----------------*/
	
	public function getTrEdition($info)
	{
		// Restituisce il codice html per la creazione delle righe della tabella informativa
		$html = "";
		$course_type = $info['course']['course_type'];
			
			$html .= "<tr>";
			$html .= "	<td>".Lang::t('_STATUS', 'standard')."</td>";
			$html .= "	<td>".Lang::t('_USER_STATUS_SUBS', 'standard')."</td>";
			$html .= "</tr>";
			
			if($course_type == "elearning"){
			
				$html .= "<tr>";
				$html .= "	<td>".Lang::t('_START', 'standard')."</td>";
				$html .= "	<td>".Format::datetimeToString($info['edition']['date_begin'], 'date', '')."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
				$html .= "	<td>".Lang::t('_END', 'standard')."</td>";
				$html .= "	<td>".Format::datetimeToString($info['edition']['date_end'], 'datetime', '')."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
				$html .= "	<td>".Lang::t('_LEVEL_6', 'levels')."</td>";
				$html .= "	<td>".$info['more_info_edition']['teacher']."</td>";
				$html .= "</tr>";
			
			}elseif($course_type == "classroom"){
				
				$html .= "<tr>";
				$html .= "	<td>".Lang::t('_DAYS', 'standard')."</td>";
				$html .= "	<td>".$info['edition']['num_day']."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
				$html .= "	<td>".Lang::t('_LEVEL_6', 'levels')."</td>";
				$html .= "	<td>".$info['more_info_edition']['teacher']."</td>";
				$html .= "</tr>";
				$html .= "	<td>".Lang::t('_CALENDAR', 'standard')."</td>";
				$html .= "	<td>";
				$html .= "		<ul class='info_dtdy_list'>";
				
				foreach ($info['more_info_edition']['days'] as $key => $value) {
					
					$html .= "		<li>"
										.$this->_concatDayInfo($value['date_begin'], $value['date_end'], $value['classroom']);
					$html .= "		</li>";
				
				}
				$html .= "		</ul>";
				$html .= "	</td>";
				$html .= "</tr>";
			}
		
		return $html;
		
	}
	
	
	public function courseEditionInfo()
	{
		//Argomenti passati alla chiamata
		$id_course = Get::req('id_course', DOTY_INT, 0);
		$id_edition = Get::req('id_edition', DOTY_INT, 0);
		$tab_page = array();
		
		//Istanzio il modello e recupero le informazioni
		$model = $this->model();
		
		$info = $model->getCourseEditionInfo($id_course, $id_edition);
		
		//Recupero tipo scheda
		if($id_edition > 0){
			$tab_page['code'] = "SUB";
			$tab_page['title'] = Lang::t('_SUBSCRIBE_COURSE', 'standard');
		}else{
			$tab_page['code'] = "CRS";
			$tab_page['title'] = Lang::t('_INFO', 'course');	
		}
		
		//Inizio tabella informativa
		$html .= "<div id='tpg_info' class = 'tinfo_container'>";
		$html .= "	<div class = 'tinfo_title'>".$tab_page['title']."</div>";
		$html .= "	<div class = 'tinfo_tool'><span class = 'tinfo_print' onclick = \"printElement('tpg_info','".$tab_page['title']."', 'tinfo_tool');\"> </span></div>";
		$html .= "	<table class = 'tinfo_table'>";
		
		//Riga titolo corso
		$html .= "		<tr>";
		$html .= "			<td>".Lang::t('_COURSE_NAME', 'standard')."</td>";
		$html .= "			<td>".$info['course']['code']."  ".$info['course']['name']."</td>";
		$html .= "		</tr>";
			
		
		//Se c'è l'iscrizione, recupero le righe informative dell'edizione
		if($tab_page['code'] == "SUB") $html .= $this->getTrEdition($info);
	
		//Riga tipo corso
		$html .= "		<tr>";	
		$html .= "			<td>".Lang::t('_COURSE_TYPE', 'course')."</td>";
		$html .= "			<td>".$info['course']['course_type']
								 .($info['course']['course_virtual'] ? " (".strtolower(Lang::t('_VIRTUAL', 'course')).")" : "");
		$html .= "			</td>";
		$html .= "		</tr>";
		
		//Riga categoria corso
		if($info['course']['cat_desc']){
			$html .= "		<tr>";	
			$html .= "			<td>".Lang::t('_CATEGORY', 'standard')."</td>";
			$html .= "			<td>".$info['course']['cat_desc']."</td>";
			$html .= "		</tr>";
		}
		
		//Riga etichetta
		if($info['course']['label_title']){
			$html .= "		<tr>";
			$html .= "			<td>".Lang::t('_LABEL', 'standard')."</td>";
			$html .= "			<td>".$info['course']['label_title']."</td>";
			$html .= "		</tr>";
		}
		
		//Riga descrizione corso
		$html .= "		<tr>";
		$html .= "			<td>".Lang::t('_DESCRIPTION', 'standard')."</td>";
		$html .= "			<td>".$info['course']['description']."</td>";
		$html .= "		</tr>";
		
		//Righe campi corso aggiuntivi (custom)
		foreach ($info['course_cfield'] as $key => $value) {
			if($value['cf_value']){
				$html .= "	<tr>";
				$html .= "		<td>".$value['cf_name']."</td>";
				$html .= "		<td>".(!$value['cf_combo_value'] ? $value['cf_value'] : $value['cf_combo_value'])."</td>";
				$html .= "	</tr>";
			}
		}
		
		//Riga edizioni pianificate per scheda corso
		if($tab_page['code'] == "CRS"){
			
			foreach ($info['course_editions'] as $key => $ed_info) {
				
				if($info['course']['course_type'] == 'classroom'){
					//info edizione aula
					$ed_tag .= "<a href='#' class = 'tinfo_dtdt_link' onclick = toggler('dateday-".$ed_info['id_date']."');>";
					$ed_tag .=		$ed_info['date_begin']." - ".$ed_info['date_end'];
					$ed_tag .= "</a>";
					$ed_tag .= "<div id = 'dateday-".$ed_info['id_date']."' class = 'tinfo_dtdy_container' style = 'display:none;'>";
	 
				
					foreach ($ed_info['days'] as $dy_info){
						//ciclo per scrittura giorni di aula
						$ed_tag .="<div>".$this->_concatDayInfo($dy_info['date_begin'], $dy_info['date_end'], $dy_info['location'].' - '.$dy_info['class_name'], 'day_name_short')."</div>";
					}	
							
					$ed_tag .= "</div> ";
					
				}else{
					//info edizione elearning
					$ed_tag .= "<div class = 'tinfo_eddt_container'>";
					$ed_tag .=		Format::datetimeToString($ed_info['date_begin'],'date')." - ";
					$ed_tag .=		Format::datetimeToString($ed_info['date_end'],'date');
					$ed_tag .= "</div>";
					
				}
							
			}
			
			if(empty($ed_tag)) $ed_tag = Lang::t('_NO_EDITIONS', 'catalogue');
			
			$html .= "	<tr>";
			$html .= "		<td>".Lang::t('_EDITIONS', 'standard')."</td>";
			$html .= "		<td>".$ed_tag."</td>";
			$html .= "	</tr>";
			
		}
		
		//chiusura tabella
		$html .= "	</table>";
		$html .= "</div>";
		
		//valori di restituzione per finestra di dialogo
		$res = array();
		$res['code'] = $info['course']['code'];
		$res['name'] = $info['course']['name'];
		$res['body'] = $html;
		$res['title'] = $info['course']['code'];
		$res['footer'] = "<a href='javascript:;' onclick='hidePanel();'><span class='close_dialog'>".Lang::t('_CLOSE', 'standard')."</span></a>";
		$res['success'] = true;

		echo $this->json->encode($res);
	}
	
	
	/**
	 * Restituisce il form modale per l'iscrizione alle edizioni
	 */
    public function chooseEdition()
    {
		require_once(_lms_.'/models/CatalogLms.php');
		$model =  new CatalogLms();
		
		
        $id_course = FormaLms\lib\Get::req('id_course', DOTY_INT, 0);
        $type_course = FormaLms\lib\Get::req('type_course', DOTY_STRING, 'elearning');
        $id_category = FormaLms\lib\Get::req('id_category', DOTY_INT, 0);
        $res = $model->courseSelectionInfo($id_course);
        
        //Mod. ABR
        $this->render('edition-modal', ['id_course' => $id_course, 'available_classrooms' => $res['available_classrooms'], 'teachers' => $res['teachers'],
            'course_name' => $res['course_name'], 'type_course' => $res['course_type'], 'id_category' => $id_category, 'course_category' => $res['course_category'], $res['course_virtual'] => $course_virtual ]);
    }
    

	
/*
 * ARRAY CORSO PARSED
 array(53) {
  ["idCourse"] => string(3) "282"
  ["course_type"] => string(9) "classroom"
  ["idCategory"] => string(2) "21"
  ["code"] => string(3) "RRR"
  ["name"] => string(7) "no name"
  ["description"] => string(76) "Corso di test per il funzionamento delle assegnazioni "
  ["box_description"] => string(53) "Corso di test per il funzionamento delle assegnazioni"
  ["difficult"] => string(6) "medium"
  ["course_status"] => string(1) "2"
  ["level_show_user"] => string(1) "0"
  ["course_edition"] => string(1) "0"
  ["max_num_subscribe"] => string(1) "0"
  ["create_date"] => string(19) "2025-04-15 12:51:45"
  ["direct_play"] => string(1) "0"
  ["img_othermaterial"] => string(0) ""
  ["course_demo"] => string(0) ""
  ["use_logo_in_courselist"] => string(1) "0"
  ["img_course"] => string(53) "../templates/standard/images/course/course_nologo.png"
  ["lang_code"] => string(7) "italian"
  ["course_virtual"] => string(1) "0"
  ["course_vote"] => string(1) "0"
  ["hour_end"] => string(2) "-1"
  ["date_begin"] => string(10) "0000-00-00"
  ["date_end"] => string(10) "0000-00-00"
  ["valid_time"] => string(1) "0"
  ["show_result"] => string(1) "0"
  ["userStatusOp"] => string(1) "8"
  ["auto_unsubscribe"] => string(1) "0"
  ["unsubscribe_date_limit"] => NULL
  ["id_edition"] => NULL
  ["date_inscr"] => NULL
  ["date_first_access"] => NULL
  ["date_complete"] => NULL
  ["waiting"] => bool(false)
  ["TRec"] => string(6) "0.0000"
  ["user_status"] => string(2) "-1"
  ["level"] => string(2) "-1"
  ["enrolled"] => int(0)
  ["numof_waiting"] => int(0)
  ["first_lo_type"] => bool(false)
  ["nameCategory"] => string(21) "1 - Office Automation"
  ["escaped_name"] => string(7) "no name"
  ["level_icon"] => string(2) "-1"
  ["level_text"] => NULL
  ["is_enrolled"] => bool(false)
  ["canEnter"] => bool(false)
  ["editions"] => array(3) {
    ["available_classrooms"] => array(1) {
      [55] => array(26) {
        ["id_date"] => string(2) "55"
        ["id_course"] => string(3) "282"
        ["code"] => string(5) "RRR-1"
        ["name"] => string(5) "RRR-1"
        ["description"] => string(11) "RRR-1 "
        ["max_par"] => string(1) "0"
        ["price"] => string(6) "0.0000"
        ["overbooking"] => bool(false)
        ["test_type"] => string(1) "0"
        ["status"] => string(1) "0"
        ["medium_time"] => string(1) "0"
        ["sub_start_date"] => string(19) "0000-00-00 00:00:00"
        ["sub_end_date"] => string(19) "0000-00-00 00:00:00"
        ["unsubscribe_date_limit"] => string(19) "0000-00-00 00:00:00"
        ["internal_note"] => string(0) ""
        ["calendarId"] => string(36) "e9e783c0-d6ef-4a59-957f-2180809400f2"
        ["date_begin"] => string(19) "2026-01-29 00:00:00"
        ["date_end"] => string(19) "2026-02-05 18:00:00"
        ["num_day"] => string(1) "4"
        ["user_subscribed"] => string(1) "0"
        ["usersids"] => NULL
        ["classroom"] => string(13) "Non assegnato"
        ["in_cart"] => bool(false)
        ["selling"] => string(1) "0"
        ["days"] => array(2) {
          [0] => array(3) {
            ["date_begin"] => string(19) "2026-02-02 09:00:00"
            ["date_end"] => string(19) "2026-02-02 18:00:00"
            ["classroom"] => string(8) " - "
          }
          [1] => array(3) {
            ["date_begin"] => string(19) "2026-02-05 09:00:00"
            ["date_end"] => string(19) "2026-02-05 18:00:00"
            ["classroom"] => string(8) " - "
          }
        }
        ["full"] => bool(false)
      }
    }
    ["teachers"] => NULL
    ["course_name"] => string(7) "no name"
  }
  ["course_full"] => bool(false)
  ["in_cart"] => bool(false)
  ["edition_exists"] => bool(true)
  ["userCanUnsubscribe"] => bool(false)
  ["show_options"] => bool(false)
  ["courseBoxEnabled"] => bool(true)
}
 */
	
}
