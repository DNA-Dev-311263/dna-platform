<?php defined("IN_FORMA") or die('Direct access is forbidden.');

/* ======================================================================== \
|   FORMA - The E-Learning Suite                                            |
|                                                                           |
|   Copyright (c) 2013 (Forma)                                              |
|   http://www.formalms.org                                                 |
|   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
|                                                                           |
|   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
|   Developed by ABR       												|
\ ======================================================================== */

class DatatablesWidget extends Widget {


	protected $json = null;
	public $id = "";
	public $ajaxUrl = "";
	
	public $startIndex = false;
	public $results = false;
	public $sort = false;
	public $dir = false;
	public $columns = array();
	public $languages = array();
	public $paginator = false;
	public $className = "table table-bordered display dataTable no-footer"; //"display compact";
	public $inlineStyle = "";
	public $showOnReady = true;
	public $rowsPerPage = false;
	public $row_page_menu = "[50, 100, 200]";
	public $use_paginator = true;
	public $tfootEnable = false;
	public $libs = false;
	public $field_id = "DT_RowID";
	public $buttons = array();
	public $check_column = false;
	public $processing = true;
	
	//these are used to enable scrollbars
	public $scroll_x = false;
	public $scroll_y = false;
	
	//L'array $columns può contenere le chiavi di $defColProp. Se mancano, vengono inseriti i valori di default
	// render è il nome dell'oggetto funzione (var obj = function( data, type, row){return data}) che deve elaborare il dato della colonna.
	protected $defColProp = array('label' => '', 'className' => '', 'visible' => true, 'render'=> 'null');
	
	// Pulsanti di default ammessi
	protected $sel_buttons = array('selectAll', 'selectNone');
	protected $exp_buttons = array('copy', 'copyHtml5', 'excel', 'excelHtml5');
	
	// Pulsanti richiesti senza proprietà
	protected $key_buttons = array();

	/**
	 * Constructor
	 * @param <string> $config the properties of the table
	 */
	public function __construct() {
		parent::__construct();
		$this->_widget = 'Datatables';
		$this->json = new Services_JSON();

	}


	/**
	 * Include the required libraries in order to have all the things ready and working
	 */
	public function init() {
		
		// Recupero i bottoni da inserire
		if ($this->buttons) {
			
			if(array_values($this->buttons) === $this->buttons) {
				// Se non ci sono chiavi associative, recupero i bottoni dai valori dell'array
				// I valori di buttons sono le proprietà del bottone
				$this->key_buttons = array_values($this->buttons);
				$this->buttons = array_fill_keys($this->key_buttons, '');
			} else {
				// Recupero i bottoni dalle chiavi dell'array
				$this->key_buttons = array_keys($this->buttons);
			}
		}
		
		//Imposto proprietà di default colonne se mancano
		foreach($this->columns as &$column) { 	
			foreach($this->defColProp as $kDef => $vDef) {
				
				if(!isset($column[$kDef]))
						$column[$kDef] = $vDef;
			}
		}  
		
		// Commodities functions (funzioni utili a creazione finestre dialogo)
		Util::get_js(FormaLms\lib\Get::rel_path('base').'/widget/dialog/dialog.js', true, true);
		
		// Load datatables libraries
		DatatablesLib::load($this->_checkLibs($this->libs));
	}

	
	public function run() {
		//>> Creazione widget Datatables
		//	 In futuro spostare la gestione della struttura in una view apposita per gestire situazioni più complesse (vedi lib.table.php)
		//	 Es. $this->render($view, $params)
		
		$th 	= "";
		$table  = "";
		
		//Tag TH del widget
		foreach($this->columns as $col) {  
			$th .= '<th class="'.$col['className'].'">'.$col['label'].'</th>';
		} 
		
		// Visualizzo tabella quando il doc è ready
		if($this->showOnReady) $this->inlineStyle = 'visibility:hidden; '.$this->inlineStyle;
			
		//Tabella html del widget	
		$table   .= '<table class="'.$this->className.'" id="'.$this->id.'" style="'.$this->inlineStyle.'">
						<thead>
							<tr>'.$th.'</tr>
						</thead>';
					
					
		if ($this->tfootEnable) {	
		
			$table	.= 	'<tfoot>
							<tr>'.$th.'</tr>
						 </tfoot>';
		}
			
		$table	 .=	'</table>';
			
			
		//Script per passaggio proprietà
			$i = 0;
			$cr = PHP_EOL;
			$js = "".$cr;
			
			$js .= 	'<script type="text/javascript">
			
						$( document ).ready(function() {
							$("#'.$this->id.'").css("visibility", "visible");
							
							$.fn.dataTable.moment("DD-MM-YYYY HH:mm");
							$.fn.dataTable.moment("DD-MM-YYYY");
							$.fn.dataTable.moment("DD/MM/YYYY HH:mm"); 
							$.fn.dataTable.moment("DD/MM/YYYY");
							
							$("#'.$this->id.'").DataTable( {
								"ajax": {
									"url": "'.$this->ajaxUrl.'",
									"dataSrc": "data"
								},
								"paging": true,
								"ordering": true,
								"searching": true,
								"autoWidth": false,
								"processing":'	.json_encode($this->processing).',
								"scrollX": '	.json_encode($this->scroll_x).',
								"rowId": "'		.$this->field_id. '",
								"lengthMenu": ' .$this->row_page_menu. ',
								"dom": '		.$this->_getJsProperty("dom"). ',
								"buttons": '	.$this->_getJsProperty("buttons"). ',
								"select": ' 	.$this->_getJsProperty("select"). ',
								"language": ' 	.$this->_getJsProperty("language"). ',
								"columnDefs": ' .$this->_getJsProperty("columnDefs"). ',
								"order": '		.$this->_getJsProperty("order"). ','.$cr;	
								
								if($this->rowsPerPage) $js .= '"length": '.$this->rowsPerPage.','.$cr;						
			
			$js .= 				'"columns": '	.$this->_getJsProperty("columns"). ' 
							} );							
						});

			</script>';
				
		echo $table.$js;

	}
	
	
	private function _getJsProperty($name) {
		//>> Restituisce la configurazione delle proprietà da inserire nel codice Javascript del componente
		
		$prop = '""';
		$i = 0;
		
		
		if ($name == "select") {
		
			if ($this->check_column)
				$prop = '{	"style":    "os",
							"selector": "td:first-child"}';
			else
				$prop = 'true';
			
			
		} elseif ($name == "dom") {
			
			if ($this->buttons) 
				$prop = '"lBfrtip"';
			else
				$prop = '"lfrtip"';


		} elseif ($name == "buttons" && $this->buttons) {
				
				//Recupero i pulsanti predefiniti
				$def_buttons = array_merge($this->sel_buttons, $this->exp_buttons);
				
				$prop = '[';

				foreach($this->buttons as $key => $extend){
					
					//Virgola di separazione
					$prop .= (strlen($prop) > 1 ? ', ' : '');
					
					if (in_array($key, $def_buttons)) {
						//Comandi di default
						
						if ($extend)
							$prop .= '{extend: "'.$key.'", '.$extend.'}';
						else
							$prop .= '"'.$key.'"';
						
					} else {
						//Comandi personalizzati
						$text 		= isset($extend['text']) 		? $extend['text'] 		: $extend;
						$id			= isset($extend['id']) 			? $extend['id'] 		: $key;
						$class 		= isset($extend['class']) 		? $extend['class'] 		: 'btn btn-default dt-custom-button';
						$callback 	= isset($extend['callback']) 	? $extend['callback'] 	: 'DtButtonClick';

						$prop .= '{	"text": "'.$text.'",
									"attr": { "id": "'.$id.'", "class": "'.$class.'" },
									"action": function ( e, dt, node, config ) {'.$callback.'(dt, node);} }';
					}
				}	
				
				$prop .= ']';

				
		} elseif ($name == "order") {	
			
			if ($this->check_column)		
				$prop = '[[ 1, "asc" ]]';
			else
				$prop = '[[ 0, "asc" ]]';
				
				
		} elseif ($name == "columnDefs" && $this->check_column) {				
					
				$prop = '[	{"orderable": false, "className": "select-checkbox", "targets": 0},
							{ "visible": true, "targets": 1 } ]';
							
		} elseif ($name == "columns") {
			$prop = '[';
								
				foreach($this->columns as $column) {
					$prop .= ($i==0 ? '' : ', ');
					
					if(!$column['visible']) 
						$prop .=  '{ "visible": false }';	
					else 
						$prop .= '{ "data": "'.$column['key'].'", "render": '.$column['render'].'}';
	
					$i++;
				} 
					
									
			$prop .= ']';

		} elseif ($name == "language") {
			
			$prop =	'{	"lengthMenu": 	  "_MENU_ righe per pagina",
						"info": 		  "Pagina _PAGE_ di _PAGES_",
						"infoFiltered":   "(filtrati da _MAX_ record totali)",
						"emptyTable":     "'.Lang::t('_NO_CONTENT', 'standard').'",
						"zeroRecords": 	  "'.Lang::t('_NO_CONTENT', 'standard').'",
						"infoEmpty": 	  "'.Lang::t('_NO_DATA', 'standard').'",
						"search":         "'.Lang::t('_SEARCH', 'standard').'",
						"paginate": {
							"first":      "'.Lang::t('_START', 'standard').'",
							"last":       "'.Lang::t('_END', 'standard').'",
							"next":       "'.Lang::t('_NEXT_B', 'standard').'",
							"previous":   "'.Lang::t('_PREV_B', 'standard').'"
						},
						"buttons": {
							"selectAll":  "'.Lang::t('_SELECT_ALL', 'standard').'",
							"selectNone": "'.Lang::t('_UNSELECT_ALL', 'standard').'",
						},
						"select": {
							"rows": "%d righe selezionate"
						}
					}';
		}
		

		return $prop;
	}
	
	
	private function _checkLibs($inLibs) {
		//>> Prepara la lista delle librerie necessarie
		
		$libs = "";
		$buttons = $this->key_buttons;

		if(strpos($inLibs, 'base') == false) 
			//Se nella lista non è contenuta la libreria base, la aggiungo
			$libs = 'base,';
		
		if ($this->check_column && !strpos($inLibs, 'select')) 
			//Se è richiesta la colonna check e non è richiesta la libreria, la aggiungo
			$libs .= 'select,';
		
		if ($buttons && !strpos($inLibs, 'buttons')) 
			//Se sono richiesti i bottoni e non è richiesta la libreria, la aggiungo
			$libs .= 'buttons,';
			
		if ($buttons && array_intersect($buttons, $this->exp_buttons) && !strpos($inLibs, 'buttons-export')) 
			//Se sono richiesti i bottoni di esportazione e non è richiesta la libreria, la aggiungo
			$libs .= 'buttons-export,';
		
		return $libs . $inLibs;
	}
	
}
/*-------------------------------------------
 * PROPRIETA' DATATABLES LINGUA
 * 
			  {
				"decimal":        "",
				"emptyTable":     "No data available in table",
				"info":           "Showing _START_ to _END_ of _TOTAL_ entries",
				"infoEmpty":      "Showing 0 to 0 of 0 entries",
				"infoFiltered":   "(filtered from _MAX_ total entries)",
				"infoPostFix":    "",
				"thousands":      ",",
				"lengthMenu":     "Show _MENU_ entries",
				"loadingRecords": "Loading...",
				"processing":     "Processing...",
				"search":         "Search:",
				"zeroRecords":    "No matching records found",
				"paginate": {
					"first":      "First",
					"last":       "Last",
					"next":       "Next",
					"previous":   "Previous"
				},
				"aria": {
					"sortAscending":  ": activate to sort column ascending",
					"sortDescending": ": activate to sort column descending"
				}
			}
 *  */

?>
