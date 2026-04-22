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
 * Report factory — maps a type identifier to a concrete class.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Centralised registry of available report types.
 */
class report_factory {

    /**
     * Returns the list of available report types: [type => [title, description]].
     *
     * @return array<string, array{title: string, description: string}>
     */
    public static function available(): array {
        return [
            users_report::type() => [
                'title'       => get_string('report_users', 'local_intelliboard'),
                'description' => get_string('report_users_desc', 'local_intelliboard'),
            ],
            courses_report::type() => [
                'title'       => get_string('report_courses', 'local_intelliboard'),
                'description' => get_string('report_courses_desc', 'local_intelliboard'),
            ],
            activities_report::type() => [
                'title'       => get_string('report_activities', 'local_intelliboard'),
                'description' => get_string('report_activities_desc', 'local_intelliboard'),
            ],
            completion_report::type() => [
                'title'       => get_string('report_completion', 'local_intelliboard'),
                'description' => get_string('report_completion_desc', 'local_intelliboard'),
            ],
        ];
    }

    /**
     * Instantiates the report matching the given type.
     *
     * @param string $type
     * @param array $filters
     * @return report_base
     * @throws \coding_exception on unknown type.
     */
    public static function make(string $type, array $filters = []): report_base {
        switch ($type) {
            case users_report::type():
                return new users_report($filters);
            case courses_report::type():
                return new courses_report($filters);
            case activities_report::type():
                return new activities_report($filters);
            case completion_report::type():
                return new completion_report($filters);
        }
        throw new \coding_exception('Unknown report type: ' . $type);
    }
}
