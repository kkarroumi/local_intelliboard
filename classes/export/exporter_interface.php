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
 * Exporter contract.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\export;

use local_intelliboard\reports\report_base;

defined('MOODLE_INTERNAL') || die();

/**
 * A minimal export contract shared by CSV, Excel and PDF.
 */
interface exporter_interface {

    /**
     * Emits the report as a downloadable file. Must call `exit` after writing.
     *
     * @param report_base $report
     * @param string $filename Filename without extension.
     */
    public function download(report_base $report, string $filename): void;

    /**
     * Writes the export to a local path (used by scheduled reports).
     *
     * @param report_base $report
     * @param string $path Absolute path (including extension).
     */
    public function save(report_base $report, string $path): void;
}
