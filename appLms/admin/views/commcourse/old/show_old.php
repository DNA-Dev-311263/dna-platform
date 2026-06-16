<?php

	// Imposto il titolo della pagina
	echo getTitleArea(Lang::t('_COMMUNICATION_COURSE', 'menu'));
	
	$nota_date_from = $tableInfo->defaults['nota_date_from'];
	$rema_date_from = $tableInfo->defaults['rema_date_from'];
	
	$ajax_url = 'ajax.adm_server.php?r='.$base_link_commcourse.'/getCourseJson&id_org='.$id_org;

	
?>

<style>
	#commcourse_panel div.form_line_l {
		margin-top: 4px;
	}
	
	#commcourse_panel div.grouping {
		width: 250px;
	}
	
	#commcourse_panel div.form_elem_button {
		padding: 0px;
		text-align: left;
	}
	
	#commcourse_panel input[type="submit"] {
		width: 250px;
	}
	
	#commcourse_panel input {
		margin-top: 0px;
		margin-left:0px;
		margin: 0px 0px 4px 0px;
	}
	
	button.buttons-select-none {
		margin-left: 4px;
	}
	button.buttons-select-all {
		margin-left: 20px;
	}
	table.dataTable.no-footer {
		border-bottom: 1px solid #DDDDDD;
		padding-bottom: 1px;
	}
	
</style>

<div class="std_block">
	<div class="data_control" id="commcourse_panel" style="display: block;">
		
	<?php
		/*---------------------------- PANNELLO COMANDI ----------------------------*/
	
		$html = Form::openForm('form_operation', '');
		
		if($is_godadmin) {
			
			$html .= Form::getDropdown('comm_filt_param', 'lbl_f_par', Lang::t('_ORGANIZATION', 'courseassn'), 'sel_f_par', 'sel_id_org', 'id_org', $model->getOrgForDropdown(), $id_org);
			$html .= Form::getBreakRow();
			
		} else {
			
			$html .= Form::getHidden('sel_id_org', 'id_org', $id_org);
			
		}
		
		$html .= Form::openCollasableFieldset(Lang::t('_COMMANDS', 'standard'))
		
		.Form::getOpenCombo(Lang::t('_REMINDER_GAP_ASSN', 'commcourse'), 'form_line_l')
		.Form::getDatefield('comm_filt_param date-picker', 'lbl_f_par', Lang::t('_FROM_DATE_ASSN', 'commcourse'), 'txt_f_par', 'txt_rema_date_from', 'rema_date_from', '30-11-2019', false) 
		.Form::openButtonSpace()
		.Form::getButton('btn_reminder_assn', 'reminder_gap_assn', Lang::t('_SEND', 'standard'))
		.Form::closeButtonSpace()
		.Form::getCloseCombo()
		.'<hr />'
		.Form::getOpenCombo(Lang::t('_NEW_EDITION', 'commcourse'), 'form_line_l')
		.Form::openButtonSpace()
		.Form::getButton('btn_new_edition', 'new_edition', Lang::t('_SEND', 'standard'))
		.Form::closeButtonSpace()
		.Form::getCloseCombo()
		
		.Form::getOpenCombo(Lang::t('_REMINDER_SUBS', 'commcourse'), 'form_line_l')
		.Form::openButtonSpace()
		.Form::getButton('btn_reminder_subs', 'reminder_subs', Lang::t('_SEND', 'standard'))
		.Form::closeButtonSpace()
		.Form::getCloseCombo()
		
		.Form::getOpenCombo(Lang::t('_NOTICE_ASSN', 'commcourse'), 'form_line_l')
		.Form::getDatefield('comm_filt_param date-picker', 'lbl_f_par', Lang::t('_FROM_DATE_ASSN', 'commcourse'), 'txt_f_par', 'txt_nota_datefrom', 'nota_date_from', $nota_date_from, false) 
		.Form::openButtonSpace()
		.Form::getButton('btn_notice_assn', 'notice_assn', Lang::t('_SEND', 'standard'))
		.Form::closeButtonSpace()
		.Form::getCloseCombo()
		
		.Form::getHidden('sel_operation', 'operation', '')
		.Form::getHidden('sel_course', 'course_list', '')
		
		.Form::getCloseFieldset()
		.Form::closeForm();
		
		
		echo $html;
		
	?>
	</div><!-- chiusura div commcourse_panel -->
	
	<div id="loadbar" class="loadbar" style="padding-bottom:30px; display:none;"></div>
	<div id = "course_list">

	<?php
	/*-------------------------- PREPARAZIONE WIDGET --------------------------*/
		
		// Inserisco il Widget
		$_params = array(
			'id'			=> 'course_list_table',
			'check_column'	=> true,
			'ajaxUrl'		=> $ajax_url,
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

<div id="container-feedback" class="container-feedback" style="display:none;"></div>

<script type="text/javascript">
	
	function sendFeedback(msg){
		var div_feedback = $("#container-feedback").get(0);	              
		
		if (msg == "nodata") msg = "Non ci sono dati per i criteri richiesti.";
		
		var pos = $(document).scrollTop() + 60;
		
		div_feedback.innerHTML = msg;
		div_feedback.style.top = pos+'px';
		div_feedback.style.display = "block";

		$( "#" + div_feedback.id ).fadeOut( 4000, function() {});
	}
	
	function successMessage(data) {
		var msg = "Sono state inviate " + data + " comunicazioni."
		sendFeedback(msg);
	}
	
	function getSel(tbl) {
		var list = "";
		
		if (tbl.rows().count() == 0) {
			sendFeedback("Non ci sono corsi.");
			
		} else {
			var selRows = tbl.rows({selected: true});
				
			selRows.every(function () {list += this.id() + ","});
			list = list.substring(0,list.length-1);
			
			if (list.length == 0) 
				sendFeedback("Devi selezionare un corso.");
		}
		return list;
	}
	

	$( document ).ready(function() {
	
		var aflds = document.getElementsByClassName("filedset-av");
		var table = $('#course_list_table').DataTable();
		var tab_url = "<?php echo $ajax_url ?>";
		var form_operation = $("#form_operation");
		var sbm = new formSubmission(form_operation);
		
		sbm.base_link = "<?php echo $base_link_commcourse ?>";
		
		//Apro i parametri
		aflds[0].click();
		
		//Funzione per gestire il cambiamento di azienda
		$("#sel_id_org").change(function() {
			
			var id_org =  $(this).val();

			tab_url = tab_url.substr(0, tab_url.indexOf("&"));
			tab_url = tab_url + "&id_org=" + id_org;
			table.ajax.url(tab_url).load();	
		});
		
		//Funzione per gestire la richiesta di operazione
		$("#btn_reminder_assn, #btn_notice_assn, #btn_new_edition, #btn_reminder_subs").click(function () {
			$("#sel_operation").val(this.name);		
		});
		
		//Funzione per gestire il submit del form
		$( "#form_operation" ).submit(function( event ) {
			
			var op = $("#sel_operation").val();
			var msg = ""
			
			//Fermo l'evento submit
			event.preventDefault();
			
			if(op == 'reminder_gap_assn') {
				msg = "Cataloghi interessati";
	
			} else {
				list = getSel(table); if(!list) return;
				$( "#sel_course" ).val(list);
				
				msg = "Corsi interessati";
			}

			sbm.infoAction(function(info){
				if (info.communication == "0") {
					sendFeedback("Non ci sono dati per l'azione richiesta.");	
			
				} else {
					msg = "Operazione richiesta: <b>" + info.operation + "</b><br/>"+msg+" <b>" + info.related + "</b>. Comunicazioni da inviare <b>"  + info.communication + "</b>. Confermi?";
					sbm.confirm_hdtext = "Conferma";
					sbm.confirm_text = msg;
					sbm.loadbar = $("#loadbar");
					sbm.success_callback =  function(data) { sendFeedback("Sono state inviate " + data + " comunicazioni.") }
					
					sbm.submitDialog();
				}				
			});			
		});
	});

</script>
