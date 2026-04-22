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
 * Learners report.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Per-learner aggregated report.
 */
class users_report extends report_base {

    public static function type(): string {
        return 'users';
    }

    public function title(): string {
        return get_string('report_users', 'local_intelliboard');
    }

    public function columns(): array {
        return [
            'fullname'    => get_string('col_user', 'local_intelliboard'),
            'email'       => get_string('col_email', 'local_intelliboard'),
            'lastaccess'  => get_string('col_lastaccess', 'local_intelliboard'),
            'visits'      => get_string('col_visits', 'local_intelliboard'),
            'timespent'   => get_string('col_timespent', 'local_intelliboard'),
            'completions' => get_string('col_completed', 'local_intelliboard'),
        ];
    }

    /**
     * @return array{sql: string, params: array}
     */
    private function sql(): array {
        global $DB;

        $params = [];
        $where = '';

        if (!empty($this->filters['courseid'])) {
            $where .= ' AND d.courseid = :courseid';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['userid'])) {
            $where .= ' AND u.id = :userid';
            $params['userid'] = (int) $this->filters['userid'];
        }

        $where .= $this->date_where('d.day', $params);

        $sql = "SELECT u.id AS id,
                       " . $DB->sql_fullname('u.firstname', 'u.lastname') . " AS fullname,
                       u.email,
                       u.lastaccess,
                       COALESCE(SUM(d.visits), 0) AS visits,
                       COALESCE(SUM(d.timespent), 0) AS timespent,
                       COALESCE(SUM(d.completions), 0) AS completions
                  FROM {user} u
             LEFT JOIN {local_intelliboard_daily} d ON d.userid = u.id
                 WHERE u.deleted = 0 AND u.id > 2 $where
              GROUP BY u.id, u.firstname, u.lastname, u.email, u.lastaccess
              ORDER BY u.lastaccess DESC";

        return ['sql' => $sql, 'params' => $params];
    }

    public function rows(): array {
        global $DB;

        ['sql' => $sql, 'params' => $params] = $this->sql();

        $rows = $DB->get_records_sql($sql, $params, $this->offset, $this->limit);
        foreach ($rows as $row) {
            $row->lastaccess = $row->lastaccess ? userdate((int) $row->lastaccess, '%Y-%m-%d %H:%M') : '-';
            $row->timespent  = format_time((int) $row->timespent);
            $row->email      = $this->maybe_anonymize($row->email);
            $row->fullname   = $this->maybe_anonymize($row->fullname, 'Learner #' . $row->id);
        }
        return array_values($rows);
    }

    public function count(): int {
        global $DB;
        $params = [];
        $where = '';
        if (!empty($this->filters['courseid'])) {
            $where .= ' AND EXISTS (SELECT 1 FROM {local_intelliboard_daily} d
                                     WHERE d.userid = u.id AND d.courseid = :courseid)';
            $params['courseid'] = (int) $this->filters['courseid'];
        }
        if (!empty($this->filters['userid'])) {
            $where .= ' AND u.id = :userid';
            $params['userid'] = (int) $this->filters['userid'];
        }
        $sql = "SELECT COUNT(*) FROM {user} u
                 WHERE u.deleted = 0 AND u.id > 2 $where";
        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Replaces identifiable data with a pseudonym when anonymisation is on.
     */
    private function maybe_anonymize(string $value, ?string $replacement = null): string {
        if (!get_config('local_intelliboard', 'anonymize')) {
            return $value;
        }
        return $replacement ?? ('user_' . substr(sha1($value), 0, 8));
    }
}
