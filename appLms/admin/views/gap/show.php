<?php 
	
	// Recupero id organizzazione da modello
	$id_org = $model->getIdOrg();
	
	// Imposto il titolo della pagina
	echo getTitleArea(Lang::t('_MANAGE_GAP', 'menu'));
	
	
/*-------------------------- FILTRI DI INTESTAZIONE TABELLA --------------------------*/

?>

<div class="std_block">
	<div id="resizablepanel"></div>
	
	<div class="data_control" id="gap_filter" style="display: block;">
		<?php
			// Preparo combo per filtri
			$curr_year = date('Y');
			$min_year = $model->getGapYearMin();
			
			for ($i = date('Y'); $i >= $min_year-1; $i--) {
				$arr_year[$i] = $i;
				if(!$min_year) break;
			}
			// Filtro anno
			echo Form::getLineDropdown('filt_param', 'lbl_f_par', Lang::t('_YEAR', 'standard'), 'sel_f_par', 'filter_year', 'filter_year', $arr_year, $curr_year, '','','');
			
			// Filtro organizzazione
			if($is_godadmin) {
				echo Form::getLineDropdown('filt_param', 'lbl_f_par', Lang::t('_ORGANIZATION', 'gap'), 'sel_f_par', 'filter_org', 'filter_org', $model->getOrgForDropdown(), $id_org, '','','');
			} else {
				echo Form::getHidden('filter_org', 'filter_org', $id_org);
			}
		?>
	</div>
	<div class="quick_search_form search_spacer">
		<div>
			<?php
				echo Form::getInputTextfield("search_t", "filter_text", "filter_text", $filter_text, '', 255, '' );
				echo Form::getButton("filter_set", "filter_set", Lang::t('_SEARCH', 'standard'), "search_b");
				echo Form::getButton("filter_reset", "filter_reset", Lang::t('_RESET', 'standard'), "reset_b");
			?>
		</div>
	</div>

<?php

/*-------------------------- COLONNE DEL DATATABLE --------------------------*/


	// Ciclo per preparare le intestazioni di colonne condivise
	$dyn_labels = array();
	$dyn_filter = array();
	
	for ($i=0; $i<$num_var_fields; $i++) {
		
		//Preparo array delle intestazioni per la creazione delle colonne dinamiche (codice html)
		$label = '<select id="_dyn_field_selector_'.$i.'" name="_dyn_field_selector['.$i.']">';
		
		foreach ($fields['dyn_fields'] as $element) {
			
				$label .= '<option sortable ="'.$element['sortable'].'" value="'.$element['key'].'"';
				
				if ($element['selected_on_col'] === $i){
					$selected[$i] = $element['key'];
					$label .= ' selected="selected"';
				}
				$label .='>'.$element['label'].'</option>';
		}
		
		$label .= '</select>';
		$label .= '<a id="_dyn_field_sort_'.$i.'" href="javascript:;">';
		$label .= '<img src="'.Get::tmpl_path().'images/standard/sort.png" title="'.Lang::t('_SORT', 'standard').'" alt="'.Lang::t('_SORT', 'standard').'" />';
		$label .= '</a>';
			
		$dyn_filter[$i] = $selected[$i];
		$dyn_labels[$i] = $label;
	}
	
	
	// Ciclo per preparare la lista di campi in colonna condivisa da inviare alla libreria Javascript (per gestione cambio campo)
	$js_arr = array();
	
	foreach ($fields['dyn_fields'] as $element) {
		$js_arr[] = $element['key'].': '.$this->json->encode($element['label']);
	}
	
	$fieldlist_js = '{'.implode(',', $js_arr).'}';
	
	
	// Preparo le colonne (statiche + dinamiche)  da inviare al widget e completo chiavi per lettura dati .
	$columns = $fields['static_fields'];
	
	for ($i=0; $i<$num_var_fields; $i++) {
		
		// Colonna condivisa: chiave e descrizione. La chiave verrà passata dal widget al controller dopo il cambio di campo (refresh)
		$columns[] = array('key' => '_dyn_field_'.$i, 'label' => $dyn_labels[$i]);
		
		// Chiave per la lettura del dato nell'array inviato dal controller
		$fields['field_keys'][] = '_dyn_field_'.$i;
		
	}
	
	
	// Completo le colonne con l'aggiunta della colonna di eliminazione
	if ($permissions['mod']) {
		$columns[] = array('key' => 'del', 'label' => Get::img('standard/delete.png', Lang::t('_DEL', 'subscribe')), 'formatter'=>'doceboDelete', 'className' => 'img-cell');
	}


/*-------------------------- PREPARAZIONE WIDGET --------------------------*/


	// Preparo i parametri per il widget
	$_params = array(
		'id'			=> 'gap_table',
		'ajaxUrl'		=> 'ajax.adm_server.php?r='.$base_link_gap.'/getGapList',
		'rowsPerPage'	=> Get::sett('visuItem', 25),
		'startIndex'	=> 0,
		'results'		=> Get::sett('visuItem', 25),
		'row_per_page_select' => '[25,100,200,500,1000]',
		'sort'			=> 'date_ins',
		'dir'			=> 'desc',
		'columns'		=> $columns,
		'fields'		=> $fields['field_keys'],
		'stdSelection' => true,
		'selectAllAdditionalFilter' => 'Gap.selectAllFilter()',
		'delDisplayField' => 'description',
		'generateRequest' => 'Gap.requestBuilder',
		'events' => array(
			'initEvent' => 'Gap.initEvent',
			'beforeRenderEvent' => 'Gap.beforeRenderEvent',
			'postRenderEvent' => 'Gap.postRenderEvent'
		)
	);

	
	// Form invisibile per esportazione dati
	
	echo Form::openForm('csv_form', "index.php?r=".$base_link_gap."/csvexport");
	echo Form::getHidden('csv_operation', 'operation', '');
	echo Form::getHidden('csv_input', 'data', '');
	echo Form::closeForm();

	
	// Link azioni da aggiungere ai params del widget
	
	// - link javascript per apertura form di importazione
	$onclick_js = "location.href='index.php?r=".$base_link_gap."/insertGapWizard&";
	
	if($is_godadmin){
		// -- recupero organizzazione della combo
		$onclick_js .= "id_org='+ document.getElementById('filter_org').value;";
	} else {
		// -- unica organizzazione ammessa
		$onclick_js .= "id_org=".$id_org."';";
	}
	
	
	if ($permissions['mod'])
		$rel_action = '<a class="ico-wt-sprite subs_import" href="#" onclick="'.$onclick_js.'"><span>'.Lang::t('_IMPORT', 'subscribe').'</span></a>';

	if ($permissions['mod']) 	
		$rel_action .= '<a class="ico-wt-sprite subs_del" href="ajax.adm_server.php?r='.$base_link_gap.'/multidel"><span>'.Lang::t('_DEL_SELECTED', 'subscribe').'</span></a>';
		

	$rel_action_top = '<button id="ma_over" name="ma"></button>';
	$rel_action_bottom = '<button id="ma_bottom" name="ma"></button>';
	
	$rel_action_top .= '&emsp; <span><b id="items_selected_top"> 0 </b> '.Lang::t('_SELECTED', 'admin_directory').'</span> &emsp;';
	$rel_action_bottom .= '&emsp; <span><b id="items_selected_bottom"> 0 </b> '.Lang::t('_SELECTED', 'admin_directory').'</span> &emsp;';
	
	
	// Termino array parametri con l'aggiunta dei comandi sopra e sotto tabella
	$_params['rel_actions'] = array($rel_action.$rel_action_top, $rel_action.$rel_action_bottom);


	// Passo tutto al widget
	$this->widget('table', $_params);

?>
</div>

<script type="text/javascript">

$(document).ready(function() {
	//Messaggio di conferma fadeout 
	var div_feedback = YAHOO.util.Dom.getElementsByClassName('container-feedback');              

	if (div_feedback.length > 0) {
		$( "#" + div_feedback[0].id ).fadeOut( 4000, function() {});
	}
});

Gap.init({
	idOrg: document.getElementById('filter_org').value,
	selYear: document.getElementById('filter_year').value,
	
	baseLink: "<?php echo $base_link_gap; ?>",
	templatePath: "<?php echo Get::tmpl_path(); ?>",
	statusList: <?php echo $status_list_js; ?>,
	cataList: <?php echo $cata_list_js; ?>,
	dynSelection: {},
	fieldList: <?php echo $fieldlist_js; ?>,
	numVarFields: <?php echo $num_var_fields; ?>,
	perms: {
		mod_gap: <?php echo $permissions['mod'] ? 'true' : 'false'; ?>,
		view_gap: <?php echo $permissions['view'] ? 'true' : 'false'; ?>
	},
	langs: {
		_GAPS: "<?php echo Lang::t('_GAPS', 'standard'); ?>",
		_SORT: "<?php echo Lang::t('_SORT', 'standard'); ?>",
		_OPERATION_SUCCESSFUL: "<?php echo Lang::t('_OPERATION_SUCCESSFUL', 'standard'); ?>",
		_OPERATION_FAILURE: "<?php echo Lang::t('_OPERATION_FAILURE', 'subscribe'); ?>",
		_MORE_ACTIONS: "<?php echo Lang::t('_MORE_ACTIONS', 'admin_directory'); ?>",
		_EXPORT_CSV: "<?php echo Lang::t('_EXPORT_CSV', 'admin_directory'); ?>",
		_EXPORT_GAP_ACTIVE:  "<?php echo Lang::t('_EXPORT_GAP_ACTIVE', 'Gap'); ?>",
		_UPDATE_GAP: "<?php echo Lang::t('_UPDATE_GAP', 'Gap'); ?>",
		_CANCEL_GAP_ACTIVE: "<?php echo Lang::t('_CANCEL_GAP_ACTIVE', 'Gap'); ?>",
		_AREYOUSURE: "<?php echo Lang::t('_AREYOUSURE', 'standard'); ?>",
		_EMPTY_SELECTION: "<?php echo Lang::t('_EMPTY_SELECTION', 'standard'); ?>",
		_DEL: "<?php echo Lang::t('_DEL', 'standard'); ?>",
		_CLOSE: 			"<?php echo Lang::t('_CLOSE', 'standard'); ?>",
		_CONFIRM: 			"<?php echo Lang::t('_CONFIRM', 'standard'); ?>",
		_UNDO: 				"<?php echo Lang::t('_UNDO', 'standard'); ?>"			
	}
});

</script>
