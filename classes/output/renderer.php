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
 * Plugin renderer.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\output;

use plugin_renderer_base;
use local_intelliboard\reports\report_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin renderer: dashboard, KPI cards, report tables.
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders the dashboard page.
     *
     * @param array $kpis Output of \local_intelliboard\analytics\kpi::all()
     * @return string HTML
     */
    public function dashboard(array $kpis): string {
        $context = [
            'kpis' => [
                $this->kpi_context('totalusers', $kpis['totalusers'], 'kpi_totalusers', 'fa-users'),
                $this->kpi_context('totalcourses', $kpis['totalcourses'], 'kpi_totalcourses', 'fa-book'),
                $this->kpi_context('totalactivities', $kpis['totalactivities'], 'kpi_totalactivities', 'fa-tasks'),
                $this->kpi_context('completionrate', $kpis['completionrate'] . '%', 'kpi_completionrate', 'fa-check-circle'),
                $this->kpi_context('avgtimespent', format_time((int) $kpis['avgtimespent']), 'kpi_avgtimespent', 'fa-clock-o'),
                $this->kpi_context('atriskusers', $kpis['atriskusers'], 'kpi_atriskusers', 'fa-exclamation-triangle',
                    $kpis['atriskusers'] > 0 ? 'danger' : 'success'),
                $this->kpi_context('loginstoday', $kpis['loginstoday'], 'kpi_loginstoday', 'fa-sign-in'),
                $this->kpi_context('submissionstoday', $kpis['submissionstoday'], 'kpi_submissionstoday', 'fa-upload'),
            ],
            'ajaxurl' => (new \moodle_url('/local/intelliboard/ajax.php'))->out(false),
            'reportsurl' => (new \moodle_url('/local/intelliboard/reports.php'))->out(false),
            'sesskey'   => sesskey(),
        ];
        return $this->render_from_template('local_intelliboard/dashboard', $context);
    }

    /**
     * Builds a KPI card template context.
     */
    private function kpi_context(string $key, $value, string $label, string $icon, string $variant = 'primary'): array {
        return [
            'key'     => $key,
            'value'   => is_numeric($value) ? number_format((float) $value, 0, '.', ' ') : $value,
            'label'   => get_string($label, 'local_intelliboard'),
            'icon'    => $icon,
            'variant' => $variant,
        ];
    }

    /**
     * Renders a report as a responsive HTML table.
     *
     * @param report_base $report
     * @param \moodle_url $pageurl
     * @return string HTML
     */
    public function report_table(report_base $report, \moodle_url $pageurl): string {
        $columns = $report->columns();
        $rows = $report->rows();

        if (empty($rows)) {
            return $this->output->notification(
                get_string('nodata', 'local_intelliboard'),
                \core\output\notification::NOTIFY_INFO
            );
        }

        $table = new \html_table();
        $table->head = array_values($columns);
        $table->attributes = ['class' => 'table table-striped table-hover local-intelliboard-table'];

        $columnkeys = array_keys($columns);
        foreach ($rows as $row) {
            $cells = [];
            foreach ($columnkeys as $key) {
                $cells[] = $row->$key ?? '';
            }
            $table->data[] = $cells;
        }

        return \html_writer::table($table);
    }

    /**
     * Renders the list of report types available.
     */
    public function report_list(array $reports): string {
        $items = [];
        foreach ($reports as $type => $meta) {
            $url = new \moodle_url('/local/intelliboard/report.php', ['type' => $type]);
            $items[] = \html_writer::tag(
                'div',
                \html_writer::tag('h5', \html_writer::link($url, $meta['title']))
                . \html_writer::tag('p', $meta['description'], ['class' => 'text-muted']),
                ['class' => 'card-body']
            );
        }

        $html = \html_writer::start_tag('div', ['class' => 'row g-3 local-intelliboard-reports']);
        foreach ($items as $item) {
            $html .= \html_writer::tag(
                'div',
                \html_writer::tag('div', $item, ['class' => 'card h-100 shadow-sm']),
                ['class' => 'col-12 col-md-6 col-lg-4']
            );
        }
        $html .= \html_writer::end_tag('div');
        return $html;
    }
}
