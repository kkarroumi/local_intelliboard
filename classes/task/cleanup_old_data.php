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
 * Scheduled task: prune raw logs older than the retention window.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\task;

use core\task\scheduled_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes raw logs older than retentiondays. Aggregated rows are kept.
 */
class cleanup_old_data extends scheduled_task {

    public function get_name(): string {
        return get_string('task_cleanup', 'local_intelliboard');
    }

    public function execute(): void {
        global $DB;

        $retention = (int) (get_config('local_intelliboard', 'retentiondays') ?: 365);
        $cutoff = time() - ($retention * DAYSECS);

        $count = $DB->count_records_select(
            'local_intelliboard_logs',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );

        if ($count === 0) {
            mtrace('[local_intelliboard] Nothing to clean up.');
            return;
        }

        $DB->delete_records_select(
            'local_intelliboard_logs',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );

        mtrace("[local_intelliboard] Deleted {$count} log rows older than {$retention} days.");
    }
}
