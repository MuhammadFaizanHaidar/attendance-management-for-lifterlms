/**
 * Attendance Management For LifterLMS Reporting Dashboard JavaScript
 *
 * @author   Muhammad Faizan Haidar
 * @package  Attendance Management For LifterLMS Reporting
 * @version  1.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    let attendanceChart = null;
    let currentCourseId = 0;
    let currentPeriod = 'monthly';
    let currentDateFrom = '';
    let currentDateTo = '';

    // Initialize dashboard
    function initDashboard() {
        // Set default values
        currentDateFrom = $('#date-from').val();
        currentDateTo = $('#date-to').val();
        
        // Load initial data
        loadAttendanceChart();
        loadCourseStats();
        
        // Bind events
        bindEvents();
    }

    // Bind event handlers
    function bindEvents() {
        $('#update-chart').on('click', function() {
            updateFilters();
            loadAttendanceChart();
            loadCourseStats();
        });

        $('#export-csv').on('click', function() {
            exportData('csv');
        });

        $('#export-pdf').on('click', function() {
            exportData('pdf');
        });

        // Auto-update when filters change
        $('#course-filter, #period-filter, #date-from, #date-to').on('change', function() {
            updateFilters();
        });
    }

    // Update filter values
    function updateFilters() {
        currentCourseId = parseInt($('#course-filter').val());
        currentPeriod = $('#period-filter').val();
        currentDateFrom = $('#date-from').val();
        currentDateTo = $('#date-to').val();
    }

    // Load attendance chart
    function loadAttendanceChart() {
        const chartContainer = $('#attendance-chart');
        if (chartContainer.length === 0) {
            return;
        }

        // Show loading state
        showLoadingState('#attendance-chart');

        $.ajax({
            url: llmsat_reporting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'llmsat_get_attendance_data',
                nonce: llmsat_reporting_ajax.nonce,
                course_id: currentCourseId,
                date_from: currentDateFrom,
                date_to: currentDateTo,
                period: currentPeriod
            },
            success: function(response) {
                if (response.success) {
                    renderChart(response.data);
                } else {
                    showError('#attendance-chart', 'Failed to load chart data');
                }
            },
            error: function() {
                showError('#attendance-chart', 'Error loading chart data');
            }
        });
    }

    // Render Chart.js chart
    function renderChart(data) {
        const ctx = document.getElementById('attendance-chart').getContext('2d');
        
        // Destroy existing chart
        if (attendanceChart) {
            attendanceChart.destroy();
        }

        // Chart configuration
        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 0,
                plugins: {
                    title: {
                        display: true,
                        text: getChartTitle(),
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: getXAxisLabel(),
                            font: {
                                size: 12
                            }
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Attendance Rate (%)',
                            font: {
                                size: 12
                            }
                        },
                        min: 0,
                        max: 100,
                        ticks: {
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        };

        // Create new chart
        attendanceChart = new Chart(ctx, config);
    }

    // Get chart title based on current filters
    function getChartTitle() {
        let title = 'Attendance Rate';
        
        if (currentCourseId > 0) {
            const courseName = $('#course-filter option:selected').text();
            title += ' - ' + courseName;
        } else {
            title += ' - All Courses';
        }
        
        return title;
    }

    // Get X-axis label based on period
    function getXAxisLabel() {
        switch (currentPeriod) {
            case 'daily':
                return 'Days';
            case 'weekly':
                return 'Weeks';
            case 'monthly':
            default:
                return 'Months';
        }
    }

    // Load course statistics
    function loadCourseStats() {
        if (currentCourseId <= 0) {
            $('#course-stats').html('<p>Select a specific course to view statistics.</p>');
            $('#top-performers').html('<p>Select a specific course to view top performers.</p>');
            return;
        }

        $.ajax({
            url: llmsat_reporting_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'llmsat_get_course_stats',
                nonce: llmsat_reporting_ajax.nonce,
                course_id: currentCourseId
            },
            success: function(response) {
                if (response.success) {
                    renderCourseStats(response.data);
                    renderTopPerformers(response.data.top_performers);
                } else {
                    $('#course-stats').html('<p>Failed to load course statistics.</p>');
                }
            },
            error: function() {
                $('#course-stats').html('<p>Error loading course statistics.</p>');
            }
        });
    }

    // Render course statistics
    function renderCourseStats(stats) {
        const html = `
            <div class="llmsat-stats-grid">
                <div class="llmsat-stat-item">
                    <div class="llmsat-stat-number">${stats.total_students}</div>
                    <div class="llmsat-stat-label">Total Students</div>
                </div>
                <div class="llmsat-stat-item">
                    <div class="llmsat-stat-number">${stats.present_today}</div>
                    <div class="llmsat-stat-label">Present Today</div>
                </div>
                <div class="llmsat-stat-item">
                    <div class="llmsat-stat-number">${stats.present_this_month}</div>
                    <div class="llmsat-stat-label">Present This Month</div>
                </div>
                <div class="llmsat-stat-item">
                    <div class="llmsat-stat-number">${stats.average_attendance}%</div>
                    <div class="llmsat-stat-label">Average Attendance</div>
                </div>
            </div>
        `;
        
        $('#course-stats').html(html);
    }

    // Render top performers
    function renderTopPerformers(performers) {
        if (!performers || performers.length === 0) {
            $('#top-performers').html('<p>No attendance data available.</p>');
            return;
        }

        let html = '<div class="llmsat-performers-list">';
        
        performers.forEach(function(performer, index) {
            const rank = index + 1;
            const badgeClass = rank <= 3 ? 'llmsat-rank-' + rank : 'llmsat-rank-other';
            
            html += `
                <div class="llmsat-performer-item">
                    <div class="llmsat-performer-rank ${badgeClass}">${rank}</div>
                    <div class="llmsat-performer-info">
                        <div class="llmsat-performer-name">${performer.student_name}</div>
                        <div class="llmsat-performer-stats">
                            ${performer.attendance_count} days (${performer.attendance_percentage}%)
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#top-performers').html(html);
    }

    // Export data
    function exportData(format) {
        updateFilters();
        
        // Create a form to submit the export request
        const form = $('<form>', {
            method: 'POST',
            action: llmsat_reporting_ajax.ajax_url,
            target: '_blank'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'llmsat_export_attendance'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: llmsat_reporting_ajax.nonce
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'course_id',
            value: currentCourseId
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'date_from',
            value: currentDateFrom
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'date_to',
            value: currentDateTo
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'format',
            value: format
        }));
        
        // Add form to page and submit
        $('body').append(form);
        form.submit();
        form.remove();
    }

    // Show loading state
    function showLoadingState(selector) {
        $(selector).html('<div class="llmsat-loading"><div class="llmsat-spinner"></div><p>Loading...</p></div>');
    }

    // Show error state
    function showError(selector, message) {
        $(selector).html(`<div class="llmsat-error"><p>${message}</p></div>`);
    }

    // Initialize dashboard when page loads
    initDashboard();

    // Auto-refresh data every 5 minutes (if enabled)
    if (typeof llmsat_reporting_ajax !== 'undefined' && llmsat_reporting_ajax.auto_refresh === 'yes') {
        setInterval(function() {
            if (attendanceChart) {
                loadAttendanceChart();
                loadCourseStats();
            }
        }, 300000); // 5 minutes
    }

    // Handle window resize with debouncing
    let resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (attendanceChart) {
                // Force chart to recalculate its size
                const chartContainer = $('#attendance-chart').parent();
                if (chartContainer.length) {
                    attendanceChart.resize();
                }
            }
        }, 250);
    });

    // Force initial resize after a short delay to ensure proper sizing
    setTimeout(function() {
        if (attendanceChart) {
            attendanceChart.resize();
        }
    }, 500);
});
