<?php

namespace appLms\Events\Lms;

define('_EDITION_INSERT', 1);
define('_EDITION_UPDATE', 2);
define('_EDITION_DELETE', 3);

use Symfony\Component\EventDispatcher\Event;

class ManageEditionEvent extends Event {
    
    const EVENT_NAME = 'lms.manage.edition';

    protected $id_edition;
    protected $id_course;
    protected $action;
    protected $action_details;

    public function __construct($id_course, $id_edition, $action, $action_details = null) {
        
        $this->id_course = $id_course;
        $this->id_edition = $id_edition;
        $this->action = $action;
        $this->action_details = $action_details;
    }

    public function getIdCourse() {
       
        return $this->id_course;
    }
    
    public function getIdEdition() {
        
        return $this->id_edition;
    }
    
    public function getAction() {
        
        return $this->action;
    }
    
    public function getActionDetails() {
        
        return $this->action_details;
    }
    
}
