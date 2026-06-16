<?php
	$id_proponent = $model->getIdProponent();
	
	Get::title(array(
		'index.php?r='.$base_link_course.'/show' => Lang::t('_COURSE', 'course'),
		Lang::t('_PROPOSAL', 'proposal').' : '.$model->getProponentName()
	)); 
?>
<div class="std_block">
<?php

echo getBackUi('index.php?r='.$base_link_course.'/show', Lang::t('_BACK', 'standard'));

// Preparo le colonne del widget
$columns = 	array(
					array('key' => 'category', 'label' => Lang::t('_CATEGORY', 'standard'), 'sortable' => true),
					array('key' => 'code', 'label' => Lang::t('_CODE', 'course'), 'sortable' => true),
					array('key' => 'name', 'label' => Lang::t('_NAME', 'course'), 'sortable' => true),
					array('key' => 'course_type', 'label' => Lang::t('_TYPE', 'course'), 'className' => 'min-cell'),
					array('key' => 'status', 'label' => Lang::t('_STATUS', 'course'), 'sortable' => true, 'formatter' => 'prop.statusFormatter'),
					array('key' => 'date_begin', 'label' => Lang::t('_DATE_BEGIN', 'standard'), 'sortable' => true),
					array('key' => 'date_end', 'label' => Lang::t('_DATE_END', 'standard'), 'sortable' => true),
	
				'fs' => array('key' => 'from_score', 'label' => Lang::t('_MIN_SCORE', 'standard'), 'className' => '', 'sortable' => true, 'formatter' => 'prop.scoreFormatter'),
				'ts' => array('key' => 'to_score', 'label' => Lang::t('_MAX_SCORE', 'standard'), 'className' => '', 'sortable' => true, 'formatter' => 'prop.scoreFormatter')
			);


// Perfezione le colonne di modifica punteggi (consento editor)
if ($permissions['mod']) {
	$columns['fs']['editor'] = 'new YAHOO.widget.TextboxCellEditor({validator: YAHOO.widget.DataTable.validateNumber, asyncSubmitter: scoreSubmit})';
	$columns['ts']['editor'] = 'new YAHOO.widget.TextboxCellEditor({validator: YAHOO.widget.DataTable.validateNumber, asyncSubmitter: scoreSubmit})';
}

// Completo le colonne con l'aggiunta della colonna di eliminazione
if ($permissions['mod']) {
	$columns[] = array('key' => 'del', 'label' => Get::img('standard/delete.png', Lang::t('_DEL', 'standard')), 'formatter'=>'doceboDelete', 'className' => 'img-cell');
}

// Preparo i parametri del widget
$params = array(
	'id'			=> 'proposal_table',
	'ajaxUrl'		=> 'ajax.adm_server.php?r='.$base_link_proposal.'/getProposalList&id_proponent='.$id_proponent.'&',
	'rowsPerPage'	=> Get::sett('visuItem', 25),
	'startIndex'	=> 0,
	'results'		=> Get::sett('visuItem', 25),
	'sort'			=> 'category',
	'dir'			=> 'asc',
	'columns'		=> $columns,
	'fields'		=> $fields,
	'delDisplayField' => 'name',
	'show'			=> 'table',
	'events' => array('initEvent' => 'prop.initTableEvent')
);

if ($permissions['mod']) {
	$params['rel_actions']  = '<a class="ico-wt-sprite subs_add" href="index.php?r='.$base_link_proposal.'/addProposal&amp;id_proponent='.$id_proponent.'"><span>'.Lang::t('_ADD', 'subscribe').'</span></a>';
	$params['rel_actions'] .= '&emsp; <a class="ico-wt-sprite subs_del" href="#" name="del_all"><span>'.Lang::t('_DEL_ALL', 'standard').'</span></a>';
}

// Passo tutto al widget
$this->widget('table', $params);


echo getBackUi('index.php?r='.$base_link_course.'/show', Lang::t('_BACK', 'course'));

?>


<script type="text/javascript">
	
	$(document).ready(function() {
		//Messaggio di conferma fadeout 
		var div_feedback = YAHOO.util.Dom.getElementsByClassName('container-feedback');              

		if (div_feedback.length > 0) {
			$( "#" + div_feedback[0].id ).fadeOut( 4000, function() {});
		}
	});

	var prop = new proposalJs();
	var evt = YAHOO.util.Event;
	
	prop.statusList		= {<?php echo $status_list_js ?>};
	prop.baseLink 		= "<?php echo $base_link_proposal ?>";
	prop.idProponent 	= "<?php echo $id_proponent ?>";
	prop.idTable		= "proposal_table";
	prop.langs			= {
		_AREYOUSURE: 		"<?php echo Lang::t('_AREYOUSURE', 'standard'); ?>",
		_NO_DATA: 			"<?php echo Lang::t('_NO_DATA', 'standard'); ?>",
		_DEL_ALL: 			"<?php echo Lang::t('_DEL_ALL', 'standard'); ?>",
		_CLOSE: 			"<?php echo Lang::t('_CLOSE', 'standard'); ?>",
		_CONFIRM: 			"<?php echo Lang::t('_CONFIRM', 'standard'); ?>",
		_UNDO: 				"<?php echo Lang::t('_UNDO', 'standard'); ?>"			
	}
	
	evt.onDOMReady(function(e) {
		var aEl = document.getElementsByName('del_all');
		evt.addListener(aEl, "click", function(e){ prop.delAll(e); });
	});
	
	var scoreSubmit = function (callback, newValue) {
         prop.scoreSave(this, newValue, callback)   
	}
	
</script>
