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
 * Scheduled task: generate and email scheduled reports.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\task;

use core\task\scheduled_task;
use local_intelliboard\export\csv_exporter;
use local_intelliboard\export\excel_exporter;
use local_intelliboard\export\pdf_exporter;
use local_intelliboard\reports\report_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Processes due scheduled reports: builds the export and emails recipients.
 */
class send_scheduled_reports extends scheduled_task {

    public function get_name(): string {
        return get_string('task_sendreports', 'local_intelliboard');
    }

    public function execute(): void {
        global $DB, $CFG;

        $now = time();
        $due = $DB->get_records_select(
            'local_intelliboard_scheduled',
            'enabled = 1 AND nextrun > 0 AND nextrun <= :now',
            ['now' => $now]
        );

        foreach ($due as $schedule) {
            try {
                $this->process($schedule);
            } catch (\Throwable $e) {
                mtrace("[local_intelliboard] Failed to send schedule {$schedule->id}: " . $e->getMessage());
            }
            $schedule->lastrun = $now;
            $schedule->nextrun = self::compute_nextrun($schedule->frequency, $now);
            $DB->update_record('local_intelliboard_scheduled', $schedule);
        }
    }

    /**
     * @param \stdClass $schedule
     */
    private function process(\stdClass $schedule): void {
        global $CFG;

        $params = $schedule->params ? json_decode($schedule->params, true) : [];
        $report = report_factory::make($schedule->reporttype, is_array($params) ? $params : []);

        $tmpdir = make_request_directory();
        $ext = $schedule->format === 'xlsx' ? 'xlsx' : ($schedule->format === 'pdf' ? 'pdf' : 'csv');
        $filepath = $tmpdir . '/' . clean_filename($schedule->name) . '.' . $ext;

        switch ($ext) {
            case 'xlsx':
                (new excel_exporter())->save($report, $filepath);
                $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'pdf':
                (new pdf_exporter())->save($report, $filepath);
                $mime = 'application/pdf';
                break;
            default:
                (new csv_exporter())->save($report, $filepath);
                $mime = 'text/csv';
        }

        $recipients = array_filter(array_map('trim', explode(',', (string) $schedule->recipients)));
        if (empty($recipients)) {
            mtrace("[local_intelliboard] Schedule {$schedule->id} has no recipients.");
            return;
        }

        $subject = get_string('scheduledreport', 'local_intelliboard', $schedule->name);
        $body = format_text(
            get_string('scheduledreport', 'local_intelliboard', $schedule->name),
            FORMAT_PLAIN
        );

        $from = \core_user::get_noreply_user();

        foreach ($recipients as $email) {
            $user = \core_user::get_user_by_email($email);
            if (!$user) {
                // Synthesize a minimal user for external recipients.
                $user = (object) [
                    'id'        => -99,
                    'email'     => $email,
                    'firstname' => '',
                    'lastname'  => '',
                    'mailformat' => 1,
                    'maildisplay' => 2,
                    'auth' => 'manual',
                    'deleted' => 0,
                    'suspended' => 0,
                ];
            }

            email_to_user(
                $user,
                $from,
                $subject,
                $body,
                '',
                $filepath,
                basename($filepath),
                true
            );
        }

        mtrace("[local_intelliboard] Schedule {$schedule->id} sent to " . count($recipients) . ' recipients.');
    }

    /**
     * Computes the next run timestamp.
     *
     * @param string $frequency daily|weekly|monthly
     * @param int $from
     * @return int
     */
    public static function compute_nextrun(string $frequency, int $from): int {
        switch ($frequency) {
            case 'daily':
                return $from + DAYSECS;
            case 'monthly':
                return strtotime('+1 month', $from) ?: ($from + (30 * DAYSECS));
            case 'weekly':
            default:
                return $from + (7 * DAYSECS);
        }
    }
}
