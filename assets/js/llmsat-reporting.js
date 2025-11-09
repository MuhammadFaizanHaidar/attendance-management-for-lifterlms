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
        
        // Initialize dark mode
        initDarkMode();
        
        // Check if Chart.js is loaded, if not wait and retry
        if (typeof Chart === 'undefined') {
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    loadAttendanceChart();
                    loadCourseStats();
                } else {
                    showError('#attendance-chart', 'Chart.js library failed to load. Please check your internet connection and refresh the page.');
                }
            }, 2000);
        } else {
            // Load initial data
            loadAttendanceChart();
            loadCourseStats();
        }
        
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
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            showError('#attendance-chart', 'Chart.js library failed to load. Please refresh the page or check your internet connection.');
            return;
        }

        const ctx = document.getElementById('attendance-chart');
        if (!ctx) {
            return;
        }
        
        const chartContext = ctx.getContext('2d');
        
        // Destroy existing chart
        if (attendanceChart) {
            attendanceChart.destroy();
        }

        // Determine initial colors based on current mode
        const isDarkMode = $('body').hasClass('llmsat-dark-mode');
        const textColor = isDarkMode ? '#f0f0f0' : '#23282d';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
        const tooltipBg = isDarkMode ? 'rgba(0, 0, 0, 0.8)' : 'rgba(255, 255, 255, 0.95)';
        const tooltipText = isDarkMode ? '#ffffff' : '#23282d';

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
                        },
                        color: textColor
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: '#0073aa',
                        borderWidth: 1,
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
                            },
                            color: textColor
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: textColor
                        },
                        grid: {
                            color: gridColor,
                            lineWidth: 1
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Attendance Rate (%)',
                            font: {
                                size: 12
                            },
                            color: textColor
                        },
                        min: 0,
                        max: 100,
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: gridColor,
                            lineWidth: 1
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
        try {
            attendanceChart = new Chart(chartContext, config);
            clearStates('#attendance-chart');
            
            // Apply dark mode colors if needed
            if ($('body').hasClass('llmsat-dark-mode')) {
                updateChartForDarkMode();
            }
        } catch (error) {
            showError('#attendance-chart', 'Failed to create chart: ' + error.message);
        }
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
        
        // Show loading state
        const exportButton = format === 'csv' ? $('#export-csv') : $('#export-pdf');
        const originalText = exportButton.text();
        exportButton.text('Generating...').prop('disabled', true);
        
        // Create a secure download URL
        const downloadUrl = createDownloadUrl(format);
        
        // Create a temporary link element for secure download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = '';
        link.style.display = 'none';
        
        // Add to DOM, click, and remove
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Reset button after a short delay
        setTimeout(function() {
            exportButton.text(originalText).prop('disabled', false);
        }, 2000);
    }
    
    // Create secure download URL
    function createDownloadUrl(format) {
        const params = new URLSearchParams();
        params.append('action', 'llmsat_export_attendance');
        params.append('nonce', llmsat_reporting_ajax.nonce);
        params.append('course_id', currentCourseId);
        params.append('date_from', currentDateFrom);
        params.append('date_to', currentDateTo);
        params.append('format', format);
        
        return llmsat_reporting_ajax.ajax_url + '?' + params.toString();
    }

    // Show loading state
    function showLoadingState(selector) {
        $(selector).addClass('loading').html('<div class="llmsat-loading"><div class="llmsat-spinner"></div><p>Loading...</p></div>');
    }

    // Show error state
    function showError(selector, message) {
        $(selector).removeClass('loading').addClass('error').html(`<div class="llmsat-error"><p>${message}</p></div>`);
    }

    // Clear states
    function clearStates(selector) {
        $(selector).removeClass('loading error');
    }

    // Dark Mode Functions
    function initDarkMode() {
        // Check for saved dark mode preference
        const savedMode = localStorage.getItem('llmsat-dark-mode');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Apply dark mode if saved preference exists or system prefers dark
        if (savedMode === 'true' || (savedMode === null && systemPrefersDark)) {
            enableDarkMode();
        } else {
            disableDarkMode();
        }
        
        // Create dark mode toggle button
        createDarkModeToggle();
    }

    function createDarkModeToggle() {
        // Check if toggle already exists
        if ($('.llmsat-dark-mode-toggle').length > 0) {
            return;
        }
        
        const toggle = $('<button>')
            .addClass('llmsat-dark-mode-toggle')
            .attr('title', 'Toggle Dark Mode')
            .html('🌙')
            .on('click', function() {
                toggleDarkMode();
            });
        
        $('body').append(toggle);
    }

    function toggleDarkMode() {
        if ($('body').hasClass('llmsat-dark-mode')) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    }

    function enableDarkMode() {
        $('body').addClass('llmsat-dark-mode').removeClass('llmsat-light-mode');
        $('.llmsat-dark-mode-toggle').html('☀️').attr('title', 'Switch to Light Mode');
        localStorage.setItem('llmsat-dark-mode', 'true');
        
        // Update chart colors for dark mode
        updateChartForDarkMode();
    }

    function disableDarkMode() {
        $('body').removeClass('llmsat-dark-mode').addClass('llmsat-light-mode');
        $('.llmsat-dark-mode-toggle').html('🌙').attr('title', 'Switch to Dark Mode');
        localStorage.setItem('llmsat-dark-mode', 'false');
        
        // Update chart colors for light mode
        updateChartForLightMode();
    }

    function updateChartForDarkMode() {
        if (attendanceChart) {
            // Update chart colors for dark mode
            attendanceChart.options.plugins.title.color = '#f0f0f0';
            attendanceChart.options.plugins.legend.labels.color = '#f0f0f0';
            attendanceChart.options.scales.x.title.color = '#f0f0f0';
            attendanceChart.options.scales.x.ticks.color = '#f0f0f0';
            attendanceChart.options.scales.x.grid.color = 'rgba(255, 255, 255, 0.2)';
            attendanceChart.options.scales.y.title.color = '#f0f0f0';
            attendanceChart.options.scales.y.ticks.color = '#f0f0f0';
            attendanceChart.options.scales.y.grid.color = 'rgba(255, 255, 255, 0.2)';
            
            // Update tooltip colors for dark mode
            attendanceChart.options.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            attendanceChart.options.plugins.tooltip.titleColor = '#ffffff';
            attendanceChart.options.plugins.tooltip.bodyColor = '#ffffff';
            
            // Update dataset colors for dark mode
            if (attendanceChart.data && attendanceChart.data.datasets) {
                attendanceChart.data.datasets.forEach(dataset => {
                    dataset.borderColor = '#00a0d2';
                    dataset.backgroundColor = 'rgba(0, 160, 210, 0.2)';
                    if (dataset.pointBackgroundColor) {
                        dataset.pointBackgroundColor = '#00a0d2';
                    }
                    if (dataset.pointBorderColor) {
                        dataset.pointBorderColor = '#ffffff';
                    }
                });
            }
            
            attendanceChart.update();
        }
    }

    function updateChartForLightMode() {
        if (attendanceChart) {
            // Update chart colors for light mode
            attendanceChart.options.plugins.title.color = '#23282d';
            attendanceChart.options.plugins.legend.labels.color = '#23282d';
            attendanceChart.options.scales.x.title.color = '#23282d';
            attendanceChart.options.scales.x.ticks.color = '#23282d';
            attendanceChart.options.scales.x.grid.color = 'rgba(0, 0, 0, 0.1)';
            attendanceChart.options.scales.y.title.color = '#23282d';
            attendanceChart.options.scales.y.ticks.color = '#23282d';
            attendanceChart.options.scales.y.grid.color = 'rgba(0, 0, 0, 0.1)';
            
            // Update tooltip colors for light mode
            attendanceChart.options.plugins.tooltip.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            attendanceChart.options.plugins.tooltip.titleColor = '#23282d';
            attendanceChart.options.plugins.tooltip.bodyColor = '#23282d';
            
            // Update dataset colors for light mode
            if (attendanceChart.data && attendanceChart.data.datasets) {
                attendanceChart.data.datasets.forEach(dataset => {
                    dataset.borderColor = '#0073aa';
                    dataset.backgroundColor = 'rgba(0, 115, 170, 0.1)';
                    if (dataset.pointBackgroundColor) {
                        dataset.pointBackgroundColor = '#0073aa';
                    }
                    if (dataset.pointBorderColor) {
                        dataset.pointBorderColor = '#ffffff';
                    }
                });
            }
            
            attendanceChart.update();
        }
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
