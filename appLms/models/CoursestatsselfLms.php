<?php

/*
 * FORMA - The E-Learning Suite
 *
 * Copyright (c) 2013-2023 (Forma)
 * https://www.formalms.org
 * License https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

defined('IN_FORMA') or exit('Direct access is forbidden.');

class CoursestatsselfLms extends Model
{
    protected $db;
    protected $tables;

    public function __construct()
    {
        $this->db = DbConn::getInstance();
        $this->tables = [
            'organization' => '%lms_organization',
            'commontrack' => '%lms_commontrack',
            'courseuser' => '%lms_courseuser',
            'course' => '%lms_course',
            'scorm_tracking' => '%lms_scorm_tracking',
            'scorm_tracking_history' => '%lms_scorm_tracking_history',
            'testtrack' => '%lms_testtrack',
            'testtrack_times' => '%lms_testtrack_times',
            'test' => '%lms_test',
        ];
        parent::__construct();
    }

    /**
     * Format a number of seconds (can be negative) as [-]HH:MM:SS.
     */
    public function formatSeconds($seconds)
    {
        $seconds = (int) $seconds;
        $sign = $seconds < 0 ? '-' : '';
        $seconds = abs($seconds);

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return $sign . sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * All trackable Learning Objects of a course (excludes folder/category
     * rows, which have an empty objectType), ordered as in the course tree.
     */
    public function getCourseLOs($id_course)
    {
        $output = [];
        $query = 'SELECT idOrg, title, objectType, idResource'
            . ' FROM ' . $this->tables['organization']
            . ' WHERE idCourse=' . (int) $id_course
            . " AND objectType != ''"
            . ' ORDER BY path ASC';

        $res = $this->db->query($query);
        if ($res) {
            while ($obj = $this->db->fetch_obj($res)) {
                $output[] = $obj;
            }
        }

        return $output;
    }

    /**
     * Course-level info for the header cards: enrollment date and configured
     * "tempo medio del corso".
     */
    public function getUserCourseInfo($id_course, $id_user)
    {
        $output = new stdClass();
        $output->date_inscr = null;
        $output->medium_time = 0;

        $query = 'SELECT date_inscr FROM ' . $this->tables['courseuser']
            . ' WHERE idUser=' . (int) $id_user . ' AND idCourse=' . (int) $id_course;
        $res = $this->db->query($query);
        if ($res && $this->db->num_rows($res) > 0) {
            $row = $this->db->fetch_obj($res);
            $output->date_inscr = $row->date_inscr;
        }

        $query = 'SELECT mediumTime FROM ' . $this->tables['course']
            . ' WHERE idCourse=' . (int) $id_course;
        $res = $this->db->query($query);
        if ($res && $this->db->num_rows($res) > 0) {
            $row = $this->db->fetch_obj($res);
            // mediumTime is configured in hours (course "Durata media in ore"),
            // convert to seconds to match the fruition time stats.
            $output->medium_time = (int) $row->mediumTime * 3600;
        }

        return $output;
    }

    /**
     * Stats for one scormorg LO: status, progress percent, total fruition
     * time (seconds) and number of sessions, for one user.
     */
    public function getScormStats($id_org, $id_user)
    {
        require_once Forma::inc(_lms_ . '/class.module/track.object.php');

        $status = Track_Object::getStatusFromId($id_org, $id_user);

        $output = new stdClass();
        $output->status = $status;
        $output->percent = $this->statusToPercent($status);
        $output->totaltime_seconds = 0;
        $output->sessions = 0;

        $query = 'SELECT t1.session_time'
            . ' FROM ' . $this->tables['scorm_tracking_history'] . ' AS t1'
            . ' INNER JOIN ' . $this->tables['scorm_tracking'] . ' AS t2'
            . ' ON t1.idscorm_tracking = t2.idscorm_tracking'
            . ' WHERE t2.idReference=' . (int) $id_org
            . ' AND t2.idUser=' . (int) $id_user;

        $res = $this->db->query($query);
        if ($res) {
            while ($row = $this->db->fetch_row($res)) {
                $output->totaltime_seconds += $this->timeToSeconds($row[0]);
                ++$output->sessions;
            }
        }

        return $output;
    }

    /**
     * Map a Track_Object status string to the badge label used by the view
     * and to an approximate progress percentage.
     */
    public function statusToPercent($status)
    {
        switch ($status) {
            case 'passed':
            case 'completed':
            case 'failed':
                return 100;
            case 'attempted':
            case 'incomplete':
            case 'browsed':
            case 'ab-initio':
                return 50;
            case 'not attempted':
            default:
                return 0;
        }
    }

    /**
     * Convert a "HH:MM:SS" (or "HH:MM:SS.ffffff") string to seconds.
     */
    private function timeToSeconds($time)
    {
        if (empty($time)) {
            return 0;
        }
        list($time) = explode('.', $time);
        $parts = explode(':', $time);
        if (count($parts) !== 3) {
            return 0;
        }
        list($h, $m, $s) = $parts;

        return ((int) $h * 3600) + ((int) $m * 60) + (int) $s;
    }

    /**
     * Stats for one "test" LO: status, progress percent, total time spent
     * (seconds), number of sessions (= attempts incl. incomplete ones),
     * score "x/max" and attempts "n/max" (or "n" if unlimited), for one user.
     */
    public function getTestStats($id_org, $id_resource, $id_user)
    {
        $output = new stdClass();
        $output->status = 'not attempted';
        $output->percent = 0;
        $output->totaltime_seconds = 0;
        $output->sessions = 0;
        $output->score = '-';
        $output->attempts_label = '-';

        // Test definition (point_required, score_max, max_attempt)
        $test = null;
        $query = 'SELECT idTest, point_type, point_required, score_max, max_attempt'
            . ' FROM ' . $this->tables['test']
            . ' WHERE idTest=' . (int) $id_resource;
        $res = $this->db->query($query);
        if ($res && $this->db->num_rows($res) > 0) {
            $test = $this->db->fetch_obj($res);
        }

        if ($test !== null) {
            $output->attempts_label = $test->max_attempt > 0 ? '0/' . (int) $test->max_attempt : '0';
        }

        // User's tracking row for this test
        $query = 'SELECT number_of_attempt, score, score_status'
            . ' FROM ' . $this->tables['testtrack']
            . ' WHERE idReference=' . (int) $id_org . ' AND idUser=' . (int) $id_user;
        $res = $this->db->query($query);
        $track = ($res && $this->db->num_rows($res) > 0) ? $this->db->fetch_obj($res) : null;

        // Sessions / total time: every row in testtrack_times is one attempt
        $query = 'SELECT time'
            . ' FROM ' . $this->tables['testtrack_times']
            . ' WHERE idReference=' . (int) $id_org . ' AND idUser=' . (int) $id_user;
        $res = $this->db->query($query);
        if ($res) {
            while ($row = $this->db->fetch_row($res)) {
                $output->totaltime_seconds += (int) $row[0];
                ++$output->sessions;
            }
        }

        if ($track === null) {
            // Never started: defaults above are correct.
            return $output;
        }

        $number_of_attempt = (int) $track->number_of_attempt;
        if ($test !== null) {
            $output->attempts_label = $test->max_attempt > 0
                ? $number_of_attempt . '/' . (int) $test->max_attempt
                : (string) $number_of_attempt;
        }

        if ($number_of_attempt <= 0 || $track->score === null) {
            // Started but no completed attempt yet.
            $output->status = 'incomplete';
            $output->percent = $this->statusToPercent('incomplete');

            return $output;
        }

        $score = (float) $track->score;
        $score_max = $test !== null ? (float) $test->score_max : 0;
        $point_required = $test !== null ? (float) $test->point_required : 0;
        $output->score = $this->formatTestScore($score, $score_max);

        $passed = $score >= $point_required;
        $output->status = $passed ? 'passed' : 'failed';
        $output->percent = $this->statusToPercent($output->status);

        return $output;
    }

    private function formatTestScore($score, $score_max)
    {
        $score_str = (floor($score) == $score) ? (string) (int) $score : (string) $score;
        $max_str = (floor($score_max) == $score_max) ? (string) (int) $score_max : (string) $score_max;

        return $score_str . '/' . $max_str;
    }

    /**
     * Human-readable Italian label for a status string, used by the view.
     */
    public function statusLabel($status)
    {
        switch ($status) {
            case 'passed':
                return 'Superato';
            case 'completed':
                return 'Completato';
            case 'failed':
                return 'Non superato';
            case 'attempted':
            case 'incomplete':
            case 'browsed':
            case 'ab-initio':
                return 'In corso';
            case 'not attempted':
            default:
                return 'Non iniziato';
        }
    }

    /**
     * CSS class suffix for the status badge, used by the view together with
     * "pui-badge--{class}".
     */
    public function statusBadgeClass($status)
    {
        switch ($status) {
            case 'passed':
            case 'completed':
                return 'success';
            case 'failed':
                return 'danger';
            case 'attempted':
            case 'incomplete':
            case 'browsed':
            case 'ab-initio':
                return 'info';
            case 'not attempted':
            default:
                return 'neutral';
        }
    }

    /**
     * Build the full per-LO stats list for one user/course, plus the
     * aggregated total fruition time and the gap ("scostamento") versus the
     * course's configured "tempo medio".
     *
     * Returns an object with:
     *  - lo_list: array of stdClass { id, title, type, status, status_label,
     *             badge_class, percent, time_formatted, sessions, score,
     *             attempts_label }
     *  - total_time_seconds, total_time_formatted
     *  - medium_time_seconds, medium_time_formatted
     *  - gap_seconds, gap_formatted
     *  - materials_total, materials_completed, materials_remaining
     */
    public function getCourseUserStatsList($id_course, $id_user)
    {
        $output = new stdClass();
        $output->lo_list = [];
        $output->total_time_seconds = 0;

        foreach ($this->getCourseLOs($id_course) as $lo) {
            if ($lo->objectType === 'test') {
                $stats = $this->getTestStats($lo->idOrg, $lo->idResource, $id_user);
            } else {
                $stats = $this->getScormStats($lo->idOrg, $id_user);
            }

            $row = new stdClass();
            $row->id = $lo->idOrg;
            $row->title = $lo->title;
            $row->type = $lo->objectType;
            $row->status = $stats->status;
            $row->status_label = $this->statusLabel($stats->status);
            $row->badge_class = $this->statusBadgeClass($stats->status);
            $row->percent = $stats->percent;
            $row->time_formatted = $this->formatSeconds($stats->totaltime_seconds);
            $row->sessions = $stats->sessions;
            $row->score = $lo->objectType === 'test' ? $stats->score : '-';
            $row->attempts_label = $lo->objectType === 'test' ? $stats->attempts_label : '-';

            $output->lo_list[] = $row;
            $output->total_time_seconds += $stats->totaltime_seconds;
        }

        $output->total_time_formatted = $this->formatSeconds($output->total_time_seconds);

        $output->materials_total = count($output->lo_list);
        $output->materials_completed = 0;
        foreach ($output->lo_list as $row) {
            if ($row->status === 'passed' || $row->status === 'completed') {
                ++$output->materials_completed;
            }
        }
        $output->materials_remaining = $output->materials_total - $output->materials_completed;

        $course_info = $this->getUserCourseInfo($id_course, $id_user);
        $output->medium_time_seconds = $course_info->medium_time;
        $output->medium_time_formatted = $this->formatSeconds($course_info->medium_time);
        $output->date_inscr = $course_info->date_inscr;

        $output->gap_seconds = $output->total_time_seconds - $output->medium_time_seconds;
        $output->gap_formatted = $this->formatSeconds($output->gap_seconds);

        return $output;
    }
}
