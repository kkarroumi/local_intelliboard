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
 * Dashboard: loads Chart.js and renders KPI charts on the main dashboard.
 *
 * Wire-up:
 *   <div data-region="local-intelliboard-dashboard" data-ajaxurl="..." data-sesskey="...">
 *   <canvas data-chart="dailyactivity"></canvas>
 *
 * @module     local_intelliboard/dashboard
 * @copyright  2026 local_intelliboard contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';

const CHART_JS_URL = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';

let chartJsLoaded = null;

/**
 * Lazy-load Chart.js via CDN. Resolves to the global Chart constructor.
 * @returns {Promise<Function>}
 */
const loadChartJs = () => {
    if (window.Chart) {
        return Promise.resolve(window.Chart);
    }
    if (chartJsLoaded) {
        return chartJsLoaded;
    }
    chartJsLoaded = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = CHART_JS_URL;
        script.async = true;
        script.onload = () => resolve(window.Chart);
        script.onerror = () => reject(new Error('Unable to load Chart.js'));
        document.head.appendChild(script);
    });
    return chartJsLoaded;
};

/**
 * Fetch JSON data from the plugin AJAX endpoint.
 * @param {string} ajaxurl
 * @param {string} sesskey
 * @param {string} chart
 * @returns {Promise<Object>}
 */
const fetchData = async(ajaxurl, sesskey, chart) => {
    const url = `${ajaxurl}?chart=${encodeURIComponent(chart)}&sesskey=${encodeURIComponent(sesskey)}`;
    const response = await fetch(url, {credentials: 'same-origin'});
    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }
    return response.json();
};

/**
 * Render the daily activity line chart.
 */
const renderDailyActivity = (Chart, canvas, data) => {
    return new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [
                {
                    label: 'Visits',
                    data: data.visits || [],
                    borderColor: '#0f6cbf',
                    backgroundColor: 'rgba(15,108,191,0.1)',
                    fill: true,
                    tension: 0.3,
                },
                {
                    label: 'Submissions',
                    data: data.submissions || [],
                    borderColor: '#a94442',
                    backgroundColor: 'rgba(169,68,66,0.1)',
                    fill: false,
                    tension: 0.3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {position: 'bottom'}},
        },
    });
};

/**
 * Render course completion doughnut.
 */
const renderCourseCompletion = (Chart, canvas, data) => {
    return new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: data.labels || [],
            datasets: [{
                data: data.values || [],
                backgroundColor: [
                    '#0f6cbf', '#5cb85c', '#f0ad4e', '#d9534f',
                    '#5bc0de', '#8e44ad', '#16a085', '#2c3e50',
                    '#e67e22', '#95a5a6',
                ],
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {position: 'bottom'}},
        },
    });
};

/**
 * Render top activities bar chart.
 */
const renderTopActivities = (Chart, canvas, data) => {
    return new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Views',
                data: data.values || [],
                backgroundColor: '#0f6cbf',
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {display: false}},
        },
    });
};

/**
 * Render the heatmap as a plain HTML grid (no Chart.js plugin required).
 */
const renderHeatmap = (container, grid) => {
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let max = 1;
    for (let d = 0; d < 7; d++) {
        for (let h = 0; h < 24; h++) {
            if (grid[d] && grid[d][h] > max) {
                max = grid[d][h];
            }
        }
    }

    const table = document.createElement('table');
    table.className = 'local-intelliboard-heatmap-table';

    const head = document.createElement('thead');
    const headrow = document.createElement('tr');
    headrow.appendChild(document.createElement('th'));
    for (let h = 0; h < 24; h++) {
        const th = document.createElement('th');
        th.textContent = h.toString();
        headrow.appendChild(th);
    }
    head.appendChild(headrow);
    table.appendChild(head);

    const body = document.createElement('tbody');
    for (let d = 0; d < 7; d++) {
        const tr = document.createElement('tr');
        const label = document.createElement('th');
        label.textContent = weekdays[d];
        tr.appendChild(label);
        for (let h = 0; h < 24; h++) {
            const val = (grid[d] && grid[d][h]) || 0;
            const intensity = max > 0 ? val / max : 0;
            const td = document.createElement('td');
            td.title = `${weekdays[d]} ${h}:00 — ${val} views`;
            td.style.backgroundColor = `rgba(15,108,191,${intensity.toFixed(2)})`;
            tr.appendChild(td);
        }
        body.appendChild(tr);
    }
    table.appendChild(body);

    container.innerHTML = '';
    container.appendChild(table);
};

/**
 * Entry point — wires up every canvas in the dashboard region.
 */
export const init = async() => {
    const root = document.querySelector('[data-region="local-intelliboard-dashboard"]');
    if (!root) {
        return;
    }

    const ajaxurl = root.dataset.ajaxurl;
    const sesskey = root.dataset.sesskey;

    try {
        const Chart = await loadChartJs();

        const canvases = root.querySelectorAll('canvas[data-chart]');
        for (const canvas of canvases) {
            const chart = canvas.dataset.chart;
            try {
                const data = await fetchData(ajaxurl, sesskey, chart);
                if (chart === 'dailyactivity') {
                    renderDailyActivity(Chart, canvas, data);
                } else if (chart === 'coursecompletion') {
                    renderCourseCompletion(Chart, canvas, data);
                } else if (chart === 'topactivities') {
                    renderTopActivities(Chart, canvas, data);
                }
            } catch (err) {
                window.console.error(`Chart ${chart} failed`, err);
            }
        }

        const heatmap = root.querySelector('[data-chart="heatmap"]');
        if (heatmap) {
            try {
                const data = await fetchData(ajaxurl, sesskey, 'heatmap');
                renderHeatmap(heatmap, data);
            } catch (err) {
                window.console.error('Heatmap failed', err);
            }
        }
    } catch (err) {
        Notification.exception(err);
    }
};
