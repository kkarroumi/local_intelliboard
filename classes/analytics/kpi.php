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
 * KPI computation for the main dashboard (cached via MUC).
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\analytics;

use cache;
use local_intelliboard\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Computes and caches dashboard KPIs.
 */
class kpi {

    /**
     * Cache key for the global KPI bundle.
     */
    private const CACHE_KEY = 'global_kpis_v1';

    /**
     * Returns the KPI bundle for the dashboard.
     *
     * @param bool $refresh Force recomputation.
     * @return array
     */
    public static function all(bool $refresh = false): array {
        $cache = cache::make('local_intelliboard', 'kpis');
        if (!$refresh) {
            $cached = $cache->get(self::CACHE_KEY);
            if ($cached !== false) {
                return $cached;
            }
        }

        $last30 = time() - (30 * DAYSECS);
        $bundle = [
            'totalusers'       => api::active_users($last30),
            'totalcourses'     => api::total_courses(),
            'totalactivities'  => api::total_activities(),
            'completionrate'   => api::average_completion(),
            'avgtimespent'     => api::average_timespent($last30),
            'atriskusers'      => risk_analyzer::count_at_risk(),
            'loginstoday'      => api::count_events_today('login'),
            'submissionstoday' => api::count_events_today('quiz_submission')
                                + api::count_events_today('assign_submission'),
        ];

        $cache->set(self::CACHE_KEY, $bundle);
        return $bundle;
    }

    /**
     * Clears the cached KPI bundle (useful after aggregation).
     */
    public static function invalidate(): void {
        cache::make('local_intelliboard', 'kpis')->delete(self::CACHE_KEY);
    }
}
