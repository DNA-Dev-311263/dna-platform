<br />

<?php

$title = array(	Lang::t('_IMPORT_ASSIGNMENT', 'courseassn'),
				$id_org => $org_name. " - File",
				Lang::t('_CHECKS', 'standard'),
				Lang::t('_CONFIRM', 'standard')	
			  );


echo	getTitleArea($title)
		.'<div class="std_block">'
		.Form::openForm('insert_courseassn', 'index.php?r='.$base_link_courseassn.'/insertAssnWizard&id_org='.$id_org, false, false, 'multipart/form-data')
		.Form::openElementSpace()
		.Form::getHidden('step', 'step', '3')
		.Form::closeElementSpace()
		."<table class = 'table table-bordered table-view' >"
		."	<tr>"
		."		<th>".Lang::t('_ROW_UPLOADED', 'courseassn')."</th>"
		."		<th>".Lang::t('_ROW_INVALID', 'courseassn')."</th>"
		."		<th>".Lang::t('_ROW_VALID', 'courseassn')."</th>"
		."		<th>".Lang::t('_NEW_USERS', 'courseassn')."</th>"
		."		<th>".Lang::t('_NEW_USERS_MAN', 'courseassn')."</th>"
		."	</tr>"
		."	</tr>"
		."		<td class='align-center'>".$num_load."</td>"
		."		<td class='align-center'>".$num_invalid."</td>"
		."		<td class='align-center'>".$num_valid."</td>"
		."		<td class='align-center'>".$num_new_user."</td>"
		."		<td class='align-center'>".$num_new_manager."</td>"
		."	</tr>"
		."</table>"

		.($num_valid > 0 ?
			 Form::getOpenFieldset(Lang::t('_COMMUNICATIONS', 'standard'))
			.Form::getCheckBox(Lang::t('_SEND_ALERT_TO_USERS', 'standard'), 'id_chk_send', 'chk_send', '1', $send_alert)
			.($num_valid > 50 && $send_alert == 1 ? '<p class="warning">'. Lang::t('_INACTIVE_QUEUE_ALERT', 'standard').'</p>' : '')
			.Form::getCloseFieldset() : ''
		 )
		.Form::openButtonSpace()
		.Form::getButton('prev', 'prev', Lang::t('_PREV', 'standard'))
		.($num_valid > 0 ? Form::getButton('next', 'next', Lang::t('_IMPORT', 'standard')) : '')
		.Form::getButton('undo', 'undo', Lang::t('_UNDO'))
		.Form::closeButtonSpace()
		.Form::closeForm()
		.'</div>';
		
		
?>


