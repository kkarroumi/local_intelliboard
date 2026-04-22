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
 * PDF exporter (TCPDF shipped with Moodle).
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\export;

use local_intelliboard\reports\report_base;

defined('MOODLE_INTERNAL') || die();

/**
 * PDF writer using Moodle's pdf class (TCPDF).
 */
class pdf_exporter implements exporter_interface {

    public function download(report_base $report, string $filename): void {
        $pdf = $this->build($report);
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }

    public function save(report_base $report, string $path): void {
        $pdf = $this->build($report);
        $pdf->Output($path, 'F');
    }

    /**
     * Builds the PDF object populated with the report table.
     */
    private function build(report_base $report): \pdf {
        global $CFG;
        require_once($CFG->libdir . '/pdflib.php');

        $report->set_pagination(0, 0);

        $pdf = new \pdf('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('local_intelliboard');
        $pdf->SetTitle($report->title());
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, $report->title(), 0, 1, 'L');
        $pdf->Ln(2);

        $columns = $report->columns();
        $columnkeys = array_keys($columns);
        $columncount = count($columns);
        $pagewidth = $pdf->getPageWidth() - 20;
        $colwidth = $columncount > 0 ? $pagewidth / $columncount : $pagewidth;

        // Header row.
        $pdf->SetFillColor(231, 231, 231);
        $pdf->SetFont('helvetica', 'B', 9);
        foreach ($columns as $label) {
            $pdf->Cell($colwidth, 7, $label, 1, 0, 'L', true);
        }
        $pdf->Ln();

        // Body.
        $pdf->SetFont('helvetica', '', 8);
        foreach ($report->rows() as $row) {
            $formatted = $report->format_row_for_export($row);
            foreach ($columnkeys as $key) {
                $value = (string) ($formatted[$key] ?? '');
                if (strlen($value) > 40) {
                    $value = substr($value, 0, 37) . '...';
                }
                $pdf->Cell($colwidth, 6, $value, 1, 0, 'L');
            }
            $pdf->Ln();
        }

        return $pdf;
    }
}
