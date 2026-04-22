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
 * Completion report.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Per-user, per-course completion status.
 */
class completion_report extends report_base {

    public static function type(): string {
        return 'completion';
    }

    public function title(): string {
        return get_string('report_completion', 'local_intelliboard');
    }

    public function columns(): array {
        return [
            'fullname'   => get_string('col_user', 'local_intelliboard'),
            'course'     => get_string('col_course', 'local_intelliboard'),
            'progress'   => get_string('col_progress', 'local_intelliboard'),
            'status'     => get_string('col_status', 'local_intelliboard'),
            'grade'      => get_string('col_grade', 'local_intelliboard'),
            'timecompleted' => get_string('col_completed', 'local_intelliboard'),
        ];
    }

    public function rows(): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $params = [];
        $where = 'WHERE u.deleted = 0';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['userid'])) {
            $where .= ' AND u.id = :userid';
            $params['userid'] = (int) $this->filters['userid'];
        }

        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $sql = "SELECT cc.id, u.id AS userid, c.id AS courseid,
                       $fullname AS fullname, c.fullname AS course,
                       cc.timestarted, cc.timecompleted,
                       gg.finalgrade
                  FROM {course_completions} cc
                  JOIN {user} u ON u.id = cc.userid
                  JOIN {course} c ON c.id = cc.course
             LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
             LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                  $where
              ORDER BY u.lastname ASC, c.fullname ASC";

        $rows = $DB->get_records_sql($sql, $params, $this->offset, $this->limit);
        $out = [];
        $coursecache = [];
        foreach ($rows as $row) {
            $cid = (int) $row->courseid;
            if (!isset($coursecache[$cid])) {
                $coursecache[$cid] = $DB->get_record('course', ['id' => $cid]);
            }
            $course = $coursecache[$cid];
            $percent = null;
            if ($course && !empty($course->enablecompletion)) {
                $percent = \core_completion\progress::get_course_progress_percentage($course, (int) $row->userid);
            }

            if (!empty($row->timecompleted)) {
                $status = get_string('status_completed', 'local_intelliboard');
                $timecompleted = userdate((int) $row->timecompleted, '%Y-%m-%d');
            } else if ($percent !== null && $percent > 0) {
                $status = get_string('status_inprogress', 'local_intelliboard');
                $timecompleted = '-';
            } else {
                $status = get_string('status_notstarted', 'local_intelliboard');
                $timecompleted = '-';
            }

            $out[] = (object) [
                'fullname' => $row->fullname,
                'course'   => format_string($row->course),
                'progress' => $percent === null ? '-' : (int) round($percent) . '%',
                'status'   => $status,
                'grade'    => $row->finalgrade !== null ? round((float) $row->finalgrade, 2) : '-',
                'timecompleted' => $timecompleted,
            ];
        }
        return $out;
    }

    public function count(): int {
        global $DB;
        $params = [];
        $where = 'WHERE u.deleted = 0';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['userid'])) {
            $where .= ' AND u.id = :userid';
            $params['userid'] = (int) $this->filters['userid'];
        }
        return (int) $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course_completions} cc
               JOIN {user} u ON u.id = cc.userid
               JOIN {course} c ON c.id = cc.course
             $where",
            $params
        );
    }
}
