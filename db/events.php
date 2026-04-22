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
 * Event observers.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Authentication / sessions.
    [
        'eventname' => '\core\event\user_loggedin',
        'callback'  => '\local_intelliboard\observer::on_login',
        'internal'  => false,
        'priority'  => 200,
    ],
    [
        'eventname' => '\core\event\user_loggedout',
        'callback'  => '\local_intelliboard\observer::on_logout',
        'internal'  => false,
        'priority'  => 200,
    ],
    // Course access.
    [
        'eventname' => '\core\event\course_viewed',
        'callback'  => '\local_intelliboard\observer::on_course_viewed',
        'internal'  => false,
        'priority'  => 200,
    ],
    // Activity access.
    [
        'eventname' => '\core\event\course_module_viewed',
        'callback'  => '\local_intelliboard\observer::on_module_viewed',
        'internal'  => false,
        'priority'  => 200,
    ],
    // Completion.
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback'  => '\local_intelliboard\observer::on_module_completion',
        'internal'  => false,
        'priority'  => 200,
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback'  => '\local_intelliboard\observer::on_course_completed',
        'internal'  => false,
        'priority'  => 200,
    ],
    // Grades.
    [
        'eventname' => '\core\event\user_graded',
        'callback'  => '\local_intelliboard\observer::on_user_graded',
        'internal'  => false,
        'priority'  => 200,
    ],
    // Quiz.
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback'  => '\local_intelliboard\observer::on_quiz_submitted',
        'internal'  => false,
        'priority'  => 200,
    ],
    // Assign.
    [
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback'  => '\local_intelliboard\observer::on_assign_submitted',
        'internal'  => false,
        'priority'  => 200,
    ],
];
