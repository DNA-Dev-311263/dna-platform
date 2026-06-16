<?php

namespace appLms\Events\Lms;

define('_EVENT_SUBSCRIPTION_INSERT', 1);
define('_EVENT_SUBSCRIPTION_UPDATE', 2);
define('_EVENT_SUBSCRIPTION_DELETE', 3);

use Symfony\Component\EventDispatcher\Event;

class SubscriptionManageEvent extends Event {
    
    const EVENT_NAME = 'lms.subscription.manage';

    protected $id_edition;
    protected $id_course;
    protected $action;
    protected $users;
    protected $action_details;

    public function __construct($users, $id_course, $id_edition, $action, $action_details = null) {
        
        $this->users = $users;
        $this->id_course = $id_course;
        $this->id_edition = $id_edition;
        $this->action = $action;
        $this->action_details = $action_details;

    }
    
}
