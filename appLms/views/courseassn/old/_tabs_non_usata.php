<div style="margin:1em;">
<?php
$w = $this->widget('lms_tab', [
  'active' => 'courseassn',
  'close' => false,
]);

// draw search
$_model = new CourseassnLms();
$_auxiliary = Form::getInputDropdown('', 'courseassn_search_filter_year', 'filter_year',
    $_model->getFilterYears(Docebo::user()->getIdst()), 0, '');

$this->widget('tablefilter', [
    'id' => 'courseassn_search',
    'filter_text' => '',
    'auxiliary_filter' => Lang::t('_SEARCH', 'standard') . ':&nbsp;&nbsp;&nbsp;' . $_auxiliary,
    'js_callback_set' => 'courseassn_search_callback_set',
    'js_callback_reset' => 'courseassn_search_callback_reset',
    'css_class' => 'tabs_filter',
]);

$w->endWidget();
?>
</div>
<script type="text/javascript">
	var lb = new LightBox();
	lb.back_url = 'index.php?r=courseassn/show&sop=unregistercourse';
	var tabView = new YAHOO.widget.TabView();

	var mytab = new YAHOO.widget.Tab({
	    label: '<?php echo Lang::t('_ALL_OPEN', 'course'); ?>',
	    dataSrc: 'ajax.server.php?r=courseassn/all&rnd=<?php echo time(); ?>',
	    cacheData: true,
	    loadMethod: "POST"
	});
	mytab.addListener('contentChange', function() {
		lb.init();
		this.set("cacheData", true);
	});
	tabView.addTab(mytab, 0);


	<?php if ($this->isTabActive('new')) { ?>
	mytab = new YAHOO.widget.Tab({
	    label: '<?php echo Lang::t('_NEW', 'course'); ?>',
	    dataSrc: 'ajax.server.php?r=courseassn/new&rnd=<?php echo time(); ?>',
	    cacheData: true,
	    loadMethod: "POST"
	});
	mytab.addListener('contentChange', function() {
		lb.init();
		this.set("cacheData", true);
	});
	tabView.addTab(mytab, 1);
	<?php } ?>

	<?php if ($this->isTabActive('inprogress')) { ?>
	mytab = new YAHOO.widget.Tab({
	    label: '<?php echo Lang::t('_USER_STATUS_BEGIN', 'course'); ?>',
	    dataSrc: 'ajax.server.php?r=courseassn/inprogress&rnd=<?php echo time(); ?>',
	    cacheData: true,
	    loadMethod: "POST"
	});
	mytab.addListener('contentChange', function() {
		lb.init();
		this.set("cacheData", true);
	});
	tabView.addTab(mytab, 2);
	<?php } ?>

	<?php if ($this->isTabActive('completed')) { ?>
	mytab = new YAHOO.widget.Tab({
	    label: '<?php echo Lang::t('_COMPLETED', 'course'); ?>',
	    dataSrc: 'ajax.server.php?r=courseassn/completed&rnd=<?php echo time(); ?>',
	    cacheData: true,
	    loadMethod: "POST"
	});
	mytab.addListener('contentChange', function() {
		lb.init();
		this.set("cacheData", true);
	});
	tabView.addTab(mytab, 3);
	<?php } ?>

	<?php if ($this->isTabActive('suggested') && false) { ?>
	mytab = new YAHOO.widget.Tab({
	    label: '<?php echo Lang::t('_SUGGESTED', 'course'); ?>',
	    dataSrc: 'ajax.server.php?r=courseassn/suggested&rnd=<?php echo time(); ?>',
	    cacheData: true,
	    loadMethod: "POST"
	});
	mytab.addListener('contentChange', function() {
		lb.init();
		this.set("cacheData", true);
	});
	tabView.addTab(mytab, 4);
	<?php } ?>

	tabView.appendTo('tab_content');
	tabView.getTab(0).addClass('first');
	tabView.set('activeIndex', 0);

	function courseassn_search_callback_set() {
		var i, tabs = tabView.get("tabs"), activeTab = tabView.get("activeTab");
		var ft = YAHOO.util.Dom.get("courseassn_search_filter_text").value;
		var fy = YAHOO.util.Dom.get("courseassn_search_filter_year").value;
		for (i=0; i<tabs.length; i++) {
			tabs[i].set("postData", "filter_text="+ft+"&filter_year="+fy);
			tabs[i].set("cacheData", false);
		}
		tabView.selectTab( tabView.getTabIndex(activeTab) );
		activeTab.set("cacheData", false);
	}

	function courseassn_search_callback_reset() {
		var i, tabs = tabView.get("tabs"), activeTab = tabView.get("activeTab");
		YAHOO.util.Dom.get("courseassn_search_filter_text").value = "";
		YAHOO.util.Dom.get("courseassn_search_filter_year").selectedIndex = 0;
		for (i=0; i<tabs.length; i++) {
			tabs[i].set("postData", "");
			tabs[i].set("cacheData", false);
		}
		tabView.selectTab( tabView.getTabIndex(activeTab) );
		activeTab.set("cacheData", false);
	}
</script>
