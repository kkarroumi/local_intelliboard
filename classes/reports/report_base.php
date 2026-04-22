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
 * Abstract base class for analytics reports.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Base report: implements filter handling, pagination, and contract for data.
 */
abstract class report_base {

    /** @var array Filter values (courseid, userid, from, to, categoryid, roleid). */
    protected array $filters;

    /** @var int Pagination offset. */
    protected int $offset = 0;

    /** @var int Pagination limit (0 = no limit). */
    protected int $limit = 0;

    /**
     * @param array $filters
     */
    public function __construct(array $filters = []) {
        $this->filters = $filters + [
            'courseid'  => 0,
            'userid'    => 0,
            'from'      => 0,
            'to'        => 0,
            'categoryid' => 0,
            'roleid'    => 0,
        ];
    }

    public function set_pagination(int $offset, int $limit): void {
        $this->offset = max(0, $offset);
        $this->limit  = max(0, $limit);
    }

    public function get_filters(): array {
        return $this->filters;
    }

    /**
     * Unique identifier used in URLs (e.g. "users", "courses").
     *
     * @return string
     */
    abstract public static function type(): string;

    /**
     * Human-readable title.
     *
     * @return string
     */
    abstract public function title(): string;

    /**
     * Column definitions as ['key' => 'Label'].
     *
     * @return array<string, string>
     */
    abstract public function columns(): array;

    /**
     * Fetches the report rows for the current filters.
     *
     * @return array<int, \stdClass> Raw rows (each property matches a column key).
     */
    abstract public function rows(): array;

    /**
     * Returns the total number of rows matching the filters (for pagination).
     *
     * @return int
     */
    abstract public function count(): int;

    /**
     * Builds a WHERE clause fragment for a standard date filter against a field.
     *
     * @param string $field Fully-qualified column (e.g. "l.timecreated").
     * @param array $params In/out parameter array.
     * @return string SQL fragment (may be empty).
     */
    protected function date_where(string $field, array &$params): string {
        $sql = '';
        if (!empty($this->filters['from'])) {
            $sql .= " AND {$field} >= :from";
            $params['from'] = (int) $this->filters['from'];
        }
        if (!empty($this->filters['to'])) {
            $sql .= " AND {$field} < :to";
            $params['to'] = (int) $this->filters['to'];
        }
        return $sql;
    }

    /**
     * Formats a row for export (scalar values only).
     *
     * @param \stdClass $row
     * @return array<string, string>
     */
    public function format_row_for_export(\stdClass $row): array {
        $out = [];
        foreach (array_keys($this->columns()) as $col) {
            $val = $row->$col ?? '';
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i');
            }
            $out[$col] = (string) $val;
        }
        return $out;
    }
}
