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
 * Activities report.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Engagement metrics per activity (course module).
 */
class activities_report extends report_base {

    public static function type(): string {
        return 'activities';
    }

    public function title(): string {
        return get_string('report_activities', 'local_intelliboard');
    }

    public function columns(): array {
        return [
            'activity'    => get_string('col_activity', 'local_intelliboard'),
            'course'      => get_string('col_course', 'local_intelliboard'),
            'views'       => get_string('col_views', 'local_intelliboard'),
            'submissions' => get_string('col_submissions', 'local_intelliboard'),
            'completions' => get_string('col_completed', 'local_intelliboard'),
        ];
    }

    public function rows(): array {
        global $DB;

        $params = [
            'viewtarget' => 'module_view',
            'subassign'  => 'assign_submission',
            'subquiz'    => 'quiz_submission',
            'comptarget' => 'module_completion',
        ];
        $where = 'WHERE cm.visible = 1 AND cm.deletioninprogress = 0';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND cm.course = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }

        $sql = "SELECT cm.id,
                       m.name AS modname,
                       c.fullname AS coursename,
                       (SELECT COUNT(*) FROM {local_intelliboard_logs} l
                         WHERE l.cmid = cm.id AND l.target = :viewtarget) AS views,
                       (SELECT COUNT(*) FROM {local_intelliboard_logs} l
                         WHERE l.cmid = cm.id AND (l.target = :subassign OR l.target = :subquiz)) AS submissions,
                       (SELECT COUNT(*) FROM {local_intelliboard_logs} l
                         WHERE l.cmid = cm.id AND l.target = :comptarget) AS completions
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {course} c ON c.id = cm.course
                  $where
              ORDER BY views DESC";

        $rows = $DB->get_records_sql($sql, $params, $this->offset, $this->limit);

        $out = [];
        foreach ($rows as $row) {
            $cm = get_coursemodule_from_id($row->modname, $row->id, 0, false, IGNORE_MISSING);
            $out[] = (object) [
                'id'          => (int) $row->id,
                'activity'    => $cm ? format_string($cm->name) : ($row->modname . ' #' . $row->id),
                'course'      => format_string($row->coursename),
                'views'       => (int) $row->views,
                'submissions' => (int) $row->submissions,
                'completions' => (int) $row->completions,
            ];
        }
        return $out;
    }

    public function count(): int {
        global $DB;
        $params = [];
        $where = 'WHERE cm.visible = 1 AND cm.deletioninprogress = 0';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND cm.course = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        return (int) $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course_modules} cm $where",
            $params
        );
    }
}
