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
    protected $json;

    public function init()
    {
        parent::init();
        require_once _base_ . '/lib/lib.json.php';
        $this->json = new Services_JSON();
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
     * link al dettaglio sessioni), iniettato via innerHTML nella stessa
     * schermata.
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
     * Popup con la tabella sessioni di un utente (stessa identica
     * informazione di "Stat. Utilizzo", senza grafico).
     */
    public function user_sessionsTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idUser = FormaLms\lib\Get::req('idUser', DOTY_INT, 0);

        $acl_man = Docebo::user()->getAclManager();
        $user_info = $acl_man->getUser($idUser, false);
        $fullname = trim($user_info[ACL_INFO_LASTNAME] . ' ' . $user_info[ACL_INFO_FIRSTNAME]);

        $this->render('user_sessions_dialog', [
            'fullname' => $fullname !== '' ? $fullname : $acl_man->relativeId($user_info[ACL_INFO_USERID]),
            'data' => $this->model->getUserSessions($idCourse, $idUser),
            'json' => $this->json,
        ]);
    }

    /**
     * Export Excel di un solo utente (link dal popup), intestato col suo nome.
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
     * Export Excel di piu' utenti selezionati: una sezione per allievo,
     * intestata col suo nome.
     */
    public function export_selectedTask()
    {
        require_once _base_ . '/lib/lib.download.php';

        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $selected = FormaLms\lib\Get::req('selected_users', DOTY_MIXED, []);
        if (!is_array($selected)) {
            $selected = [];
        }
        $selected = array_filter(array_map('intval', $selected));

        $acl_man = Docebo::user()->getAclManager();
        $output = '';

        if (empty($selected)) {
            $output = '<tr><td>' . Lang::t('_NO_DATA', 'standard') . '</td></tr>';
        }

        foreach ($selected as $idUser) {
            $user_info = $acl_man->getUser($idUser, false);
            if (!$user_info) {
                continue;
            }
            $fullname = trim($user_info[ACL_INFO_LASTNAME] . ' ' . $user_info[ACL_INFO_FIRSTNAME]);
            $username = $acl_man->relativeId($user_info[ACL_INFO_USERID]);

            $output .= $this->buildUserSection($fullname !== '' ? $fullname : $username, $username, $idCourse, $idUser);
            $output .= '<tr><td colspan="4">&nbsp;</td></tr>';
        }

        sendStrAsFile('<table border="1">' . $output . '</table>', 'registro_presenze_' . date('Ymd') . '.xls');
        exit();
    }

    /**
     * Sezione (intestazione + righe sessioni + totali) per un singolo
     * utente, condivisa fra export singolo e multiplo.
     */
    private function buildUserSection($displayName, $username, $idCourse, $idUser)
    {
        $data = $this->model->getUserSessions($idCourse, $idUser);

        $html = '<tr><th colspan="4">' . htmlspecialchars($displayName) . ' (' . htmlspecialchars($username) . ')</th></tr>'
            . '<tr>'
            . '<th>' . Lang::t('_SESSION_STARTED', 'statistic') . '</th>'
            . '<th>' . Lang::t('_LAST_ACTION_AT', 'statistic') . '</th>'
            . '<th>' . Lang::t('_HOW_MUCH_TIME', 'statistic') . '</th>'
            . '<th>' . Lang::t('_NUMBER_OF_OP', 'statistic') . '</th>'
            . '</tr>';

        foreach ($data['rows'] as $row) {
            $html .= '<tr>'
                . '<td>' . htmlspecialchars($row['start']) . '</td>'
                . '<td>' . htmlspecialchars($row['end']) . '</td>'
                . '<td>' . htmlspecialchars($row['duration']) . '</td>'
                . '<td>' . (int) $row['num_op'] . '</td>'
                . '</tr>';
        }

        $html .= '<tr>'
            . '<td><b>' . Lang::t('_TOTAL', 'standard') . '</b></td>'
            . '<td><b>' . Lang::t('_NUMBER_OF_ACCESS', 'statistic') . ': ' . $data['session_count'] . '</b></td>'
            . '<td><b>' . $data['total_duration'] . '</b></td>'
            . '<td></td>'
            . '</tr>';

        return $html;
    }
}
