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
 * Scheduled task: aggregate yesterday's raw logs into daily buckets.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\task;

use core\task\scheduled_task;
use local_intelliboard\analytics\kpi;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregates raw logs into the per-day, per-user, per-course summary.
 *
 * Runs once a day after midnight so the previous day is complete.
 * Approximates "time spent" from the median gap between successive events,
 * capped by the inactivity threshold.
 */
class aggregate_daily extends scheduled_task {

    public function get_name(): string {
        return get_string('task_aggregate', 'local_intelliboard');
    }

    public function execute(): void {
        global $DB;

        // Aggregate yesterday (UTC-ish midnight using usergetmidnight against UTC 0).
        $dayend = usergetmidnight(time());
        $daystart = $dayend - DAYSECS;

        mtrace("[local_intelliboard] Aggregating logs for " . userdate($daystart, '%Y-%m-%d'));

        $inactivity = (int) (get_config('local_intelliboard', 'inactivity') ?: 60);

        // Load all events for the window into memory grouped by (user, course).
        $events = $DB->get_recordset_select(
            'local_intelliboard_logs',
            'timecreated >= :from AND timecreated < :to AND userid > 0',
            ['from' => $daystart, 'to' => $dayend],
            'userid ASC, courseid ASC, timecreated ASC',
            'id, userid, courseid, target, timecreated'
        );

        $buckets = [];
        $lasttime = [];

        foreach ($events as $ev) {
            $key = $ev->userid . ':' . $ev->courseid;
            if (!isset($buckets[$key])) {
                $buckets[$key] = (object) [
                    'userid'      => (int) $ev->userid,
                    'courseid'    => (int) $ev->courseid,
                    'day'         => $daystart,
                    'visits'      => 0,
                    'timespent'   => 0,
                    'submissions' => 0,
                    'completions' => 0,
                ];
            }

            $b = $buckets[$key];
            $b->visits++;

            if (isset($lasttime[$key])) {
                $delta = (int) $ev->timecreated - $lasttime[$key];
                if ($delta > 0 && $delta <= $inactivity) {
                    $b->timespent += $delta;
                }
            }
            $lasttime[$key] = (int) $ev->timecreated;

            if ($ev->target === 'assign_submission' || $ev->target === 'quiz_submission') {
                $b->submissions++;
            }
            if ($ev->target === 'module_completion') {
                $b->completions++;
            }
        }
        $events->close();

        // Upsert aggregates.
        foreach ($buckets as $b) {
            $existing = $DB->get_record('local_intelliboard_daily', [
                'userid'   => $b->userid,
                'courseid' => $b->courseid,
                'day'      => $b->day,
            ], 'id');

            if ($existing) {
                $b->id = $existing->id;
                $DB->update_record('local_intelliboard_daily', $b);
            } else {
                $DB->insert_record('local_intelliboard_daily', $b);
            }
        }

        mtrace("[local_intelliboard] Aggregated " . count($buckets) . " user-course buckets.");

        // Refresh cached dashboard KPIs so the next view reflects new data.
        kpi::invalidate();
    }
}
