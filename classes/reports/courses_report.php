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
 *
 * We compute each metric as its own aggregate query (keyed by courseid) and
 * merge the results in PHP. This avoids correlated subqueries, which are
 * poorly optimised on MySQL/MariaDB and can break on some hosted databases.
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

    /**
     * Returns the courses matching the current filters (paginated).
     *
     * @return array<int, \stdClass>
     */
    private function filtered_courses(): array {
        global $DB, $SITE;

        $params = ['siteid' => $SITE->id];
        $where = 'c.id <> :siteid';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['categoryid'])) {
            $where .= ' AND c.category = :categoryid';
            $params['categoryid'] = (int) $this->filters['categoryid'];
        }

        return $DB->get_records_sql(
            "SELECT c.id, c.fullname
               FROM {course} c
              WHERE $where
              ORDER BY c.fullname ASC",
            $params,
            $this->offset,
            $this->limit
        );
    }

    public function rows(): array {
        global $DB;

        $courses = $this->filtered_courses();
        if (empty($courses)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'c');

        // Metric 1: enrolments per course.
        $enrolled = $DB->get_records_sql_menu(
            "SELECT e.courseid, COUNT(DISTINCT ue.userid)
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.status = 0 AND e.courseid $insql
              GROUP BY e.courseid",
            $inparams
        );

        // Metric 2: active users per course (optional date window).
        $activewhere = "courseid $insql";
        $activeparams = $inparams;
        if (!empty($this->filters['from'])) {
            $activewhere .= ' AND day >= :actfrom';
            $activeparams['actfrom'] = (int) $this->filters['from'];
        }
        if (!empty($this->filters['to'])) {
            $activewhere .= ' AND day < :actto';
            $activeparams['actto'] = (int) $this->filters['to'];
        }
        $active = $DB->get_records_sql_menu(
            "SELECT courseid, COUNT(DISTINCT userid)
               FROM {local_intelliboard_daily}
              WHERE $activewhere
              GROUP BY courseid",
            $activeparams
        );

        // Metric 3: completions per course.
        $completed = $DB->get_records_sql_menu(
            "SELECT course, COUNT(*)
               FROM {course_completions}
              WHERE timecompleted > 0 AND course $insql
              GROUP BY course",
            $inparams
        );

        // Metric 4: average final grade per course.
        $avggrade = $DB->get_records_sql_menu(
            "SELECT gi.courseid, AVG(gg.finalgrade)
               FROM {grade_items} gi
               JOIN {grade_grades} gg ON gg.itemid = gi.id
              WHERE gi.itemtype = 'course' AND gi.courseid $insql
              GROUP BY gi.courseid",
            $inparams
        );

        // Metric 5: average per-user time spent (aggregate by user first, then average).
        $avgtimespent = $DB->get_records_sql_menu(
            "SELECT courseid, AVG(total) FROM (
                SELECT courseid, userid, SUM(timespent) AS total
                  FROM {local_intelliboard_daily}
                 WHERE courseid $insql
                 GROUP BY courseid, userid
             ) sub
             GROUP BY courseid",
            $inparams
        );

        $out = [];
        foreach ($courses as $course) {
            $cid = (int) $course->id;
            $out[] = (object) [
                'id'           => $cid,
                'fullname'     => format_string($course->fullname),
                'enrolled'     => (int) ($enrolled[$cid] ?? 0),
                'active'       => (int) ($active[$cid] ?? 0),
                'completed'    => (int) ($completed[$cid] ?? 0),
                'avggrade'     => isset($avggrade[$cid]) && $avggrade[$cid] !== null
                                    ? round((float) $avggrade[$cid], 2) : '-',
                'avgtimespent' => format_time((int) round((float) ($avgtimespent[$cid] ?? 0))),
            ];
        }
        return $out;
    }

    public function count(): int {
        global $DB, $SITE;
        $params = ['siteid' => $SITE->id];
        $where = 'c.id <> :siteid';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['categoryid'])) {
            $where .= ' AND c.category = :categoryid';
            $params['categoryid'] = (int) $this->filters['categoryid'];
        }
        return (int) $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course} c WHERE $where",
            $params
        );
    }
}
