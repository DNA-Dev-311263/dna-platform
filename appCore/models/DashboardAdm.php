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

defined('IN_FORMA') or exit('Direct access is forbidden.');

class DashboardAdm extends Model
{
    protected $db;

    protected $user_level;
    protected $users_filter;
    protected $courses_filter;

    //--- init functions ---------------------------------------------------------

    public function __construct()
    {
        $this->db = DbConn::getInstance();

        $this->users_filter = false;
        $this->courses_filter = false;

        $this->user_level = Docebo::user()->getUserLevelId();
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            require_once _base_ . '/lib/lib.preference.php';

            $adminManager = new AdminPreference();
            $this->users_filter = $adminManager->getAdminUsers(Docebo::user()->getIdST());

            $all_courses = false;
            $array_courses = [];
            $admin_courses = $adminManager->getAdminCourse(Docebo::user()->getIdST());
            foreach ($admin_courses['course'] as $key => $id_course) {
                if ($key > 0) {
                    $array_courses[$key] = $id_course;
                }
            }
            if (isset($admin_courses['course'][0])) {
                $all_courses = true;
            } elseif (isset($admin_courses['course'][-1])) {
                require_once _lms_ . '/lib/lib.catalogue.php';
                $cat_man = new Catalogue_Manager();
                $user_catalogue = $cat_man->getUserAllCatalogueId(Docebo::user()->getIdSt());
                if (count($user_catalogue) > 0) {
                    $courses = [];
                    foreach ($user_catalogue as $id_cat) {
                        $catalogue_course = &$cat_man->getCatalogueCourse($id_cat, true);
                        if (empty($courses)) {
                            $courses = $catalogue_course;
                        } else {
                            $courses = array_merge($courses, $catalogue_course);
                        }
                    }
                    foreach ($courses as $id_course) {
                        if ($id_course != 0) {
                            $array_courses[$id_course] = $id_course;
                        }
                    }
                } elseif (FormaLms\lib\Get::sett('on_catalogue_empty', 'off') == 'on') {
                    $all_courses = true;
                }
            } else {
                if (!empty($admin_courses['coursepath'])) {
                    require_once _lms_ . '/lib/lib.coursepath.php';
                    $path_man = new CoursePath_Manager();
                    $coursepath_course = &$path_man->getAllCourses($admin_courses['coursepath']);
                    $array_courses = array_merge($array_courses, $coursepath_course);
                }
                if (!empty($admin_courses['catalogue'])) {
                    require_once _lms_ . '/lib/lib.catalogue.php';
                    $cat_man = new Catalogue_Manager();
                    foreach ($admin_courses['catalogue'] as $id_cat) {
                        $catalogue_course = &$cat_man->getCatalogueCourse($id_cat, true);
                        $array_courses = array_merge($array_courses, $catalogue_course);
                    }
                }
            }

            if (!$all_courses) {
                $this->courses_filter = array_values($array_courses);
            }
            //if "$all_courses" is true, than leave "$this->courses_filter" as false
        }
        parent::__construct();
    }

    public function getPerm()
    {
        return ['view' => 'standard/view.png'];
    }

    //----------------------------------------------------------------------------
    public function deactivateFeeds()
    {
        $query = "UPDATE %adm_setting SET param_value = 'off' WHERE param_name = 'welcome_use_feed'";
        $res = $this->db->query($query);

        return $res ? true : false;
    }

    public function activateFeeds()
    {
        $query = "UPDATE %adm_setting SET param_value = 'on' WHERE param_name = 'welcome_use_feed'";
        $res = $this->db->query($query);

        return $res ? true : false;
    }

    public function getSqlInfo()
    {
        $query = 'SELECT @@GLOBAL.sql_mode';
        $res = $this->db->query($query);
        list($sql_mode) = $this->db->fetch_row($res);

        $info_character = [];
        $info_collation = [];

        //string sql_client_encoding ([ resource $link_identifier ] )
        $query = "SHOW VARIABLES LIKE 'character_set%'";
        $res = $this->db->query($query);
        while (list($name, $value) = $this->db->fetch_row($res)) {
            $info_character[$name] = $value;
        }

        $query = "SHOW VARIABLES LIKE 'collation%'";
        $res = $this->db->query($query);
        while (list($name, $value) = $this->db->fetch_row($res)) {
            $info_collation[$name] = $value;
        }

        $query = 'SELECT @@time_zone';
        $res = $this->db->query($query);
        list($sql_timezone) = $this->db->fetch_row($res);

        return [
            'sql_mode' => $sql_mode,
            'character_info' => $info_character,
            'collation_info' => $info_collation,
            'sql_timezone' => $sql_timezone,
        ];
    }

    public function updateVersion($old_version, $new_version)
    {
        if ($this->db->query("UPDATE %adm_setting SET param_value = '" . $new_version . "' WHERE param_name = 'core_version'")) {
            return $new_version;
        } else {
            return $old_version;
        }
    }

    public function getVersionExternalInfo()
    {
        $version = [
            'db_version' => FormaLms\lib\Get::sett('core_version'),
            'file_version' => _file_version_,
            'online_version' => '',
        ];

        // check for differences beetween files and database version
        if (version_compare($version['file_version'], $version['db_version']) == 1) {
            switch ($version['db_version']) {
                // handling old docebo ce version
                case '3.6.0.3':
                case '3.6.0.4':
                case '4.0.0':
                case '4.0.5':
                    break;
                case '4.0.1':
                case '4.0.2':
                case '4.0.3':
                case '4.0.4':
                    $version['db_version'] = $this->updateVersion($version['db_version'], '4.0.5');
                    break;
                // new formalms versions
                case '1.0':
                case '1.1':
                case '1.2':
                    break;
            }
        }

        if (FormaLms\lib\Get::sett('welcome_use_feed') == 'on') {
            require_once _base_ . '/lib/lib.fsock_wrapper.php';
            $fp = new Fsock();
            $versions_raw = $fp->send_request('http://www.formalms.org/versions/list');
            if ($versions_raw
                && ($versions = json_decode($versions_raw, true))
                && isset($versions[0])
                && isset($versions[0]['version'])
            ) {
                $version['online_version'] = $versions[0]['version'];
            }
        }

        return $version;
    }

    /**
     * various stats and data retrieving to display in the dashboard.
     *
     * @param bool $stats_required
     * @param bool $arr_users
     *
     * @return array
     */
    public function getUsersStats($stats_required = false, $arr_users = false)
    {
        $aclManager = Docebo::user()->getACLManager();
        $users = [];
        if ($stats_required == false || empty($stats_required) || !is_array($stats_required)) {
            $stats_required = ['all', 'suspended', 'register_today', 'register_yesterday', 'register_7d',
                'now_online', 'inactive_30d', 'waiting', 'superadmin', 'admin', 'public_admin', ];
        }
        $stats_required = array_flip($stats_required);

        $data = new PeopleDataRetriever($GLOBALS['dbConn'], $GLOBALS['prefix_fw']);

        if (!empty($this->users_filter)) {
            $data->setUserFilter($this->users_filter);
        }

        if (isset($stats_required['all'])) {
            $users['all'] = $data->getTotalRows();
        }
        if (isset($stats_required['suspended'])) {
            $data->addFieldFilter('valid', 0);
            $data->addFieldFilter('userid', 'Anonymous', '<>'); //or idst <> Docebo::user()->getAnonymousId() ...
            $users['suspended'] = $data->getTotalRows();
        }
        if (isset($stats_required['register_today'])) {
            $data->resetFieldFilter();
            $data->addFieldFilter('register_date', date('Y-m-d') . ' 00:00:00', '>');
            $users['register_today'] = $data->getTotalRows();
        }
        if (isset($stats_required['register_yesterday'])) {
            $data->resetFieldFilter();
            $yesterday = date('Y-m-d', time() - 86400);
            $data->addFieldFilter('register_date', $yesterday . ' 00:00:00', '>');
            $data->addFieldFilter('register_date', $yesterday . ' 23:59:59', '<');
            $users['register_yesterday'] = $data->getTotalRows();
        }
        if (isset($stats_required['register_7d'])) {
            $data->resetFieldFilter();
            $sevendaysago = date('Y-m-d', time() - (7 * 86400));
            $data->addFieldFilter('register_date', $sevendaysago . ' 00:00:00', '>');
            $users['register_7d'] = $data->getTotalRows();
        }
        if (isset($stats_required['now_online'])) {
            $data->resetFieldFilter();
            $data->addFieldFilter('lastenter', date('Y-m-d H:i:s', time() - REFRESH_LAST_ENTER), '>');
            $users['now_online'] = $data->getTotalRows();
            if (($arr_users !== false) && (is_array($arr_users)) && (count($arr_users) > 0)) {
                $data->setUserFilter($arr_users);
                $users['now_online_filtered'] = $data->getTotalRows();
            } else {
                $users['now_online_filtered'] = 0;
            }
        }
        if (isset($stats_required['inactive_30d'])) {
            $data->resetFieldFilter();
            $data->addFieldFilter('lastenter', date('Y-m-d', time() - 30 * 86400) . ' 00:00:00', '<');
            $data->addFieldFilter('userid', 'Anonymous', '<>'); //or idst <> Docebo::user()->getAnonymousId() ...
            $users['inactive_30d'] = $data->getTotalRows();
        }
        if (isset($stats_required['waiting'])) {
            $users['waiting'] = $aclManager->getTempUserNumber();
        }
        if (isset($stats_required['superadmin'])) {
            $idst_sadmin = $aclManager->getGroupST(ADMIN_GROUP_GODADMIN);
            $users['superadmin'] = $aclManager->getGroupUMembersNumber($idst_sadmin);
        }
        if (isset($stats_required['admin'])) {
            $idst_admin = $aclManager->getGroupST(ADMIN_GROUP_ADMIN);
            $users['admin'] = $aclManager->getGroupUMembersNumber($idst_admin);
        }

        return $users;
    }

    public function getCoursesStats()
    {
        require_once _lms_ . '/lib/lib.course.php';
        require_once _lms_ . '/lib/lib.course_managment.php';

        $course_man = new AdminCourseManagment();

        return $course_man->getCoursesStats($this->courses_filter);
    }

    /**
     * Corsi attivi/accessibili agli utenti. La libreria framework
     * (AdminCourseManagment::getCoursesStats) conta solo status=1
     * (CST_AVAILABLE/"Disponibile"), ma il controllo di accesso reale al corso
     * (vedi lib.course.php, switch su $course['status']) blocca l'accesso solo
     * per CST_PREPARATION(0)/CST_CONCLUDED(3)/CST_CANCELLED(4) — quindi anche
     * CST_EFFECTIVE(2, "Confermato") e' un corso pienamente attivo.
     */
    public function getActiveCoursesCount()
    {
        $courses_filter_sql = '';
        if ($this->courses_filter !== false) {
            $courses_filter_sql = empty($this->courses_filter)
                ? ' AND 0 '
                : ' AND idCourse IN (' . implode(',', $this->courses_filter) . ') ';
        }

        $query = "SELECT COUNT(*) FROM %lms_course WHERE status IN ('1','2') " . $courses_filter_sql;
        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    public function getCoursesMonthsStats()
    {
        $output = [
            'month_subs_1' => 0,
            'month_subs_2' => 0,
            'month_subs_3' => 0,
        ];

        //extract subscriptions for the last three months
        for ($i = 0; $i < 3; ++$i) {
            $date = date('Y-m', strtotime('-' . $i . ' months'));
            $query = "SELECT COUNT(*) FROM %lms_courseuser WHERE date_inscr>'" . $date . "-01' AND date_inscr<'" . $date . "-31'";
            if ($this->user_level != ADMIN_GROUP_GODADMIN) {
                if ($this->users_filter !== false) {
                    if (empty($this->users_filter)) {
                        $query .= ' AND 0 ';
                    } else {
                        $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                    }
                }
                if ($this->courses_filter !== false) {
                    if (empty($this->courses_filter)) {
                        $query .= ' AND 0 ';
                    } else {
                        $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                    }
                }
            }
            list($num) = $this->db->fetch_row($this->db->query($query));
            $output['month_subs_' . ($i + 1)] = (int) $num;
        }

        return $output;
    }

    public function getUsersChartAccessData($how_many_days)
    {
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT MAX(enterTime) FROM %lms_tracksession '
            . " WHERE enterTime>'" . $last_date . " 00:00:00' "
            . " AND enterTime<='" . $today . " 23:59:59' GROUP BY idUser";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($last_access) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($last_access));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        foreach ($dates as $date => $count) {
            $output[] = ['x_axis' => $date, 'c' => $count];
        }

        return $output;
    }

    public function getUsersChartRegisterData($how_many_days)
    {
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT register_date FROM %adm_user '
            . " WHERE register_date>'" . $last_date . " 00:00:00' "
            . " AND register_date<='" . $today . " 23:59:59' ";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idst IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
        }
        $query .= ' ORDER BY register_date DESC';
        $res = $this->db->query($query);
        while (list($last_access) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($last_access));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        foreach ($dates as $date => $count) {
            $output[] = ['x_axis' => $date, 'y_axis' => $count];
        }

        return $output;
    }

    public function getUsersChartAccessDataJS($how_many_days)
    {
        require_once _base_ . '/lib/lib.json.php';
        $json = new Services_JSON();
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT MAX(enterTime) FROM %lms_tracksession '
            . " WHERE enterTime>'" . $last_date . " 00:00:00' "
            . " AND enterTime<='" . $today . " 23:59:59' GROUP BY idUser";

        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);

        while (list($last_access) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($last_access));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }
        $outputCounts = [];
        $outputDates = [];
        foreach ($dates as $date => $count) {
            if (!is_array($count) && !is_array($date)) {
                $outputCounts[] = $count;
                $outputDates[] = $date;
            }
        }

        return ['x_axis' => $json->encode($outputDates), 'y_axis' => $json->encode($outputCounts)];
    }

    public function getUsersChartRegisterDataJS($how_many_days)
    {
        require_once _base_ . '/lib/lib.json.php';
        $json = new Services_JSON();
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT register_date FROM %adm_user '
            . " WHERE register_date>'" . $last_date . " 00:00:00' "
            . " AND register_date<='" . $today . " 23:59:59' ";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idst IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
        }
        $query .= ' ORDER BY register_date DESC';
        $res = $this->db->query($query);
        while (list($last_access) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($last_access));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        $outputCounts = [];
        $outputDates = [];
        foreach ($dates as $date => $count) {
            if (!is_array($count) && !is_array($date)) {
                $outputCounts[] = $count;
                $outputDates[] = $date;
            }
        }

        return ['x_axis' => $json->encode($outputDates), 'y_axis' => $json->encode($outputCounts)];
    }

    public function getCoursesChartSubscriptionData($how_many_days)
    {
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT date_inscr FROM %lms_courseuser '
            . " WHERE date_inscr>'" . $last_date . " 00:00:00' AND date_inscr<='" . $today . " 23:59:59'";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($date_inscr) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($date_inscr));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        foreach ($dates as $date => $count) {
            $output[] = ['x_axis' => $date, 'y_axis' => $count];
        }

        return $output;
    }

    public function getCoursesChartStartAttendingData($how_many_days)
    {
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT date_first_access FROM %lms_courseuser '
            . " WHERE date_first_access>'" . $last_date . " 00:00:00' AND date_first_access<='" . $today . " 23:59:59'";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($date_first) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($date_first));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        foreach ($dates as $date => $count) {
            $output[] = ['x_axis' => $date, 'y_axis' => $count];
        }

        return $output;
    }

    public function getCoursesChartCompletedData($how_many_days)
    {
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT date_complete FROM %lms_courseuser '
            . " WHERE date_complete>'" . $last_date . " 00:00:00' AND date_complete<='" . $today . " 23:59:59'";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($date_first) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($date_first));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        foreach ($dates as $date => $count) {
            $output[] = ['x_axis' => $date, 'y_axis' => $count];
        }

        return $output;
    }

    public function getCoursesChartSubscriptionDataJS($how_many_days)
    {
        require_once _base_ . '/lib/lib.json.php';
        $json = new Services_JSON();
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT date_inscr FROM %lms_courseuser '
            . " WHERE date_inscr>'" . $last_date . " 00:00:00' AND date_inscr<='" . $today . " 23:59:59'";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($date_inscr) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($date_inscr));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        $outputCounts = [];
        $outputDates = [];
        foreach ($dates as $date => $count) {
            if (!is_array($count) && !is_array($date)) {
                $outputCounts[] = $count;
                $outputDates[] = $date;
            }
        }

        return ['x_axis' => $json->encode($outputDates), 'y_axis' => $json->encode($outputCounts)];
    }

    public function getCoursesChartStartAttendingDataJS($how_many_days)
    {
        require_once _base_ . '/lib/lib.json.php';
        $json = new Services_JSON();
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT date_first_access FROM %lms_courseuser '
            . " WHERE date_first_access>'" . $last_date . " 00:00:00' AND date_first_access<='" . $today . " 23:59:59'";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($date_first) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($date_first));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        $outputCounts = [];
        $outputDates = [];
        foreach ($dates as $date => $count) {
            if (!is_array($count) && !is_array($date)) {
                $outputCounts[] = $count;
                $outputDates[] = $date;
            }
        }

        return ['x_axis' => $json->encode($outputDates), 'y_axis' => $json->encode($outputCounts)];
    }

    public function getCoursesChartCompletedDataJS($how_many_days)
    {
        require_once _base_ . '/lib/lib.json.php';
        $json = new Services_JSON();
        $output = [];
        $dates = [];

        $today = date('Y-m-d');
        for ($i = $how_many_days - 1; $i >= 0; --$i) {//for ($i=0; $i<$how_many_days; $i++) {
            $date = date('Y-m-d', strtotime('-' . (int) $i . ' days'));
            $dates[$date] = 0;
        }
        $last_date = date('Y-m-d', strtotime('-' . ((int) $how_many_days - 1) . ' days'));

        $query = 'SELECT date_complete FROM %lms_courseuser '
            . " WHERE date_complete>'" . $last_date . " 00:00:00' AND date_complete<='" . $today . " 23:59:59'";
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                if (empty($this->users_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idUser IN (' . implode(',', $this->users_filter) . ') ';
                }
            }
            if ($this->courses_filter !== false) {
                if (empty($this->courses_filter)) {
                    $query .= ' AND 0 ';
                } else {
                    $query .= ' AND idCourse IN (' . implode(',', $this->courses_filter) . ')';
                }
            }
        }
        $res = $this->db->query($query);
        while (list($date_first) = $this->db->fetch_row($res)) {
            $date = date('Y-m-d', strtotime($date_first));
            if (isset($dates[$date])) {
                ++$dates[$date];
            }
        }

        $outputCounts = [];
        $outputDates = [];
        foreach ($dates as $date => $count) {
            if (!is_array($count) && !is_array($date)) {
                $outputCounts[] = $count;
                $outputDates[] = $date;
            }
        }

        return ['x_axis' => $json->encode($outputDates), 'y_axis' => $json->encode($outputCounts)];
    }

    public function getDashBoardReportList()
    {
        $report_list = [];
        $where_cond = '';
        $user_idst = Docebo::user()->getIdSt();
        $user_level = Docebo::user()->getUserLevelId();

        if ($user_level != ADMIN_GROUP_GODADMIN) {
            $where_cond .= "AND (author='" . $user_idst . "' OR is_public>0)";
        }

        $query = 'SELECT id_filter, filter_name, author, creation_date, is_public '
            . ' FROM %lms_report_filter '
            . ' WHERE (author>0 OR is_public>0) ' . $where_cond
            . ' ORDER BY filter_name ASC ';

        $r = $this->db->query($query);
        while (list($idrep, $name, $author, $creation_date, $is_public) = $this->db->fetch_row($r)) {
            $report_list[$idrep] = $name;
        }

        return $report_list;
    }

    public function getDashBoardCertList($id_course, $id_user)
    {
        $query = 'SELECT cc.id_certificate, ce.name, available_for_status, cu.status '
            . ' FROM (%lms_certificate AS ce '
            . ' JOIN %lms_certificate_course AS cc '
            . '        ON (ce.id_certificate = cc.id_certificate) )'
            . ' JOIN %lms_courseuser AS cu '
            . '        ON (cu.idCourse = cc.id_course)'
            . ' WHERE cu.idCourse = ' . (int) $id_course . ' '
            . '    AND idUser = ' . (int) $id_user . ' ';

        return sql_query($query);
    }

    /**
     * Convert a period key ('month','3months','6months') into a [from, to] date range.
     *
     * @param string $period
     * @return array
     */
    private function periodToRange($period)
    {
        $to = date('Y-m-d 23:59:59');
        switch ($period) {
            case '3months':
                $from = date('Y-m-01 00:00:00', strtotime('-2 months'));
                break;
            case '6months':
                $from = date('Y-m-01 00:00:00', strtotime('-5 months'));
                break;
            case 'month':
            default:
                $from = date('Y-m-01 00:00:00');
                break;
        }

        return [$from, $to];
    }

    private function scopeFilterSql($users_column, $courses_column)
    {
        $sql = '';
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            if ($this->users_filter !== false) {
                $sql .= empty($this->users_filter)
                    ? ' AND 0 '
                    : ' AND ' . $users_column . ' IN (' . implode(',', $this->users_filter) . ') ';
            }
            if ($this->courses_filter !== false) {
                $sql .= empty($this->courses_filter)
                    ? ' AND 0 '
                    : ' AND ' . $courses_column . ' IN (' . implode(',', $this->courses_filter) . ') ';
            }
        }

        return $sql;
    }

    /**
     * Utenti che hanno aperto una sessione nel periodo (accessi alla piattaforma,
     * indipendentemente dal fatto che abbiano poi visionato contenuti o no).
     */
    public function getUsersAccessCount($period)
    {
        list($from, $to) = $this->periodToRange($period);

        // join di validazione: learning_tracksession puo' contenere righe storiche
        // di utenti nel frattempo cancellati da core_user (stesso problema verificato
        // su learning_certificate_assign) — contiamo solo utenti ancora esistenti,
        // cosi' il numero coincide sempre con l'elenco mostrabile nel drill-down.
        $query = 'SELECT COUNT(DISTINCT ts.idUser) FROM %lms_tracksession ts '
            . ' JOIN %adm_user u ON u.idst = ts.idUser '
            . " WHERE ts.enterTime BETWEEN '" . $from . "' AND '" . $to . "' "
            . $this->scopeFilterSql('ts.idUser', 'ts.idCourse');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Utenti che hanno visionato almeno un contenuto formativo nel periodo
     * (sempre filtrato sull'anno corrente, come richiesto dalla spec).
     */
    public function getUsersActiveCount($period)
    {
        list($from, $to) = $this->periodToRange($period);
        $year_start = date('Y-01-01 00:00:00');
        if ($from < $year_start) {
            $from = $year_start;
        }

        // learning_commontrack.idReference points to learning_organization.idOrg, NOT
        // to learning_course.idCourse directly — must join through learning_organization
        // to filter by course (see lib.stats.php:176-180 for the established pattern).
        // Join anche su %adm_user per lo stesso motivo di getUsersAccessCount() sopra.
        $query = 'SELECT COUNT(DISTINCT ct.idUser) FROM %lms_commontrack ct '
            . ' JOIN %adm_user u ON u.idst = ct.idUser '
            . ' JOIN %lms_organization org ON org.idOrg = ct.idReference '
            . " WHERE ct.dateAttempt BETWEEN '" . $from . "' AND '" . $to . "' "
            . $this->scopeFilterSql('ct.idUser', 'org.idCourse');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Andamento mensile (ultimi $how_many_months mesi) per Accessi o Utenti attivi.
     *
     * @param string $type 'access' o 'active'
     * @param int $how_many_months
     * @return array lista di ['label' => 'Gen', 'count' => N], dal piu' vecchio al piu' recente
     */
    public function getUsersMonthlyTrend($type, $how_many_months = 6)
    {
        $output = [];
        for ($i = $how_many_months - 1; $i >= 0; --$i) {
            $month_start = date('Y-m-01 00:00:00', strtotime('-' . $i . ' months'));
            $month_end = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            if ($type === 'active') {
                // same idReference -> idOrg join + %adm_user validation as
                // getUsersActiveCount() above
                $query = 'SELECT COUNT(DISTINCT ct.idUser) FROM %lms_commontrack ct '
                    . ' JOIN %adm_user u ON u.idst = ct.idUser '
                    . ' JOIN %lms_organization org ON org.idOrg = ct.idReference '
                    . " WHERE ct.dateAttempt BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                    . $this->scopeFilterSql('ct.idUser', 'org.idCourse');
            } else {
                // %adm_user validation as getUsersAccessCount() above
                $query = 'SELECT COUNT(DISTINCT ts.idUser) FROM %lms_tracksession ts '
                    . ' JOIN %adm_user u ON u.idst = ts.idUser '
                    . " WHERE ts.enterTime BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                    . $this->scopeFilterSql('ts.idUser', 'ts.idCourse');
            }

            list($count) = $this->db->fetch_row($this->db->query($query));
            $output[] = ['label' => date('M', strtotime($month_start)), 'count' => (int) $count];
        }

        return $output;
    }

    /**
     * Elenco utenti per il drill-down della Dashboard.
     *
     * @param string $kind 'total','online','admin','superadmin','access','active'
     * @param string|false $period 'month'|'3months'|'6months', usato solo per 'access'/'active'
     */
    public function getUsersDrilldownList($kind, $period = false)
    {
        $aclManager = Docebo::user()->getACLManager();
        $rows = [];

        if ($kind === 'admin' || $kind === 'superadmin') {
            $idst_group = $aclManager->getGroupST($kind === 'superadmin' ? ADMIN_GROUP_GODADMIN : ADMIN_GROUP_ADMIN);
            $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname, u.email FROM %adm_user u '
                . ' JOIN %adm_group_members gm ON gm.idstMember = u.idst '
                . " WHERE gm.idst = " . (int) $idst_group;
            $res = $this->db->query($query);
            while (list($idst, $userid, $firstname, $lastname, $email) = $this->db->fetch_row($res)) {
                $rows[] = [
                    'idst' => $idst,
                    'userid' => ltrim($userid, '/'),
                    'name' => $firstname . ' ' . $lastname,
                    'email' => $email,
                    'company' => $this->getCompanyNameForUser($idst),
                ];
            }

            return $rows;
        }

        if ($kind === 'access' || $kind === 'active') {
            list($from, $to) = $this->periodToRange($period ?: 'month');
            if ($kind === 'active') {
                // ct.idReference -> learning_organization.idOrg, not idCourse directly;
                // join through organization to filter by course (same fix as Task 4's
                // getUsersActiveCount/getUsersMonthlyTrend).
                $query = 'SELECT DISTINCT u.idst, u.userid, u.firstname, u.lastname FROM %adm_user u '
                    . ' JOIN %lms_commontrack ct ON ct.idUser = u.idst '
                    . ' JOIN %lms_organization org ON org.idOrg = ct.idReference '
                    . " WHERE ct.dateAttempt BETWEEN '" . $from . "' AND '" . $to . "' "
                    . $this->scopeFilterSql('ct.idUser', 'org.idCourse');
            } else {
                $query = 'SELECT DISTINCT u.idst, u.userid, u.firstname, u.lastname FROM %adm_user u '
                    . ' JOIN %lms_tracksession ts ON ts.idUser = u.idst '
                    . " WHERE ts.enterTime BETWEEN '" . $from . "' AND '" . $to . "' "
                    . $this->scopeFilterSql('ts.idUser', 'ts.idCourse');
            }
            $res = $this->db->query($query);
            while (list($idst, $userid, $firstname, $lastname) = $this->db->fetch_row($res)) {
                $rows[] = [
                    'idst' => $idst,
                    'userid' => ltrim($userid, '/'),
                    'name' => $firstname . ' ' . $lastname,
                    'company' => $this->getCompanyNameForUser($idst),
                ];
            }

            return $rows;
        }

        // 'total' or 'online'
        $data = new PeopleDataRetriever($GLOBALS['dbConn'], $GLOBALS['prefix_fw']);
        if (!empty($this->users_filter)) {
            $data->setUserFilter($this->users_filter);
        }
        if ($kind === 'online') {
            $data->addFieldFilter('lastenter', date('Y-m-d H:i:s', time() - REFRESH_LAST_ENTER), '>');
        }
        // getRows() returns a raw DB resource (SELECT idst, userid, firstname, lastname, email, valid, signature),
        // not an array — fetch it row by row like the rest of this codebase does.
        $res = $data->getRows(0, 500);
        while ($row = sql_fetch_assoc($res)) {
            $rows[] = [
                'idst' => $row['idst'],
                'userid' => ltrim($row['userid'], '/'),
                'name' => $row['firstname'] . ' ' . $row['lastname'],
                'company' => $this->getCompanyNameForUser($row['idst']),
            ];
        }

        return $rows;
    }

    /**
     * Risale dal nodo di organigramma dell'admin corrente fino al nodo di
     * primo livello (l'"azienda" che gestisce). Ritorna false per il GodAdmin
     * (che non e' scoped su una singola azienda) o se l'admin non e' legato
     * a nessun nodo.
     */
    public function getAdminCompanyNode()
    {
        if ($this->user_level == ADMIN_GROUP_GODADMIN) {
            return false;
        }

        $query = 'SELECT oct.idOrg, oct.idParent FROM core_org_chart_tree oct '
            . ' JOIN core_group_members gm ON (gm.idst = oct.idst_oc OR gm.idst = oct.idst_ocd) '
            . ' WHERE gm.idstMember = ' . (int) Docebo::user()->getIdSt()
            . ' LIMIT 1';
        $res = $this->db->query($query);
        if (!$res || $this->db->num_rows($res) <= 0) {
            return false;
        }
        list($idOrg, $idParent) = $this->db->fetch_row($res);

        // risale fino al nodo con idParent = 0
        while ((int) $idParent !== 0) {
            $query = 'SELECT idOrg, idParent FROM core_org_chart_tree WHERE idOrg = ' . (int) $idParent;
            $res = $this->db->query($query);
            if (!$res || $this->db->num_rows($res) <= 0) {
                break;
            }
            list($idOrg, $idParent) = $this->db->fetch_row($res);
        }

        return (int) $idOrg;
    }

    /**
     * Numero di "aziende" (nodi di primo livello dell'organigramma).
     * Per un Admin scoped su una singola azienda, ritorna sempre 1 (o 0 se
     * non e' legato a nessun nodo).
     */
    public function getCompaniesCount()
    {
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            return $this->getAdminCompanyNode() ? 1 : 0;
        }

        list($count) = $this->db->fetch_row(
            $this->db->query('SELECT COUNT(*) FROM core_org_chart_tree WHERE idParent = 0')
        );

        return (int) $count;
    }

    /**
     * Elenco delle aziende (nodi di primo livello) con il numero di utenti
     * totali nel loro sottoalbero. Per un Admin, elenco con la sola azienda
     * gestita.
     */
    public function getCompaniesList()
    {
        $where = 'oct.idParent = 0';
        if ($this->user_level != ADMIN_GROUP_GODADMIN) {
            $node = $this->getAdminCompanyNode();
            $where = $node ? 'oct.idOrg = ' . (int) $node : '0';
        }

        $query = 'SELECT oct.idOrg, oct.iLeft, oct.iRight, c.translation '
            . ' FROM core_org_chart_tree oct '
            . ' JOIN core_org_chart c ON c.id_dir = oct.idOrg AND c.lang_code = "' . getLanguage() . '" '
            . ' WHERE ' . $where
            . ' ORDER BY c.translation ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($idOrg, $iLeft, $iRight, $name) = $this->db->fetch_row($res)) {
            $users_query = 'SELECT COUNT(*) FROM %adm_group_members gm '
                . ' JOIN core_org_chart_tree d ON (d.idst_oc = gm.idst OR d.idst_ocd = gm.idst) '
                . ' WHERE d.iLeft >= ' . (int) $iLeft . ' AND d.iRight <= ' . (int) $iRight;
            list($users_count) = $this->db->fetch_row($this->db->query($users_query));

            $rows[] = ['idOrg' => $idOrg, 'name' => $name, 'users_count' => (int) $users_count];
        }

        return $rows;
    }

    /**
     * Figli diretti di un nodo di organigramma (per il drill-down ricorsivo).
     */
    public function getCompanyChildren($idOrg)
    {
        $query = 'SELECT oct.idOrg, c.translation '
            . ' FROM core_org_chart_tree oct '
            . ' JOIN core_org_chart c ON c.id_dir = oct.idOrg AND c.lang_code = "' . getLanguage() . '" '
            . ' WHERE oct.idParent = ' . (int) $idOrg
            . ' ORDER BY c.translation ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($childId, $name) = $this->db->fetch_row($res)) {
            $has_children_query = 'SELECT COUNT(*) FROM core_org_chart_tree WHERE idParent = ' . (int) $childId;
            list($has_children) = $this->db->fetch_row($this->db->query($has_children_query));
            $rows[] = ['idOrg' => $childId, 'name' => $name, 'has_children' => (int) $has_children > 0];
        }

        return $rows;
    }

    /**
     * Andamento mensile (ultimi $how_many_months mesi) di nuove aziende
     * (nodi di primo livello) create, basato su date_created.
     */
    public function getCompaniesMonthlyTrend($how_many_months = 6)
    {
        $output = [];
        for ($i = $how_many_months - 1; $i >= 0; --$i) {
            $month_start = date('Y-m-01 00:00:00', strtotime('-' . $i . ' months'));
            $month_end = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            $query = 'SELECT COUNT(*) FROM core_org_chart_tree '
                . " WHERE idParent = 0 AND date_created BETWEEN '" . $month_start . "' AND '" . $month_end . "'";
            list($count) = $this->db->fetch_row($this->db->query($query));
            $output[] = ['label' => date('M', strtotime($month_start)), 'count' => (int) $count];
        }

        return $output;
    }

    /**
     * Numero di iscrizioni completate (_CUS_END = 2), opzionalmente filtrate
     * su un periodo (false = nessun filtro periodo, conteggio totale).
     */
    public function getCoursesCompletedCount($period = false)
    {
        // join di validazione: learning_courseuser puo' contenere righe storiche
        // che puntano a utenti/corsi nel frattempo cancellati (verificato sui dati
        // reali) — contiamo solo completamenti riferiti a utenti/corsi esistenti.
        $query = 'SELECT COUNT(*) FROM %lms_courseuser cu '
            . ' JOIN %adm_user u ON u.idst = cu.idUser '
            . ' JOIN %lms_course c ON c.idCourse = cu.idCourse '
            . ' WHERE cu.status = 2 ';
        if ($period) {
            list($from, $to) = $this->periodToRange($period);
            $query .= " AND cu.date_complete BETWEEN '" . $from . "' AND '" . $to . "' ";
        }
        $query .= $this->scopeFilterSql('cu.idUser', 'cu.idCourse');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Numero di certificati rilasciati (learning_certificate_assign),
     * opzionalmente filtrati su un periodo. Stesso join di validazione di
     * getCoursesCompletedCount(): solo certificati di utenti/corsi esistenti.
     */
    public function getCertificatesIssuedCount($period = false)
    {
        $query = 'SELECT COUNT(*) FROM %lms_certificate_assign ce '
            . ' JOIN %adm_user u ON u.idst = ce.id_user '
            . ' JOIN %lms_course c ON c.idCourse = ce.id_course '
            . ' WHERE 1=1 ';
        if ($period) {
            list($from, $to) = $this->periodToRange($period);
            $query .= " AND ce.on_date BETWEEN '" . $from . "' AND '" . $to . "' ";
        }
        $query .= $this->scopeFilterSql('ce.id_user', 'ce.id_course');

        list($count) = $this->db->fetch_row($this->db->query($query));

        return (int) $count;
    }

    /**
     * Andamento mensile (ultimi $how_many_months mesi) di Iscrizioni vs
     * Completamenti, per il grafico a doppia serie della sezione Corsi.
     */
    public function getCoursesEnrollmentCompletionTrend($how_many_months = 6)
    {
        $output = [];
        for ($i = $how_many_months - 1; $i >= 0; --$i) {
            $month_start = date('Y-m-01 00:00:00', strtotime('-' . $i . ' months'));
            $month_end = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            $sub_query = 'SELECT COUNT(*) FROM %lms_courseuser cu '
                . ' JOIN %adm_user u ON u.idst = cu.idUser '
                . ' JOIN %lms_course c ON c.idCourse = cu.idCourse '
                . " WHERE cu.date_inscr BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                . $this->scopeFilterSql('cu.idUser', 'cu.idCourse');
            list($subs) = $this->db->fetch_row($this->db->query($sub_query));

            $comp_query = 'SELECT COUNT(*) FROM %lms_courseuser cu '
                . ' JOIN %adm_user u ON u.idst = cu.idUser '
                . ' JOIN %lms_course c ON c.idCourse = cu.idCourse '
                . " WHERE cu.status = 2 AND cu.date_complete BETWEEN '" . $month_start . "' AND '" . $month_end . "' "
                . $this->scopeFilterSql('cu.idUser', 'cu.idCourse');
            list($comp) = $this->db->fetch_row($this->db->query($comp_query));

            $output[] = ['label' => date('M', strtotime($month_start)), 'subscriptions' => (int) $subs, 'completions' => (int) $comp];
        }

        return $output;
    }

    /**
     * Top corsi per numero di iscritti. Le subquery validano anche l'utente
     * per lo stesso motivo delle altre metriche Corsi (vedi sopra).
     */
    public function getTopViewedCourses($limit = 5)
    {
        $query = 'SELECT c.idCourse, c.name, '
            . ' (SELECT COUNT(*) FROM %lms_courseuser cu2 JOIN %adm_user u2 ON u2.idst = cu2.idUser WHERE cu2.idCourse = c.idCourse) AS enrolled, '
            . ' (SELECT COUNT(*) FROM %lms_courseuser cu3 JOIN %adm_user u3 ON u3.idst = cu3.idUser WHERE cu3.idCourse = c.idCourse AND cu3.status = 2) AS completed, '
            . ' c.status '
            . ' FROM %lms_course c WHERE 1=1 ';
        if ($this->courses_filter !== false) {
            $query .= empty($this->courses_filter)
                ? ' AND 0 '
                : ' AND c.idCourse IN (' . implode(',', $this->courses_filter) . ') ';
        }
        $query .= ' ORDER BY enrolled DESC LIMIT ' . (int) $limit;

        $res = $this->db->query($query);
        $rows = [];
        while (list($idCourse, $name, $enrolled, $completed, $status) = $this->db->fetch_row($res)) {
            $rows[] = [
                'idCourse' => $idCourse,
                'name' => $name,
                'enrolled' => (int) $enrolled,
                'completed' => (int) $completed,
                'active' => in_array((int) $status, [1, 2], true),
            ];
        }

        return $rows;
    }

    /**
     * Numero di corsi per categoria (learning_category), solo categorie con
     * almeno un corso.
     */
    public function getCoursesByCategory()
    {
        $courses_filter_sql = '';
        if ($this->courses_filter !== false) {
            $courses_filter_sql = empty($this->courses_filter)
                ? ' AND 0 '
                : ' AND c.idCourse IN (' . implode(',', $this->courses_filter) . ') ';
        }

        $query = 'SELECT cat.idCategory, cat.path, COUNT(c.idCourse) AS num_courses '
            . ' FROM %lms_category cat '
            . ' JOIN %lms_course c ON c.idCategory = cat.idCategory '
            . ' WHERE 1=1 ' . $courses_filter_sql
            . ' GROUP BY cat.idCategory, cat.path '
            . ' ORDER BY num_courses DESC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($idCategory, $path, $num_courses) = $this->db->fetch_row($res)) {
            // path e' del tipo "/root/Pacchetto Office": mostriamo solo l'ultimo segmento
            $parts = explode('/', $path);
            $name = trim(end($parts));
            $rows[] = ['idCategory' => $idCategory, 'name' => $name, 'count' => (int) $num_courses];
        }

        return $rows;
    }

    /**
     * Elenco di dettaglio per il drill-down dei KPI della sezione Corsi.
     *
     * @param string $kind 'active','activating','certificates','subscriptions','category'
     * @param int $idCategory usato solo per $kind === 'category'
     */
    public function getCoursesDrilldownList($kind, $idCategory = 0)
    {
        $rows = [];
        $courses_filter_sql = '';
        if ($this->courses_filter !== false) {
            $courses_filter_sql = empty($this->courses_filter)
                ? ' AND 0 '
                : ' AND idCourse IN (' . implode(',', $this->courses_filter) . ') ';
        }

        if ($kind === 'active') {
            $query = "SELECT idCourse, name FROM %lms_course WHERE status IN ('1','2') " . $courses_filter_sql . ' ORDER BY name ASC LIMIT 200';
            $res = $this->db->query($query);
            while (list($idCourse, $name) = $this->db->fetch_row($res)) {
                $rows[] = ['idCourse' => $idCourse, 'name' => $name, 'detail' => ''];
            }

            return $rows;
        }

        if ($kind === 'category') {
            $query = 'SELECT idCourse, name, status FROM %lms_course WHERE idCategory = ' . (int) $idCategory . ' '
                . $courses_filter_sql . ' ORDER BY name ASC LIMIT 200';
            $res = $this->db->query($query);
            while (list($idCourse, $name, $status) = $this->db->fetch_row($res)) {
                $rows[] = ['idCourse' => $idCourse, 'name' => $name, 'detail' => (in_array((int) $status, [1, 2], true) ? 'Attivo' : 'Non attivo')];
            }

            return $rows;
        }

        if ($kind === 'activating') {
            $from = date('Y-m-d H:i:s');
            $to = date('Y-m-d', time() + 7 * 24 * 3600) . ' 23:59:59';
            $query = "SELECT idCourse, name, date_begin FROM %lms_course WHERE date_begin > '" . $from . "' AND date_begin < '" . $to . "' "
                . $courses_filter_sql . ' ORDER BY date_begin ASC LIMIT 200';
            $res = $this->db->query($query);
            while (list($idCourse, $name, $date_begin) = $this->db->fetch_row($res)) {
                $rows[] = ['idCourse' => $idCourse, 'name' => $name, 'detail' => date('d/m/Y', strtotime($date_begin))];
            }

            return $rows;
        }

        if ($kind === 'completed') {
            $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname, c.name, cu.date_complete '
                . ' FROM %lms_courseuser cu '
                . ' JOIN %adm_user u ON u.idst = cu.idUser '
                . ' JOIN %lms_course c ON c.idCourse = cu.idCourse '
                . ' WHERE cu.status = 2 '
                . $this->scopeFilterSql('cu.idUser', 'cu.idCourse')
                . ' ORDER BY cu.date_complete DESC LIMIT 200';
            $res = $this->db->query($query);
            while (list($idst, $userid, $firstname, $lastname, $name, $date_complete) = $this->db->fetch_row($res)) {
                $rows[] = [
                    'userid' => ltrim($userid, '/'),
                    'name' => $firstname . ' ' . $lastname,
                    'course' => $name,
                    'detail' => date('d/m/Y', strtotime($date_complete)),
                ];
            }

            return $rows;
        }

        if ($kind === 'certificates') {
            $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname, c.name, ce.on_date, cert.name AS cert_name '
                . ' FROM %lms_certificate_assign ce '
                . ' JOIN %adm_user u ON u.idst = ce.id_user '
                . ' JOIN %lms_course c ON c.idCourse = ce.id_course '
                . ' JOIN %lms_certificate cert ON cert.id_certificate = ce.id_certificate '
                . ' WHERE 1=1 '
                . $this->scopeFilterSql('ce.id_user', 'ce.id_course')
                . ' ORDER BY ce.on_date DESC LIMIT 200';
            $res = $this->db->query($query);
            while (list($idst, $userid, $firstname, $lastname, $name, $on_date, $cert_name) = $this->db->fetch_row($res)) {
                $rows[] = [
                    'userid' => ltrim($userid, '/'),
                    'name' => $firstname . ' ' . $lastname,
                    'course' => $name . ' (' . $cert_name . ')',
                    'detail' => date('d/m/Y', strtotime($on_date)),
                ];
            }

            return $rows;
        }

        // 'subscriptions'
        $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname, c.name, cu.date_inscr '
            . ' FROM %lms_courseuser cu '
            . ' JOIN %adm_user u ON u.idst = cu.idUser '
            . ' JOIN %lms_course c ON c.idCourse = cu.idCourse '
            . ' WHERE cu.waiting = 0 '
            . $this->scopeFilterSql('cu.idUser', 'cu.idCourse')
            . ' ORDER BY cu.date_inscr DESC LIMIT 200';
        $res = $this->db->query($query);
        while (list($idst, $userid, $firstname, $lastname, $name, $date_inscr) = $this->db->fetch_row($res)) {
            $rows[] = [
                'userid' => ltrim($userid, '/'),
                'name' => $firstname . ' ' . $lastname,
                'course' => $name,
                'detail' => date('d/m/Y', strtotime($date_inscr)),
            ];
        }

        return $rows;
    }

    /**
     * Nome dell'azienda (nodo di primo livello dell'organigramma) a cui
     * appartiene un utente specifico. Ritorna '-' se l'utente non e' legato
     * a nessun nodo. Generalizzazione di getAdminCompanyNode() per un idst
     * arbitrario invece dell'utente corrente.
     */
    public function getCompanyNameForUser($idst)
    {
        $query = 'SELECT oct.idOrg, oct.idParent FROM core_org_chart_tree oct '
            . ' JOIN core_group_members gm ON (gm.idst = oct.idst_oc OR gm.idst = oct.idst_ocd) '
            . ' WHERE gm.idstMember = ' . (int) $idst
            . ' LIMIT 1';
        $res = $this->db->query($query);
        if (!$res || $this->db->num_rows($res) <= 0) {
            return '-';
        }
        list($idOrg, $idParent) = $this->db->fetch_row($res);

        while ((int) $idParent !== 0) {
            $query = 'SELECT idOrg, idParent FROM core_org_chart_tree WHERE idOrg = ' . (int) $idParent;
            $res = $this->db->query($query);
            if (!$res || $this->db->num_rows($res) <= 0) {
                break;
            }
            list($idOrg, $idParent) = $this->db->fetch_row($res);
        }

        $query = 'SELECT translation FROM core_org_chart WHERE id_dir = ' . (int) $idOrg . ' AND lang_code = "' . getLanguage() . '"';
        $res = $this->db->query($query);
        if (!$res || $this->db->num_rows($res) <= 0) {
            return '-';
        }
        list($name) = $this->db->fetch_row($res);

        return $name;
    }

    /**
     * Utenti iscritti a un corso, con l'azienda di provenienza di ciascuno.
     */
    public function getCourseEnrolledUsers($idCourse)
    {
        $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname FROM %adm_user u '
            . ' JOIN %lms_courseuser cu ON cu.idUser = u.idst '
            . ' WHERE cu.idCourse = ' . (int) $idCourse
            . ' ORDER BY u.lastname ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($idst, $userid, $firstname, $lastname) = $this->db->fetch_row($res)) {
            $rows[] = [
                'idst' => $idst,
                'userid' => ltrim($userid, '/'),
                'name' => $firstname . ' ' . $lastname,
                'company' => $this->getCompanyNameForUser($idst),
            ];
        }

        return $rows;
    }
}
