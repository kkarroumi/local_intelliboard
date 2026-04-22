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
 * Reports gallery: lists every available analytics report.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/intelliboard:viewreports', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/intelliboard/reports.php');
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('reports', 'local_intelliboard'));
$PAGE->set_heading(get_string('pluginname', 'local_intelliboard'));
$PAGE->requires->css('/local/intelliboard/styles.css');

$output = $PAGE->get_renderer('local_intelliboard');

echo $output->header();
echo $output->heading(get_string('reports', 'local_intelliboard'));
echo $output->report_list(\local_intelliboard\reports\report_factory::available());
echo $output->footer();
