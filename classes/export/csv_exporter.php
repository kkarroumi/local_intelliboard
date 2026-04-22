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
 * CSV exporter.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\export;

use local_intelliboard\reports\report_base;

defined('MOODLE_INTERNAL') || die();

/**
 * CSV writer using Moodle's csv_export_writer.
 */
class csv_exporter implements exporter_interface {

    public function download(report_base $report, string $filename): void {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $report->set_pagination(0, 0);

        $csv = new \csv_export_writer();
        $csv->set_filename($filename);
        $csv->add_data(array_values($report->columns()));

        foreach ($report->rows() as $row) {
            $csv->add_data($report->format_row_for_export($row));
        }

        $csv->download_file();
        exit;
    }

    public function save(report_base $report, string $path): void {
        $report->set_pagination(0, 0);

        $fh = fopen($path, 'w');
        if ($fh === false) {
            throw new \moodle_exception('cannotwritefile', 'error', '', $path);
        }

        // BOM for Excel compatibility.
        fwrite($fh, "\xEF\xBB\xBF");

        fputcsv($fh, array_values($report->columns()));
        foreach ($report->rows() as $row) {
            fputcsv($fh, $report->format_row_for_export($row));
        }
        fclose($fh);
    }
}
