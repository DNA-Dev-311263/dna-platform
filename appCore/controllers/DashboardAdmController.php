<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 *
 * from docebo 4.0.5 CE 2008-2012 (c) docebo
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

defined('IN_FORMA') or exit('Direct access is forbidden');

define('DASH_MAX_RSS_NEWS', 5);
define('_DOCEBO_CORP_BLOG_FEED_ID', 3);

class DashboardAdmController extends AdmController
{
    protected $model;
    protected $json;
    protected $permissions;

    /*
     * initialize the class
     */
    public function init()
    {
        parent::init();
        require_once _base_ . '/lib/lib.json.php';
        $this->json = new Services_JSON();
        $this->model = new DashboardAdm();

        YuiLib::load('autocomplete,tabview');

        $this->permissions = [
            'view' => checkPerm('view', true, 'dashboard', 'framework'),
            'view_user' => checkPerm('view', true, 'usermanagement', 'framework'),
            'add_user' => checkPerm('add', true, 'usermanagement', 'framework'),
            'mod_user' => checkPerm('mod', true, 'usermanagement', 'framework'),
            'del_user' => checkPerm('del', true, 'usermanagement', 'framework'),
            'view_course' => checkPerm('view', true, 'course', 'lms'),
            'add_course' => checkPerm('add', true, 'course', 'lms'),
            'mod_course' => checkPerm('mod', true, 'course', 'lms'),
            'del_course' => checkPerm('del', true, 'course', 'lms'),
            'view_communications' => checkPerm('view', true, 'communication', 'lms'),
            'add_communications' => checkPerm('add', true, 'communication', 'lms'),
            'view_games' => checkPerm('view', true, 'games', 'lms'),
            'add_games' => checkPerm('add', true, 'games', 'lms'),
            'subscribe' => checkPerm('subscribe', true, 'course', 'lms'),
        ];
    }

    //----------------------------------------------------------------------------

    public function show()
    {
        //if (!checkPerm('view', true)) return;

        //YuiLib::load('tabview,charts');
        Util::get_js(FormaLms\lib\Get::rel_path('adm') . '/views/dashboard/dashboard.js', true, true);
        Util::get_js(FormaLms\lib\Get::rel_path('adm') . '/views/dashboard/js/show.js', true, true);
        Util::get_css(FormaLms\lib\Get::rel_path('adm') . '/views/dashboard/css/show.css', true, true);
        Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);

        //check if there are any problems with technical configuration of the server
        $php_conf = ini_get_all(); //this
        $problem = false;

        if ($php_conf['register_globals']['local_value']) {
            $problem = true;
        }

        if (version_compare(phpversion(), '5.2.0', '>')) {
            if ($php_conf['allow_url_include']['local_value']) {
                $problem = true;
            }
        }

        $arr_report = $this->model->getDashBoardReportList();

        //load date script for user creation and editing mask
        Form::loadDatefieldScript();

        //render view
        $this->render('show', [
            'diagnostic_problem' => $problem,
            'lang_dir' => Lang::direction(),

            'can_approve' => checkPerm('approve_waiting_user', true, 'directory', 'framework'),
            'version' => $this->model->getVersionExternalInfo(),
            'is_godadmin' => Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN,

            'user_stats' => $this->model->getUsersStats(),
            'users_access' => [
                'month' => $this->model->getUsersAccessCount('month'),
                '3months' => $this->model->getUsersAccessCount('3months'),
                '6months' => $this->model->getUsersAccessCount('6months'),
            ],
            'users_active' => [
                'month' => $this->model->getUsersActiveCount('month'),
                '3months' => $this->model->getUsersActiveCount('3months'),
                '6months' => $this->model->getUsersActiveCount('6months'),
            ],
            'users_access_trend' => $this->model->getUsersMonthlyTrend('access', 6),
            'users_active_trend' => $this->model->getUsersMonthlyTrend('active', 6),
            'companies_count' => $this->model->getCompaniesCount(),
            'companies_list' => $this->model->getCompaniesList(),
            'companies_trend' => $this->model->getCompaniesMonthlyTrend(6),
            'current_month_label' => Lang::t('_MONTH_' . date('m'), 'standard'),

            'course_stats' => $this->model->getCoursesStats(),
            'courses_active' => $this->model->getActiveCoursesCount(),
            'course_months_stats' => $this->model->getCoursesMonthsStats(),
            'certificates_issued' => $this->model->getCertificatesIssuedCount(),
            'courses_trend' => $this->model->getCoursesEnrollmentCompletionTrend(6),
            'top_courses' => $this->model->getTopViewedCourses(5),
            'courses_by_category' => $this->model->getCoursesByCategory(),

            'permissions' => $this->permissions,
            'reports' => $arr_report,
        ]);
    }

    public function deactivate()
    {
        $output = ['success' => $this->model->deactivateFeeds()];
        echo $this->json->encode($output);
    }

    public function activate()
    {
        $output = ['success' => $this->model->activateFeeds()];
        echo $this->json->encode($output);
    }

    public function diagnostic_dialogTask()
    {
        $this->render('diagnostic_dialog', [
            'title' => Lang::t('_SERVERINFO', 'configuration'),
            'php_conf' => ini_get_all(),
            'sql_server_info' => sql_get_server_info(),
            'sql_additional_info' => $this->model->getSqlInfo(),
            'json' => $this->json,
        ]);
    }

    public function user_status_dialogTask()
    {
        $this->render('user_status_dialog', [
            'title' => Lang::t('_PROFILE', 'profile'),
            'json' => $this->json,
        ]);
    }

    public function certificateTask()
    {
        $json = new Services_JSON();
        $body = '';

        $body .= Form::openForm('subscr_course_form', 'ajax.adm_server.php?r=adm/dashboard/findcertificate');

        $body .= Form::getHidden('subscr_id_user', 'id_user', 0); //init with invalid id: we have to choose it with autocomplete textfield
        $body .= Form::getHidden('subscr_id_course', 'id_course', 0); //init with invalid id: we have to choose it with autocomplete textfield

        $body .= Form::getTextfield(Lang::t('_COURSE', 'standard'), 'certificate_course', 'certificate_course', 255, '');
        $body .= '<div id="certificate_course_container"></div>';

        $body .= Form::getTextfield(Lang::t('_USER', 'standard'), 'certificate_userid', 'certificate_userid', 255, '');
        $body .= '<div id="certificate_userid_container"></div>';

        $body .= Form::closeForm();

        $output['header'] = Lang::t('_CERTIFICATE', 'menu');
        $output['body'] = $body;
        echo $json->encode($output);
    }

    public function findcertificateTask()
    {
        $json = new Services_JSON();

        $c_course = FormaLms\lib\Get::req('certificate_course', DOTY_MIXED, '');
        $id_course = FormaLms\lib\Get::req('id_course', DOTY_INT, 0);
        $c_userid = FormaLms\lib\Get::req('certificate_userid', DOTY_MIXED, '');
        $id_user = FormaLms\lib\Get::req('id_user', DOTY_INT, 0);

        require_once _lms_ . '/lib/lib.course.php';
        $man_course = new Man_Course();
        $acl_man = Docebo::user()->getAclManager();

        if ($id_user <= 0) {
            $id_user = $acl_man->getUserST($c_userid);
        }
        if ($id_course <= 0) {
            ////eliminates che code from the course name
            if ($c_course != '') {
                $c_course = trim(preg_replace('|^\[([^\]]*)\][\s]*|i', '', $c_course));
            }
            $id_course = $man_course->getCourseIdByName($c_course);
        }

        //check if input is correct
        if ($id_user <= 0 || $id_course <= 0) {
            $output['success'] = false;
            $output['message'] = Lang::t('_INVALID_INPUT'); // International message
            echo $this->json->encode($output);

            return;
        }

        require_once Forma::inc(_lms_ . '/lib/lib.certificate.php');
        $cert = new Certificate();
        $released = $cert->certificateStatus($id_user, $id_course);
        $print = [];
        foreach ($released as $id_cert => $name) {
            $print[] = '<a class="ico-wt-sprite subs_pdf" href="index.php?modname=certificate&certificate_id=' . $id_cert . '&course_id=' . $id_course . '&user_id=' . $id_user . '&op=send_certificate&of_platform=lms">'
                . '<span>' . $name . '</span>'
                . '</a>';
        }

        $res = $this->model->getDashBoardCertList($id_course, $id_user);
        $relesable = [];
        while (list($id_certificate, $name, $available_for_status, $user_status) = sql_fetch_row($res)) {
            if ($cert->canRelease($available_for_status, $user_status) && !isset($released[$id_certificate])) {
                $relesable[] = '<a class="ico-wt-sprite subs_pdf" href="index.php?modname=certificate&certificate_id=' . $id_certificate . '&course_id=' . $id_course . '&user_id=' . $id_user . '&op=print_certificate&of_platform=lms">'
                    . '<span>' . $name . '</span>'
                    . '</a>';
            }
        }

        $output['success'] = true;
        $output['message'] = '';
        $output['message'] .= '<b>' . Lang::t('_CERTIFICATE_VIEW_CAPTION', 'certificate') . ':</b> ' . (count($print) > 0
            ? implode(', ', $print)
            : Lang::t('_NONE', 'standard')
        ) . '<br /><br />';
        $output['message'] .= '<b>' . Lang::t('_NEW_CERTIFICATE', 'certificate') . ':</b> ' . (count($relesable) > 0
            ? implode(', ', $relesable)
            : Lang::t('_NONE', 'standard')
        ) . '<br /><br />';

        echo $this->json->encode($output);
    }

    public function users_drilldownTask()
    {
        $kind = FormaLms\lib\Get::req('kind', DOTY_ALPHANUM, 'total');
        $period = FormaLms\lib\Get::req('period', DOTY_ALPHANUM, 'month');

        $titles = [
            'total' => 'Totale utenti caricati',
            'online' => 'Utenti connessi ora',
            'admin' => 'Totale amministratori',
            'superadmin' => 'Totale super admin',
            'access' => 'Accessi',
            'active' => 'Utenti attivi',
        ];

        $this->render('users_drilldown_dialog', [
            'rows' => $this->model->getUsersDrilldownList($kind, $period),
            'title' => isset($titles[$kind]) ? $titles[$kind] : 'Dettaglio',
            'json' => $this->json,
        ]);
    }

    public function companies_drilldownTask()
    {
        $idOrg = FormaLms\lib\Get::req('idOrg', DOTY_INT, 0);

        $users = [];
        if ($idOrg <= 0) {
            // root: per GodAdmin, lista delle aziende; per Admin, la sua azienda
            $companies = $this->model->getCompaniesList();
            $children = array_map(function ($c) {
                return ['idOrg' => $c['idOrg'], 'name' => $c['name'], 'has_children' => true];
            }, $companies);
            $current_name = 'Tutte le aziende';
        } else {
            $children = $this->model->getCompanyChildren($idOrg);
            $users = $this->model->getCompanyDirectUsers($idOrg);
            $current_name = 'Sotto-nodi';
        }

        $this->render('companies_drilldown_dialog', [
            'children' => $children,
            'users' => $users,
            'current_name' => $current_name,
            'json' => $this->json,
        ]);
    }

    public function course_drilldownTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);

        $this->render('course_drilldown_dialog', [
            'rows' => $this->model->getCourseEnrolledUsers($idCourse),
            'json' => $this->json,
        ]);
    }

    public function courses_drilldownTask()
    {
        $kind = FormaLms\lib\Get::req('kind', DOTY_ALPHANUM, 'active');
        $idCategory = FormaLms\lib\Get::req('idCategory', DOTY_INT, 0);

        $this->render('courses_drilldown_dialog', [
            'kind' => $kind,
            'rows' => $this->model->getCoursesDrilldownList($kind, $idCategory),
            'json' => $this->json,
        ]);
    }

    public function exportformatTask()
    {
        $this->render('export_dialog', [
            'id_report' => FormaLms\lib\Get::req('id_report', DOTY_INT, true),
            'json' => $this->json,
        ]);
    }
}
