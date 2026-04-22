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
 * Privacy provider.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Data subject requests: export & delete user data held by this plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_intelliboard_logs',
            [
                'userid'      => 'privacy:metadata:local_intelliboard_logs:userid',
                'courseid'    => 'privacy:metadata:local_intelliboard_logs:courseid',
                'action'      => 'privacy:metadata:local_intelliboard_logs:action',
                'timecreated' => 'privacy:metadata:local_intelliboard_logs:timecreated',
            ],
            'privacy:metadata:local_intelliboard_logs'
        );

        $collection->add_database_table(
            'local_intelliboard_tracking',
            [
                'userid'    => 'privacy:metadata:local_intelliboard_tracking:userid',
                'timespent' => 'privacy:metadata:local_intelliboard_tracking:timespent',
            ],
            'privacy:metadata:local_intelliboard_tracking'
        );

        $collection->add_database_table(
            'local_intelliboard_daily',
            [
                'userid'    => 'privacy:metadata:local_intelliboard_daily:userid',
                'timespent' => 'privacy:metadata:local_intelliboard_daily:timespent',
            ],
            'privacy:metadata:local_intelliboard_daily'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        // Plugin data lives at system context.
        $contextlist->add_system_context();
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT userid FROM {local_intelliboard_logs}
                 UNION
                SELECT userid FROM {local_intelliboard_tracking}
                 UNION
                SELECT userid FROM {local_intelliboard_daily}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $logs = $DB->get_records('local_intelliboard_logs', ['userid' => $userid], 'timecreated ASC');
            $tracking = $DB->get_records('local_intelliboard_tracking', ['userid' => $userid]);
            $daily = $DB->get_records('local_intelliboard_daily', ['userid' => $userid], 'day ASC');

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_intelliboard')],
                (object) [
                    'logs'     => array_values($logs),
                    'tracking' => array_values($tracking),
                    'daily'    => array_values($daily),
                ]
            );
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $DB->delete_records('local_intelliboard_logs');
        $DB->delete_records('local_intelliboard_tracking');
        $DB->delete_records('local_intelliboard_daily');
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }
            $DB->delete_records('local_intelliboard_logs', ['userid' => $userid]);
            $DB->delete_records('local_intelliboard_tracking', ['userid' => $userid]);
            $DB->delete_records('local_intelliboard_daily', ['userid' => $userid]);
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_intelliboard_logs', "userid $insql", $params);
        $DB->delete_records_select('local_intelliboard_tracking', "userid $insql", $params);
        $DB->delete_records_select('local_intelliboard_daily', "userid $insql", $params);
    }
}
