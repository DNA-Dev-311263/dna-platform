<?php
YuiLib::load(array('animation' => 'my_animation', 'container' => 'container-min', 'container' => 'container_core-min'));

echo Util::get_js(FormaLms\lib\Get::rel_path('lms') . '/views/courseassn/courseassn.js', true);
?>
<script type="text/javascript">
    YAHOO.util.Event.onDOMReady(function () {
        initialize("<?php echo Lang::t('_UNDO', 'standard'); ?>");
    });
        
</script>

<script type="text/javascript">
    var lb = new LightBox();
    lb.back_url = 'index.php?r=lms/courseassn/show&sop=unregistercourse';

    var Config = {};
    Config.langs = {_CLOSE: '<?php echo Lang::t('_CLOSE', 'standard'); ?>'};
    lb.init(Config);  
</script>


<?php

//ABR: inserisco nell'array del controller la descrizione stato non iscritto (-1)
$this->levels[-1] = Lang::t('_USER_STATUS_NOTSUBSCRIBED', 'standard');

function ntz($exp, $ret_val = 0)
{
	if(!is_null($exp)){
		$ret_val = $exp;
	}
	return $ret_val;
}

function GetCategory($the_course)
{
    $ret_val = str_replace("/", " - ", substr($the_course['nameCategory'], 1));
    if ($ret_val == "") {
        $ret_val = Lang::t('_NO_CATEGORY', 'standard');
    } else {
        $ret_val = substr($the_course['nameCategory'], 6);
    }
    return $ret_val;
}

function dataEndExists($the_course)
{
    $date = Format::date($the_course['date_end'], 'date');
    return ($date != "00-00-0000");
}

function GetCourseYear($the_course)
{
    $date = Format::date($the_course['date_end'], 'date');
    $date_split = explode('-', $date);
    return $date_split[2];
}

function GetCourseMonth($the_course)
{
    setlocale(LC_ALL, "IT");// TBD: setting to platform locale
    $date = Format::date($the_course['date_end'], 'date');
    $month_name = ucfirst(strftime("%B", strtotime($date)));
    return substr($month_name, 0, 3);
}

function GetCourseDay($the_course)
{
    $date = Format::date($the_course['date_end'], 'date');
    $date_split = explode('-', $date);
    return $date_split[0];
}

function GetCourseImage($the_course, $path_image)
{

    if ($the_course['img_course']) {
        return $path_image . $the_course['img_course'];
    } else {
        return FormaLms\lib\Get::tmpl_path() . 'images/course/course_nologo.png';
    }
}

function TruncateText($the_text, $size)
{
    if (strlen($the_text) > $size)
        return substr($the_text, 0, $size) . '...';
    return $the_text;
}

function typeOfCourse ($t) {
    switch ($t) {
       case "elearning":
         return Lang::t('_ELEARNING', 'catalogue');       
       case "classroom":
            return Lang::t('_CLASSROOM_COURSE', 'cart');       
       case "all":
            return Lang::t('_ALL_COURSES', 'standard');       
    }
    return '';
}

function txpurif($text, $decode_html = false) {
	//>> Sostituisce i caratteri speciali con i tag html
	//>> Se richiesto riconverte le entità senza convertire gli apostrofi singoli
	$text = htmlspecialchars($text, ENT_QUOTES);
	if ($decode_html)
		$text = htmlspecialchars_decode($text, ENT_COMPAT);
		
	return $text;
}

?>
<link rel="shortcut icon" href="../favicon.ico">

<?php if ($use_label) : ?>
    <div class="container-back">
        <a href="index.php?r=courseassn/show&id_common_label=-2">
            <span>&lsaquo;&lsaquo; <?php echo Lang::t('_BACK_TO_LABEL', 'course') ?></span>
        </a>
    </div>
<?php endif; ?>

<div id="resizablepanel">
    <div class="hd"></div><!--Resizable panel-->
    <div class="bd"></div>
    <div class="ft"></div>
</div>

<div id='container1_<?php echo $course_state; ?>'>
    <h1 class="page-header col-xs-12"><strong><?php echo typeOfCourse($filter_type); ?></strong>
		<span class = "link-subs"><?php echo "<a href='index.php?r=lms/elearning/showUserSubscriptions&amp;op=unregistercourse'>".Lang::t('_SUBS_REPORT', 'course')."</a>"; ?></span>
	</h1>
	
    <?php if ($courselist) : ?>
			<div class = "col-md-12">
				<div id="courseassn-fpage-msg" class="fpage-message">
					<?php echo txpurif( Lang::t('_ASSN_FPAGE_MESSAGE', 'courseassn')); ?>
				</div>
            </div>
    <?php endif; ?>
    
    <div class="clearfix" id='mia_area_<?php echo $stato_corso; ?>'>
        <?php if (empty($courselist)) : ?>
            <p><?php echo Lang::t('_NO_CONTENT', 'standard'); ?></p>
        <?php endif; ?>
        <?php foreach ($courselist as $course){  ?>
        <div class="col-xs-12 col-md-4">
            <div class="course-box"> <!-- NEW BLOCK -->
                    <div class="course-box__item">
                        <div class="course-box__title icon--filter-<?php echo $course['user_status']; ?>">
							<a id="link_info_<?php echo $course['idCourse']?>" href="javascript:;" onclick="<?php echo "courseinfoPopUp('".$course['idCourse']."', '".($course['edition_valid'] ? $course['id_edition'] : 0)."')";?>">
								<span class="title_info"><?php echo TruncateText($course['name'], 100); ?></span>
								
							</a>
                        </div>
                    </div>
                    <div class="course-box__item course-box__item--no-padding">
                        <?php if ($course['use_logo_in_courselist']) { ?>
                        <div class="course-box__img" style="background-image: url(<?php echo GetCourseImage($course, $path_course) ?>)">
                        <?php } else { ?>
                        <div class="course-box__img">
                        <?php } ?>
                            <div class="course-box__img-title">
                                <?php echo GetCategory($course) ?>
                            </div>
                        </div>
                    </div>
                    <div class="course-box__item">
                        <div id="infolevel_<?php echo $course['idCourse']; ?>" class="course-box__owner course-box__owner--<?php echo $course['level']; ?>">
                            <?php echo $this->levels[$course['level']]; ?>
                        </div>
                        <div class="course-box__desc">
                            <?php echo TruncateText($course['box_description'], 120); ?>
                        </div>
                    </div>
                    <?php if (dataEndExists($course)) { // if exists end course, show it ?>
                    <div class="course-box__item course-box__item--half">
                        <div class="course-box__date-text">
                            <span><?php echo Lang::t('_CLOSING_DATA', 'course') ?></span><br>
                            <?php echo GetCourseDay($course)?>&nbsp;<?php echo GetCourseMonth($course)?>&nbsp;<?php echo GetCourseYear($course);?>
                        </div>
                    </div>
                    <?php } 
                    
						$divId =  "action_". $course['idCourse'];
						$divClass = "course-box__item course-box__item";
						$divClass .= (dataEndExists($course) ? '--half' : '');
                    ?>
                    <div class="<?php echo $divClass;?>"  id="<?php echo $divId;?>" >
                 
					<?php 

						// Verifico lo stato del pulsante di ingresso
						$button_state = 9;				//disabilitato
						
						if ($course['user_status'] == -1){
							//se l'utente non è iscritto e ci sono edizioni disponibili
								//iscrizione
								$button_state = (count($course['editions_available']) > 0 ? 1 : 8); 		
						
						}elseif (!$course['edition_valid']){
							//se la sua iscrizione all'edizione non è più valida e ci sono edizioni disponibili
								//reiscrizione 
								$button_state = (count($course['editions_available']) > 0 ? 2 : 8); 
								   
						}elseif ($course['can_enter']['can']){
							//se la sua iscrizione è ancora valida e può entrare
								$button_state = 3; 		//può entrare
						}
							
						//Inserisco il pulsante
						
						switch ($button_state) {
							case 1:
								echo 
								'<a class="forma-button forma-button--green forma-button--orange-hover" href="javascript:;" onclick="courseSelection(\'' . $course['idCourse'] . '\', \'0\')" title="' . Lang::t('_CAN_SUBSCRIBE', 'course') . '"><span class="forma-button__label">' . Lang::t('_CAN_SUBSCRIBE', 'course') . '</span></a>';

								break;
							case 2:
								echo
								'<a class="forma-button forma-button--green forma-button--orange-hover" href="javascript:;" onclick="courseSelection(\'' . $course['idCourse'] . '\', \'0\')" title="' . Lang::t('_CAN_SUBSCRIBE_AGAIN', 'course') . '"><span class="forma-button__label">' . Lang::t('_CAN_SUBSCRIBE_AGAIN', 'course') . '</span></a>';

								break;
							case 3:
								echo 
								"<a class='forma-button forma-button--orange-hover forma-button--full' title='".Util::purge($course['name'])."'"
								."		href='index.php?modname=course&amp;op=aula&amp;idCourse=".$course['idCourse']."' ".($course['direct_play'] == 1 && $course['level'] <= 3 && $course['first_lo_type'] == 'scormorg' ? "rel='lightbox'" : "").">"
								."	<span class='forma-button__label'>".Lang::t('_USER_STATUS_ENTER', 'catalogue')."</span>"
								."</a>";
								
								break;
							case 8:
								echo 
								"<a class='forma-button forma-button--disabled' href='javascript:void(0)'>"
								."	<span class='forma-button__label'>".Lang::t('_NO_EDITIONS', 'catalogue')."</span>"
								."</a>";
								
								break;
							case 9:
								//Inserisco il button "disabilitato" con un title che indica la ragione
								$reasons = array('waiting' => Lang::t('_WAITING_SUBSCRIPTION', 'dashboard'), 
								                 'subscription_not_started' => Lang::t('_SUBSCRIPTION_CLOSED', 'course'),
								                 'subscription_expired' => Lang::t('_SUBSCRIPTION_CLOSED', 'course'),
								                 'course_edition_date_begin' => Lang::t('_EDITION_NOT_STARTED', 'course'),
								                 'course_edition_date_end' => Lang::t('_EDITION_ENDED', 'course')
												);
								
								$reasonLock = $course['can_enter']['reason'];				
								
								if (array_key_exists($reasonLock, $reasons))
									$reasonLock = $reasons[$reasonLock];
														
								echo 
								"<span title = '".$reasonLock."'>"
								."	<a class='forma-button forma-button--disabled' href='javascript:void(0)'>"
								."		<span class='forma-button__label'>".Lang::t('_DISABLED', 'course')."</span>"
								."	</a>"
								."</span>";
								
								break;
						}
						
					?>
						
                    </div>
                </div>
            </div>
            <?php } // end foreach ?>
        </div>
        
    </div>


    <div id="container-feedback"></div>

</div>   

