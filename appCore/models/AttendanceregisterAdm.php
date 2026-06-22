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
     * Sessioni dell'utente nel corso scelto, stessa query/formato di
     * "Stat. Utilizzo" (statistic.php::userdetails()).
     */
    public function getUserSessions($idCourse, $idUser)
    {
        $query = 'SELECT enterTime, lastTime, (UNIX_TIMESTAMP(lastTime) - UNIX_TIMESTAMP(enterTime)) AS howm, numOp '
            . ' FROM %lms_tracksession '
            . ' WHERE idCourse = ' . (int) $idCourse . ' AND idUser = ' . (int) $idUser
            . ' ORDER BY enterTime ASC';
        $res = $this->db->query($query);

        $rows = [];
        $total_seconds = 0;
        while (list($enter, $last, $how, $num_op) = $this->db->fetch_row($res)) {
            $total_seconds += (int) $how;
            $rows[] = [
                'start' => Format::date($enter),
                'end' => Format::date($last, false, true),
                'duration' => $this->formatDuration($how),
                'num_op' => (int) $num_op,
            ];
        }

        return [
            'rows' => $rows,
            'session_count' => count($rows),
            'total_seconds' => $total_seconds,
            'total_duration' => $this->formatDuration($total_seconds),
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
