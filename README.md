# local_intelliboard

Open-source Moodle **analytics & reporting** plugin (Moodle 4.3+), reproducing
the main functional capabilities of IntelliBoard. Written from scratch —
no upstream code is copied.

## Features

- **Dashboard** with 8 live KPIs (active users, courses, activities, completion,
  time spent, learners at risk, daily logins & submissions).
- **Charts** (Chart.js): 30-day activity, course completion doughnut, top
  activities bar chart, weekday × hour heatmap.
- **Reports**: Learners, Courses, Activities, Completion. Each with date-range,
  course and learner filters, pagination, and export buttons.
- **Exports**: CSV, Excel (XLSX via `MoodleExcelWorkbook`) and PDF (via Moodle's
  bundled TCPDF).
- **Scheduled reports** emailed on a daily / weekly / monthly cadence.
- **Learner tracking** via Events API observer (logins, course & module views,
  completions, grades, quiz & assign submissions).
- **Risk detection** (rule-based): inactivity + low progress + failed attempts.
- **RGPD / GDPR**: anonymisation toggle replaces identifying fields with
  pseudonyms; configurable raw-log retention.
- **Performance**: nightly aggregation into a daily summary table, MUC cache
  (TTL 15 min) on KPIs, indexed hot columns.
- **Security**: capabilities for view / viewreports / managereports / export /
  viewlearnertracking, `require_login`, `require_capability`, `require_sesskey`,
  parameterised SQL everywhere.

## Installation

1. Copy this folder into `<moodledir>/local/intelliboard/`.
   ```bash
   cd <moodledir>/local
   git clone <repo-url> intelliboard
   ```
2. Visit **Site administration → Notifications** (or run
   `php admin/cli/upgrade.php`) as a site admin to install the schema.
3. Grant `local/intelliboard:view` and related capabilities to the roles that
   should access the dashboard.
4. Configure the plugin at
   **Site administration → Plugins → Local plugins → IntelliBoard Analytics →
   General settings**.

## Configuration

| Setting | Default | Description |
|---|---|---|
| `enabletracking` | On | Capture Moodle events into the analytics log. |
| `inactivity` | 60 s | Inactivity cap used when estimating time spent. |
| `retentiondays` | 365 | Raw logs older than this are purged weekly. |
| `anonymize` | Off | Replaces identifying fields in exports with pseudonyms. |
| `riskinactivedays` | 14 | Days of inactivity after which a learner is flagged. |
| `riskcompletionthreshold` | 25 | % completion below which a learner is flagged. |

## Scheduled tasks

| Task | Default cron | Purpose |
|---|---|---|
| `aggregate_daily` | 01:30 every day | Roll raw events into the daily summary. |
| `cleanup_old_data` | 03:00 every Sunday | Prune raw logs older than retention. |
| `send_scheduled_reports` | Every 15 min | Email any due scheduled reports. |

## Capabilities

| Capability | Archetype defaults |
|---|---|
| `local/intelliboard:view` | manager, coursecreator, editingteacher, teacher |
| `local/intelliboard:viewreports` | manager, editingteacher, teacher |
| `local/intelliboard:managereports` | manager |
| `local/intelliboard:viewlearnertracking` | manager, editingteacher, teacher |
| `local/intelliboard:export` | manager, editingteacher |

## Architecture

```
local/intelliboard/
├── version.php, lib.php, settings.php        # Plugin core
├── index.php, reports.php, report.php        # Pages
├── export.php, ajax.php                      # Endpoints
├── db/                                        # XMLDB, access, events, tasks, caches
├── classes/
│   ├── observer.php                          # Events API consumer
│   ├── api.php                               # Reusable SQL helpers
│   ├── analytics/{kpi,risk_analyzer}.php     # KPI + rule-based risk engine
│   ├── reports/                              # report_base + factory + 4 reports
│   ├── export/                               # CSV / XLSX / PDF exporters
│   ├── task/                                 # 3 scheduled tasks
│   ├── output/renderer.php                   # Plugin renderer
│   └── form/filter_form.php                  # Moodleform
├── templates/dashboard.mustache              # Mustache dashboard
├── amd/src/dashboard.js                      # Chart.js loader (ES6 module)
└── lang/en/local_intelliboard.php            # Strings
```

## Licence

GNU GPL v3 or later — same as Moodle itself.
