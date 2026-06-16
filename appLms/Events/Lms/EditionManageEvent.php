<?php

namespace appLms\Events\Lms;

define('_EVENT_EDITION_INSERT', 2);
define('_EVENT_EDITION_UPDATE', 4);
define('_EVENT_EDITION_DELETE', 6);
define('_EVENT_EDITION_DAY_BEFORE_UPDATE', 12);
define('_EVENT_EDITION_DAY_AFTER_UPDATE', 14);


//use Symfony\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\Event;

class EditionManageEvent extends Event {
    
    const EVENT_NAME = 'lms.edition.manage';

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
    
}
