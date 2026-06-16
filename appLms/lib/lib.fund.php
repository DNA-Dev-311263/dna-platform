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

class FundManager
{
	protected $lang;
	protected $acl_man;
	protected $type_entry;


	public function __construct($type_entry = 'date') {
		
		$this->lang = DoceboLanguage::CreateInstance('admin_fund', 'lms');
		$this->acl_man = Docebo::user()->getAclManager();
		$this->type_entry = $type_entry; 						//al momento gestito solo date
		
		// Controllo validità di type_entry
		if ($type_entry !== 'date' && $type_entry !== 'edition')
			throw new InvalidArgumentException('type entry is only date or edition');
	}


	public function __destruct() {
		
	}
	
	
	public function getTypeEntry() {	
		return $this->type_entry;
	}
		
		
	public function getFundForDropdown() {	
		//ABR: Restituisce i fondi di finanziamento per le combo
		$output = array();
		$query = "SELECT id_fund, code, name FROM %lms_fund WHERE is_active = 1 ";
		$res = sql_query($query);
		
		$output[0] = Lang::t('_NONE', 'standard');

		while (list($id_fund, $code, $name) = sql_fetch_row($res)) {
			$output[$id_fund] = $name . " (".$code.")";
		}
				
		return $output;
	}
	
	
	public function getFundList() {	
		//ABR: Restituisce i fondi di finanziamento con chiave (id) e nome
		$output = array();
		$query = "SELECT id_fund, name FROM %lms_fund";
		$res = sql_query($query);

		while (list($id_fund, $name) = sql_fetch_row($res)) {
			$output[$id_fund] = $name;
		}
				
		return $output;
	}
	
	
	public function getFundEntryFields(int $id_fund) {	
		//ABR: Restituisce i titoli dei campi informativi (array)
		
		$res = array();
		$query = "SELECT entry_fields FROM %lms_fund WHERE id_fund = ".$id_fund;
		$result = sql_query($query);
		
		if($result) {
			list($fields) = sql_fetch_row($result);
		
			if($fields)
				$res = unserialize($fields);
		}	
				
		return $res;
	}
	
	
	public function isFundEntryCompiled(int $id_entry): bool {
		$res = false;
		$query = $this->getFundEntrySql();

		if ($query) {

			$query .= " WHERE fe.id_entry = " . (int)$id_entry . "
						AND CONCAT(
							IFNULL(fund_text_01, ''),
							IFNULL(fund_text_02, ''),
							IFNULL(fund_text_03, ''),
							IFNULL(fund_text_04, ''),
							IFNULL(fund_text_05, ''),
							IFNULL(fund_text_06, ''),
							IFNULL(fund_text_07, ''),
							IFNULL(fund_text_08, '')
						) <> ''";

			$result = sql_query($query);

			// QUI il controllo corretto: ci sono righe?
			if ($result && sql_num_rows($result) > 0) {
				$res = true;
			}
		}

		return $res;
	}
		
	
	public function getFundEntry($id_entry, $id_fund = false) {
		//>> Restituisce le informazioni fondo associato all'ogetto (es. edizione)
		
		$res = array();
		$query = $this->getFundEntrySql();
		
		if ($query) {
			// Preparo Where
			$whr = ( !$id_fund ? 1 : "f.id_fund = ".(int)$id_fund );
			$query .= " WHERE fe.id_entry = ".(int)$id_entry ." AND ".$whr;

			// Lancio la query		  
			$result = sql_query($query);
		
			// Recupero le informazioni
			if($result) 
				$res = sql_fetch_array($result);
		}
		
		// Out
		return $res;
	}
	
	
	public function getFundEntrySql() {
		//>> Restituisce la stinga SQL per il recupero delle informazioni fondo
		
		$query = "";
		$type_entry = $this->type_entry;
		
		// Preparo espressione join
		switch($type_entry)
		{
			case 'date':
				$join_exp = "%lms_course_date d ON (fe.id_entry = d.id_date AND fe.type_entry = '".$type_entry."')";
			break;
			
			default:
				$join_exp = "";
		} 
		
		if ($join_exp) {
			// Preparo la query
			$query = "	SELECT f.name, fe.*, d.id_course
						FROM %lms_fund f
							JOIN %lms_fund_entry fe ON f.id_fund = fe.id_fund 
							JOIN  ".$join_exp;			
		}
		
		// Out
		return $query;	
	}
	
	
	public function getIdFund(int $id_entry):int {
		//>> Restituisce l'ID del fondo associato all'ediizone
		
		$result = 0;
		$info = $this->getFundEntry($id_entry);
		
		if($info)
			$result = $info['id_fund'];
		
		return $result;
	}
	
	
	public function delFundEntry($id_entry) {
		//>> ABR: Elimina le informazioni sul finanziamento
		
		$type_entry = $this->type_entry;
		
		$query = "DELETE FROM %lms_fund_entry WHERE type_entry = '".$type_entry."' AND id_entry = ".(int)$id_entry; 
		$res = sql_query($query);
		
		return $res;
	}
	
	
	public function delFundEntryByCourse(int $id_course) {
		//>> ABR: Elimina le informazioni sul finanziamento in base a un id corso
		
		$res = false;
		$entries = [];
		
		// Preparo la query per il recupero dei record da eliminare
		$query = $this->getFundEntrySql();
		$query .= " WHERE d.id_course = ".$id_course;
		
		// Lancio la query		  
		$result = sql_query($query);
	
		// Recupero entries in base al corso
		if($result) {
			while ($row = sql_fetch_array($result)) {
				$entries[] = $row;
			}
		}
	
		// Procedo all'eliminazione
		if ( !empty($entries) ) {
			$entry_ids = array_column($entries, 'id_entry');
			
			$query = " DELETE FROM %lms_fund_entry"
					." WHERE type_entry = '".$this->type_entry."'"
					." 	AND id_entry IN (".implode(',', $entry_ids).")"; 
				
			$res = sql_query($query);
		}
		
		// Out
		return $res;
	}
	
	
	public function insFundEntry($id_entry, $id_fund, $info) {
		//>> ABR: Inserisce le informazioni sul finanziamento (elimina le preedenti)
		
		$id_entry = (int)$id_entry;
		$id_fund = (int)$id_fund;
		
		// Esco se non c'è nulla da inserire/aggiornare
		if ( empty($info) ) return true;
		
		// Elimino
		$res = $this->delFundEntry($id_entry);
		
		if ($res && $id_fund) {
			// Inserisco dopo aver eliminato l'eventuale record

			$values = array();
			$fields = array('fund_text_01',
							'fund_text_02',
							'fund_text_03',
							'fund_text_04',
							'fund_text_05',
							'fund_text_06',
							'fund_text_07',
							'fund_text_08');
			
			// Preparo array valori di inserimento
			$values['id_entry'] = $id_entry;
			$values['id_fund'] = $id_fund;
			$values['type_entry'] = $this->type_entry;
										
			foreach	($fields as $field) {
				if ( isset($info[$field]) ) {
					$values[$field] = $info[$field];	
				}
			}
			
			// Preparo query
			$query = "INSERT INTO %lms_fund_entry (".implode(", ", array_keys($values)).") 
						VALUES (".$this->esStrArr($values).")";
														
			// Lancio query
			$res = sql_query($query);		
		}
		
		//Out
		return $res;	
	}
	
	/**
	 * Escape array string
	 */
	function esStrArr(array $values)
	{
		if (empty($values)) {
			return "NULL";
		}

		$clean = array_map(function($v) {
			return "'" . sql_escape_string($v) . "'";
		}, $values);

		return implode(',', $clean);
	}	
	
}

?>
