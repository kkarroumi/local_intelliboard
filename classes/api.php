<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Central analytics API: reusable queries used by reports and the dashboard.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Static façade gathering parameterised SQL helpers.
 */
class api {

    /**
     * Returns the list of courses the given user can access for reporting.
     * A site manager / admin sees every course; teachers only see courses where
     * they have the capability at the course context level.
     *
     * @param int $userid
     * @return array<int, \stdClass> Keyed by course id.
     */
    public static function accessible_courses(int $userid): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $syscontext = \context_system::instance();
        if (has_capability('moodle/site:config', $syscontext, $userid)) {
            return $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, fullname, shortname, category, startdate');
        }

        $courses = enrol_get_users_courses($userid, true, 'id, fullname, shortname, category, startdate');
        $result = [];
        foreach ($courses as $course) {
            $ctx = \context_course::instance($course->id, IGNORE_MISSING);
            if ($ctx && has_capability('local/intelliboard:viewreports', $ctx, $userid)) {
                $result[$course->id] = $course;
            }
        }
        return $result;
    }

    /**
     * Counts distinct users who connected on the given time window.
     *
     * @param int $from Unix timestamp (inclusive).
     * @param int $to Unix timestamp (exclusive). 0 means now.
     * @return int
     */
    public static function active_users(int $from, int $to = 0): int {
        global $DB;

        $to = $to ?: time();
        $sql = "SELECT COUNT(DISTINCT userid)
                  FROM {local_intelliboard_logs}
                 WHERE timecreated >= :from AND timecreated < :to AND userid > 0";
        return (int) $DB->count_records_sql($sql, ['from' => $from, 'to' => $to]);
    }

    /**
     * Total visible courses.
     *
     * @return int
     */
    public static function total_courses(): int {
        global $DB, $SITE;
        $params = ['siteid' => $SITE->id];
        return (int) $DB->count_records_sql(
            'SELECT COUNT(*) FROM {course} WHERE id <> :siteid',
            $params
        );
    }

    /**
     * Total visible course modules.
     *
     * @return int
     */
    public static function total_activities(): int {
        global $DB;
        return (int) $DB->count_records('course_modules', ['visible' => 1, 'deletioninprogress' => 0]);
    }

    /**
     * Average course completion percentage across all enrolments.
     *
     * @return float 0..100
     */
    public static function average_completion(): float {
        global $DB;

        $sql = "SELECT
                    SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) AS completed,
                    COUNT(cc.id) AS total
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1";
        $row = $DB->get_record_sql($sql);
        if (!$row || (int) $row->total === 0) {
            return 0.0;
        }
        return round(((float) $row->completed / (float) $row->total) * 100, 1);
    }

    /**
     * Average time spent per active user (seconds) in the given window.
     *
     * @param int $from
     * @param int $to
     * @return int Seconds per user (integer average).
     */
    public static function average_timespent(int $from, int $to = 0): int {
        global $DB;
        $to = $to ?: time();
        $sql = "SELECT AVG(total) AS avgspent
                  FROM (SELECT SUM(timespent) AS total
                          FROM {local_intelliboard_daily}
                         WHERE day >= :from AND day < :to
                         GROUP BY userid) sub";
        $row = $DB->get_record_sql($sql, ['from' => $from, 'to' => $to]);
        return (int) round((float) ($row->avgspent ?? 0));
    }

    /**
     * Number of events of a given target that happened since midnight today.
     *
     * @param string $target
     * @return int
     */
    public static function count_events_today(string $target): int {
        global $DB;
        $start = usergetmidnight(time());
        $sql = "SELECT COUNT(*) FROM {local_intelliboard_logs}
                 WHERE target = :target AND timecreated >= :start";
        return (int) $DB->count_records_sql($sql, ['target' => $target, 'start' => $start]);
    }

    /**
     * Returns the 30-day daily activity series (visits, submissions).
     *
     * @param int $days Number of days to include.
     * @return array{labels: string[], visits: int[], submissions: int[]}
     */
    public static function daily_activity_series(int $days = 30): array {
        global $DB;

        $end = usergetmidnight(time()) + DAYSECS;
        $start = $end - ($days * DAYSECS);

        $records = $DB->get_records_sql(
            "SELECT day, SUM(visits) AS visits, SUM(submissions) AS submissions
               FROM {local_intelliboard_daily}
              WHERE day >= :start AND day < :end
              GROUP BY day
              ORDER BY day ASC",
            ['start' => $start, 'end' => $end]
        );

        $labels = [];
        $visits = [];
        $submissions = [];
        $map = [];
        foreach ($records as $r) {
            $map[(int) $r->day] = $r;
        }

        for ($ts = $start; $ts < $end; $ts += DAYSECS) {
            $labels[] = userdate($ts, '%b %d');
            if (isset($map[$ts])) {
                $visits[] = (int) $map[$ts]->visits;
                $submissions[] = (int) $map[$ts]->submissions;
            } else {
                $visits[] = 0;
                $submissions[] = 0;
            }
        }

        return ['labels' => $labels, 'visits' => $visits, 'submissions' => $submissions];
    }

    /**
     * Top N courses by completion percentage (amongst courses with completion enabled).
     *
     * @param int $limit
     * @return array{labels: string[], values: float[]}
     */
    public static function course_completion_series(int $limit = 10): array {
        global $DB;

        $sql = "SELECT c.id, c.fullname,
                       SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) AS completed,
                       COUNT(cc.id) AS total
                  FROM {course} c
                  JOIN {course_completions} cc ON cc.course = c.id
                 WHERE c.enablecompletion = 1
                 GROUP BY c.id, c.fullname
                 HAVING COUNT(cc.id) > 0
                 ORDER BY (SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) * 1.0 / COUNT(cc.id)) DESC";
        $rows = $DB->get_records_sql($sql, null, 0, $limit);

        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = format_string($r->fullname);
            $values[] = $r->total > 0 ? round(((float) $r->completed / (float) $r->total) * 100, 1) : 0.0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Heatmap of activity by weekday × hour (0..6 × 0..23). Values are visit counts.
     *
     * @param int $days
     * @return array<int, array<int, int>>
     */
    public static function heatmap(int $days = 30): array {
        global $DB;

        $start = time() - ($days * DAYSECS);
        $grid = [];
        for ($d = 0; $d < 7; $d++) {
            $grid[$d] = array_fill(0, 24, 0);
        }

        $rs = $DB->get_recordset_sql(
            "SELECT timecreated FROM {local_intelliboard_logs}
              WHERE target = :t AND timecreated >= :start",
            ['t' => 'module_view', 'start' => $start]
        );
        foreach ($rs as $row) {
            $ts = (int) $row->timecreated;
            $wd = (int) userdate($ts, '%w'); // 0 = Sunday.
            $hr = (int) userdate($ts, '%H');
            $grid[$wd][$hr]++;
        }
        $rs->close();
        return $grid;
    }

    /**
     * Top N activities by number of views in the given window.
     *
     * @param int $limit
     * @param int $days
     * @return array{labels: string[], values: int[]}
     */
    public static function top_activities(int $limit = 10, int $days = 30): array {
        global $DB;

        $start = time() - ($days * DAYSECS);
        $sql = "SELECT cm.id, m.name AS modname, COUNT(l.id) AS views
                  FROM {local_intelliboard_logs} l
                  JOIN {course_modules} cm ON cm.id = l.cmid
                  JOIN {modules} m ON m.id = cm.module
                 WHERE l.target = :target AND l.timecreated >= :start
                 GROUP BY cm.id, m.name
                 ORDER BY views DESC";
        $rows = $DB->get_records_sql($sql, ['target' => 'module_view', 'start' => $start], 0, $limit);

        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $cm = get_coursemodule_from_id($r->modname, $r->id, 0, false, IGNORE_MISSING);
            $labels[] = $cm ? format_string($cm->name) : ($r->modname . ' #' . $r->id);
            $values[] = (int) $r->views;
        }
        return ['labels' => $labels, 'values' => $values];
    }
}
