<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

defined('IN_FORMA') or exit('Direct access is forbidden.');

use FormaLms\lib\Session\SessionManager;

class CoursestatsselfLmsController extends LmsController
{
    protected $model;
    protected $idCourse;

    public function init()
    {
        $this->model = new CoursestatsselfLms();
        $this->permissions = [
            'view' => true,
        ];

        $this->idCourse = false;
        $session = SessionManager::getInstance()->getSession();
        if ($session->has('idCourse') && $session->get('idCourse') > 0) {
            $this->idCourse = $session->get('idCourse');
        }
    }

    public function showTask()
    {
        $id_user = getLogUserId();

        $stats = false;
        if ($this->idCourse !== false && $id_user > 0) {
            $stats = $this->model->getCourseUserStatsList($this->idCourse, $id_user);
        }

        $params = [
            'stats' => $stats,
        ];

        $this->render('show', $params);
    }
}
