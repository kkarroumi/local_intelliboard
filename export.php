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
 * Export dispatcher.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/intelliboard:viewreports', $context);
require_capability('local/intelliboard:export', $context);

$type   = required_param('type', PARAM_ALPHANUMEXT);
$format = required_param('format', PARAM_ALPHA);

$filters = [
    'courseid'  => optional_param('courseid', 0, PARAM_INT),
    'userid'    => optional_param('userid', 0, PARAM_INT),
    'from'      => optional_param('from', 0, PARAM_INT),
    'to'        => optional_param('to', 0, PARAM_INT),
];

$report = \local_intelliboard\reports\report_factory::make($type, $filters);

$filename = 'intelliboard_' . $type . '_' . date('Ymd_His');

switch ($format) {
    case 'xlsx':
    case 'excel':
        (new \local_intelliboard\export\excel_exporter())->download($report, $filename);
        break;
    case 'pdf':
        (new \local_intelliboard\export\pdf_exporter())->download($report, $filename);
        break;
    case 'csv':
    default:
        (new \local_intelliboard\export\csv_exporter())->download($report, $filename);
        break;
}
