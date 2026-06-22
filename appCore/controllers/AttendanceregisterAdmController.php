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

class AttendanceregisterAdmController extends AdmController
{
    protected $model;

    public function init()
    {
        parent::init();
        $this->model = new AttendanceregisterAdm();
    }

    public function show()
    {
        Util::get_js(FormaLms\lib\Get::rel_path('adm') . '/views/attendanceregister/attendanceregister.js', true, true);
        Util::get_css(FormaLms\lib\Get::rel_path('base') . '/css/pandp-ui.css', true, true);

        $this->render('show', [
            'courses' => $this->model->getAllCourses(),
        ]);
    }

    /**
     * AJAX: frammento HTML con l'elenco utenti del corso scelto (checkbox +
     * link al dettaglio sessioni), iniettato via innerHTML nel pannello
     * sinistro della stessa schermata.
     */
    public function course_usersTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);

        $this->render('course_users', [
            'idCourse' => $idCourse,
            'users' => $idCourse > 0 ? $this->model->getCourseUsers($idCourse) : [],
        ]);
    }

    /**
     * AJAX: frammento HTML con il dettaglio (sessioni raggruppate per
     * giorno) di un utente, iniettato nel pannello destro della stessa
     * schermata (nessun popup).
     */
    public function user_sessionsTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idUser = FormaLms\lib\Get::req('idUser', DOTY_INT, 0);

        $acl_man = Docebo::user()->getAclManager();
        $user_info = $acl_man->getUser($idUser, false);
        $fullname = trim($user_info[ACL_INFO_LASTNAME] . ' ' . $user_info[ACL_INFO_FIRSTNAME]);
        $username = $acl_man->relativeId($user_info[ACL_INFO_USERID]);

        $this->render('user_detail', [
            'idCourse' => $idCourse,
            'idUser' => $idUser,
            'fullname' => $fullname !== '' ? $fullname : $username,
            'username' => $username,
            'data' => $this->model->getUserSessionsByDay($idCourse, $idUser),
        ]);
    }

    /**
     * Export Excel di un solo utente (link dal pannello dettaglio),
     * intestato col suo nome.
     */
    public function export_userTask()
    {
        require_once _base_ . '/lib/lib.download.php';

        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idUser = FormaLms\lib\Get::req('idUser', DOTY_INT, 0);

        $acl_man = Docebo::user()->getAclManager();
        $user_info = $acl_man->getUser($idUser, false);
        $fullname = trim($user_info[ACL_INFO_LASTNAME] . ' ' . $user_info[ACL_INFO_FIRSTNAME]);
        $username = $acl_man->relativeId($user_info[ACL_INFO_USERID]);

        $output = $this->buildUserSection($fullname !== '' ? $fullname : $username, $username, $idCourse, $idUser);

        sendStrAsFile('<table border="1">' . $output . '</table>', 'registro_presenze_' . preg_replace('/[^a-zA-Z0-9]/', '_', $username) . '_' . date('Ymd') . '.xls');
        exit();
    }

    /**
     * Export Excel di utenti: se ci sono checkbox selezionate esporta solo
     * quelle persone, altrimenti tutti gli iscritti al corso. Una sezione
     * per allievo, intestata col suo nome. Stessa regola di selezione di
     * export_wordTask().
     */
    public function export_excelTask()
    {
        require_once _base_ . '/lib/lib.download.php';

        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $selected = FormaLms\lib\Get::req('selected_users', DOTY_MIXED, []);

        $users = $this->resolveExportUsers($idCourse, $selected);
        $output = '';
        foreach ($users as $u) {
            $output .= $this->buildUserSection($u['name'], $u['userid'], $idCourse, $u['idst']);
            $output .= '<tr><td colspan="5">&nbsp;</td></tr>';
        }
        if (empty($users)) {
            $output = '<tr><td>' . Lang::t('_NO_DATA', 'standard') . '</td></tr>';
        }

        sendStrAsFile('<table border="1">' . $output . '</table>', 'registro_presenze_' . date('Ymd') . '.xls');
        exit();
    }

    /**
     * Export Word di utenti: stessa regola di selezione dell'export Excel
     * (selezionati, o tutti se nessuno selezionato). Una sezione per
     * allievo, intestata col suo nome.
     */
    public function export_wordTask()
    {
        require_once _base_ . '/lib/lib.download.php';

        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $selected = FormaLms\lib\Get::req('selected_users', DOTY_MIXED, []);
        $courseName = $this->model->getCourseName($idCourse);

        $users = $this->resolveExportUsers($idCourse, $selected);
        $output = '<h2>' . htmlspecialchars($courseName) . '</h2>'
            . '<p>' . Lang::t('_ATTENDANCE_REGISTER', 'standard') . ' - ' . date('d/m/Y') . '</p>'
            . '<table border="1">';

        if (empty($users)) {
            $output .= '<tr><td>' . Lang::t('_NO_DATA', 'standard') . '</td></tr>';
        }
        foreach ($users as $u) {
            $output .= $this->buildUserSection($u['name'], $u['userid'], $idCourse, $u['idst']);
            $output .= '<tr><td colspan="5">&nbsp;</td></tr>';
        }
        $output .= '</table>';

        sendStrAsFile($output, 'registro_presenze_' . preg_replace('/[^a-zA-Z0-9]/', '_', $courseName) . '_' . date('Ymd') . '.doc');
        exit();
    }

    /**
     * Utenti da esportare: solo quelli con checkbox selezionata, oppure
     * tutti gli iscritti al corso se non e' stato selezionato nessuno.
     */
    private function resolveExportUsers($idCourse, $selected)
    {
        if (!is_array($selected)) {
            $selected = [];
        }
        $selected = array_filter(array_map('intval', $selected));

        if (empty($selected)) {
            return $this->model->getCourseUsers($idCourse);
        }

        $acl_man = Docebo::user()->getAclManager();
        $users = [];
        foreach ($selected as $idUser) {
            $user_info = $acl_man->getUser($idUser, false);
            if (!$user_info) {
                continue;
            }
            $fullname = trim($user_info[ACL_INFO_LASTNAME] . ' ' . $user_info[ACL_INFO_FIRSTNAME]);
            $username = $acl_man->relativeId($user_info[ACL_INFO_USERID]);
            $users[] = [
                'idst' => $idUser,
                'userid' => $username,
                'name' => $fullname !== '' ? $fullname : $username,
            ];
        }

        return $users;
    }

    /**
     * Sezione (intestazione + righe per giorno + totali) per un singolo
     * utente, condivisa fra tutte le esportazioni.
     */
    private function buildUserSection($displayName, $username, $idCourse, $idUser)
    {
        $data = $this->model->getUserSessionsByDay($idCourse, $idUser);

        $html = '<tr><th colspan="5">' . htmlspecialchars($displayName) . ' (' . htmlspecialchars($username) . ')</th></tr>'
            . '<tr>'
            . '<th>' . Lang::t('_DATE', 'standard') . '</th>'
            . '<th>' . Lang::t('_FIRST_ENTRY', 'statistic') . '</th>'
            . '<th>' . Lang::t('_LAST_EXIT', 'statistic') . '</th>'
            . '<th>' . Lang::t('_HOW_MUCH_TIME', 'statistic') . '</th>'
            . '<th>' . Lang::t('_NUMBER_OF_OP', 'statistic') . '</th>'
            . '</tr>';

        foreach ($data['rows'] as $row) {
            $html .= '<tr>'
                . '<td>' . htmlspecialchars($row['date']) . '</td>'
                . '<td>' . htmlspecialchars($row['first_entry']) . '</td>'
                . '<td>' . htmlspecialchars($row['last_exit']) . '</td>'
                . '<td>' . htmlspecialchars($row['duration']) . '</td>'
                . '<td>' . (int) $row['num_op'] . '</td>'
                . '</tr>';
        }

        $html .= '<tr>'
            . '<td><b>' . Lang::t('_TOTAL', 'standard') . '</b></td>'
            . '<td><b>' . Lang::t('_NUMBER_OF_ACCESS', 'statistic') . ': ' . $data['session_count'] . '</b></td>'
            . '<td></td>'
            . '<td><b>' . $data['total_duration'] . '</b></td>'
            . '<td></td>'
            . '</tr>';

        return $html;
    }
}
