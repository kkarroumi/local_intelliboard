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
 * Event observer.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard;

use core\event\base as event_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles Moodle events and persists them in the analytics log.
 */
class observer {

    /**
     * Central entry point used by every subscribed observer.
     *
     * @param event_base $event
     * @param string $target Short identifier for the event category (e.g. "login").
     * @return void
     */
    protected static function record(event_base $event, string $target): void {
        global $DB;

        if (!get_config('local_intelliboard', 'enabletracking')) {
            return;
        }

        $record = (object) [
            'userid'      => (int) ($event->userid ?? 0),
            'courseid'    => (int) ($event->courseid ?? 0),
            'contextid'   => (int) ($event->contextid ?? 0),
            'cmid'        => (int) ($event->contextinstanceid ?? 0),
            'action'      => (string) $event->eventname,
            'target'      => $target,
            'component'   => (string) ($event->component ?? ''),
            'timecreated' => (int) $event->timecreated,
        ];

        // Low-level insert (fast, no return value needed).
        try {
            $DB->insert_record('local_intelliboard_logs', $record, false, true);
        } catch (\dml_exception $e) {
            debugging(
                'local_intelliboard observer failed: ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }

    public static function on_login(event_base $event): void {
        self::record($event, 'login');
    }

    public static function on_logout(event_base $event): void {
        self::record($event, 'logout');
    }

    public static function on_course_viewed(event_base $event): void {
        self::record($event, 'course_view');
    }

    public static function on_module_viewed(event_base $event): void {
        self::record($event, 'module_view');
    }

    public static function on_module_completion(event_base $event): void {
        self::record($event, 'module_completion');
    }

    public static function on_course_completed(event_base $event): void {
        self::record($event, 'course_completed');
    }

    public static function on_user_graded(event_base $event): void {
        self::record($event, 'graded');
    }

    public static function on_quiz_submitted(event_base $event): void {
        self::record($event, 'quiz_submission');
    }

    public static function on_assign_submitted(event_base $event): void {
        self::record($event, 'assign_submission');
    }
}
