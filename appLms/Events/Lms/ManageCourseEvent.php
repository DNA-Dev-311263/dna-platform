<?php

namespace appLms\Events\Lms;

define('_COURSE_INSERT', 1);
define('_COURSE_UPDATE', 2);
define('_COURSE_DELETE', 3);
define('_COURSE_SHOW', 9);


use Symfony\Component\EventDispatcher\Event;

class ManageCourseEvent extends Event {
    
    const EVENT_NAME = 'lms.manage.course';

    protected $id_course;
    protected $action;
    protected $action_details;

    public function __construct($id_course, $action, $action_details = null) {
        
        $this->id_course = $id_course;
        $this->action = $action;
        $this->action_details = $action_details;

    }

    public function getIdCourse() {
       
        return $this->id_course;
    }
    
    public function getAction() {
        
        return $this->action;
    }
    
    public function getActionDetails() {
        
        return $this->action_details;
    }
}
