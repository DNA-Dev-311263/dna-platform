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
     * AJAX: elenco aziende (con almeno un iscritto al corso scelto), per
     * popolare la tendina di filtro accanto a quella del corso.
     */
    public function companiesTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);

        header('Content-Type: application/json');
        echo json_encode($idCourse > 0 ? $this->model->getCompaniesForCourse($idCourse) : []);
        exit();
    }

    /**
     * AJAX: frammento HTML con l'elenco utenti del corso scelto (checkbox +
     * link al dettaglio sessioni), iniettato via innerHTML nel pannello
     * sinistro della stessa schermata. Filtro azienda opzionale.
     */
    public function course_usersTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idOrg = FormaLms\lib\Get::req('idOrg', DOTY_INT, 0);

        $this->render('course_users', [
            'idCourse' => $idCourse,
            'users' => $idCourse > 0 ? $this->model->getCourseUsers($idCourse, $idOrg) : [],
        ]);
    }

    /**
     * AJAX: anteprima a video (Riepilogo o Dettaglio, secondo $detailed) di
     * una sezione per ciascun utente selezionato (o tutti gli iscritti, con
     * l'eventuale filtro azienda, se nessuno e' selezionato). Iniettata nel
     * pannello destro: la si rivede ogni volta che cambia la selezione o si
     * passa da Riepilogo a Dettaglio (e viceversa), prima di decidere se
     * stampare o esportare. La stampa riusa direttamente questo stesso
     * contenuto (vedi @media print in pandp-ui.css), niente fetch separato.
     */
    public function previewTask()
    {
        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idOrg = FormaLms\lib\Get::req('idOrg', DOTY_INT, 0);
        $selected = FormaLms\lib\Get::req('selected_users', DOTY_MIXED, []);
        $detailed = FormaLms\lib\Get::req('detailed', DOTY_INT, 0) == 1;

        $sections = [];
        foreach ($this->resolveExportUsers($idCourse, $selected, $idOrg) as $u) {
            $sections[] = [
                'displayName' => $u['name'],
                'username' => $u['userid'],
                'data' => $this->model->getUserSessionsByDay($idCourse, $u['idst']),
            ];
        }

        $this->render('preview', [
            'sections' => $sections,
            'detailed' => $detailed,
        ]);
    }

    /**
     * Export Excel di utenti: se ci sono checkbox selezionate esporta solo
     * quelle persone, altrimenti tutti gli iscritti al corso (secondo
     * l'eventuale filtro azienda). Una sezione per allievo, intestata col
     * suo nome. Stessa regola di selezione di export_wordTask().
     */
    public function export_excelTask()
    {
        require_once _base_ . '/lib/lib.download.php';

        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idOrg = FormaLms\lib\Get::req('idOrg', DOTY_INT, 0);
        $selected = FormaLms\lib\Get::req('selected_users', DOTY_MIXED, []);
        $detailed = FormaLms\lib\Get::req('detailed', DOTY_INT, 0) == 1;

        $users = $this->resolveExportUsers($idCourse, $selected, $idOrg);
        $output = '';
        foreach ($users as $u) {
            $output .= $this->buildUserSection($u['name'], $u['userid'], $idCourse, $u['idst'], $detailed);
            $output .= '<tr><td colspan="4">&nbsp;</td></tr>';
        }
        if (empty($users)) {
            $output = '<tr><td>' . Lang::t('_NO_DATA', 'standard') . '</td></tr>';
        }

        sendStrAsFile('<table border="1">' . $output . '</table>', 'registro_presenze_' . date('Ymd') . '.xls');
        exit();
    }

    /**
     * Export Word di utenti: stessa regola di selezione dell'export Excel
     * (selezionati, o tutti se nessuno selezionato, secondo l'eventuale
     * filtro azienda). Una sezione per allievo, intestata col suo nome.
     */
    public function export_wordTask()
    {
        require_once _base_ . '/lib/lib.download.php';

        $idCourse = FormaLms\lib\Get::req('idCourse', DOTY_INT, 0);
        $idOrg = FormaLms\lib\Get::req('idOrg', DOTY_INT, 0);
        $selected = FormaLms\lib\Get::req('selected_users', DOTY_MIXED, []);
        $detailed = FormaLms\lib\Get::req('detailed', DOTY_INT, 0) == 1;
        $courseName = $this->model->isCourseAllowed($idCourse) ? $this->model->getCourseName($idCourse) : '';

        $users = $this->resolveExportUsers($idCourse, $selected, $idOrg);
        $output = '<h2>' . htmlspecialchars($courseName) . '</h2>'
            . '<p>' . Lang::t('_ATTENDANCE_REGISTER', 'standard') . ' - ' . date('d/m/Y') . '</p>'
            . '<table border="1">';

        if (empty($users)) {
            $output .= '<tr><td>' . Lang::t('_NO_DATA', 'standard') . '</td></tr>';
        }
        foreach ($users as $u) {
            $output .= $this->buildUserSection($u['name'], $u['userid'], $idCourse, $u['idst'], $detailed);
            $output .= '<tr><td colspan="4">&nbsp;</td></tr>';
        }
        $output .= '</table>';

        sendStrAsFile($output, 'registro_presenze_' . preg_replace('/[^a-zA-Z0-9]/', '_', $courseName) . '_' . date('Ymd') . '.doc');
        exit();
    }

    /**
     * Utenti da esportare/stampare: solo quelli con checkbox selezionata
     * (anche uno solo), oppure tutti gli iscritti al corso (con l'eventuale
     * filtro azienda) se non e' stato selezionato nessuno. Il filtro per
     * perimetro amministrativo (AttendanceregisterAdm::filterAllowedUsers)
     * si applica anche qui: un admin non puo' farsi esportare un utente
     * fuori dal suo perimetro passando direttamente il suo idst.
     */
    private function resolveExportUsers($idCourse, $selected, $idOrg = 0)
    {
        if (!$this->model->isCourseAllowed($idCourse)) {
            return [];
        }

        if (!is_array($selected)) {
            $selected = [];
        }
        $selected = $this->model->filterAllowedUsers(array_filter(array_map('intval', $selected)));

        if (empty($selected)) {
            return $this->model->getCourseUsers($idCourse, $idOrg);
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
     * utente, condivisa fra tutte le esportazioni. Con $detailed=true, sotto
     * ogni giorno vengono aggiunte le singole sessioni che lo compongono.
     */
    private function buildUserSection($displayName, $username, $idCourse, $idUser, $detailed = false)
    {
        $data = $this->model->getUserSessionsByDay($idCourse, $idUser);

        $html = '<tr><th colspan="4">' . htmlspecialchars($displayName) . ' (' . htmlspecialchars($username) . ')</th></tr>'
            . '<tr>'
            . '<th>' . Lang::t('_DATE', 'standard') . '</th>'
            . '<th>' . Lang::t('_FIRST_ENTRY', 'statistic') . '</th>'
            . '<th>' . Lang::t('_LAST_EXIT', 'statistic') . '</th>'
            . '<th>' . Lang::t('_HOW_MUCH_TIME', 'statistic') . '</th>'
            . '</tr>';

        foreach ($data['rows'] as $row) {
            $html .= '<tr>'
                . '<td>' . htmlspecialchars($row['date']) . '</td>'
                . '<td>' . htmlspecialchars($row['first_entry']) . '</td>'
                . '<td>' . htmlspecialchars($row['last_exit']) . '</td>'
                . '<td>' . htmlspecialchars($row['duration']) . '</td>'
                . '</tr>';

            if ($detailed) {
                foreach ($row['sessions'] as $s) {
                    $html .= '<tr>'
                        . '<td></td>'
                        . '<td>' . htmlspecialchars($s['enter']) . '</td>'
                        . '<td>' . htmlspecialchars($s['exit']) . '</td>'
                        . '<td>' . htmlspecialchars($s['duration']) . '</td>'
                        . '</tr>';
                }
            }
        }

        $html .= '<tr>'
            . '<td><b>' . Lang::t('_TOTAL', 'standard') . '</b></td>'
            . '<td><b>' . Lang::t('_NUMBER_OF_ACCESS', 'statistic') . ': ' . $data['session_count'] . '</b></td>'
            . '<td></td>'
            . '<td><b>' . $data['total_duration'] . '</b></td>'
            . '</tr>';

        return $html;
    }
}
