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
        $query = 'SELECT DATE(enterTime) AS theday, MIN(enterTime) AS first_entry, MAX(lastTime) AS last_exit, '
            . ' SUM(UNIX_TIMESTAMP(lastTime) - UNIX_TIMESTAMP(enterTime)) AS total_seconds, '
            . ' SUM(numOp) AS total_num_op, COUNT(*) AS session_count '
            . ' FROM %lms_tracksession '
            . ' WHERE idCourse = ' . (int) $idCourse . ' AND idUser = ' . (int) $idUser
            . ' GROUP BY DATE(enterTime) '
            . ' ORDER BY theday ASC';
        $res = $this->db->query($query);

        $rows = [];
        $grand_total_seconds = 0;
        $grand_total_sessions = 0;
        while (list($day, $first_entry, $last_exit, $day_seconds, $day_num_op, $day_sessions) = $this->db->fetch_row($res)) {
            $grand_total_seconds += (int) $day_seconds;
            $grand_total_sessions += (int) $day_sessions;
            $rows[] = [
                'date' => date('d/m/Y', strtotime($day)),
                'first_entry' => date('H:i', strtotime($first_entry)),
                'last_exit' => date('H:i', strtotime($last_exit)),
                'duration' => $this->formatDuration($day_seconds),
                'num_op' => (int) $day_num_op,
                'session_count' => (int) $day_sessions,
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
