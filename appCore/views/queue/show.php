<?php 
	$today = Format::date(date("Y-m-d"), 'date');
	
	$lb_button['delSel'] = Lang::t('_DEL_SELECTED', 'standard');
	$lb_button['expDet'] = Lang::t('_EXPORT_DETAIL', 'standard');
	$lb_button['refresh'] = Lang::t('_UPDATE', 'standard');
	$lb_button['restart'] = Lang::t('_RESTART', 'standard');

	$q_str		= '&date_from='.$today.'&status=-1';
	$ajax_url	= 'ajax.adm_server.php?r='.$base_link_queue.'/getRegisterList';

	echo getTitleArea(Lang::t('_LOG_QUEUE', 'menu')); 

?>

<div class="std_block">
	<div class="" id="queue_filter">
	<?php 
	
	/*---------------------------- PANNELLO FILTRI ----------------------------*/
	
		$html = "";
				
		$html .= Form::getLineDatefield('queue_filt_param', 'lbl_f_par', Lang::t('_FROM_DATE', 'commcourse'), 'txt_f_par', 'txt_date_from', 'date_from', $today, false, '','','','');
		
		$html .= Form::getLineDropdown('queue_filt_param', 'lbl_f_par', Lang::t('_STATUS', 'standard'), 'sel_f_par', 'sel_status', 'status', $model->getStatusForDropdown(), -1, '','','');
				
		echo $html;
	?>
	</div>
	<div id = "queue_list">
	<?php
	
	/*-------------------------- PREPARAZIONE WIDGET --------------------------*/
		// Pulsanti del datatable
		$buttons = array(	'selectAll' => '', 
							'selectNone'=> '', 
							'expDet' => $lb_button['expDet'],
							'refresh' => $lb_button['refresh']
						);
		
		if ($permissions['mod'])
			$buttons['restart'] = $lb_button['restart'];
			
		if ($permissions['del'])
			$buttons['delSel'] = $lb_button['delSel'];
			
		
		// Inserisco il Widget
		$_params = array(
			'id'			=> 'queue_list_table',
			'check_column'	=> true,
			'ajaxUrl'		=> $ajax_url . $q_str,
			'columns'		=> $tableInfo,
			'field_id'		=> 'id_queue',
			'buttons'		=> $buttons,
			'inlineSyle'	=> 'display:block;width:100%;',
			'row_page_menu'	=> '[ [50, 200, 500, -1], [50, 200, 500, "All"] ]'
		);
		
		$this->widget('datatables', $_params);
		
		
		// Item array per esportazione dati di tabella in excel (comando integrato, non usato)
		//'excel' => 'text: "Esporta", exportOptions: { modifier: { selected: true} }',
	?>
	</div><!-- chiusura div queue_list-->
	
	<?php
	
	/*-------------------------- FORM ESPORTAZIONE --------------------------*/

		echo Form::openForm('export_form', "index.php?r=".$base_link_queue."/exportTaskDetail");
		echo Form::getHidden('export_input', 'data', '');
		echo Form::closeForm();
	?>
	
</div>

<script type="text/javascript">
	
function DtButtonClick(dt, node) {
	switch(node.attr('id')) {
		case 'delSel':
			Queue.delItems();
			break;
			
		case 'expDet':
			Queue.exportTaskDetail();
			break;
			
		case 'refresh':
			Queue.refreshTable();
			break;
			
		case 'restart':
			Queue.restart();
			break;
			
		default:
	} 
} 
	
Queue.init({
	selStatus: document.getElementById('sel_status').value,
	dateFrom: document.getElementById('txt_date_from').value,
	
	baseLink: 	"<?php echo $base_link_queue; ?>",
	ajaxUrl:	"<?php echo $ajax_url ?>",
	perms: {
		view: 	 <?php echo $permissions['view'] ? 'true' : 'false'; ?>,
		mod: 	 <?php echo $permissions['mod'] ? 'true' : 'false'; ?>,
		del: 	 <?php echo $permissions['del'] ? 'true' : 'false'; ?>
	},
	langs: {
		_OPERATION_SUCCESSFUL: 	"<?php echo Lang::t('_OPERATION_SUCCESSFUL', 'standard'); ?>",
		_OPERATION_FAILURE: 	"<?php echo Lang::t('_OPERATION_FAILURE', 'subscribe'); ?>",
		_AREYOUSURE: 			"<?php echo Lang::t('_AREYOUSURE', 'standard'); ?>",
		_CLOSE: 				"<?php echo Lang::t('_CLOSE', 'standard'); ?>",
		_CONFIRM: 				"<?php echo Lang::t('_CONFIRM', 'standard'); ?>",
		_UNDO: 					"<?php echo Lang::t('_UNDO', 'standard'); ?>",
		_DEL: 					"<?php echo Lang::t('_DEL', 'standard'); ?>",
		_DEL_SELECTED:			"<?php echo $lb_button['delSel']; ?>",
		_RESTART:				"<?php echo $lb_button['restart']; ?>",
		_EMPTY_SELECTION: 		"<?php echo Lang::t('_EMPTY_SELECTION', 'standard'); ?>"
	}
});

</script>
