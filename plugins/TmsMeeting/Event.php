<?php
require_once _plugins_.'/TmsMeeting/Features/appLms/admin/controllers/TmsmeetingAlmsController.php';

$controller = new TmsmeetingAlmsController();

Events::listen(
    'lms.edition.manage',
    [$controller, 'onEditionChanged'],
    Events::PRIORITY_CORE
);

Events::listen(
    'lms.edition.list',
    [$controller, 'onEditionList'],
    Events::PRIORITY_CORE
);

Events::listen(
    'lms.subscription.manage',
    [$controller, 'onSubscriptionChanged'],
    Events::PRIORITY_CORE
);

Events::listen(
    'lms.course.deleted',
    [$controller, 'onCourseDeleted'],
    Events::PRIORITY_CORE
);
