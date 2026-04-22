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
 * Admin settings.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $category = new admin_category(
        'local_intelliboard',
        get_string('pluginname', 'local_intelliboard')
    );
    $ADMIN->add('localplugins', $category);

    $settings = new admin_settingpage(
        'local_intelliboard_settings',
        get_string('settings', 'local_intelliboard')
    );

    $settings->add(new admin_setting_heading(
        'local_intelliboard/general',
        get_string('generalsettings', 'local_intelliboard'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_intelliboard/enabletracking',
        get_string('enabletracking', 'local_intelliboard'),
        get_string('enabletracking_desc', 'local_intelliboard'),
        1
    ));

    $settings->add(new admin_setting_configduration(
        'local_intelliboard/inactivity',
        get_string('inactivity', 'local_intelliboard'),
        get_string('inactivity_desc', 'local_intelliboard'),
        60,
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_intelliboard/retentiondays',
        get_string('retentiondays', 'local_intelliboard'),
        get_string('retentiondays_desc', 'local_intelliboard'),
        '365',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'local_intelliboard/risk',
        get_string('risksettings', 'local_intelliboard'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_intelliboard/riskinactivedays',
        get_string('riskinactivedays', 'local_intelliboard'),
        get_string('riskinactivedays_desc', 'local_intelliboard'),
        '14',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_intelliboard/riskcompletionthreshold',
        get_string('riskcompletionthreshold', 'local_intelliboard'),
        get_string('riskcompletionthreshold_desc', 'local_intelliboard'),
        '25',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'local_intelliboard/privacy',
        get_string('privacysettings', 'local_intelliboard'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_intelliboard/anonymize',
        get_string('anonymize', 'local_intelliboard'),
        get_string('anonymize_desc', 'local_intelliboard'),
        0
    ));

    $category->add('local_intelliboard', $settings);

    $category->add('local_intelliboard', new admin_externalpage(
        'local_intelliboard_dashboard',
        get_string('dashboard', 'local_intelliboard'),
        new moodle_url('/local/intelliboard/index.php'),
        'local/intelliboard:view'
    ));

    $category->add('local_intelliboard', new admin_externalpage(
        'local_intelliboard_reports',
        get_string('reports', 'local_intelliboard'),
        new moodle_url('/local/intelliboard/reports.php'),
        'local/intelliboard:viewreports'
    ));
}

// Prevent Moodle from auto-adding the settings page (we manage it manually).
$settings = null;
