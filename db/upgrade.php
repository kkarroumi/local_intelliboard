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
 * Upgrade steps.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script.
 *
 * @param int $oldversion Previous plugin version.
 * @return bool
 */
function xmldb_local_intelliboard_upgrade(int $oldversion): bool {
    if ($oldversion < 2026042201) {
        // Invalidate cached KPI bundle — earlier versions could leave a
        // half-baked cache behind after exceptions during computation.
        try {
            \cache_helper::purge_by_definition('local_intelliboard', 'kpis');
        } catch (\Throwable $e) {
            // Cache may not exist yet on fresh installs — safe to ignore.
        }

        upgrade_plugin_savepoint(true, 2026042201, 'local', 'intelliboard');
    }

    return true;
}
