<?php 

$title = array(	Lang::t('_UPDATE_GAP', 'gap'),
				$id_org => $org_name. " - File",
				Lang::t('_CHECKS', 'standard')
			  );

echo	getTitleArea($title);

?>
<div id="check_feedback"></div>
<div class="std_block">

<?php

	// Pulsanti
	echo
		 Form::openForm('update_gap', 'index.php?r='.$base_link_gap.'/updateGapWizard&id_org='.$id_org, false, false, 'multipart/form-data')
		.Form::openElementSpace()
		.Form::getHidden('step', 'step', '2')
		.Form::closeElementSpace()
		.Form::openButtonSpace()
		.Form::getButton('prev', 'prev', Lang::t('_PREV', 'standard'))
		.Form::getButton('next', 'next', Lang::t('_NEXT', 'standard'))
		.Form::getButton('undo', 'undo', Lang::t('_UNDO'))
		.Form::closeButtonSpace()
		.Form::closeForm();
							
							
	// Preparo i parametri per il widget
	$_params = array(
		'id'			=> 'checks_table',
		'ajaxUrl'		=> 'ajax.adm_server.php?r='.$base_link_gap.'/getInvalidList&id_org='.$id_org.'&op=upd&',
		'rowsPerPage'	=> Get::sett('visuItem', 25),
		'startIndex'	=> 0,
		'results'		=> Get::sett('visuItem', 25),
		'columns'		=> $fields['view_fields'],
		'fields'		=> $fields['field_keys'],
		'events' 		=> array('initEvent' => 'initEvent', 'postRenderEvent' => 'postRenderEvent')
		);


	// Passo tutto al widget
	$this->widget('table', $_params);
	
?>
</div>

<script type="text/javascript">
	
function initEvent(){
	//Scatta all'apertura dopo il primo caricamento
	var totrow = $("#yui-pg0-0-total-records-span").text();
	var divfbk = $("#check_feedback");
	
	if(totrow > 0){
		divfbk.addClass("fbk-err");
		divfbk.text("<?php echo Lang::t('_IMPORT_CHECK_ALERT', 'courseassn') ?>");
	}else{
		divfbk.addClass("fbk-ok");
		divfbk.text("<?php echo Lang::t('_IMPORT_CHECK_OK', 'courseassn') ?>");
	}
	
}
function postRenderEvent(a){
	//Scatta al refresh
	
}

</script>
