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
 * Courses report.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregates key metrics per course.
 */
class courses_report extends report_base {

    public static function type(): string {
        return 'courses';
    }

    public function title(): string {
        return get_string('report_courses', 'local_intelliboard');
    }

    public function columns(): array {
        return [
            'fullname'      => get_string('col_course', 'local_intelliboard'),
            'enrolled'      => get_string('col_enrolled', 'local_intelliboard'),
            'active'        => get_string('col_active', 'local_intelliboard'),
            'completed'     => get_string('col_completed', 'local_intelliboard'),
            'avggrade'      => get_string('col_grade', 'local_intelliboard'),
            'avgtimespent'  => get_string('col_timespent', 'local_intelliboard'),
        ];
    }

    public function rows(): array {
        global $DB, $SITE;

        $params = ['siteid' => $SITE->id];
        $where = '';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['categoryid'])) {
            $where .= ' AND c.category = :categoryid';
            $params['categoryid'] = (int) $this->filters['categoryid'];
        }

        // Active = users with any daily activity in the filter window.
        $activesub = 'SELECT DISTINCT userid, courseid FROM {local_intelliboard_daily}';
        $activeparams = [];
        if (!empty($this->filters['from'])) {
            $activesub .= ' WHERE day >= :from';
            $activeparams['from'] = (int) $this->filters['from'];
            if (!empty($this->filters['to'])) {
                $activesub .= ' AND day < :to';
                $activeparams['to'] = (int) $this->filters['to'];
            }
        } else if (!empty($this->filters['to'])) {
            $activesub .= ' WHERE day < :to';
            $activeparams['to'] = (int) $this->filters['to'];
        }
        $params = array_merge($params, $activeparams);

        $sql = "SELECT c.id, c.fullname,
                       (SELECT COUNT(DISTINCT ue.userid)
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE e.courseid = c.id AND ue.status = 0) AS enrolled,
                       (SELECT COUNT(DISTINCT a.userid) FROM ($activesub) a WHERE a.courseid = c.id) AS active,
                       (SELECT COUNT(*) FROM {course_completions} cc
                         WHERE cc.course = c.id AND cc.timecompleted > 0) AS completed,
                       (SELECT AVG(gg.finalgrade) FROM {grade_items} gi
                          JOIN {grade_grades} gg ON gg.itemid = gi.id
                         WHERE gi.courseid = c.id AND gi.itemtype = 'course') AS avggrade,
                       (SELECT COALESCE(AVG(sub.total), 0) FROM (
                           SELECT SUM(timespent) AS total
                             FROM {local_intelliboard_daily}
                            WHERE courseid = c.id
                            GROUP BY userid
                       ) sub) AS avgtimespent
                  FROM {course} c
                 WHERE c.id <> :siteid $where
              ORDER BY c.fullname ASC";

        $rows = $DB->get_records_sql($sql, $params, $this->offset, $this->limit);
        foreach ($rows as $row) {
            $row->fullname     = format_string($row->fullname);
            $row->avggrade     = $row->avggrade !== null ? round((float) $row->avggrade, 2) : '-';
            $row->avgtimespent = format_time((int) round((float) $row->avgtimespent));
        }
        return array_values($rows);
    }

    public function count(): int {
        global $DB, $SITE;
        $params = ['siteid' => $SITE->id];
        $where = '';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['categoryid'])) {
            $where .= ' AND c.category = :categoryid';
            $params['categoryid'] = (int) $this->filters['categoryid'];
        }
        return (int) $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course} c WHERE c.id <> :siteid $where",
            $params
        );
    }
}
