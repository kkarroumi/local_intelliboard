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
 * Excel exporter (XLSX via Moodle's MoodleExcelWorkbook).
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\export;

use local_intelliboard\reports\report_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Excel writer (XLSX) using Moodle's native MoodleExcelWorkbook.
 */
class excel_exporter implements exporter_interface {

    public function download(report_base $report, string $filename): void {
        global $CFG;
        require_once($CFG->libdir . '/excellib.class.php');

        $report->set_pagination(0, 0);

        $workbook = new \MoodleExcelWorkbook($filename . '.xlsx');
        $this->write($workbook, $report);
        $workbook->close();
        exit;
    }

    public function save(report_base $report, string $path): void {
        global $CFG;
        require_once($CFG->libdir . '/excellib.class.php');

        $report->set_pagination(0, 0);

        $workbook = new \MoodleExcelWorkbook($path, 'Excel2007', true);
        $this->write($workbook, $report);
        $workbook->close();
    }

    /**
     * @param \MoodleExcelWorkbook $workbook
     * @param report_base $report
     */
    private function write(\MoodleExcelWorkbook $workbook, report_base $report): void {
        $sheet = $workbook->add_worksheet(substr($report->title(), 0, 31));

        $headerformat = $workbook->add_format(['bold' => 1, 'bg_color' => '#e7e7e7']);

        $columns = array_values($report->columns());
        foreach ($columns as $i => $label) {
            $sheet->write_string(0, $i, $label, $headerformat);
        }

        $rowindex = 1;
        foreach ($report->rows() as $row) {
            $formatted = $report->format_row_for_export($row);
            $col = 0;
            foreach ($formatted as $value) {
                if (is_numeric($value)) {
                    $sheet->write_number($rowindex, $col, (float) $value);
                } else {
                    $sheet->write_string($rowindex, $col, (string) $value);
                }
                $col++;
            }
            $rowindex++;
        }
    }
}
