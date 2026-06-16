<?php defined("IN_FORMA") or die("Direct access is forbidden");

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
|	By ABR													|
\ ======================================================================== */

Class ProposalAlms extends Model {

	protected $db;
	protected $acl_man;
	protected $id_proponent;
	protected $course_man;
	protected $proposal_man;

	public function __construct($id_proponent = 0) {

		require_once(_lms_.'/lib/lib.proposal.php');
		require_once(_lms_.'/lib/category/class.categorytree.php');
		
		$this->id_proponent = $id_proponent;
		$this->db = DbConn::getInstance();
		$this->proposal_man = new ProposalManager();
		$this->course_man = new Man_Course();
		$this->acl_man =& Docebo::user()->getAclManager();

	}
	
	
	public function getIdProponent() {
		//>> Restituisce l'id_corso di istanza
		return $this->id_proponent;
	}
	
	
	public function getCourseStatusForDropdown() {
		//>> Restituisce un array con i valori di stato del corso
		return $this->course_man->getStatusForDropdown();
	}
	
	
	public function getProponentName() {
		//>> Restituisce il nome del corso in base all'id_proponent di istanza
		
		$course_info = $this->course_man->getCourseInfo($this->id_proponent);
		$name = ($course_info['code'] !== '' ? '['.$course_info['code'].'] ' : '').$course_info['name'];
		
		return $name;
	}
	
	public function totalUnusedCourse($id_category, $filter_text) {
		//>> Restituisce il numero di corsi non ancora aggiunti
		
		$whrExp = false;
		$prop_man = $this->proposal_man;
		
		// Formo espressione where
		if ($filter_text)
			$whrExp = str_replace("[@filter_text]", $filter_text, $prop_man->getFilterExpression());

		//Lancio il metodo 
		$res = $prop_man->countUnusedCourses($this->id_proponent, $id_category, $whrExp);
		
		//Restituisco il numero
		return  $res;
	}
	
	
	public function loadUnusedCourse ($id_category, $start_index, $results, $sort, $dir, $filter_text) {
		//>> Carica i corsi on ancora aggiunti alla proposta
		
		$whrExp = false;
		$orderExp = false;
		$limitExp = false;
		$prop_man = $this->proposal_man;
		
		// Formo espressione where
		if ($filter_text)
			$whrExp = str_replace("[@filter_text]", $filter_text, $prop_man->getFilterExpression());

		//Formo espressione ordinamenti
		switch($sort)
		{
			case 'code':
				$orderExp = $sort." ".$dir.", c.name ".$dir;
			break;
			case 'name':
				$orderExp = $sort." ".$dir.", c.code ".$dir;
			break;
			case 'status':
				$orderExp = $sort." ".$dir.", c.code ".$dir.", c.name ".$dir;
			break;
			case 'category':
				$orderExp = "ca.path ".$dir.", c.code ".$dir.", c.name ".$dir;
			break;			
			default:
				$orderExp = "ORDER BY ".$sort." ".$dir.", c.code ".$dir;
			break;
		}

		//Formo espressione limit
		($start_index === false ? '' : $limitExp = $start_index.", ".$results);
		
		//Lancio il metodo 
		$res = $prop_man->getUnusedCourses($this->id_proponent, $id_category, $whrExp, $orderExp, $limitExp);

		//Output
		return $res;
		
	}
	
	public function getUnusedIdCourses($id_category, $filter_text) {
		//>> Restituisce solo gli id dei corsi non aggiunti
		
		$whrExp = false;
		$retVal = array();
		
		// Formo espressione where
		if ($filter_text)
			$whrExp = "c.code LIKE '%".$filter_text."%' OR c.name LIKE '%".$filter_text."%'";
		
		//Lancio il metodo 
		$res = $this->proposal_man->getUnusedCourses($this->id_proponent, $id_category, $whrExp);
		
		
		if($res)
			$retVal = array_column($res, 'idCourse');

		//Output
		return $retVal;
		
	}
	
	
	public function getCatUnusedCourses() {
		//>> Restituisce solo le categorie dei corsi non aggiunti
			
		$treecat = new Categorytree();
		$parents = array();
		
		//Recupero i corsi importabili
		$res = $this->proposal_man->getUnusedCourses($this->id_proponent, $id_category);
		
		//Recupero le categorie in cui sono inseriti
		$categories = array_unique(array_column($res, 'idCategory'));

		//Ciclo alla ricerca delle categrie parent
		foreach ($categories as $cat) {
			
			$folder = &$treecat->getFolderById($cat);
			$res 	= $treecat->getAllParentId($folder, $folder->tdb);
			
			if ($res)
				$parents = array_merge($parents, $res);
		}
		
		//Prendo gli id parent in modo univoco
		$parents = array_unique($parents);
		
		//Restituisco le categorie trovate
		return  array_merge($categories, $parents);

	}


	public function loadCourseProposal($start_index, $results, $sort, $dir) {
		//>> Restituisce un array con i dati relativi alle proposte del corso
		
		$orderExp = false;
		$limitExp = false;
		
		//Formo espressione filtro
		$whrExp = "id_proponent = ".(int)$this->id_proponent;
				
		//Formo espressione ordinamenti
		switch($sort)
		{
			case 'code':
				$orderExp = $sort." ".$dir.", c.name ".$dir;
			break;
			case 'name':
				$orderExp = $sort." ".$dir.", c.code ".$dir;
			break;
			case 'status':
				$orderExp = $sort." ".$dir.", c.code ".$dir.", c.name ".$dir;
			break;
			case 'category':
				$orderExp = "ca.path ".$dir.", c.code ".$dir.", c.name ".$dir;
			break;
			case 'from_score':
				$orderExp = "p.from_score ".$dir.", c.code ".$dir;
			break;
			case 'to_score':
				$orderExp = "p.to_score ".$dir.", c.code ".$dir;
			break;	
			default:
				$orderExp = $sort." ".$dir.", c.code ".$dir;
			break;
		}

		//Formo espressione limit
		($start_index === false ? '' : $limitExp = $start_index.", ".$results);
		
		//Lancio il metodo 
		$res = $this->proposal_man->getProposal($whrExp, $orderExp, $limitExp);

		//Output
		return $res;
	
	}
	

	public function totalCourseProposal() {
		return $this->proposal_man->getProposalNumber($this->id_proponent);
	}
	
	
	public function updProposalFromScore($id_proposal, $from_score) {
		return $this->proposal_man->updProposalScore($id_proposal, $from_score, false);
	}
	
	
	public function updProposalToScore($id_proposal, $to_score) {
		return $this->proposal_man->updProposalScore($id_proposal, false, $to_score);
	}
	
	
	public function delProposal($id_proposal = false){
		//>> Elimina la proposta in base al suo id o tutte le proposte di un proponent
		
		if ($id_proposal)
			$retVal = $this->proposal_man->delProposal($id_proposal);
		else
			$retVal = $this->proposal_man->delAllProposal($this->id_proponent);
		
		//Out
		return $retVal;
	}
	
	public function insProposal($course_list) {
		//>> Inserisce i corsi da una lista (stringa "1,2,3 ...")
		
		$courses	= explode(",", $course_list);
		$retVal		= $this->proposal_man->insProposal($this->id_proponent, $courses);
		
		return $retVal;
	}
	
}

?>
