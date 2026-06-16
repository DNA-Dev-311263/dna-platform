<?php

	// Imposto il titolo della pagina
	echo getTitleArea(Lang::t('_COMMUNICATION_COURSE', 'menu'));
	
	$q_str		= '&id_org='.$id_org;
	$ajax_url	= 'ajax.adm_server.php?r='.$base_link_commcourse.'/getCourseJson';

?>

<div class="std_block">
	<div class="data_control" id="commcourse_panel" style="display: block;">
		
	<?php
		/*---------------------------- PANNELLO COMANDI ----------------------------*/
	
		$html =  Form::openForm('form_operation', '')
				.Form::openCollasableFieldset(Lang::t('_COMMANDS', 'standard'))
				
				
				.Form::getOpenCombo(Lang::t('_SELECT_COMMCOURSE', 'commcourse'), 'form_line_l comm_list_box')
				.Form::getLineDropdown('exct_filt_param', 'lbl_f_par', Lang::t('_NAME', 'standard'), 'sel_f_par', 'sel_comm', 'exct_code', $list, $sel_comm, '','','')
				.Form::getBreakRow()
				.Form::getCloseCombo()
				
				
				.Form::getOpenCombo(Lang::t('_FILTERS', 'commcourse'), 'form_line_l');
				if($is_godadmin) {
					$html .= Form::getLineDropdown('comm_filt_param', 'lbl_f_par', Lang::t('_ORGANIZATION', 'courseassn'), 'sel_f_par', 'sel_id_org', 'id_org', $model->getOrgForDropdown(), $id_org, '','','');
				} else {
					$html .= Form::getHidden('sel_id_org', 'id_org', $id_org);
				}
				
		$html .= Form::getLineDatefield('comm_filt_param date-picker', 'lbl_f_par', Lang::t('_FROM_DATE', 'commcourse'), 'txt_f_par', 'txt_date_from', 'date_from', '', false, '','','','')
				.Form::getCloseCombo()
				
				.Form::getOpenCombo('')
				.Form::openButtonSpace()
				.Form::getButton('btn_send', 'send', Lang::t('_SEND', 'standard'))
				.Form::closeButtonSpace()
				.Form::getCloseCombo()
				
				
				.Form::getCloseFieldset()
				.Form::closeForm();

		echo $html;
		
	?>
	</div><!-- chiusura div commcourse_panel -->
	
	<div id = "course_list">

	<?php
	/*-------------------------- PREPARAZIONE WIDGET --------------------------*/
		
		// Inserisco il Widget
		$_params = array(
			'id'			=> 'course_list_table',
			'check_column'	=> true,
			'ajaxUrl'		=> $ajax_url . $q_str,
			'inlineSyle'	=> 'width:100%;',
			'columns'		=> $tableInfo->columns,
			'field_id'		=> 'idCourse',
			'buttons'		=> array('selectAll', 'selectNone'),
			'inlineStyle'	=> 'display:block;',
			'row_page_menu'	=> '[ [50, 200, 500, -1], [50, 200, 500, "All"] ]'
		);
		
		$this->widget('datatables', $_params);

	/*-------------------------------------------------------------------------*/ 

	?>
	
	</div><!-- chiusura div course_list-->
</div><!-- chiusura div std_block-->

<script type="text/javascript">
	
Commcourse.init({
	idOrg: document.getElementById('sel_id_org').value,
	selComm: document.getElementById('sel_comm').value,
	
	baseLink: 	"<?php echo $base_link_commcourse; ?>",
	ajaxUrl:	"<?php echo $ajax_url ?>",
	perms: {
		view: 	 <?php echo $permissions['view'] ? 'true' : 'false'; ?>
	},
	langs: {
		_OPERATION_SUCCESSFUL: 	"<?php echo Lang::t('_OPERATION_SUCCESSFUL', 'standard'); ?>",
		_OPERATION_FAILURE: 	"<?php echo Lang::t('_OPERATION_FAILURE', 'subscribe'); ?>",
		_AREYOUSURE: 			"<?php echo Lang::t('_AREYOUSURE', 'standard'); ?>",
		_CLOSE: 				"<?php echo Lang::t('_CLOSE', 'standard'); ?>",
		_CONFIRM: 				"<?php echo Lang::t('_CONFIRM', 'standard'); ?>",
		_UNDO: 					"<?php echo Lang::t('_UNDO', 'standard'); ?>",
		_EMPTY_SELECTION: 		"Devi selezionare un corso",
		_NO_COURSES:			"Non ci sono corsi",
		_SUCCESS:				"Sono state inviate [communication_count] comunicazioni.",
		_NO_DATA:				"Non ci sono dati per questa azione",
		_CONFIRM_ACTION: 		"Operazione richiesta: <b>[operation]</b><br/>[related_desc] interessati <b>[related_count]</b>. Comunicazioni da inviare <b>[communication_count]</b>. Confermi?"
		
	}
});

</script>
