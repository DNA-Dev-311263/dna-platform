<div class="row">
	
	<div class="col-md-12">
	

                <?php
                $_model = new CourseassnLms();
                $count = 0;
                $statusFilters = $_model->getFilterStatusCourse(Docebo::user()->getIdst());

                $html = '<ul class="nav nav-pills">';

                while( list($key, $value) = each($statusFilters) ) {

                    $html_code .= '	<option value="'.$key.'"'
                        .((string)$key == (string)$selected ? ' selected="selected"' : '' )
                        .'>'.$value.'</option>'."\n";

                    if ($count === 0) {
                        $html .= '<li class="selected js-label-menu-filter" data-value="' . $key . '">';
                    } else {
                        $html .= '<li class="js-label-menu-filter" data-value="' . $key . '">';
                    }

                    $html .= '<a class="icon--filter-' . $key . '" href="#" >' . $value . '</a>';
                    $html .= '</li>';
                    $count++;
                }

                $html .= '</ul>';

                $inline_filters = $html;


				$w = $this->widget('lms_tab', array(
	     			'active' => 'courseassn',
	     			'close' => false
	    		));

                // select year
                $_auxiliary = Form::getInputDropdown('', 'courseassn_search_filter_year', 'filter_year', $_model->getFilterYears(Docebo::user()->getIdst()), 0, '');
                $_auxiliary = str_replace('class="form-control "', 'class="selectpicker"  data-selected-text-format="count > 1" data-width=""  data-actions-box="true"', $_auxiliary);
                
                                                       
                $_list_category = Form::getInputDropdown('', 'courseassn_search_filter_cat', 'filter_cat', $_model->getListCategory(Docebo::user()->getIdst(),false), 0, '');
                $_list_category = str_replace('class="form-control "', 'class="selectpicker"  data-selected-text-format="count > 1" data-width="" multiple data-actions-box="true"', $_list_category);

                $this->widget('courseassnfilter', array(
                    'id' => 'courseassn_search',
                    'filter_text' => "",
                    'list_category' => $_list_category,
                    'auxiliary_filter' => $_auxiliary,
                    'inline_filters' => $inline_filters,
                    'js_callback_set' => 'courseassn_search_callback_set',
                    'js_callback_reset' => 'courseassn_search_callback_reset',
                    'css_class' => 'nav'
                ));
				?>


	
	</div>
    
      
  <div class="nofloat" ></div>
    
    
 <!-- DIV CONTENT COURSE-LIST  -->       
<div  class="col-md-12" id="div_course">
    <br><p align="center"><img src='<?php echo Layout::path() ?>images/standard/loadbar.gif'></p>
</div>
 

<script type="text/javascript">


    
    $('.js-label-menu-filter').on('click', function () {
        $(this).addClass('selected').siblings().removeClass('selected');
        saveCurrentFilter();
        courseassn_search_callback_set();
    });
    
    function saveCurrentFilter(){
        var this_user = '<?php echo Docebo::user()->idst ?>'
        var ctype = $('#courseassn_search_filter_type').selectpicker().val();
        setCookie(this_user+'.my_courseassn.type',ctype,60,"/")
        var category = $('#courseassn_search_filter_cat').selectpicker().val();
        setCookie(this_user+'.my_courseassn.category',category,60,"/")
        var cyear = $("#courseassn_search_filter_year").selectpicker().val();
        setCookie(this_user+'.my_courseassn.year',cyear,60,"/")        
        
    }

    function clearCurrentFilter(){
        var this_user = '<?php echo Docebo::user()->idst ?>'
        prev = ["0"];
        setCookie(this_user+'.my_courseassn.type',"",3650,"/")
        setCookie(this_user+'.my_courseassn.category',"",-3650,"/")
        setCookie(this_user+'.my_courseassn.year',"",-3650,"/")
    }
    
    
	function courseassn_search_callback_set() {
	
        var ft = $("#courseassn_search_filter_text").val();

        var ctype = $("#courseassn_search_filter_type").selectpicker().val();
        var category = $('#courseassn_search_filter_cat').selectpicker().val();
        var cyear = $("#courseassn_search_filter_year").selectpicker().val();
        var json_subscription = $('.js-label-menu-filter.selected').attr('data-value');

        $("#div_course").html("<br><p align='center'><img src='<?php echo Layout::path() ?>images/standard/loadbar.gif'></p>");
        var posting = $.get( 'ajax.server.php?r=courseassn/all&rnd=<?php echo time(); ?>&filter_text=' + ft + '&filter_type=' + ctype + '&filter_cat=' + category + '&filter_status=' + json_subscription + '&filter_year=' + cyear, {}
		
        );
                  
        posting.done(function(responseText){
            $("#div_course").html(responseText);
        });
	}

	function courseassn_search_callback_reset() {
        
        clearCurrentFilter();
        
        $("#courseassn_search_filter_year").selectpicker('val', 0);
        $("#courseassn_search_filter_type").selectpicker('val', 'all');
        $("#courseassn_search_filter_cat").selectpicker('val', [0]);
        $("#courseassn_search_filter_text").val("")
                
        $("#div_course").html("<br><p align='center'><img src='<?php echo Layout::path() ?>images/standard/loadbar.gif'></p>");
        var posting = $.get( 'ajax.server.php?r=courseassn/all&rnd=<?php echo time(); ?>&filter_text=&filter_type=all&filter_cat=0&filter_status=all&filter_year=0'
		
        );
                  
        posting.done(function(responseText){
            $("#div_course").html(responseText);
        });
	}
</script>


