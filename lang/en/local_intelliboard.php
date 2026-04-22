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
 * English language strings.
 *
 * @package    local_intelliboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'IntelliBoard Analytics';
$string['settings'] = 'General settings';
$string['generalsettings'] = 'General';
$string['privacysettings'] = 'Privacy';
$string['risksettings'] = 'Risk detection';

// Settings.
$string['enabletracking'] = 'Enable activity tracking';
$string['enabletracking_desc'] = 'When enabled, the plugin captures Moodle events to feed analytics.';
$string['inactivity'] = 'Inactivity threshold';
$string['inactivity_desc'] = 'Duration of inactivity after which a session is considered ended.';
$string['retentiondays'] = 'Raw log retention (days)';
$string['retentiondays_desc'] = 'Number of days raw event logs are kept before being purged by the cleanup task.';
$string['anonymize'] = 'Anonymise exports';
$string['anonymize_desc'] = 'Replace user names with pseudonyms in exports and shared reports (GDPR).';
$string['riskinactivedays'] = 'Inactivity days (at risk)';
$string['riskinactivedays_desc'] = 'Learners inactive for this many days are flagged as at risk.';
$string['riskcompletionthreshold'] = 'Completion threshold (%)';
$string['riskcompletionthreshold_desc'] = 'Learners below this completion rate past the mid-point of the course are flagged at risk.';

// Capabilities.
$string['intelliboard:view'] = 'View the analytics dashboard';
$string['intelliboard:viewreports'] = 'View analytics reports';
$string['intelliboard:managereports'] = 'Manage analytics reports (create, edit, schedule)';
$string['intelliboard:viewlearnertracking'] = 'View detailed learner tracking data';
$string['intelliboard:export'] = 'Export analytics data';

// Navigation.
$string['dashboard'] = 'Dashboard';
$string['reports'] = 'Reports';
$string['learners'] = 'Learners';
$string['courses'] = 'Courses';
$string['activities'] = 'Activities';
$string['completion'] = 'Completion';
$string['back'] = 'Back';

// Dashboard KPIs.
$string['kpi_totalusers'] = 'Active users';
$string['kpi_totalcourses'] = 'Courses';
$string['kpi_totalactivities'] = 'Activities';
$string['kpi_completionrate'] = 'Average completion';
$string['kpi_avgtimespent'] = 'Avg. time spent';
$string['kpi_atriskusers'] = 'Learners at risk';
$string['kpi_loginstoday'] = 'Logins today';
$string['kpi_submissionstoday'] = 'Submissions today';

// Charts.
$string['chart_dailyactivity'] = 'Daily activity (last 30 days)';
$string['chart_coursecompletion'] = 'Completion by course';
$string['chart_topactivities'] = 'Top activities by views';
$string['chart_heatmap'] = 'Activity heatmap (hour × weekday)';

// Reports.
$string['report_users'] = 'Learners';
$string['report_users_desc'] = 'Overview of each learner: last access, time spent, completion.';
$string['report_courses'] = 'Courses';
$string['report_courses_desc'] = 'Course-level stats: enrolled, active, completed, average grade.';
$string['report_activities'] = 'Activities';
$string['report_activities_desc'] = 'Activity engagement: views, submissions, completions.';
$string['report_completion'] = 'Completion';
$string['report_completion_desc'] = 'Per-user completion of each course and activity.';

// Filters.
$string['filter_course'] = 'Course';
$string['filter_user'] = 'Learner';
$string['filter_from'] = 'From';
$string['filter_to'] = 'To';
$string['filter_category'] = 'Category';
$string['filter_role'] = 'Role';
$string['filter_apply'] = 'Apply';
$string['filter_reset'] = 'Reset';

// Table columns.
$string['col_user'] = 'Learner';
$string['col_email'] = 'Email';
$string['col_course'] = 'Course';
$string['col_activity'] = 'Activity';
$string['col_lastaccess'] = 'Last access';
$string['col_timespent'] = 'Time spent';
$string['col_visits'] = 'Visits';
$string['col_progress'] = 'Progress';
$string['col_grade'] = 'Grade';
$string['col_status'] = 'Status';
$string['col_enrolled'] = 'Enrolled';
$string['col_completed'] = 'Completed';
$string['col_active'] = 'Active';
$string['col_views'] = 'Views';
$string['col_submissions'] = 'Submissions';
$string['col_risk'] = 'Risk';

// Status & risk.
$string['status_completed'] = 'Completed';
$string['status_inprogress'] = 'In progress';
$string['status_notstarted'] = 'Not started';
$string['risk_low'] = 'Low';
$string['risk_medium'] = 'Medium';
$string['risk_high'] = 'High';
$string['risk_reason_inactive'] = 'Inactive for {$a} days';
$string['risk_reason_lowprogress'] = 'Low progress ({$a}%)';
$string['risk_reason_failedattempts'] = 'Multiple failed attempts';

// Export.
$string['export_csv'] = 'CSV';
$string['export_excel'] = 'Excel';
$string['export_pdf'] = 'PDF';
$string['export_title'] = 'Export';
$string['nothingtoexport'] = 'No data to export.';

// Scheduled reports.
$string['schedule_title'] = 'Scheduled reports';
$string['schedule_add'] = 'Schedule a report';
$string['schedule_name'] = 'Name';
$string['schedule_frequency'] = 'Frequency';
$string['schedule_recipients'] = 'Recipients (comma-separated emails)';
$string['schedule_format'] = 'Format';
$string['schedule_next'] = 'Next run';
$string['freq_daily'] = 'Daily';
$string['freq_weekly'] = 'Weekly';
$string['freq_monthly'] = 'Monthly';

// Tasks.
$string['task_aggregate'] = 'Aggregate daily analytics';
$string['task_cleanup'] = 'Clean up old analytics data';
$string['task_sendreports'] = 'Send scheduled reports';

// Events / caches.
$string['cachedef_kpis'] = 'Cached dashboard KPIs';
$string['cachedef_reports'] = 'Cached report results';

// Misc.
$string['nodata'] = 'No data available.';
$string['loading'] = 'Loading…';
$string['viewreport'] = 'View report';
$string['scheduledreport'] = 'Scheduled report: {$a}';
$string['privacy:metadata:local_intelliboard_logs'] = 'Stores events captured from Moodle for analytics purposes.';
$string['privacy:metadata:local_intelliboard_logs:userid'] = 'The user whose event was recorded.';
$string['privacy:metadata:local_intelliboard_logs:courseid'] = 'The course the event relates to.';
$string['privacy:metadata:local_intelliboard_logs:action'] = 'The event name.';
$string['privacy:metadata:local_intelliboard_logs:timecreated'] = 'The time the event occurred.';
$string['privacy:metadata:local_intelliboard_tracking'] = 'Stores time spent on pages.';
$string['privacy:metadata:local_intelliboard_tracking:userid'] = 'The tracked user.';
$string['privacy:metadata:local_intelliboard_tracking:timespent'] = 'Number of seconds on the page.';
$string['privacy:metadata:local_intelliboard_daily'] = 'Per-user, per-course daily aggregates.';
$string['privacy:metadata:local_intelliboard_daily:userid'] = 'The aggregated user.';
$string['privacy:metadata:local_intelliboard_daily:timespent'] = 'Aggregated time spent for the day.';
