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
 * AJAX endpoint — returns JSON data for Chart.js widgets.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/intelliboard:view', $context);

$chart = required_param('chart', PARAM_ALPHAEXT);

header('Content-Type: application/json; charset=utf-8');

switch ($chart) {
    case 'dailyactivity':
        echo json_encode(\local_intelliboard\api::daily_activity_series(30));
        break;

    case 'coursecompletion':
        echo json_encode(\local_intelliboard\api::course_completion_series(10));
        break;

    case 'topactivities':
        echo json_encode(\local_intelliboard\api::top_activities(10, 30));
        break;

    case 'heatmap':
        echo json_encode(\local_intelliboard\api::heatmap(30));
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'unknown_chart']);
}
