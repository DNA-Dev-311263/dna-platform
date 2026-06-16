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
 * Mod. ABR
 */
defined('IN_FORMA') or exit('Direct access is forbidden');

use FormaLms\lib\Get; 		//ABR

class ClassroomAlms extends Model
{
    protected $db;
    protected $acl_man;
    public $classroom_man;
    public $course_man;
    public $courseassn_man;	//ABR
    public $fund_man; 		//ABR

    protected $id_course;
    protected $id_date;
    protected $course_info; //ABR

    public function __construct($id_course = 0, $id_date = 0)
    {
        parent::__construct();
        require_once _lms_ . '/lib/lib.date.php';
        require_once _lms_ . '/lib/lib.course.php';
 		require_once(_lms_.'/lib/lib.courseassn.php');	//ABR
		require_once(_lms_.'/lib/lib.fund.php');		//ABR

        $this->id_course = $id_course;
        $this->id_date = $id_date;
        $this->db = DbConn::getInstance();
        $this->classroom_man = new DateManager();
        $this->course_man = new Man_Course();
        $this->acl_man = &Docebo::user()->getAclManager();
		$this->courseassn_man = new CourseassnManager();  	//ABR: manager corsi assegnazioni
		$this->fund_man = new FundManager('date');			//ABR: manager fondi finanziamento
		
		$this->course_info = $this->getCourseInfo($id_course); //ABR: recupero info corso
		
        parent::__construct();
    }

    public function getPerm()
    {
        return ['view' => 'standard/view.png'];
    }

    public function getClassroomsNumber($filter = false)
    {
        $categories = false;
        $filter_text = false;
        $filter_waiting = false;
        if ($filter) {
            if (isset($filter['id_category'])) {
                if (isset($filter['descendants'])) {
                    $categories = $this->getCategoryDescendants($filter['id_category']);
                } else {
                    $categories = $filter['id_category'];
                }
            }
            if (isset($filter['text'])) {
                $filter_text = $filter['text'];
            }

            if (isset($filter['waiting']) && $filter['waiting'] == 1) {
                $filter_waiting = true;
            }
        }

        return $this->course_man->getClassroomsNumber($categories, $filter_text, $filter_waiting);
    }

    public function loadCourse($start_index, $results, $sort, $dir, $filter = false)
    {
        $categories = false;
        $filter_text = false;
        $filter_waiting = false;
        if ($filter) {
            if (isset($filter['id_category'])) {
                if (isset($filter['descendants']) && $filter['descendants'] != 0) {
                    $categories = $this->getCategoryDescendants($filter['id_category']);
                } else {
                    $categories = $filter['id_category'];
                }
            }
            if (isset($filter['text']) && $filter['text'] !== '') {
                $filter_text = $filter['text'];
            }

            if (isset($filter['waiting']) && $filter['waiting'] == 1) {
                $filter_waiting = true;
            }
        }

        return $this->course_man->getClassrooms($start_index, $results, $sort, $dir, $categories, $filter_text, $filter_waiting);
    }

    public function getCategoryDescendants($id_category)
    {
        $output = [];

        if ($id_category != 0) {
            $query = 'SELECT iLeft, iRight FROM %lms_category WHERE idCategory=' . (int)$id_category;
            $res = $this->db->query($query);
            list($left, $right) = $this->db->fetch_row($res);

            $query = 'SELECT idCategory FROM %lms_category WHERE iLeft>=' . $left . ' AND iRight<=' . $right;
            $res = $this->db->query($query);
            while (list($id_cat) = $this->db->fetch_row($res)) {
                $output[] = $id_cat;
            }
        } else {
            $output[] = 0;

            $query = 'SELECT idCategory FROM %lms_category';
            $res = $this->db->query($query);
            while (list($id_cat) = $this->db->fetch_row($res)) {
                $output[] = $id_cat;
            }
        }

        return $output;
    }

    public function getIdCourse()
    {
        return $this->id_course;
    }

    public function getIdDate()
    {
        return $this->id_date;
    }

    public function getCourseEditionNumber()
    {
        return $this->classroom_man->getDateNumberNoLimit($this->id_course);
    }

    public function loadCourseEdition($start_index, $results, $sort, $dir)
    {
        return $this->classroom_man->getCourseEdition($this->id_course, $start_index, $results, $sort, $dir);
    }
    
	public function getCourseMaxNumSubscribe()
	{
		//ABR: restituisce il numero massimo di iscritti
		$result = $this->course_man->getCourseInfo($this->id_course);
		return $result['max_num_subscribe'];
	}

    public function getCourseInfo()
    {
        return $this->course_man->getCourseInfo($this->id_course);
    }

    public function getStatusForDropdown()
    {
        return $this->classroom_man->getStatusForDropdown();
    }

    public function getTestTypeForDropdown()
    {
        return $this->classroom_man->getTestTypeForDropdown();
    }

    public function getClassroomForDropdown($location_virtual = false)
    {
        return $this->classroom_man->getClassroomForDropdown($location_virtual); //ABR
    }

    public function getDateInfoFromPost()
    {
        $res = [];

        $res['customFields'] = [];
        $res['code'] = FormaLms\lib\Get::req('code', DOTY_MIXED, '');
        $res['name'] = FormaLms\lib\Get::req('name', DOTY_MIXED, '');
        $res['max_par'] = FormaLms\lib\Get::req('max_par', DOTY_INT, 0);
        $res['price'] = FormaLms\lib\Get::req('price', DOTY_MIXED, '');
        $res['overbooking'] = FormaLms\lib\Get::req('overbooking', DOTY_INT, 0);
        $res['test'] = FormaLms\lib\Get::req('test', DOTY_INT, 0);
        $res['status'] = FormaLms\lib\Get::req('status', DOTY_INT, 0);
        //$res['date_selected'] = FormaLms\lib\Get::req('date_selected', DOTY_MIXED, ''); //ABR non serve più
        $res['mediumTime'] = FormaLms\lib\Get::req('mediumTime', DOTY_INT, 0);
        $res['description'] = FormaLms\lib\Get::req('description', DOTY_MIXED, '');
        $res['sub_start_date'] = FormaLms\lib\Get::req('sub_start_date', DOTY_MIXED, '');
        $res['sub_end_date'] = FormaLms\lib\Get::req('sub_end_date', DOTY_MIXED, '');
        $res['unsubscribe_date_limit'] = FormaLms\lib\Get::req('unsubscribe_date_limit', DOTY_MIXED, '');
        $res['customFields'] = array_replace($res['customFields'], FormaLms\lib\Get::req('textfield', DOTY_MIXED, []));
        $res['customFields'] = array_replace($res['customFields'], FormaLms\lib\Get::req('dropdown', DOTY_MIXED, []));
        
		$res['internal_note'] = Get::req('internal_note', DOTY_MIXED, ''); //ABR
		$res['id_fund'] = Get::req('id_fund', DOTY_INT, 0);
		$res['fund_info'] = Get::req('fund_info', DOTY_MIXED, []);
	
		
        $array_day = [];

        if ($res['date_selected'] !== '') {
            $array_day = explode(',', $res['date_selected']);
        }

        $res['array_day'] = $array_day;

        return $res;
    }

    public function getDayTable($array_day, $id_date = 0)
    {
        require_once _base_ . '/lib/lib.table.php';

        //$tb = new Table(0, Lang::t('_DETAILS', 'course'), Lang::t('_DETAILS', 'course'));
        $tb = new Table(0, Lang::t('_DAY_DETAILS', 'classroom'), Lang::t('_DAY_DETAILS', 'classroom')); //ABR
        

        $cont_h = [Lang::t('_DAY', 'course'),
            Lang::t('_HOUR_BEGIN', 'course'),
            Lang::t('_PAUSE_BEGIN', 'course'),
            Lang::t('_PAUSE_END', 'course'),
            Lang::t('_HOUR_END', 'course'),
            Lang::t('_CLASSROOM', 'course'),];

        $type_h = ['align_center', 'align_center', 'align_center', 'align_center'];

        $classroom_array = $this->classroom_man->getClassroomForDropdown( $this->course_info['course_virtual'] ); //ABR
        

        $tb->setColsStyle($type_h);
        $tb->addHead($cont_h);

        $days = [];
        if ((int)$id_date > 0) {
            $days = $this->classroom_man->getDateDayForControl($id_date);
        }

        $arrayLenght = count($array_day);
        for ($i = 0; $i < $arrayLenght; ++$i) {
            if (isset($days[$array_day[$i]])) {
                $b_hours = $days[$array_day[$i]]['b_hours'];
                $b_minutes = $days[$array_day[$i]]['b_minutes'];
                $pb_hours = $days[$array_day[$i]]['pb_hours'];
                $pb_minutes = $days[$array_day[$i]]['pb_minutes'];
                $pe_hours = $days[$array_day[$i]]['pe_hours'];
                $pe_minutes = $days[$array_day[$i]]['pe_minutes'];
                $e_hours = $days[$array_day[$i]]['e_hours'];
                $e_minutes = $days[$array_day[$i]]['e_minutes'];
                $classroom = $days[$array_day[$i]]['classroom'];
            } else {
                $b_hours = false;
                $b_minutes = false;
                $pb_hours = false;
                $pb_minutes = false;
                $pe_hours = false;
                $pe_minutes = false;
                $e_hours = false;
                $e_minutes = false;
                $classroom = 0;
            }

            $classroom_array_checked = [];
            $occupied = $this->getOccupiedClassrooms($array_day[$i]);
            foreach ($classroom_array as $key => $value) {
                $classroom_array_checked[$key] = (in_array($key, $occupied) && $key != 0 ? '* ' : '') . $value;
            }
            
 			//ABR:  Recupero le descrizioni delle classroom con indicazione dell'occupazione
			//$classroom_array_checked = $this->getClassroomItems($array_day[$i], $classroom, $classroom_array);

            $tb->addBody([Format::date($array_day[$i], 'date'),
                Form::getInputDropdown('', 'b_hours_' . $i, 'b_hours_' . $i, $this->classroom_man->getHours(), $b_hours, false) . ' : ' . Form::getInputDropdown('', 'b_minutes_' . $i, 'b_minutes_' . $i, $this->classroom_man->getMinutes(), $b_minutes, false),
                Form::getInputDropdown('', 'pb_hours_' . $i, 'pb_hours_' . $i, $this->classroom_man->getHours(), $pb_hours, false) . ' : ' . Form::getInputDropdown('', 'pb_minutes_' . $i, 'pb_minutes_' . $i, $this->classroom_man->getMinutes(), $pb_minutes, false),
                Form::getInputDropdown('', 'pe_hours_' . $i, 'pe_hours_' . $i, $this->classroom_man->getHours(), $pe_hours, false) . ' : ' . Form::getInputDropdown('', 'pe_minutes_' . $i, 'pe_minutes_' . $i, $this->classroom_man->getMinutes(), $pe_minutes, false),
                Form::getInputDropdown('', 'e_hours_' . $i, 'e_hours_' . $i, $this->classroom_man->getHours(), $e_hours, false) . ' : ' . Form::getInputDropdown('', 'e_minutes_' . $i, 'e_minutes_' . $i, $this->classroom_man->getMinutes(), $e_minutes, false),
                Form::getInputDropdown('', 'classroom_' . $i, 'classroom_' . $i, $classroom_array_checked, $classroom, false),
                Form::getInputDropdown('sel-classroom', 'classroom_'.$i, 'classroom_'.$i, $classroom_array_checked, $classroom, "data-day='".$array_day[$i]."' data-regclass='".$classroom."'") //ABR
            ]);
        }
        if (count($array_day) > 1) {
            $tb->addBody([
                Lang::t('_SET', 'course'),
                Form::getInputDropdown('', 'b_hours', 'b_hours', $this->classroom_man->getHours(), '00', false) . ' : ' . Form::getInputDropdown('', 'b_minutes', 'b_minutes', $this->classroom_man->getMinutes(), '00', false),
                Form::getInputDropdown('', 'pb_hours', 'pb_hours', $this->classroom_man->getHours(), '00', false) . ' : ' . Form::getInputDropdown('', 'pb_minutes', 'pb_minutes', $this->classroom_man->getMinutes(), '00', false),
                Form::getInputDropdown('', 'pe_hours', 'pe_hours', $this->classroom_man->getHours(), '00', false) . ' : ' . Form::getInputDropdown('', 'pe_minutes', 'pe_minutes', $this->classroom_man->getMinutes(), '00', false),
                Form::getInputDropdown('', 'e_hours', 'e_hours', $this->classroom_man->getHours(), '00', false) . ' : ' . Form::getInputDropdown('', 'e_minutes', 'e_minutes', $this->classroom_man->getMinutes(), '00', false),
                Form::getInputDropdown('', 'classroom', 'classroom', $classroom_array, 0, false),
            ]);
        }

        $table = '<script type="text/javascript">'
            . 'var num_day = ' . count($array_day) . ';'
            . 'YAHOO.util.Event.on("b_hours", "change", changeBeginHours);'
            . 'YAHOO.util.Event.on("b_minutes", "change", changeBeginMinutes);'
            . 'YAHOO.util.Event.on("pb_hours", "change", changePBeginHours);'
            . 'YAHOO.util.Event.on("pb_minutes", "change", changePBeginMinutes);'
            . 'YAHOO.util.Event.on("pe_hours", "change", changePEndHours);'
            . 'YAHOO.util.Event.on("pe_minutes", "change", changePEndMinutes);'
            . 'YAHOO.util.Event.on("e_hours", "change", changeEndHours);'
            . 'YAHOO.util.Event.on("e_minutes", "change", changeEndMinutes);'
            . 'YAHOO.util.Event.on("classroom", "change", changeClassroom);'
            . '</script>'
            . $tb->getTable();

        return $table;
    }
    
    
    public function saveNewDate()
    {
		//ABR - riscritta
		 
        $date_info = $this->getDateInfoFromPost();

        $sub_start_date = trim($date_info['sub_start_date']);
        $sub_end_date = trim($date_info['sub_end_date']);
        $unsubscribe_date_limit = trim($date_info['unsubscribe_date_limit']);
        $internal_note = trim( $date_info['internal_note'] );		//ABR

        $sub_start_date = (!empty($sub_start_date) ? Format::dateDb($sub_start_date, 'date') : '0000-00-00') . ' 00:00:00';
        $sub_end_date = (!empty($sub_end_date) ? Format::dateDb($sub_end_date, 'date') : '0000-00-00') . ' 00:00:00';
        $unsubscribe_date_limit = (!empty($unsubscribe_date_limit) ? Format::dateDb($unsubscribe_date_limit, 'date') : '0000-00-00') . ' 00:00:00';
		
		// Inserisco l'edizione
        $id_date = $this->classroom_man->insDate($this->id_course, $date_info['code'], $date_info['name'], $date_info['description'], $date_info['mediumTime'], $date_info['max_par'], $date_info['price'], $date_info['overbooking'], $date_info['status'], $date_info['test'],
            $sub_start_date, $sub_end_date, $unsubscribe_date_limit, $internal_note);
            

        if (isset($date_info['customFields'])) {
            foreach ($date_info['customFields'] as $idField => $customEntry) {
                $this->classroom_man->addCustomFieldValue($id_date, $idField, $customEntry);
            }
        }
        
		//ABR Modifica passaggi salvataggio
		$success = ($id_date > 0);
		
        if ($success) {
			//ABR: Inserisco info finanziamento
			$success = $this->insFundEntry($id_date, $date_info['id_fund'], $date_info['fund_info']);
        }

        //Out ABR: se ok restituisco id edizione
		return ($success ? $id_date : false);
    }


	/*
    public function saveNewDate()
    {
		//Mod. ABR
		 
        $date_info = $this->getDateInfoFromPost();

        $array_day_tmp = !empty($date_info['date_selected']) ? explode(',', $date_info['date_selected']) : [];
        $array_day = [];

        $countDays = count($array_day_tmp);
        for ($i = 0; $i < $countDays; ++$i) {
            $array_day[$i]['date_begin'] = $array_day_tmp[$i] . ' ' . $_POST['b_hours_' . $i] . ':' . $_POST['b_minutes_' . $i] . ':00';
            $array_day[$i]['pause_begin'] = $array_day_tmp[$i] . ' ' . $_POST['pb_hours_' . $i] . ':' . $_POST['pb_minutes_' . $i] . ':00';
            $array_day[$i]['pause_end'] = $array_day_tmp[$i] . ' ' . $_POST['pe_hours_' . $i] . ':' . $_POST['pe_minutes_' . $i] . ':00';
            $array_day[$i]['date_end'] = $array_day_tmp[$i] . ' ' . $_POST['e_hours_' . $i] . ':' . $_POST['e_minutes_' . $i] . ':00';
            $array_day[$i]['classroom'] = $_POST['classroom_' . $i];
        }

        $sub_start_date = trim($date_info['sub_start_date']);
        $sub_end_date = trim($date_info['sub_end_date']);
        $unsubscribe_date_limit = trim($date_info['unsubscribe_date_limit']);
        $internal_note = trim( $date_info['internal_note'] );		//ABR

        $sub_start_date = (!empty($sub_start_date) ? Format::dateDb($sub_start_date, 'date') : '0000-00-00') . ' 00:00:00';
        $sub_end_date = (!empty($sub_end_date) ? Format::dateDb($sub_end_date, 'date') : '0000-00-00') . ' 00:00:00';
        $unsubscribe_date_limit = (!empty($unsubscribe_date_limit) ? Format::dateDb($unsubscribe_date_limit, 'date') : '0000-00-00') . ' 00:00:00';
		
		// Inserisco l'edizione
        $id_date = $this->classroom_man->insDate($this->id_course, $date_info['code'], $date_info['name'], $date_info['description'], $date_info['mediumTime'], $date_info['max_par'], $date_info['price'], $date_info['overbooking'], $date_info['status'], $date_info['test'],
            $sub_start_date, $sub_end_date, $unsubscribe_date_limit, $internal_note);
            

        if (isset($date_info['customFields'])) {
            foreach ($date_info['customFields'] as $idField => $customEntry) {
                $this->classroom_man->addCustomFieldValue($id_date, $idField, $customEntry);
            }
        }
        
		//ABR Modifica passaggi salvataggio
		$success = ($id_date > 0);
		
        if ($success) {
			//ABR: Inserisco info finanziamento
			$success = $this->insFundEntry($id_date, $date_info['id_fund'], $date_info['fund_info']);
			
			
			// Aggiorno le date. Ha senso? Il codice delle date non sembra necessario perché è stato cambiato il sistema della schedulazione (updateDateDays)
            if ($countDays > 0 && $success) {
                $success = $this->classroom_man->updateDateDay($id_date, $array_day);
            }
        }

        //Out ABR: se ok restituisco id edizione
		return ($success ? $id_date : false);
    }
    */

    public function getDateInfo()
    {
		//Mod. ABR
		
        if (isset($_POST['back'])) {
            $date_info = [];
        } else {
			// Informazioni dell'edizione
            $date_info = $this->classroom_man->getDateInfo($this->id_date);
            
            //ABR: Recupero informazioni del fondo
            $date_info['id_fund'] = $this->fund_man->getIdFund($this->id_date);
            $date_info['fund_info_exists'] = $this->fund_man->isFundEntryCompiled($this->id_date);
        }

        return $date_info;
    }
    
	public function getDateAllInfo()
	{
		//>> ABR Aggiunge altre informazioni a quelle standard (nome docente)
		
		//Recupero info di base
		$date_info = $this->classroom_man->getDateInfo($this->id_date);
		
		//Aggiungo insegnanti all'array
		$new_info = $this->classroom_man->getDateTeachers($this->id_date);
		$date_info = array_merge($date_info, array('teachers'=>$new_info));
		
		//Aggiungo i nomi delle aule all'array
		$new_info =  $this->classroom_man->getDateClassrooms($this->id_date, true);
		$date_info = array_merge($date_info, array('classrooms'=>$new_info));
		
		//Aggiungo id fondo
		$new_info = $this->fund_man->getIdFund($this->id_date); //ABR
		$date_info = array_merge($date_info, array('id_fund'=>$new_info));

		return $date_info;
	}

    public function getCustomFieldsValue($idDate, $idField)
    {
        return $this->classroom_man->getCustomFieldValue($idDate, $idField);
    }

    public function getDateDay($idDate = null)
    {
        return $this->classroom_man->getDateDay($idDate ?? $this->id_date);
    }

    public function getAllDateDay($idDate = null)
    {
        return $this->classroom_man->getAllDateDay($idDate ?? $this->id_date);
    }
    
    /** ABR **
     * Recupera i giorni di una edizione classroom con info aggiungitve
     */
    public function getDateDaysAndLocations($idDate = null, $format_date_name = 'month_name_long')
    {
        return $this->classroom_man->getDaysAndLocations($idDate ?? $this->id_date, $format_date_name);
    }

    public function getDateString()
    {
        $array_day = $this->getDateDay();

        $date_string = '';
        $start_mounth = '';

        $first = true;

        $countDays = count($array_day);
        for ($i = 0; $i < $countDays; ++$i) {
            if ($first) {
                $first = false;
                $start_mounth = substr($array_day[$i]['date_begin'], 5, 2) . '/' . substr($array_day[$i]['date_begin'], 0, 4);
                $date_string .= substr($array_day[$i]['date_begin'], 5, 2) . '/' . substr($array_day[$i]['date_begin'], 8, 2) . '/' . substr($array_day[$i]['date_begin'], 0, 4);
            } else {
                $date_string .= ',' . substr($array_day[$i]['date_begin'], 5, 2) . '/' . substr($array_day[$i]['date_begin'], 8, 2) . '/' . substr($array_day[$i]['date_begin'], 0, 4);
            }
        }

        return $date_string;
    }
    
    
    public function updateDate()
    {
		//ABR - riscritta
		
        $date_info = $this->getDateInfoFromPost();

        if (isset($date_info['customFields'])) {
            foreach ($date_info['customFields'] as $idField => $customEntry) {
                $this->classroom_man->addCustomFieldValue($this->id_date, $idField, $customEntry);
            }
        }

        $res = $this->classroom_man->upDate($this->id_date, $date_info['code'], $date_info['name'], $date_info['description'], $date_info['mediumTime'], $date_info['max_par'], $date_info['price'], $date_info['overbooking'], $date_info['status'], $date_info['test'], Format::dateDb($date_info['sub_start_date'], 'date') . ' 00:00:00', Format::dateDb($date_info['sub_end_date'], 'date') . ' 00:00:00', Format::dateDb($date_info['unsubscribe_date_limit'], 'date') . ' 00:00:00', $date_info['internal_note']); 	//ABR

		//Mod. ABR
        if ($res) {
			
			//ABR: aggiorno stato assegnazioni
			$res = $this->courseassn_man->updAssnStatus($this->id_course, $this->id_date, $date_info['status'], 'classroom');
			
			//ABR: aggiorno/inserisco le informazioni sul finanziamento
			$res = $this->insFundEntry($this->id_date, $date_info['id_fund'], $date_info['fund_info']);
        }

        return $res;
    }

	/*
    public function updateDate()
    {
		//Mod. ABR
		
        $date_info = $this->getDateInfoFromPost();

        $array_day_tmp = !empty($date_info['date_selected']) ? explode(',', $date_info['date_selected']) : [];
        $array_day = [];

        $countDays = count($array_day_tmp);
        for ($i = 0; $i < $countDays; ++$i) {
            $array_day[$i]['date_begin'] = $array_day_tmp[$i] . ' ' . $_POST['b_hours_' . $i] . ':' . $_POST['b_minutes_' . $i] . ':00';
            $array_day[$i]['pause_begin'] = $array_day_tmp[$i] . ' ' . $_POST['pb_hours_' . $i] . ':' . $_POST['pb_minutes_' . $i] . ':00';
            $array_day[$i]['pause_end'] = $array_day_tmp[$i] . ' ' . $_POST['pe_hours_' . $i] . ':' . $_POST['pe_minutes_' . $i] . ':00';
            $array_day[$i]['date_end'] = $array_day_tmp[$i] . ' ' . $_POST['e_hours_' . $i] . ':' . $_POST['e_minutes_' . $i] . ':00';
            $array_day[$i]['classroom'] = $_POST['classroom_' . $i];
        }

        if (isset($date_info['customFields'])) {
            foreach ($date_info['customFields'] as $idField => $customEntry) {
                $this->classroom_man->addCustomFieldValue($this->id_date, $idField, $customEntry);
            }
        }

        $res = $this->classroom_man->upDate($this->id_date, $date_info['code'], $date_info['name'], $date_info['description'], $date_info['mediumTime'], $date_info['max_par'], $date_info['price'], $date_info['overbooking'], $date_info['status'], $date_info['test'], Format::dateDb($date_info['sub_start_date'], 'date') . ' 00:00:00', Format::dateDb($date_info['sub_end_date'], 'date') . ' 00:00:00', Format::dateDb($date_info['unsubscribe_date_limit'], 'date') . ' 00:00:00', $date_info['internal_note']); 	//ABR

		//Mod. ABR
        if ($res) {
			
			//ABR: aggiorno stato assegnazioni
			$res = $this->courseassn_man->updAssnStatus($this->id_course, $this->id_date, $date_info['status'], 'classroom');
			
			//ABR: aggiorno/inserisco le informazioni sul finanziamento
			$res = $this->insFundEntry($this->id_date, $date_info['id_fund'], $date_info['fund_info']);
			
			// Aggiorno le date
            if ($res && $countDays > 0) {
                $res = $this->classroom_man->updateDateDay($this->id_date, $array_day);
         );
            }

        }

        return $res;
    }
    */

    public function removeDateDay($days)
    {
        return $this->classroom_man->removeDateDay($this->id_date, $days);
    }

    public function updateDateDays($days)
    {
        return $this->classroom_man->updateDateDay($this->id_date, $days);
    }

    public function delClassroom($customFields = [])
    {
		//Mod. ABR
		
		// ELimino edizione
        $res = $this->classroom_man->delDate($this->id_date, $customFields);

		//ABR: Aggiorno eventuali assegnazioni e elimino info fondo
		if($res) {
			// Aggiorno assegnazioni
			$res = $this->courseassn_man->updAssnEditionExistent($this->id_course, $this->id_date);
			
			// Elimino informazioni del fondo associate
			$this->fund_man->delFundEntry($this->id_date);
		}
			
		return $res;
    }

    public function delCourse()
    {
        $classroom = $this->classroom_man->getDateIdForCourse($this->id_course);

        foreach ($classroom as $id_date) {
            if (!$this->classroom_man->delDate($id_date)) {
                return false;
            }
        }

        require_once _lms_ . '/admin/modules/course/course.php';

        return removeCourse($this->id_course);
    }

    public function getTestType()
    {
        return $this->classroom_man->getTestType($this->id_date);
    }

    public function getPresenceTable()
    {
        $user = $this->classroom_man->getUserForPresence($this->id_date, $this->id_course);
        $day = $this->getDateDay($this->id_date);
        $test_type = $this->getTestType();
        $user_presence = $this->classroom_man->getUserPresenceForDate($this->id_date);

        $tb = new Table(0, Lang::t('_ATTENDANCE', 'course'), Lang::t('_ATTENDANCE', 'course'));

        $cont_h = [Lang::t('_USERNAME', 'course'),
            Lang::t('_FULLNAME', 'course'),];

        $type_h = ['', ''];

        foreach ($day as $id_day => $day_info) {
            $cont_h[] = Format::date($day_info['date_begin'], 'date') . '<br />'
                . '<a href="javascript:;" onClick="checkAllDay(' . $id_day . ')">' . FormaLms\lib\Get::img('standard/checkall.png', Lang::t('_CHECK_ALL_DAY', 'presence')) . '</a>'
                . ' '
                . '<a href="javascript:;" onClick="unCheckAllDay(' . $id_day . ')">' . FormaLms\lib\Get::img('standard/uncheckall.png', Lang::t('_UNCHECK_ALL_DAY', 'presence')) . '</a>';
            $type_h[] = 'img-cell';
        }

        $cont_h[] = '';
        $type_h[] = 'img-cell';

        if ($test_type == _DATE_TEST_TYPE_PAPER) {
            $cont_h[] = Lang::t('_SCORE', 'course');
            $type_h[] = 'img-cell';
        }

        $cont_h[] = Lang::t('_NOTES', 'course');
        $type_h[] = 'img-cell';

        $tb->setColsStyle($type_h);
        $tb->addHead($cont_h);

        $array_user_id = [];

        foreach ($user as $id_user => $user_info) {
            reset($day);

            $array_user_id[] = $id_user;

            $cont = [];

            $cont[] = $user_info['userid'];
            $cont[] = $user_info['lastname'] . ' ' . $user_info['firstname'];

            foreach ($day as $id_day => $day_info) {
                if (isset($user_presence[$id_user][substr($day_info['date_begin'], 0, 10)]) && $user_presence[$id_user][substr($day_info['date_begin'], 0, 10)]['presence'] == 1) {
                    $presence = true;
                } elseif (isset($user_presence[$id_user][substr($day_info['date_begin'], 0, 10)]) && $user_presence[$id_user][substr($day_info['date_begin'], 0, 10)]['presence'] == 0) {
                    $presence = false;
                } else {
                    $presence = false;
                }

                $cont[] = Form::getInputCheckbox('date_' . $id_day . '_' . $id_user, 'date_' . $id_day . '_' . $id_user, 1, $presence, false);
            }

            $cont[] = '<a href="javascript:;" onClick="checkAllUser(' . $id_user . ')">' . FormaLms\lib\Get::img('standard/checkall.png', Lang::t('_CHECK_ALL_USER', 'presence')) . '</a>'
                . '<br />'
                . '<a href="javascript:;" onClick="unCheckAllUser(' . $id_user . ')">' . FormaLms\lib\Get::img('standard/uncheckall.png', Lang::t('_UNCHECK_ALL_USER', 'presence')) . '</a>';

            if ($test_type == _DATE_TEST_TYPE_PAPER) {
                if (isset($user_presence[$id_user]['0000-00-00']) && $user_presence[$id_user]['0000-00-00']['presence'] == 1) {
                    $passed = true;
                } else {
                    $passed = false;
                }

                //$cont[] = Form::getTextfield('', 'score_'.$id_user, 'score_'.$id_user, 255, (isset($user_presence[$id_user]['0000-00-00']['score']) ? $user_presence[$id_user]['0000-00-00']['score'] : '0'));
                $cont[] = Form::getInputTextfield('', 'score_' . $id_user, 'score_' . $id_user, (isset($user_presence[$id_user]['0000-00-00']['score']) ? $user_presence[$id_user]['0000-00-00']['score'] : '0'), Lang::t('_SCORE', 'course'), 255, '');
            }

            //$cont[] = Form::getSimpleTextarea('', 'note_'.$id_user, 'note_'.$id_user, (isset($user_presence[$id_user]['0000-00-00']['note']) ? $user_presence[$id_user]['0000-00-00']['note'] : ''), false, false, false, 2);
            $cont[] = Form::getInputTextarea('note_' . $id_user, 'note_' . $id_user, (isset($user_presence[$id_user]['0000-00-00']['note']) ? $user_presence[$id_user]['0000-00-00']['note'] : ''), '', 5, 22);
            $tb->addBody($cont);
        }

        return $tb->getTable();
    }

    public function savePresence()
    {
		//Mod. ABR
		
        $score_min = FormaLms\lib\Get::req('score_min', DOTY_INT, 0);

        $user = $this->classroom_man->getUserForPresence($this->id_date);
        $days = $this->getDateDay($this->id_date);
        $test_type = $this->classroom_man->getTestType($this->id_date);

        foreach ($user as $id_user => $user_info) {
            $user[$id_user]['score'] = FormaLms\lib\Get::req('score_' . $id_user, DOTY_INT, 0);
            $user[$id_user]['note'] = FormaLms\lib\Get::req('note_' . $id_user, DOTY_MIXED, '');
            $user[$id_user]['day_presence'] = [];


            foreach ($days as $index => $day) {
                $requestString = 'date_' . $day['id'] . '_' . $id_user;

                $user[$id_user]['day_presence'][$day['id']] = FormaLms\lib\Get::req($requestString, DOTY_INT, 0);
            }
        }
		
		// Inserisco le presenze
        $res = $this->classroom_man->insDatePresence($this->id_course, $this->id_date, $user, $days, $score_min);
        
		//ABR: imposto stato assegnazione se già impostato stato del corso a concluso
		//     altrimenti interviene già il cambiamento di stato del corso
		$id_edition = array($this->id_date);
		$edition = $this->classroom_man->getCourseEdition($this->id_course, false, false, false, false, $id_edition);
		
		if (count($edition) == 1){
			if(current($edition)['status'] == 1){	
				$res *= $this->courseassn_man->updAssnStatus($this->id_course, $this->id_date, 1, 'classroom');
			}
		}
		
		//Out
		return $res;

    }

    /**
     * Check if the days and classroom selection is available: return the intersecation
     * and if availability is ok the result will be an empty array.
     *
     * @param <type> $info
     *
     * @return array
     */
    public function checkDateAvailability($info)
    {
        $output = [];
        if (!empty($info)) {
            //get class occupation
            $classrooms = [];
            foreach ($info as $day) {
                if ($day['classroom'] > 0 && !in_array($day['classroom'], $classrooms)) {
                    $classrooms[] = $day['classroom'];
                }
            }

            if (!empty($classrooms)) {
                $query = 'SELECT * FROM %lms_course_date_day WHERE classroom IN (' . implode(',', $classrooms) . ')';
                $res = sql_query($query);
                while ($obj = sql_fetch_object($res)) {
                }
            }
        }

        return $output;
    }

    /**
     * Check if at any date the classrooms are occupied.
     *
     * @param <type> $date
     *
     * @return array
     */
    public function getOccupiedClassrooms($date)
    {
        if (!$date) {
            return false;
        }
        if (!is_string($date) || strlen($date) < 10) {
            return false;
        }
        $date = substr($date, 0, 10);
        $output = [];
        $query = 'SELECT DISTINCT(classroom) FROM %lms_course_date_day '
            . " WHERE date_begin <= '" . $date . " 23:59:59' AND date_end >= '" . $date . " 00:00:00'";
        $res = sql_query($query);
        while (list($id_classroom) = sql_fetch_row($res)) {
            $output[] = $id_classroom;
        }

        return $output;
    }


	/** ABR: 
	 * Lasciata per utilizzo da API. Servirebbe un service per condivisione tra api e controller
	 */
    public function sendCalendarToAllSubscribers()
    {
        $subscriptionModel = new SubscriptionAlms($this->id_course, false, $this->id_date);

        $users = $subscriptionModel->loadUser();

        $calendarMailer = new CalendarMailer();
        foreach ($users as $user) {
            $user = Docebo::user()->getAclManager()->getUserMappedData(Docebo::user()->getAclManager()->getUser($user['id_user'], false));

            $calendar = CalendarManager::getCalendarDataContainerForDateDays((int)$this->id_course, (int)$this->id_date, (int)$user['idst']);

            $calendarMailer->sendCalendarToUser($calendar, $user);
        }
    }

    
    /** ABR **
     * Restituisce gli utenti iscritti all'edizione
     */
 	public function getSubscribedUsers() {
		return $this->classroom_man->getDateUsers($this->id_date);
	}

    /** ABR **
     * Restituisce i fondi di finanziamento per le combo
     */
	public function getFundForDropdown() {	
		return $this->fund_man->getFundForDropdown();
	}
	
	
    /** ABR **
     * Restituisce gli input text per l'inserimento delle informazioni sul finanziamento
     * Se id_date è valorizzato, il metodo inserisce gli eventuali valori già presenti
     */
	public function getFundEntryInput($id_fund, $id_date = false) {

		$output = [];
		$entries = array();
		$fields = $this->fund_man->getFundEntryFields($id_fund);
			
		if ($id_date) {
			$info = $this->fund_man->getFundEntry($id_date, $id_fund);
		}	
		
		// Preparo il codice html dei controlli
		foreach($fields as $key => $caption) {
			$output[$key][0] = $caption;		//key è fund_text_01, fund_text_02 ecc.
			$output[$key][1] = isset($info[$key]) ? stripslashes($info[$key]) : '';
		}
			
		//Out
		return $output;
	}
	
	
    /** ABR **
     * Inserisce le informazioni sul finanziamento (elimina le precedenti)
     */	
	public function insFundEntry($id_date, $id_fund, $info) {
		return $this->fund_man->insFundEntry($id_date, $id_fund, $info);
	}
	
	
    /** ABR **
     * Restituisce se l'event manager è configurato per inviare l'aggiornamento del calendario
     */		
	public function getEventCalendarChangeState(): bool
	{
		$sql = "
			SELECT permission
			FROM %adm_event_class AS ec
			JOIN %adm_event_manager AS em ON ec.idClass = em.idClass
			WHERE ec.class = 'ClassroomCalendarChange'
		";

		list($state) = sql_fetch_row(sql_query($sql));

		return ($state === 'mandatory');
	}
    
}
