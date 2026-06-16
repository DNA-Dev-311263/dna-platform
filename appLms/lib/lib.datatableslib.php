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
\ ======================================================================== */

/** 
 * ABR
 * The purpose of this class is to contain method that will help in the use of
 * ajax and web 2.0 interface using the datatables libraries.
 * Dalla versione di formalms 3 alcune librerie sono già caricate su tutte le pagine: DataTables 1.10.18, Buttons 1.5.4, HTML5 export 1.5.4, Responsive 2.2.2, RowGroup 1.1.0, Select 1.2.6
 * !!! Verificare se occorrono alcune librerie dinamiche caricate in base alla configurazione
 */

class DatatablesLib {

	private static $_css_loaded = array('datatables.css'); //already loaded

	private static $_js_loaded = array('datatables.js');   //already loaded

	private static $_css_map = array(
		'base' => array(
            //'DataTables-1.10.18/css/jquery.dataTables.min.css'
		),
		'select' => array(
            'DataTables-1.10.18/css/select.dataTables.min.css'

		)
	);

	private static $_js_map = array(
		'base' => array(
			'DataTables-1.10.18/js/moment.js',
			'DataTables-1.10.18/js/datetime-moment.js'
		),
		'select' => array(
			'DataTables-1.10.18/js/dataTables.select.min.js'
		),
		'buttons' => array(
			'DataTables-1.10.18/js/dataTables.buttons.min.js'
		),
		'buttons-export' => array(
			'DataTables-1.10.18/js/buttons.html5.min.js',
			'DataTables-1.10.18/js/jszip.min.js'
		)
	);

	/**
	 * Load css and js
	 * @return null
	 * @param $js Array[optional]
	 * @param $css Array[optional]
	 */
	public static function load($module_list = false, $noprint = false) {
	
		$list = explode(',', $module_list);

		$js_load = array();
		$css_load = array();
		
		foreach($list as $k => $module) {

			if(isset(self::$_css_map[$module])) $css_load = array_unique(array_merge($css_load, self::$_css_map[$module]));
			if(isset(self::$_js_map[$module])) $js_load = array_unique(array_merge($js_load, self::$_js_map[$module]));
		}
		
		// remove js alredy loaded
		$css_load = array_diff($css_load, self::$_css_loaded);
		$js_load = array_diff($js_load, self::$_js_loaded);

		if(empty($css_load) && empty($js_load)) return '';

		// load new css
		$to_load = '';
		if(!empty($css_load)) {
			$to_load .= PHP_EOL.'<!-- datatables css -->';
			foreach($css_load as $k => $filename) {
				
				$to_load .= Util::get_js('/addons/jquery/datatables/'.$filename);
			}
		}
		// load new js
		if(!empty($js_load)) {
			$to_load .= PHP_EOL.'<!-- datatables js -->';
			foreach($js_load as $k => $filename) {

				$to_load .= Util::get_js('/addons/jquery/datatables/'.$filename);
				
			}
		}
		// add loaded file to the cache
		if(!empty($css_load)) self::$_css_loaded = array_merge(self::$_css_loaded, $css_load);
		if(!empty($js_load)) self::$_js_loaded = array_merge(self::$_js_loaded, $js_load);

		if(function_exists('cout') && !$noprint) cout($to_load, 'page_head');
		else return $to_load;
	}

	/**
	 * @return html to attach to the output
	 * @param $pattern String patter for the selector
	 * @param $title String[optional] the dialog title
	 * @param $text String[optional] the dialog text
	 * @param $confirm String[optional] the confirm button label
	 * @param $undo String[optional] the undo button label
	 */
	public static function attachHrefDialog($pattern, $title = false, $text = false, $confirm = false, $undo = false) {}


}

?>
