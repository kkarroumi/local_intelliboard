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
 *
 * Metrics are computed via three aggregate queries and merged in PHP to avoid
 * correlated subqueries.
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

        $cmparams = [];
        $where = 'cm.visible = 1 AND cm.deletioninprogress = 0';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND cm.course = :courseid';
            $cmparams['courseid'] = (int) $this->filters['courseid'];
        }

        $cmrows = $DB->get_records_sql(
            "SELECT cm.id, cm.course, m.name AS modname, c.fullname AS coursename
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
               JOIN {course} c ON c.id = cm.course
              WHERE $where",
            $cmparams
        );

        if (empty($cmrows)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($cmrows), SQL_PARAMS_NAMED, 'cm');

        $views = $DB->get_records_sql_menu(
            "SELECT cmid, COUNT(*)
               FROM {local_intelliboard_logs}
              WHERE target = :t AND cmid $insql
              GROUP BY cmid",
            array_merge($inparams, ['t' => 'module_view'])
        );

        $submissions = $DB->get_records_sql_menu(
            "SELECT cmid, COUNT(*)
               FROM {local_intelliboard_logs}
              WHERE cmid $insql AND (target = :t1 OR target = :t2)
              GROUP BY cmid",
            array_merge($inparams, ['t1' => 'quiz_submission', 't2' => 'assign_submission'])
        );

        $completions = $DB->get_records_sql_menu(
            "SELECT cmid, COUNT(*)
               FROM {local_intelliboard_logs}
              WHERE target = :t AND cmid $insql
              GROUP BY cmid",
            array_merge($inparams, ['t' => 'module_completion'])
        );

        $all = array_values($cmrows);
        // Pre-sort by views desc so the default output is most-viewed first.
        usort($all, static function($a, $b) use ($views) {
            return ((int) ($views[$b->id] ?? 0)) <=> ((int) ($views[$a->id] ?? 0));
        });

        // Apply pagination in PHP (dataset is already filtered by course).
        if ($this->limit > 0) {
            $all = array_slice($all, $this->offset, $this->limit);
        }

        $out = [];
        foreach ($all as $row) {
            $cm = get_coursemodule_from_id($row->modname, $row->id, 0, false, IGNORE_MISSING);
            $out[] = (object) [
                'id'          => (int) $row->id,
                'activity'    => $cm ? format_string($cm->name) : ($row->modname . ' #' . $row->id),
                'course'      => format_string($row->coursename),
                'views'       => (int) ($views[$row->id] ?? 0),
                'submissions' => (int) ($submissions[$row->id] ?? 0),
                'completions' => (int) ($completions[$row->id] ?? 0),
            ];
        }
        return $out;
    }

    public function count(): int {
        global $DB;
        $params = [];
        $where = 'cm.visible = 1 AND cm.deletioninprogress = 0';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND cm.course = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        return (int) $DB->count_records_sql(
            "SELECT COUNT(*) FROM {course_modules} cm WHERE $where",
            $params
        );
    }
}
