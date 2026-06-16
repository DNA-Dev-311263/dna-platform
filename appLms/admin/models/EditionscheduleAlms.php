<?php

defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   BKO       																|
\ ======================================================================== *

/**
 * The language model class
 *
 * This Model is used to retrieve and manipulate all kind of
 * information about the classrooms and their locations (add-edit-delete).
 * @author: ABR
 * @date: 2018-02-09
 */
class EditionscheduleAlms extends Model {

	protected $courseassn_man;
	protected $course_man;
	protected $edition_man;
	protected $date_man;
	
	protected $acl_man;
	protected $id_org;
	protected $id_user;
	
	public function __construct($id_org = 0, $id_user = 0) {

		require_once(_lms_.'/lib/lib.edition.php');
		require_once(_lms_.'/lib/lib.date.php');
		require_once(_lms_.'/lib/lib.course.php');
		require_once(_lms_.'/lib/lib.courseassn.php');
	
		$this->course_man = new Man_Course();
		$this->date_man = new DateManager();
		$this->edition_man = new EditionManager();
		
		$this->acl_man =& Docebo::user()->getAclManager();
		$this->courseassn_man = new CourseassnManager(); 
		
		
		//>> $id_user è l'id dell'operatore che sta caricando/visualizzando i dati.
		//>> $id_org l'organizzazione di lavoro (se è godadmin può non essere la sua).
		
		$this->id_user = $id_user;
		$this->id_org = $id_org;
		
	}
	
	public function getTotalAssnByCourse() {
		//IN SVILUPPO...
		// Sospesa per fine budget.
		
		$res = null;
		
		// Preparo argomenti per GroupBy dinamica
		$funcArr[]	= array('COUNT' => 'id_assn');
		$funcArr[]	= array('SUM' => array('status = 1', 'assn_active'));
		
		$groupExp	= "id_entry, course_code, course_name, course_type, course_difficult, course_idcategory, "
					. "course_max_num_subscribe";
		
		$whrExp		= "(assn.status = "._ASSN_STATUS_ACTIVE." OR assn.status = "._ASSN_STATUS_CLOSED.")	AND "
					. "crs.status = ".CST_EFFECTIVE." AND idOrg = ".(int)$this->id_org;
		
		// Recupero i dati raggruppati corsi/assegnazioni per elearning e classroom virtuali (senza gruppo per full-time/part-time e sede)
		$whrExp		.= " AND course_type = 'elearning' OR (course_type = 'classroom' AND course_virtual = 1)";
		$orderExp	 = "course_code, user_time_availability";
		
		$res_e = $this->courseassn_man->getAssnGroupBy($funcArr, $groupExp, $whrExp, $orderExp, 'id_entry');
		
		
		// Recupero i dati raggruppati corsi/assegnazioni per classroom normali (con gruppo full-time/part-time e sede)
		$groupExp	.= ", user_time_availability, user_job_location";
		$whrExp		.= " AND course_type = 'classroom'";
		$orderExp	 = "course_code, user_time_availability";
		
		$res_c = $this->courseassn_man->getAssnGroupBy($funcArr, $groupExp, $whrExp, $orderExp, 'id_entry');
		
	
		$l = $this->date_man->getLocations();
		//$l = $this->course_man->getAllLabels(); //non serve
		
		
		//FERMO QUI
		return;
		//------------------------------------------------------------
		
		if($res_e || $res_c) {
			
			// - date		
			if ($res_c) 
				$dates = $this->date_man->getCourseDate(array_keys($res_c));
		
			// - conteggio
			foreach($dates as $key => $row) {
				
				$id = $row['id_course'];
				$status = $row['status'];
				
				$hr_diff = date('H',strtotime($row['date_end'])) - date('H',strtotime($row['date_begin']));
				
				$pf_time = ($hr_diff <= 0 || $hr_diff > 4 ? 'full-time' : 'part-time');
				
				$num_total[$id] += ($status == _DATE_STATUS_ACTIVE || $status == _DATE_STATUS_FINISHED ? 1 : 0);
				$num_open[$id]  += ($status == _DATE_STATUS_ACTIVE ? 1 : 0);
				
				$cs_ed_count['classroom'][$id] = array('total' => $num_total[$id], 'active' => $num_open[$id]);
			}
				 
			
			// Assegnazioni elearning
			$filter = $filter = array('course_type' => 'elearning');
			// - corsi
			$res_e = array_uintersect($result, $filter, function($rv, $fv){
						return strcmp($rv['course_type'], $fv['course_type']);
					});
			
			// - edizioni		
			if ($res_e) 
				$editions = $this->edition_man->getEditionsInfoByCourses(array_keys($res_e));
				
			// - conteggio
			foreach($editions as $key => $row) {
				$id = $row['id_course'];
				$status = $row['status'];

				
				$num_total[$id] += ($status == CST_EFFECTIVE || $status == CST_CONCLUDED ? 1 : 0);
				$num_open[$id]  += ($status == CST_EFFECTIVE ? 1 : 0);
				
				$cs_ed_count['elearning'][$id] = array('total' => $num_total[$id], 'active' => $num_open[$id]);
			}


			// Recupero le categorie
			$categories = $this->course_man->getCategoriesInfo();
			
			
			// Preparo array di risposta
			foreach($result as $key => $row) {
				
				$id = $row['id_entry'];
				$type = $row['course_type'];
				$id_cat = $row['course_idcategory'];
				$ed_count = array(0, 0);
				
				// Recupero i totali edizione per corso
				if(!empty($cs_ed_count[$type][$id])){
					
					$ed_count[0] = $cs_ed_count[$type][$id]['total'];
					$ed_count[1] = $cs_ed_count[$type][$id]['active'];
				}
				
				// Scrivo i due nuovi elementi per l'array di ritorno
				$row['total_editions'] = $ed_count[0];
				$row['active_editions'] = $ed_count[1];
				
				// Aggiungo la categoria
				$row['cat_path'] = str_ireplace('/root/', '', $categories[$id_cat]['path']);
				
				// Passo ad array di ritorno
				$res[$key] = $row;
				
			}
		}
		
	
		return $res;
		
	}

	public function getPerm(){
		//>> Usata per restituire i check dei permessi su profilo amministratori
		
		return array(
			'view' => 'standard/view.png',
			'mod' => 'standard/edit.png'
		);
	}

	

	

}
