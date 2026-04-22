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
 * Rule-based risk detection for learners.
 *
 * Predicts whether a learner is "at risk" based on three simple signals:
 *   1. Inactivity (no login or course view for N days)
 *   2. Low progress past the course mid-point
 *   3. Multiple failed quiz attempts on the same activity
 *
 * This engine is intentionally rule-based to stay deterministic and
 * explainable, but the interface (score + reasons) is suitable for
 * swapping in a trained classifier later.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Computes risk levels for enrolled learners.
 */
class risk_analyzer {

    public const RISK_LOW = 0;
    public const RISK_MEDIUM = 1;
    public const RISK_HIGH = 2;

    /**
     * Count of distinct learners currently flagged medium or high risk.
     *
     * @return int
     */
    public static function count_at_risk(): int {
        $rows = self::evaluate_all();
        $n = 0;
        foreach ($rows as $r) {
            if ($r->level >= self::RISK_MEDIUM) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Evaluates every enrolled learner across active courses.
     *
     * @return array<int, \stdClass> Each row has userid, courseid, level, score, reasons[].
     */
    public static function evaluate_all(): array {
        global $DB;

        $inactivedays = (int) (get_config('local_intelliboard', 'riskinactivedays') ?: 14);
        $compthreshold = (int) (get_config('local_intelliboard', 'riskcompletionthreshold') ?: 25);
        $inactivity = time() - ($inactivedays * DAYSECS);

        // Candidate set: everyone enrolled on a course where completion is tracked.
        $sql = "SELECT ue.userid, e.courseid, u.firstname, u.lastname, u.email,
                       c.fullname AS coursename,
                       MAX(ll.timecreated) AS lastseen,
                       cc.timestarted, cc.timecompleted, cc.timeenrolled
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {user} u ON u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0
                  JOIN {course} c ON c.id = e.courseid AND c.visible = 1
             LEFT JOIN {local_intelliboard_logs} ll ON ll.userid = ue.userid AND ll.courseid = e.courseid
             LEFT JOIN {course_completions} cc ON cc.userid = ue.userid AND cc.course = e.courseid
                 WHERE ue.status = 0
              GROUP BY ue.userid, e.courseid, u.firstname, u.lastname, u.email,
                       c.fullname, cc.timestarted, cc.timecompleted, cc.timeenrolled";

        $records = $DB->get_records_sql($sql);
        $out = [];

        foreach ($records as $r) {
            if (!empty($r->timecompleted)) {
                continue; // Already completed — not at risk.
            }

            $score = 0;
            $reasons = [];

            // Signal 1: inactivity.
            $lastseen = (int) ($r->lastseen ?? 0);
            if ($lastseen > 0 && $lastseen < $inactivity) {
                $score += 2;
                $reasons[] = get_string(
                    'risk_reason_inactive',
                    'local_intelliboard',
                    (int) floor((time() - $lastseen) / DAYSECS)
                );
            } else if ($lastseen === 0) {
                $score += 2;
                $reasons[] = get_string(
                    'risk_reason_inactive',
                    'local_intelliboard',
                    $inactivedays
                );
            }

            // Signal 2: low progress past the mid-point.
            $progress = self::course_progress((int) $r->userid, (int) $r->courseid);
            if ($progress !== null && $progress < $compthreshold) {
                $score += 2;
                $reasons[] = get_string(
                    'risk_reason_lowprogress',
                    'local_intelliboard',
                    $progress
                );
            }

            // Signal 3: failed quiz attempts.
            if (self::has_failed_attempts((int) $r->userid, (int) $r->courseid)) {
                $score += 1;
                $reasons[] = get_string('risk_reason_failedattempts', 'local_intelliboard');
            }

            if ($score === 0) {
                continue;
            }

            $level = self::RISK_LOW;
            if ($score >= 3) {
                $level = self::RISK_HIGH;
            } else if ($score >= 2) {
                $level = self::RISK_MEDIUM;
            }

            $out[] = (object) [
                'userid'   => (int) $r->userid,
                'fullname' => trim($r->firstname . ' ' . $r->lastname),
                'email'    => $r->email,
                'courseid' => (int) $r->courseid,
                'coursename' => format_string($r->coursename),
                'progress' => $progress,
                'lastseen' => $lastseen,
                'score'    => $score,
                'level'    => $level,
                'reasons'  => $reasons,
            ];
        }

        return $out;
    }

    /**
     * Returns course progress percentage (0..100) using Moodle's completion API.
     *
     * @param int $userid
     * @param int $courseid
     * @return int|null Null if completion is disabled on the course.
     */
    private static function course_progress(int $userid, int $courseid): ?int {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $course = $DB->get_record('course', ['id' => $courseid], 'id, enablecompletion');
        if (!$course || empty($course->enablecompletion)) {
            return null;
        }

        $info = new \completion_info($course);
        if (!$info->is_enabled()) {
            return null;
        }

        $percent = \core_completion\progress::get_course_progress_percentage($course, $userid);
        return $percent === null ? null : (int) round($percent);
    }

    /**
     * Whether the user has at least two failed quiz attempts in this course.
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    private static function has_failed_attempts(int $userid, int $courseid): bool {
        global $DB;

        $sql = "SELECT COUNT(qa.id)
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                 WHERE qa.userid = :userid
                   AND q.course = :courseid
                   AND qa.state = :state
                   AND qa.sumgrades IS NOT NULL
                   AND q.grade > 0
                   AND (qa.sumgrades / q.grade) < 0.5";
        $count = (int) $DB->count_records_sql($sql, [
            'userid'   => $userid,
            'courseid' => $courseid,
            'state'    => 'finished',
        ]);
        return $count >= 2;
    }
}
