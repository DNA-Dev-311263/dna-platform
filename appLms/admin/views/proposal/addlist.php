<?php

	$id_proponent = $model->getIdProponent();
	$page_show = $base_link_proposal.'/show&id_proponent='.$id_proponent;
	$ajax_url = 'ajax.adm_server.php?r='.$base_link_proposal.'/getCourseList&id_proponent='.$id_proponent;
	
	
	// Imposto il titolo della pagina
	Get::title(array(
		'index.php?r='.$base_link_course.'/show' => Lang::t('_COURSE', 'course'),
		'index.php?r='.$page_show => Lang::t('_PROPOSAL', 'proposal').' : '.$model->getProponentName(),
		Lang::t('_ADDING_COURSES', 'proposal')
	)); 
	


	echo getBackUi('index.php?r='.$page_show, Lang::t('_BACK', 'standard'));
?>

<div class="std_block">
	<div class="folder_tree" id="category_tree" style="display: block;">
		
	<?php
	/*---------------------------- TREE VIEW ----------------------------*/
	
	$languages = array('_ROOT' => $root_name, '_AJAX_FAILURE' => Lang::t('_CONNECTION_ERROR', 'standard'));
    
	$tree_params = array(
						'id' => 'category_tree',
						'ajaxUrl' => 'ajax.adm_server.php?r='.$base_link_proposal.'/gettreedata&id_proponent='. $id_proponent,
						'treeClass' => 'CourseFolderTree',
						'treeFile' => Get::rel_path('lms').'/admin/views/proposal/coursefoldertree.js',
						'languages' => array('_ROOT' => $root_name),
						'dragDrop' => false
					);
					
	$this->widget('tree', $tree_params);
	
	?>
	</div>
	<div class = "vspacer">&nbsp;</div>
	<div class="quick_search_form">
		<div>
			<?php
				echo Form::getInputTextfield("search_t", "filter_text", "filter_text", '', '', 255, '' );
				echo Form::getButton("filter_set", "filter_set", Lang::t('_SEARCH', 'standard'), "search_b");
				echo Form::getButton("filter_reset", "filter_reset", Lang::t('_RESET', 'standard'), "reset_b");
			?>
		</div>
	</div>
	
	<div id = "course_list">

	<?php
	/*---------------------------- TABLE ----------------------------*/
	
	// Preparo le colonne del widget
	$columns = 	array(
						array('key' => 'category', 'label' => Lang::t('_CATEGORY', 'standard'), 'sortable' => true),
						array('key' => 'code', 'label' => Lang::t('_CODE', 'course'), 'sortable' => true),
						array('key' => 'name', 'label' => Lang::t('_NAME', 'course'), 'sortable' => true),
						array('key' => 'course_type', 'label' => Lang::t('_TYPE', 'course')),
						array('key' => 'status', 'label' => Lang::t('_STATUS', 'course'), 'sortable' => true, 'formatter' => 'addJs.statusFormatter')
				);

	// Preparo i parametri del widget
	$params = array(
		'id'			=> 'courselist_table',
		'ajaxUrl'		=> 'ajax.adm_server.php?r='.$base_link_proposal.'/getCourseList&id_proponent='.$id_proponent.'&',
		'rowsPerPage'	=> Get::sett('visuItem', 25),
		'startIndex'	=> 0,
		'results'		=> Get::sett('visuItem', 25),
		'sort'			=> 'category',
		'dir'			=> 'asc',
		'columns'		=> $columns,
		'fields'		=> $fields,
		'stdSelection' => true,
		'show'			=> 'table',
		'generateRequest' => 'addJs.requestBuilder',
		'selectAllAdditionalFilter' => 'addJs.selectAllFilter()',
		'events' => array('initEvent' => 'addJs.initTableEvent')
	);


	if ($permissions['mod'])
		$params['rel_actions'] = '<a name="add_course" class="ico-wt-sprite subs_confirm" href="#"><span>'.Lang::t('_CONFIRM', 'standard').'</span></a>';
	
	$params['rel_actions'] .= '&emsp; <a class="ico-wt-sprite subs_cancel" href="index.php?r='.$page_show.'"><span>'.Lang::t('_UNDO', 'standard').'</span></a>';
	$params['rel_actions'] .= '&emsp; <span><b id="items_selected"> 0 </b> '.Lang::t('_SELECTED', 'admin_directory').'</span>';
	
	
	// Passo tutto al widget
	$this->widget('table', $params);


	echo getBackUi('index.php?r='.$page_show, Lang::t('_BACK', 'standard'));
	// Form invisibile per esportazione dati
	
	?>

	
	</div><!-- chiusura div course_list-->
</div><!-- chiusura div std_block-->


<script type="text/javascript">
	
	var addJs = new addlistJs();
	var evt = YAHOO.util.Event;
	
	addJs.baseLink 		= "<?php echo $base_link_proposal; ?>";
	addJs.idProponent 	= "<?php echo $id_proponent; ?>";
	addJs.authReq 		= "<?php echo Util::getSignature(); ?>";
	addJs.statusList 	= {<?php echo $status_list_js ?>};
	addJs.idCategory 	= 0;
	addJs.idTable 		= "courselist_table";
	addJs.langs			= {_EMPTY_SELECTION: "<?php echo Lang::t('_EMPTY_SELECTION', 'standard'); ?>"};
	
	evt.onDOMReady(function(e) {
		var aEl = document.getElementsByName('add_course');
		
		evt.addListener(aEl, "click", function(e){ addJs.addCourse(); });
		evt.addListener("filter_set", "click", function(e){ addJs.setFilterText(1); });
		evt.addListener("filter_text", "keypress", function(e){if(evt.getCharCode(e) == 13) addJs.setFilterText(1); });
		evt.addListener("filter_reset", "click", function(e){ addJs.setFilterText(0); });	
	});
	
	var setCategory = function(idCat) {
		addJs.idCategory = idCat;
		addJs.table.refresh();
	}

</script>
