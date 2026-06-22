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

    public function __construct()
    {
        $this->db = DbConn::getInstance();
    }

    /**
     * Tutti i corsi della piattaforma (esclusi solo quelli "In costruzione").
     */
    public function getAllCourses()
    {
        $query = "SELECT idCourse, name FROM %lms_course WHERE status <> '0' ORDER BY name ASC";
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
     * orfane: stesso problema gia' risolto altrove in questa Dashboard).
     */
    public function getCourseUsers($idCourse)
    {
        $query = 'SELECT u.idst, u.userid, u.firstname, u.lastname FROM %lms_courseuser cu '
            . ' JOIN %adm_user u ON u.idst = cu.idUser '
            . ' WHERE cu.idCourse = ' . (int) $idCourse
            . ' ORDER BY u.lastname ASC, u.firstname ASC';
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
     * Sessioni dell'utente nel corso scelto, raggruppate per giorno (come un
     * registro presenze: un giorno puo' avere piu' sessioni separate, qui
     * diventano una riga con primo ingresso/ultima uscita/totali del giorno).
     */
    public function getUserSessionsByDay($idCourse, $idUser)
    {
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
