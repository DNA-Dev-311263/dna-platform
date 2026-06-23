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

/**
 * Registro presenze: stessa logica di "Stat. Utilizzo" (appLms/modules/statistic),
 * ma con scelta esplicita del corso (qui non c'e' un corso "di sessione") e
 * pensata per una singola schermata nel backoffice (no grafici).
 */
class AttendanceregisterAdm extends Model
{
    protected $db;

    protected $user_level;
    protected $users_filter;
    protected $courses_filter;

    /**
     * Un amministratore non-godadmin vede solo i corsi e gli utenti che gli
     * sono stati assegnati in gestione (stesso meccanismo/stessa logica della
     * Dashboard: AdminPreference::getAdminUsers()/getAdminCourse()). false
     * significa "nessun filtro" (tutto visibile), un array (anche vuoto)
     * significa "solo questi id".
     */
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
            // se "$all_courses" e' true, "$this->courses_filter" resta false (nessun filtro)
        }
    }

    /**
     * Vero se l'amministratore corrente puo' vedere questo corso (sempre
     * vero per il godadmin). Va richiamato su qualsiasi idCourse che arriva
     * da una richiesta, non solo su quelli proposti dalla tendina: un admin
     * non deve poter vedere un corso fuori dal suo perimetro semplicemente
     * passando un idCourse diverso nella richiesta.
     */
    public function isCourseAllowed($idCourse)
    {
        return $this->courses_filter === false || in_array((int) $idCourse, $this->courses_filter);
    }

    /**
     * Filtra una lista di idst utente, tenendo solo quelli che l'amministratore
     * corrente puo' vedere (sempre tutti, per il godadmin). Usata per non
     * fidarsi ciecamente di un elenco di "selected_users" arrivato dal client.
     */
    public function filterAllowedUsers($idUserList)
    {
        if ($this->users_filter === false) {
            return $idUserList;
        }

        return array_values(array_intersect($idUserList, $this->users_filter));
    }

    /**
     * Tutti i corsi della piattaforma (esclusi solo quelli "In costruzione"),
     * limitati a quelli assegnati in gestione per un admin non-godadmin.
     */
    public function getAllCourses()
    {
        $query = "SELECT idCourse, name FROM %lms_course WHERE status <> '0' ";
        if ($this->courses_filter !== false) {
            $query .= empty($this->courses_filter)
                ? ' AND 0 '
                : ' AND idCourse IN (' . implode(',', array_map('intval', $this->courses_filter)) . ') ';
        }
        $query .= ' ORDER BY name ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($idCourse, $name) = $this->db->fetch_row($res)) {
            $rows[] = ['idCourse' => $idCourse, 'name' => $name];
        }

        return $rows;
    }

    public function getCourseName($idCourse)
    {
        $query = 'SELECT name FROM %lms_course WHERE idCourse = ' . (int) $idCourse;
        list($name) = $this->db->fetch_row($this->db->query($query));

        return $name;
    }

    /**
     * Utenti iscritti al corso scelto (validati su core_user, niente righe
     * orfane: stesso problema gia' risolto altrove in questa Dashboard), con
     * filtro opzionale per azienda (nodo di primo livello dell'organigramma,
     * sotto-alberatura completa incluso, stesso pattern usato dalla
     * Dashboard per "Elenco utenti azienda").
     */
    public function getCourseUsers($idCourse, $idOrg = 0)
    {
        if (!$this->isCourseAllowed($idCourse)) {
            return [];
        }

        $query = 'SELECT DISTINCT u.idst, u.userid, u.firstname, u.lastname FROM %lms_courseuser cu '
            . ' JOIN %adm_user u ON u.idst = cu.idUser ';

        if ($idOrg > 0) {
            $range_query = 'SELECT iLeft, iRight FROM core_org_chart_tree WHERE idOrg = ' . (int) $idOrg;
            $range_res = $this->db->query($range_query);
            if (!$range_res || $this->db->num_rows($range_res) <= 0) {
                return [];
            }
            list($iLeft, $iRight) = $this->db->fetch_row($range_res);

            $query .= ' JOIN %adm_group_members gm ON gm.idstMember = u.idst '
                . ' JOIN core_org_chart_tree d ON (d.idst_oc = gm.idst OR d.idst_ocd = gm.idst) '
                . ' WHERE cu.idCourse = ' . (int) $idCourse
                . ' AND d.iLeft >= ' . (int) $iLeft . ' AND d.iRight <= ' . (int) $iRight;
        } else {
            $query .= ' WHERE cu.idCourse = ' . (int) $idCourse;
        }

        if ($this->users_filter !== false) {
            $query .= empty($this->users_filter)
                ? ' AND 0 '
                : ' AND u.idst IN (' . implode(',', array_map('intval', $this->users_filter)) . ') ';
        }

        $query .= ' ORDER BY u.lastname ASC, u.firstname ASC';
        $res = $this->db->query($query);

        $rows = [];
        while (list($idst, $userid, $firstname, $lastname) = $this->db->fetch_row($res)) {
            $rows[] = [
                'idst' => $idst,
                'userid' => ltrim($userid, '/'),
                'name' => trim($firstname . ' ' . $lastname),
            ];
        }

        return $rows;
    }

    /**
     * Nodi dell'organigramma (aziende di primo livello e relativi sotto-nodi/
     * filiali) che hanno almeno un iscritto al corso scelto: solo questi
     * vanno mostrati nella tendina di filtro, altrimenti si potrebbero
     * scegliere nodi sempre vuoti. Ordine ad albero (un nodo subito dopo il
     * suo genitore, come nell'organigramma), con indentazione nel nome per
     * far capire a quale livello appartiene ciascun nodo.
     */
    public function getCompaniesForCourse($idCourse)
    {
        if (!$this->isCourseAllowed($idCourse)) {
            return [];
        }

        $query = 'SELECT oct.idOrg, oct.idParent, oct.iLeft, oct.iRight, c.translation '
            . ' FROM core_org_chart_tree oct '
            . ' JOIN core_org_chart c ON c.id_dir = oct.idOrg AND c.lang_code = "' . getLanguage() . '" '
            . ' ORDER BY oct.iLeft ASC';
        $res = $this->db->query($query);

        $nodes = [];
        while (list($idOrg, $idParent, $iLeft, $iRight, $name) = $this->db->fetch_row($res)) {
            $nodes[(int) $idOrg] = [
                'idParent' => (int) $idParent,
                'iLeft' => (int) $iLeft,
                'iRight' => (int) $iRight,
                'name' => $name,
            ];
        }

        $companies = [];
        foreach ($nodes as $idOrg => $node) {
            $count_query = 'SELECT COUNT(DISTINCT u.idst) FROM %lms_courseuser cu '
                . ' JOIN %adm_user u ON u.idst = cu.idUser '
                . ' JOIN %adm_group_members gm ON gm.idstMember = u.idst '
                . ' JOIN core_org_chart_tree d ON (d.idst_oc = gm.idst OR d.idst_ocd = gm.idst) '
                . ' WHERE cu.idCourse = ' . (int) $idCourse
                . ' AND d.iLeft >= ' . $node['iLeft'] . ' AND d.iRight <= ' . $node['iRight'];
            if ($this->users_filter !== false) {
                $count_query .= empty($this->users_filter)
                    ? ' AND 0 '
                    : ' AND u.idst IN (' . implode(',', array_map('intval', $this->users_filter)) . ') ';
            }
            list($count) = $this->db->fetch_row($this->db->query($count_query));

            if ((int) $count > 0) {
                $depth = $this->orgNodeDepth($idOrg, $nodes);
                $indent = str_repeat("\u{00A0}\u{00A0}\u{00A0}\u{00A0}", $depth);
                $prefix = $depth > 0 ? "\u{21B3} " : '';
                $companies[] = ['idOrg' => $idOrg, 'name' => $indent . $prefix . $node['name']];
            }
        }

        return $companies;
    }

    /**
     * Quanti livelli separano questo nodo dalla radice (azienda di primo
     * livello), camminando all'indietro lungo idParent.
     */
    private function orgNodeDepth($idOrg, $nodes)
    {
        $depth = 0;
        while (isset($nodes[$idOrg]) && $nodes[$idOrg]['idParent'] > 0) {
            $idOrg = $nodes[$idOrg]['idParent'];
            ++$depth;
        }

        return $depth;
    }

    /**
     * Sessioni dell'utente nel corso scelto, raggruppate per giorno (come un
     * registro presenze: un giorno puo' avere piu' sessioni separate, qui
     * diventano una riga con primo ingresso/ultima uscita/totali del giorno).
     */
    public function getUserSessionsByDay($idCourse, $idUser)
    {
        if (!$this->isCourseAllowed($idCourse) || empty($this->filterAllowedUsers([$idUser]))) {
            return [
                'rows' => [],
                'day_count' => 0,
                'session_count' => 0,
                'total_seconds' => 0,
                'total_duration' => $this->formatDuration(0),
            ];
        }

        // Sessioni grezze (non aggregate in SQL): servono sia per calcolare i
        // totali del giorno sia per poter mostrare il dettaglio delle singole
        // sessioni quando si espande un giorno a video.
        $query = 'SELECT enterTime, lastTime, (UNIX_TIMESTAMP(lastTime) - UNIX_TIMESTAMP(enterTime)) AS howm, numOp '
            . ' FROM %lms_tracksession '
            . ' WHERE idCourse = ' . (int) $idCourse . ' AND idUser = ' . (int) $idUser
            . ' ORDER BY enterTime ASC';
        $res = $this->db->query($query);

        $days = [];
        $grand_total_seconds = 0;
        $grand_total_sessions = 0;
        while (list($enter, $last, $how, $num_op) = $this->db->fetch_row($res)) {
            $day_key = date('Y-m-d', strtotime($enter));
            $enter_ts = strtotime($enter);
            $exit_ts = strtotime($last);

            if (!isset($days[$day_key])) {
                $days[$day_key] = [
                    'date' => date('d/m/Y', $enter_ts),
                    'first_entry_ts' => $enter_ts,
                    'last_exit_ts' => $exit_ts,
                    'total_seconds' => 0,
                    'num_op' => 0,
                    'session_count' => 0,
                    'sessions' => [],
                ];
            }
            if ($enter_ts < $days[$day_key]['first_entry_ts']) {
                $days[$day_key]['first_entry_ts'] = $enter_ts;
            }
            if ($exit_ts > $days[$day_key]['last_exit_ts']) {
                $days[$day_key]['last_exit_ts'] = $exit_ts;
            }
            $days[$day_key]['total_seconds'] += (int) $how;
            $days[$day_key]['num_op'] += (int) $num_op;
            ++$days[$day_key]['session_count'];
            $days[$day_key]['sessions'][] = [
                'enter' => date('H:i', $enter_ts),
                'exit' => date('H:i', $exit_ts),
                'duration' => $this->formatDuration($how),
                'num_op' => (int) $num_op,
            ];

            $grand_total_seconds += (int) $how;
            ++$grand_total_sessions;
        }

        $rows = [];
        foreach ($days as $day) {
            $rows[] = [
                'date' => $day['date'],
                'first_entry' => date('H:i', $day['first_entry_ts']),
                'last_exit' => date('H:i', $day['last_exit_ts']),
                'duration' => $this->formatDuration($day['total_seconds']),
                'num_op' => $day['num_op'],
                'session_count' => $day['session_count'],
                'sessions' => $day['sessions'],
            ];
        }

        return [
            'rows' => $rows,
            'day_count' => count($rows),
            'session_count' => $grand_total_sessions,
            'total_seconds' => $grand_total_seconds,
            'total_duration' => $this->formatDuration($grand_total_seconds),
        ];
    }

    /**
     * Formatta una durata in secondi come HH:MM:SS.
     */
    public function formatDuration($seconds)
    {
        $hours = (int) ($seconds / 3600);
        $minutes = (int) (($seconds % 3600) / 60);
        $secs = (int) ($seconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
