<?php 
	
	// Imposto il titolo della pagina
	echo getTitleArea(Lang::t('_EXTRACTION', 'menu'));
	

/*-------------------------- FILTRI DI INTESTAZIONE TABELLA --------------------------*/

?>
<div class="std_block">
	<div class="data_control" id="extraction_filter" style="display: block;">
		<?php
		
		//Imposto i criteri di default
		$def_date = array();
		$sel_exct = "";
		$ajax_url = "";
		$id_org = $model->getIdOrg();
		
		
		
		switch ($postData->mode)
		{
			case 2:
				$sel_exct = $postData->exct_code;
				$def_date[0] = $postData->date_from;
				$def_date[1] = $postData->date_to;
				$ajax_url = 'ajax.adm_server.php?r='.$base_link_extraction.'/getExtractionJson&exct_code='.$sel_exct.'&mode='.$postData->mode
													.'&date_from='.$def_date[0].'&date_to='.$def_date[1].'&id_org='.$id_org.'&id_group='.$postData->id_group.'&status='.$postData->status.'&ctm_field='.$postData->ctm_field;
				
				
				break;
			case 1:
				$sel_exct = $postData->exct_code;
				$def_date[0] = $postData->date_from;
				$def_date[1] = $postData->date_to;
			
				break;
			case 0:
				$sel_exct = $props->code;
				$def_date[0] = $props->defaults['date_from'];
				$def_date[1] = $props->defaults['date_to'];	
				
		}								

		// Creo il form di selezione
		$html_ctl = "";
	
		$html_ctl .= Form::openForm('form_extraction', '')
					.Form::openCollasableFieldset(Lang::t('_PARAMS', 'extraction'))
					
					.Form::getOpenCombo(Lang::t('_SELECT_EXTRACTION', 'extraction'), 'form_line_l exct_list_box')
					.Form::getLineDropdown('exct_filt_param', 'lbl_f_par', Lang::t('_NAME', 'standard'), 'sel_f_par', 'sel_exct_code', 'exct_code', $list, $sel_exct, '','','')
					.Form::getBreakRow()
					.Form::getCloseCombo()
					
					.Form::getOpenCombo(Lang::t('_FILTERS', 'extraction'));
					
					if($is_godadmin) {
						$html_ctl .= Form::getLineDropdown('exct_filt_param', 'lbl_f_par', Lang::t('_ORGANIZATION', 'courseassn'), 'sel_f_par', 'sel_id_org', 'id_org', $model->getOrgForDropdown(), $id_org, '','','');
					} else {
						$html_ctl .= Form::getHidden('exct_filter_org', 'id_org', $id_org);
					}
					

		$html_ctl .= Form::getLineDropdown('container-hide', 'lbl_f_par', Lang::t('_GROUP', 'standard'), 'sel_f_par', 'sel_id_group', 'id_group', $model->getGroupForDropdown(), $postData->id_group, '','','')
		
					.Form::getLineDatefield('container-hide exct_filt_param date-picker', 'lbl_f_par', Lang::t('_DATE_BEGIN', 'extraction'), 'txt_f_par', 'txt_date_from', 'date_from', '', false, '','','','') 
					.Form::getLineDatefield('container-hide exct_filt_param date-picker', 'lbl_f_par', Lang::t('_DATE_END', 'extraction'), 'txt_f_par', 'txt_date_to', 'date_to', '', false, '', '','','')
					.Form::getLineDropdown('container-hide exct_filt_param', 'lbl_f_par', Lang::t('_STATUS', 'standard'), 'sel_f_par', 'sel_status', 'status', $model->getStatusForDropdown($sel_exct), $postData->status, '','','')	
					.Form::getLineCheckbox('container-hide exct_filt_param', 'lbl_f_par',  Lang::t('_CUSTOM_FIELDS', 'extraction'), 'chk_ctm_field', 'ctm_field', 1, $postData->ctm_field, '','','')
					.Form::getCloseCombo()

					.Form::getOpenCombo(Lang::t('_OPTIONS', 'extraction'))
					.Form::getLineRadio('exct_filt_param radio', 'lbl_f_par', Lang::t('_EXPORT_TO_FILE', 'extraction'), 'opt_export', 'mode', '1', ($postData->mode < 2))
					.Form::getLineRadio('exct_filt_param radio', 'lbl_f_par', Lang::t('_SHOW_IN_TABLE', 'extraction'), 'opt_view', 'mode', '2', ($postData->mode == 2))
					.Form::getCloseCombo()
					
					.Form::openButtonSpace()
					.Form::getButton('btn_extract', 'extract', Lang::t('_EXTRACT', 'extraction'))
					.Form::closeButtonSpace()
					
					.Form::getCloseFieldset()
					.Form::closeForm();
	
		echo $html_ctl;
		
		?>
	</div><!-- chiusura div extraction_filter -->
	<div id = "container_report">

	<?php
	/*-------------------------- PREPARAZIONE WIDGET --------------------------*/

		if($postData->mode == 2) {
			
			// Inserisco titolo tabella
			echo '<div id="exct_table_title">'.$props->title.'</div>';
			
			// Inserisco il Widget
			$_params = array(
				'id'			=> 'extraction_table',
				'ajaxUrl'		=> $ajax_url,
				'inlineSyle'	=> 'width:100%;display:none;',
				'columns'		=> $tableInfo->columns,
				'scroll_x'		=> true,
				'buttons'		=> [ "excelHtml5"=>"", "copyHtml5"=>"text:'".Lang::t('_COPY', 'field')."'" ],
				'row_page_menu'	=> '[ [50, 200, 500, -1], [50, 200, 500, "All"] ]'
			);
			
			$this->widget('datatables', $_params);
		}

	/*-------------------------------------------------------------------------*/ 

	?>
	</div><!-- chiusura div container_report-->
</div><!-- chiusura div std_block-->

<div id="container-feedback" class="container-feedback" style="display:none;"></div>


<script type="text/javascript">
	
	function sendFeedback(msg){
		var div_feedback = $("#container-feedback").get(0);	              
		
		if (msg == "nodata") msg = "Non ci sono dati per i criteri richiesti.";
		
		div_feedback.innerHTML = msg;
		div_feedback.style.display = "block";
		$( "#" + div_feedback.id ).fadeOut( 3000, function() {});
	}
	
		
	function setVisibleParams(arrFilt){
		
		//> Campi custom
		if (arrFilt.includes('ctm_field') ) {
			// Rendo visibile
			document.getElementById("chk_ctm_field").parentNode.style.display = "block";
			
		} else {
			// Nascondo
			document.getElementById("chk_ctm_field").parentNode.style.display = "none";
		}
		
		//> Stato
		if (arrFilt.includes('status') ) {
			// Rendo visibile
			document.getElementById("sel_status").parentNode.style.display = "block";
			
		} else {
			// Nascondo
			document.getElementById("sel_status").parentNode.style.display = "none";
		}
		
		//> Date
		if (arrFilt.includes('date')) {
			// Rendo visibile
			document.getElementById("txt_date_from").parentNode.style.display = "block";
			document.getElementById("txt_date_to").parentNode.style.display = "block";
			
		} else {
			// Nascondo
			document.getElementById("txt_date_from").parentNode.style.display = "none";
			document.getElementById("txt_date_to").parentNode.style.display = "none";
		}
		
		//> Gruppi
		if (arrFilt.includes('group') ) {
			// Rendo visibile
			document.getElementById("sel_id_group").parentNode.style.display = "block";
			
		} else {
			// Nascondo
			document.getElementById("sel_id_group").parentNode.style.display = "none"					
			
		}
	}
	
	
	function changeParams(exct_code){
		//Imposta i default dell'estrazione selezionata
		$.ajax({
		  type: "GET",
		  url:  "ajax.adm_server.php?r=alms/extraction/getParamJson&exct_code="+exct_code,
		  dataType: "json",
		  
		  success: function(params){
			  
				var def = params.defaults;
				var arrSt = params.statusList;
				var arrGroup = params.groupList;
				var arrFilt = params.filterAllowed;
				
				//> Campi custom
				if (arrFilt.includes('ctm_field')) {
					
					// Imposto default
					$("#chk_ctm_field").prop('checked', def['ctm_field']);
				}
				
				//> Stato
				if (arrFilt.includes('status') ) {
					//Svuoto lista stati
					$("#sel_status").empty();
					
					//Creo lista stati
					for (var x in arrSt) {
						$("#sel_status").append($('<option>', {value: x, text: arrSt[x]}
						));
					}
					
					//Imposto stato di default
					$("#sel_status").val(def['status']);	
				}
				
				//> Date
				if (arrFilt.includes('date')) {
					
					// Imposto default
					$("#txt_date_from").val(def['date_from']);
					$("#txt_date_to").val(def['date_to']);	
				}
				
				//> Gruppi
				if (arrFilt.includes('group') ) {
					//Svuoto lista gruppi
					$("#sel_id_group").empty();
					
					//Creo lista gruppi
					for (var x in arrGroup) {
						$("#sel_id_group").append($('<option>', {value: x, text: arrGroup[x]}
						));
					}
					
					//Imposto gruppo di default
					$("#sel_id_group").val((def['group']));	
				}
				
				//Visibilità campi filtro
				setVisibleParams(arrFilt);
				
				//Nascondo eventuale tabella precedente
				$("#container_report").hide();	
		  },
		  error: function(){
			alert("Errore nel recupero informazioni tabella");
		  }
		});
	}

	$( document ).ready(function() {
		
		var msg_result  = '<?php echo $postData->result; ?>';
		var mode_load 	=  <?php echo $postData->mode; ?>;
		var base_link 	= '<?php echo $base_link_extraction; ?>';
		var start_filt	= '<?php echo json_encode($props->filterAllowed); ?>';
		
		var aflds = document.getElementsByClassName("filedset-av");
	
		//Scopro i filtri per l'estrazione di avvio
		setVisibleParams(start_filt);
		
		// Carico le prime date di default
		$('#txt_date_from').datepicker('update', '<?php echo $def_date[0] ?>');
		$('#txt_date_to').datepicker('update', '<?php echo $def_date[1] ?>');

		
		//Apro i filtri
		aflds[0].click();
		
		//Se la modalità è view, mostro la tabella dati e porto lo scroll sulla tabella
		if(mode_load == 2) {
			$('#extraction_table').show();
			$('html, body').animate({scrollTop: ($('#exct_table_title').offset().top)}, 500);
		}
		
		//Eventuali messaggi al caricamento
		if(msg_result != "") sendFeedback(msg_result);
		
		//Funzione per gestire il cambiamento di report
		$("#sel_exct_code").change(function() {
			var sel_exct =  $(this).val();	
			//window.location.href = "index.php?r="+base_link+"/show&exct_code="+sel_exct+"&mode=0";
			changeParams(sel_exct);
		});
		
		//Funzione per gestire il submit del form
		$( "#form_extraction" ).submit(function( event ) {
			
				var form_extraction = $("#form_extraction").get(0)
			
				//Fermo l'evento submit
				event.preventDefault();
				  
				//Controllo la validita dei campi
				var dt_from = $("#txt_date_from").val();
				var dt_to = $("#txt_date_to").val();
			  
				if(dt_from == "" || dt_to == ""){
				  sendFeedback("Inserisci la data di inizio e di fine");
				  return;
				}
				
				if($("#opt_export").is(":checked")) { 
					//Azione esporta
					form_extraction.action = "index.php?r="+base_link+"/getExtractionCsv";
					
				}else if($("#opt_view").is(":checked")){
					//Azione mostra in tabella
					form_extraction.action = "index.php?r="+base_link+"/show";		
				}
				
				//Submit del form
				form_extraction.submit();
		});
			
	});

</script>
