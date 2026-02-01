// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Dashboard charts module for usage monitor.
 *
 * @module     report_usage_monitor/dashboard_charts
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ChartJS from 'core/chartjs';

/**
 * Chart color palette.
 */
const COLORS = {
    primary: '#007bff',
    success: '#28a745',
    warning: '#ffc107',
    danger: '#dc3545',
    secondary: '#6c757d',
    light: '#dee2e6',
};

/** @var {ChartJS|null} usersLineChart - Reference to users line chart instance */
let usersLineChart = null;

/** @var {Object|null} globalConfig - Stored chart configuration for deferred initialization */
let globalConfig = null;

/**
 * Initialize the doughnut chart for disk distribution.
 *
 * @param {Object} config Chart configuration
 */
const initDoughnutChart = (config) => {
    const canvas = document.getElementById('chartjs-doughnut');
    if (!canvas || !config.labels || !config.data) {
        return;
    }

    new ChartJS(canvas, {
        type: 'doughnut',
        data: {
            labels: config.labels,
            datasets: [{
                data: config.data,
                backgroundColor: [
                    COLORS.primary,
                    COLORS.success,
                    COLORS.warning,
                    COLORS.light,
                ],
                borderColor: 'transparent',
            }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const label = context.label || '';
                            const valueGb = context.parsed;
                            return `${label}: ${valueGb} GB`;
                        },
                    },
                },
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
};

/**
 * Initialize the line chart for last 10 days users.
 * This chart is in a hidden tab, so it must be initialized when the tab becomes visible.
 *
 * @param {Object} config Chart configuration
 */
const initUsersLineChart = (config) => {
    const canvas = document.getElementById('chartjs-last10days');
    if (!canvas || !config.labels || !config.data) {
        return;
    }

    // Destroy existing chart if it exists (for re-initialization).
    if (usersLineChart) {
        usersLineChart.destroy();
        usersLineChart = null;
    }

    usersLineChart = new ChartJS(canvas, {
        type: 'line',
        data: {
            labels: config.labels,
            datasets: [
                {
                    label: config.strings.usersQuantity || 'Users',
                    fill: true,
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderColor: COLORS.primary,
                    data: config.data,
                    yAxisID: 'percentage',
                },
                {
                    label: config.strings.warning70 || 'Warning (70%)',
                    fill: false,
                    borderColor: COLORS.warning,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    data: new Array(config.labels.length).fill(70),
                    yAxisID: 'percentage',
                },
                {
                    label: config.strings.critical90 || 'Critical (90%)',
                    fill: false,
                    borderColor: COLORS.danger,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    data: new Array(config.labels.length).fill(90),
                    yAxisID: 'percentage',
                },
                {
                    label: config.strings.limit100 || 'Limit (100%)',
                    fill: false,
                    borderColor: COLORS.secondary,
                    borderDash: [2, 2],
                    pointRadius: 0,
                    data: new Array(config.labels.length).fill(100),
                    yAxisID: 'percentage',
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                    },
                },
                percentage: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                    },
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                    title: {
                        display: true,
                        text: config.strings.percentOfThreshold || '% of threshold',
                    },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            if (context.dataset.label === (config.strings.usersQuantity || 'Users')) {
                                const rawValue = config.dataRaw[context.dataIndex] || 0;
                                return `${context.dataset.label}: ${rawValue} (${context.parsed.y}%)`;
                            }
                            return `${context.dataset.label}: ${context.parsed.y}%`;
                        },
                    },
                },
            },
        },
    });
};

/**
 * Initialize the line chart for disk history.
 *
 * @param {Object} config Chart configuration
 */
const initDiskHistoryChart = (config) => {
    const canvas = document.getElementById('chartjs-disk-history');
    if (!canvas || !config.labels || !config.data) {
        return;
    }

    new ChartJS(canvas, {
        type: 'line',
        data: {
            labels: config.labels,
            datasets: [
                {
                    label: config.strings.percentageUsed || 'Percentage used',
                    fill: true,
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderColor: COLORS.primary,
                    data: config.data,
                    spanGaps: true,
                    tension: 0.2,
                    yAxisID: 'percentage',
                },
                {
                    label: config.strings.warning70 || 'Warning (70%)',
                    fill: false,
                    borderColor: COLORS.warning,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    data: new Array(config.labels.length).fill(70),
                    yAxisID: 'percentage',
                },
                {
                    label: config.strings.critical90 || 'Critical (90%)',
                    fill: false,
                    borderColor: COLORS.danger,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    data: new Array(config.labels.length).fill(90),
                    yAxisID: 'percentage',
                },
                {
                    label: config.strings.limit100 || 'Limit (100%)',
                    fill: false,
                    borderColor: COLORS.secondary,
                    borderDash: [2, 2],
                    pointRadius: 0,
                    data: new Array(config.labels.length).fill(100),
                    yAxisID: 'percentage',
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                x: {
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                    },
                },
                percentage: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                    },
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                    title: {
                        display: true,
                        text: config.strings.percentOfThreshold || '% of threshold',
                    },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            if (context.dataset.label === (config.strings.percentageUsed || 'Percentage used')) {
                                return `${context.parsed.y}%`;
                            }
                            return context.dataset.label;
                        },
                    },
                },
                legend: {
                    position: 'top',
                },
            },
        },
    });
};

/**
 * Set up tab event listeners to initialize charts when tabs become visible.
 * Chart.js cannot properly size a canvas in a hidden container.
 */
const setupTabListeners = () => {
    // Listen for Bootstrap tab show events on the users chart tab.
    // Support both Bootstrap 4 (shown.bs.tab via jQuery) and Bootstrap 5 (shown.bs.tab native).
    const chartTab = document.getElementById('grafica10-tab');
    if (chartTab) {
        // Handler function to initialize chart.
        const initChartOnShow = () => {
            if (globalConfig && globalConfig.usersLine && !usersLineChart) {
                initUsersLineChart(globalConfig.usersLine);
            }
        };

        // Try jQuery event (Bootstrap 4 in Moodle).
        if (typeof jQuery !== 'undefined') {
            jQuery(chartTab).on('shown.bs.tab', initChartOnShow);
        }

        // Also add native event listener for Bootstrap 5 compatibility.
        chartTab.addEventListener('shown.bs.tab', initChartOnShow);

        // Fallback: listen for click and use setTimeout to wait for tab transition.
        chartTab.addEventListener('click', () => {
            setTimeout(() => {
                if (globalConfig && globalConfig.usersLine && !usersLineChart) {
                    // Check if the chart pane is now visible.
                    const chartPane = document.getElementById('grafica10');
                    if (chartPane && chartPane.classList.contains('active')) {
                        initUsersLineChart(globalConfig.usersLine);
                    }
                }
            }, 200);
        });
    }
};

/**
 * Initialize all dashboard charts.
 * Reads configuration from data attribute on the page.
 */
export const init = () => {
    // Wait for DOM to be ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => loadAndInitCharts());
    } else {
        loadAndInitCharts();
    }
};

/**
 * Load configuration from data attribute and initialize charts.
 */
const loadAndInitCharts = () => {
    const configElement = document.getElementById('usage-monitor-chart-config');
    if (!configElement) {
        return;
    }

    try {
        globalConfig = JSON.parse(configElement.getAttribute('data-chart-config') || '{}');
        initCharts(globalConfig);
        setupTabListeners();
    } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Failed to parse chart configuration:', e);
    }
};

/**
 * Initialize all charts with provided configuration.
 * Note: Charts in hidden tabs are deferred until the tab is shown.
 *
 * @param {Object} config Configuration object
 */
const initCharts = (config) => {
    // Doughnut chart for disk distribution (visible immediately).
    if (config.doughnut) {
        initDoughnutChart(config.doughnut);
    }

    // Line chart for disk history (visible immediately).
    if (config.diskHistory) {
        initDiskHistoryChart(config.diskHistory);
    }

    // Note: Users line chart (chartjs-last10days) is initialized when its tab is shown,
    // because Chart.js cannot properly calculate dimensions in hidden containers.
    // See setupTabListeners() for the deferred initialization.
};

export default {
    init,
};
