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
 * Report filter form.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intelliboard\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Common filter form reused across report pages.
 */
class filter_form extends \moodleform {

    protected function definition() {
        global $USER;
        $mform = $this->_form;

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUMEXT);

        // Courses.
        $courses = \local_intelliboard\api::accessible_courses((int) $USER->id);
        $courseoptions = [0 => get_string('all')];
        foreach ($courses as $course) {
            $courseoptions[$course->id] = format_string($course->fullname);
        }
        $mform->addElement('autocomplete', 'courseid',
            get_string('filter_course', 'local_intelliboard'),
            $courseoptions
        );
        $mform->setDefault('courseid', 0);

        // User (free-text id; resolved server-side — kept simple to avoid heavy autocomplete queries).
        $mform->addElement('text', 'userid',
            get_string('filter_user', 'local_intelliboard'),
            ['size' => 10, 'placeholder' => '#']
        );
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', 0);

        $mform->addElement('date_selector', 'from', get_string('filter_from', 'local_intelliboard'),
            ['optional' => true]);
        $mform->addElement('date_selector', 'to', get_string('filter_to', 'local_intelliboard'),
            ['optional' => true]);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton',
            get_string('filter_apply', 'local_intelliboard'));
        $buttonarray[] = $mform->createElement('cancel', 'resetbutton',
            get_string('filter_reset', 'local_intelliboard'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }
}
