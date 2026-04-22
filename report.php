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
 * Single report view with filters and export links.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/intelliboard:viewreports', $context);

$type = required_param('type', PARAM_ALPHANUMEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/intelliboard/report.php', ['type' => $type]));
$PAGE->set_pagelayout('report');
$PAGE->requires->css('/local/intelliboard/styles.css');

// Read persisted filters from URL.
$filters = [
    'courseid'  => optional_param('courseid', 0, PARAM_INT),
    'userid'    => optional_param('userid', 0, PARAM_INT),
    'from'      => optional_param('from', 0, PARAM_INT),
    'to'        => optional_param('to', 0, PARAM_INT),
];

$report = \local_intelliboard\reports\report_factory::make($type, $filters);
$report->set_pagination($page * $perpage, $perpage);

$PAGE->set_title($report->title());
$PAGE->set_heading(get_string('pluginname', 'local_intelliboard'));

$form = new \local_intelliboard\form\filter_form(
    new moodle_url('/local/intelliboard/report.php', ['type' => $type])
);
$form->set_data(['type' => $type] + $filters);
if ($data = $form->get_data()) {
    $redirect = new moodle_url('/local/intelliboard/report.php', [
        'type'     => $type,
        'courseid' => (int) ($data->courseid ?? 0),
        'userid'   => (int) ($data->userid ?? 0),
        'from'     => (int) ($data->from ?? 0),
        'to'       => (int) ($data->to ?? 0),
    ]);
    redirect($redirect);
}

$output = $PAGE->get_renderer('local_intelliboard');

echo $output->header();
echo $output->heading($report->title());

echo html_writer::start_div('local-intelliboard-filter card card-body mb-3');
$form->display();
echo html_writer::end_div();

// Export buttons.
if (has_capability('local/intelliboard:export', $context)) {
    $exportbase = new moodle_url('/local/intelliboard/export.php', [
        'type'     => $type,
        'courseid' => $filters['courseid'],
        'userid'   => $filters['userid'],
        'from'     => $filters['from'],
        'to'       => $filters['to'],
        'sesskey'  => sesskey(),
    ]);

    echo html_writer::start_div('mb-3 local-intelliboard-exports');
    echo html_writer::span(get_string('export_title', 'local_intelliboard') . ':', 'me-2');
    foreach (['csv', 'xlsx', 'pdf'] as $fmt) {
        $url = new moodle_url($exportbase, ['format' => $fmt]);
        echo html_writer::link(
            $url,
            strtoupper($fmt),
            ['class' => 'btn btn-sm btn-outline-secondary me-1']
        );
    }
    echo html_writer::end_div();
}

echo $output->report_table($report, $PAGE->url);

// Pagination.
$total = $report->count();
$baseurl = new moodle_url('/local/intelliboard/report.php', [
    'type'     => $type,
    'courseid' => $filters['courseid'],
    'userid'   => $filters['userid'],
    'from'     => $filters['from'],
    'to'       => $filters['to'],
]);
echo $output->paging_bar($total, $page, $perpage, $baseurl);

echo $output->footer();
