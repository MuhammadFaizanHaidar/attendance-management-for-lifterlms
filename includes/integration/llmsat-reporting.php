<?php
/**
 * Attendance Management For LifterLMS Reporting Dashboard
 *
 * @author   Muhammad Faizan Haidar
 * @package  Attendance Management For LifterLMS Reporting
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LLMS_AT_Reporting Class
 */
class LLMS_AT_Reporting {

	/**
	 * Hybrid manager instance.
	 */
	private $hybrid_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->hybrid_manager = new LLMS_AT_Hybrid_Manager();
		$this->hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function hooks() {
		add_action( 'admin_menu', array( $this, 'add_reporting_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_reporting_scripts' ) );
		add_action( 'wp_ajax_llmsat_get_attendance_data', array( $this, 'get_attendance_data_ajax' ) );
		add_action( 'wp_ajax_llmsat_export_attendance', array( $this, 'export_attendance_data' ) );
		add_action( 'wp_ajax_llmsat_get_course_stats', array( $this, 'get_course_stats_ajax' ) );
		add_action( 'wp_ajax_llmsat_get_student_stats', array( $this, 'get_student_stats_ajax' ) );
		add_action( 'llmsat_daily_attendance_check', array( $this, 'check_low_attendance' ) );

		// Schedule daily attendance check.
		if ( ! wp_next_scheduled( 'llmsat_daily_attendance_check' ) ) {
			wp_schedule_event( time(), 'daily', 'llmsat_daily_attendance_check' );
		}
	}

	/**
	 * Add reporting menu to admin.
	 */
	public function add_reporting_menu() {
		// Only add menu if reporting is enabled.
		if ( 'yes' !== get_option( 'llms_integration_reporting_enabled', 'yes' ) ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=course',
			__( 'Attendance Reports', 'llms-attendance' ),
			__( 'Attendance Reports', 'llms-attendance' ),
			'manage_options',
			'llms-attendance-reports',
			array( $this, 'reporting_dashboard_page' )
		);
	}

	/**
	 * Enqueue reporting scripts and styles.
	 */
	public function enqueue_reporting_scripts( $hook ) {
		if ( 'course_page_llms-attendance-reports' !== $hook ) {
			return;
		}

		// Chart.js for data visualization with fallback
		wp_enqueue_script(
			'chart-js',
			'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
			array(),
			'3.9.1',
			true
		);

		// Custom reporting script.
		wp_enqueue_script(
			'llmsat-reporting-script',
			LLMS_At_ASSETS_URL . 'js/llmsat-reporting.js',
			array( 'jquery', 'chart-js' ),
			LLMS_Attendance::VERSION,
			true
		);

		// Reporting styles.
		wp_enqueue_style(
			'llmsat-reporting-style',
			LLMS_At_ASSETS_URL . 'css/llmsat-reporting.css',
			array(),
			LLMS_Attendance::VERSION
		);

		// Localize script for AJAX.
		wp_localize_script(
			'llmsat-reporting-script',
			'llmsat_reporting_ajax',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'llmsat_reporting_nonce' ),
				'auto_refresh' => get_option( 'llms_integration_auto_refresh_enabled', 'yes' ),
			)
		);
	}

	/**
	 * Get attendance data for charts.
	 */
	public function get_attendance_data_ajax() {
		check_ajax_referer( 'llmsat_reporting_nonce', 'nonce' );

		try {
			global $wpdb;
			
			$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
			$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
			$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
			$period    = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : 'monthly';

		// Check if hybrid manager is properly initialized.
		if ( ! $this->hybrid_manager ) {
			$this->hybrid_manager = new LLMS_AT_Hybrid_Manager();
		}
		
		// Ensure hybrid manager is using the correct data source.
		$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
		if ( 'completed' === $migration_status || 'cleanup_completed' === $migration_status ) {
			// Force refresh of hybrid manager to use custom table.
			$this->hybrid_manager = new LLMS_AT_Hybrid_Manager();
		}

			$data = $this->get_attendance_chart_data( $course_id, $date_from, $date_to, $period );

			wp_send_json_success( $data );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error processing attendance data: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Get course statistics.
	 */
	public function get_course_stats_ajax() {
		check_ajax_referer( 'llmsat_reporting_nonce', 'nonce' );

		$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;

		if ( $course_id > 0 ) {
			// Single course statistics.
			$stats = $this->get_course_attendance_stats( $course_id );
		} else {
			// All courses statistics.
			$stats = $this->get_all_courses_attendance_stats();
		}

		wp_send_json_success( $stats );
	}

	/**
	 * Get student statistics.
	 */
	public function get_student_stats_ajax() {
		check_ajax_referer( 'llmsat_reporting_nonce', 'nonce' );

		$student_id = isset( $_POST['student_id'] ) ? intval( $_POST['student_id'] ) : 0;
		$course_id  = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
		$stats      = $this->get_student_attendance_stats( $student_id, $course_id );

		wp_send_json_success( $stats );
	}

	/**
	 * Export attendance data.
	 */
	public function export_attendance_data() {
		// Handle both GET and POST requests.
		$request_data = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;
		
		// Verify nonce for security.
		if ( ! wp_verify_nonce( $request_data['nonce'] ?? '', 'llmsat_reporting_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'llms-attendance' ) );
		}

		$course_id = isset( $request_data['course_id'] ) ? intval( $request_data['course_id'] ) : 0;
		$date_from = isset( $request_data['date_from'] ) ? sanitize_text_field( wp_unslash( $request_data['date_from'] ) ) : '';
		$date_to   = isset( $request_data['date_to'] ) ? sanitize_text_field( wp_unslash( $request_data['date_to'] ) ) : '';
		$format    = isset( $request_data['format'] ) ? sanitize_text_field( wp_unslash( $request_data['format'] ) ) : 'csv';

		// Validate user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export data.', 'llms-attendance' ) );
		}

		// Validate format.
		if ( ! in_array( $format, array( 'csv', 'pdf' ), true ) ) {
			wp_die( esc_html__( 'Invalid export format.', 'llms-attendance' ) );
		}

		if ( 'csv' === $format ) {
			$this->export_csv( $course_id, $date_from, $date_to );
		} elseif ( 'pdf' === $format ) {
			$this->export_pdf( $course_id, $date_from, $date_to );
		}
	}

	/**
	 * Get attendance chart data.
	 */
	private function get_attendance_chart_data( $course_id = 0, $date_from = '', $date_to = '', $period = 'monthly' ) {
		global $wpdb;


		$data = array(
			'labels'   => array(),
			'datasets' => array(
				array(
					'label'           => __( 'Attendance Rate', 'llms-attendance' ),
					'data'            => array(),
					'borderColor'     => '#0073aa',
					'backgroundColor' => 'rgba(0, 115, 170, 0.1)',
					'tension'         => 0.1,
				),
			),
		);

		// Set default date range if not provided.
		if ( empty( $date_from ) ) {
			$date_from = date( 'Y-m-01' ); // First day of current month.
		}
		if ( empty( $date_to ) ) {
			$date_to = date( 'Y-m-t' ); // Last day of current month.
		}

		// Get courses to analyze.
		$courses = array();
		if ( $course_id > 0 ) {
			$courses[] = $course_id;
		} else {
			$course_posts = get_posts(
				array(
					'post_type'      => 'course',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
			$courses      = $course_posts;
		}

		// Generate data based on period.
		switch ( $period ) {
			case 'daily':
				$data = $this->get_daily_attendance_data( $courses, $date_from, $date_to );
				break;
			case 'weekly':
				$data = $this->get_weekly_attendance_data( $courses, $date_from, $date_to );
				break;
			case 'monthly':
			default:
				$data = $this->get_monthly_attendance_data( $courses, $date_from, $date_to );
				break;
		}

		return $data;
	}

	/**
	 * Get daily attendance data.
	 */
	private function get_daily_attendance_data( $courses, $date_from, $date_to ) {
		$labels           = array();
		$attendance_rates = array();

		$current_date = new DateTime( $date_from );
		$end_date     = new DateTime( $date_to );

		while ( $current_date <= $end_date ) {
			$date_str = $current_date->format( 'Y-m-d' );
			$labels[] = $current_date->format( 'M j' );

			$total_students   = 0;
			$present_students = 0;

			foreach ( $courses as $course_id ) {
				$enrolled_students = llms_get_enrolled_students( $course_id );
				$total_students   += count( $enrolled_students );

				foreach ( $enrolled_students as $student_id ) {
					$attendance_key = $current_date->format( 'Y-m-d' ) . '-' . $course_id;
					$attendance     = $this->hybrid_manager->has_attendance( $student_id, $course_id, $current_date->format( 'Y-m-d' ) );
					if ( $attendance ) {
						++$present_students;
					}
				}
			}

			$rate               = $total_students > 0 ? ( $present_students / $total_students ) * 100 : 0;
			$attendance_rates[] = round( $rate, 1 );

			$current_date->add( new DateInterval( 'P1D' ) );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'                => __( 'Daily Attendance Rate (%)', 'llms-attendance' ),
					'data'                 => $attendance_rates,
					'borderColor'          => '#00a0d2',
					'backgroundColor'      => 'rgba(0, 160, 210, 0.2)',
					'pointBackgroundColor' => '#00a0d2',
					'pointBorderColor'     => '#ffffff',
					'pointBorderWidth'     => 2,
					'tension'              => 0.1,
				),
			),
		);
	}

	/**
	 * Get weekly attendance data.
	 */
	private function get_weekly_attendance_data( $courses, $date_from, $date_to ) {
		$labels           = array();
		$attendance_rates = array();

		$current_date = new DateTime( $date_from );
		$end_date     = new DateTime( $date_to );

		// Start from the beginning of the week.
		$current_date->modify( 'monday this week' );

		while ( $current_date <= $end_date ) {
			$week_start = clone $current_date;
			$week_end   = clone $current_date;
			$week_end->add( new DateInterval( 'P6D' ) );

			$labels[] = $week_start->format( 'M j' ) . ' - ' . $week_end->format( 'M j' );

			$total_students   = 0;
			$present_students = 0;

			foreach ( $courses as $course_id ) {
				$enrolled_students = llms_get_enrolled_students( $course_id );
				$total_students   += count( $enrolled_students );

				foreach ( $enrolled_students as $student_id ) {
					$week_present = false;
					$check_date   = clone $week_start;

					// Check each day of the week.
					for ( $i = 0; $i < 7; $i++ ) {
						$attendance = $this->hybrid_manager->has_attendance( $student_id, $course_id, $check_date->format( 'Y-m-d' ) );
						if ( $attendance ) {
							$week_present = true;
							break;
						}
						$check_date->add( new DateInterval( 'P1D' ) );
					}

					if ( $week_present ) {
						++$present_students;
					}
				}
			}

			$rate               = $total_students > 0 ? ( $present_students / $total_students ) * 100 : 0;
			$attendance_rates[] = round( $rate, 1 );

			$current_date->add( new DateInterval( 'P7D' ) );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'                => __( 'Weekly Attendance Rate (%)', 'llms-attendance' ),
					'data'                 => $attendance_rates,
					'borderColor'          => '#00a0d2',
					'backgroundColor'      => 'rgba(0, 160, 210, 0.2)',
					'pointBackgroundColor' => '#00a0d2',
					'pointBorderColor'     => '#ffffff',
					'pointBorderWidth'     => 2,
					'tension'              => 0.1,
				),
			),
		);
	}

	/**
	 * Get monthly attendance data.
	 */
	private function get_monthly_attendance_data( $courses, $date_from, $date_to ) {
		$labels           = array();
		$attendance_rates = array();

		try {
			$current_date = new DateTime( $date_from );
			$end_date     = new DateTime( $date_to );

			// Start from the beginning of the month.
			$current_date->modify( 'first day of this month' );

			while ( $current_date <= $end_date ) {
				$month_start = clone $current_date;
				$month_end   = clone $current_date;
				$month_end->modify( 'last day of this month' );

				$labels[] = $current_date->format( 'M Y' );

				$total_students   = 0;
				$present_students = 0;

				foreach ( $courses as $course_id ) {
					$enrolled_students = llms_get_enrolled_students( $course_id );
					$total_students   += count( $enrolled_students );

					foreach ( $enrolled_students as $student_id ) {
						$month_present = false;
						$check_date    = clone $month_start;
						$attendance_found = false;

						// Check each day of the month.
						while ( $check_date <= $month_end ) {
							$date_string = $check_date->format( 'Y-m-d' );
							$attendance = $this->hybrid_manager->has_attendance( $student_id, $course_id, $date_string );
							
							if ( $attendance ) {
								$month_present = true;
								$attendance_found = true;
								break;
							}
							$check_date->add( new DateInterval( 'P1D' ) );
						}
						
						if ( ! $attendance_found ) {
							// No attendance found for this student in this month
						}

						if ( $month_present ) {
							++$present_students;
						}
					}
				}

				$rate               = $total_students > 0 ? ( $present_students / $total_students ) * 100 : 0;
				$attendance_rates[] = round( $rate, 1 );

				$current_date->add( new DateInterval( 'P1M' ) );
			}
		} catch ( Exception $e ) {
			// Return empty data on error
			return array(
				'labels'   => array(),
				'datasets' => array(
					array(
						'label'           => __( 'Monthly Attendance Rate (%)', 'llms-attendance' ),
						'data'            => array(),
						'borderColor'     => '#00a0d2',
						'backgroundColor' => 'rgba(0, 160, 210, 0.2)',
						'pointBackgroundColor' => '#00a0d2',
						'pointBorderColor'     => '#ffffff',
						'pointBorderWidth'     => 2,
						'tension'              => 0.1,
					),
				),
			);
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'                => __( 'Monthly Attendance Rate (%)', 'llms-attendance' ),
					'data'                 => $attendance_rates,
					'borderColor'          => '#00a0d2',
					'backgroundColor'      => 'rgba(0, 160, 210, 0.2)',
					'pointBackgroundColor' => '#00a0d2',
					'pointBorderColor'     => '#ffffff',
					'pointBorderWidth'     => 2,
					'tension'              => 0.1,
				),
			),
		);
	}

	/**
	 * Get course attendance statistics.
	 */
	private function get_course_attendance_stats( $course_id ) {
		if ( $course_id <= 0 ) {
			return array();
		}

		$enrolled_students = llms_get_enrolled_students( $course_id );
		$total_students    = count( $enrolled_students );

		$current_date  = date( 'Y-m-d' );
		$current_month = date( 'Y-m' );
		$current_day   = date( 'd' );

		$stats = array(
			'total_students'     => $total_students,
			'present_today'      => 0,
			'present_this_month' => 0,
			'average_attendance' => 0,
			'top_performers'     => array(),
		);

		$student_attendance = array();

		foreach ( $enrolled_students as $student_id ) {
			$user = get_userdata( $student_id );
			if ( ! $user ) {
				continue;
			}

			// Check today's attendance.
			$today_attendance = $this->hybrid_manager->has_attendance( $student_id, $course_id, $current_date );
			if ( $today_attendance ) {
				++$stats['present_today'];
			}

			// Calculate monthly attendance using hybrid manager.
			$monthly_count = $this->hybrid_manager->get_attendance_data( $student_id, $course_id, $current_month . '-01', $current_month . '-31' );
			$monthly_count = intval( $monthly_count );

			if ( $monthly_count > 0 ) {
				++$stats['present_this_month'];
			}

			// Calculate attendance percentage based on actual possible days.
			// Get the first attendance date for this student.
			$first_mark_key   = 'first_mark' . '-' . $course_id;
			$first_attendance = get_user_meta( $student_id, $first_mark_key, true );

			if ( ! empty( $first_attendance ) ) {
				// Parse the first attendance date.
				list( $first_year, $first_month, $first_day ) = explode( '-', $first_attendance );
				$first_date                                   = new DateTime( $first_year . '-' . $first_month . '-' . $first_day );
				$today_date                                   = new DateTime( $current_date );

				// Calculate days since first attendance.
				$days_since_first = $first_date->diff( $today_date )->days + 1;

				// Calculate percentage based on actual possible days.
				$attendance_percentage = $days_since_first > 0 ? ( $monthly_count / $days_since_first ) * 100 : 0;
			} else {
				// If no first attendance date, use current day of month as fallback.
				$attendance_percentage = $current_day > 0 ? ( $monthly_count / $current_day ) * 100 : 0;
			}

			$student_attendance[] = array(
				'student_id'            => $student_id,
				'student_name'          => $user->display_name,
				'attendance_count'      => $monthly_count,
				'attendance_percentage' => round( $attendance_percentage, 1 ),
			);
		}

		// Sort by attendance percentage..
		usort(
			$student_attendance,
			function ( $a, $b ) {
				return $b['attendance_percentage'] <=> $a['attendance_percentage'];
			}
		);

		$stats['top_performers']     = array_slice( $student_attendance, 0, 5 );
		$stats['average_attendance'] = $total_students > 0 ? round( ( $stats['present_this_month'] / $total_students ) * 100, 1 ) : 0;

		return $stats;
	}

	/**
	 * Get all courses attendance statistics.
	 */
	private function get_all_courses_attendance_stats() {
		$courses = get_posts(
			array(
				'post_type'      => 'course',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$total_students         = 0;
		$present_today          = 0;
		$present_this_month     = 0;
		$all_student_attendance = array();

		$current_date  = date( 'Y-m-d' );
		$current_month = date( 'Y-m' );
		$current_day   = date( 'd' );

		foreach ( $courses as $course_id ) {
			$enrolled_students = llms_get_enrolled_students( $course_id );
			$total_students   += count( $enrolled_students );

			foreach ( $enrolled_students as $student_id ) {
				$user = get_userdata( $student_id );
				if ( ! $user ) {
					continue;
				}

				// Check today's attendance.
				$today_attendance = $this->hybrid_manager->has_attendance( $student_id, $course_id, $current_date );
				if ( $today_attendance ) {
					++$present_today;
				}

				// Calculate monthly attendance using hybrid manager.
				$monthly_count = $this->hybrid_manager->get_attendance_data( $student_id, $course_id, $current_month . '-01', $current_month . '-31' );
				$monthly_count = intval( $monthly_count );

				if ( $monthly_count > 0 ) {
					++$present_this_month;
				}

				// Calculate attendance percentage. based on actual possible days.
				$first_mark_key   = 'first_mark' . '-' . $course_id;
				$first_attendance = get_user_meta( $student_id, $first_mark_key, true );

				if ( ! empty( $first_attendance ) ) {
					// Parse the first attendance date.
					list( $first_year, $first_month, $first_day ) = explode( '-', $first_attendance );
					$first_date                                   = new DateTime( $first_year . '-' . $first_month . '-' . $first_day );
					$today_date                                   = new DateTime( $current_date );

					// Calculate days since first attendance.
					$days_since_first = $first_date->diff( $today_date )->days + 1;

					// Calculate percentage based on actual possible days.
					$attendance_percentage = $days_since_first > 0 ? ( $monthly_count / $days_since_first ) * 100 : 0;
				} else {
					// If no first attendance date, use current day of month as fallback.
					$attendance_percentage = $current_day > 0 ? ( $monthly_count / $current_day ) * 100 : 0;
				}

				// Store student data for top performers.
				$all_student_attendance[] = array(
					'student_id'            => $student_id,
					'student_name'          => $user->display_name,
					'attendance_count'      => $monthly_count,
					'attendance_percentage' => round( $attendance_percentage, 1 ),
				);
			}
		}

		// Sort by attendance percentage.
		usort(
			$all_student_attendance,
			function ( $a, $b ) {
				return $b['attendance_percentage'] <=> $a['attendance_percentage'];
			}
		);

		$stats = array(
			'total_students'     => $total_students,
			'present_today'      => $present_today,
			'present_this_month' => $present_this_month,
			'average_attendance' => $total_students > 0 ? round( ( $present_this_month / $total_students ) * 100, 1 ) : 0,
			'top_performers'     => array_slice( $all_student_attendance, 0, 5 ),
		);

		return $stats;
	}

	/**
	 * Get student attendance statistics.
	 */
	private function get_student_attendance_stats( $student_id, $course_id ) {
		if ( $student_id <= 0 ) {
			return array();
		}

		$user = get_userdata( $student_id );
		if ( ! $user ) {
			return array();
		}

		$current_date  = date( 'Y-m-d' );
		$current_month = date( 'Y-m' );
		$current_day   = date( 'd' );

		$stats = array(
			'student_name'          => $user->display_name,
			'student_id'            => $student_id,
			'attendance_count'      => 0,
			'attendance_percentage' => 0,
			'attendance_history'    => array(),
		);

		if ( $course_id > 0 ) {
			// Single course stats using hybrid manager.
			$attendance_count = $this->hybrid_manager->get_attendance_data( $student_id, $course_id, $current_month . '-01', $current_month . '-31' );
			$attendance_count = intval( $attendance_count );

			// Calculate attendance percentage based on actual possible days.
			$first_mark_key   = 'first_mark' . '-' . $course_id;
			$first_attendance = get_user_meta( $student_id, $first_mark_key, true );

			if ( ! empty( $first_attendance ) ) {
				// Parse the first attendance date.
				list( $first_year, $first_month, $first_day ) = explode( '-', $first_attendance );
				$first_date                                   = new DateTime( $first_year . '-' . $first_month . '-' . $first_day );
				$today_date                                   = new DateTime( $current_date );

				// Calculate days since first attendance.
				$days_since_first = $first_date->diff( $today_date )->days + 1;

				// Calculate percentage based on actual possible days.
				$attendance_percentage = $days_since_first > 0 ? ( $attendance_count / $days_since_first ) * 100 : 0;
			} else {
				// If no first attendance date, use current day of month as fallback.
				$attendance_percentage = $current_day > 0 ? ( $attendance_count / $current_day ) * 100 : 0;
			}

			$stats['attendance_count']      = $attendance_count;
			$stats['attendance_percentage'] = round( $attendance_percentage, 1 );
		} else {
			// All courses stats.
			$courses = get_posts(
				array(
					'post_type'      => 'course',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			$total_attendance = 0;
			$total_possible   = 0;

			foreach ( $courses as $course ) {
				$enrolled_students = llms_get_enrolled_students( $course );
				if ( in_array( $student_id, $enrolled_students ) ) {
					$attendance_count = $this->hybrid_manager->get_attendance_data( $student_id, $course, $current_month . '-01', $current_month . '-31' );
					$attendance_count = intval( $attendance_count );

					$total_attendance += $attendance_count;
					$total_possible   += $current_day;

					$stats['attendance_history'][] = array(
						'course_id'             => $course,
						'course_name'           => get_the_title( $course ),
						'attendance_count'      => $attendance_count,
						'attendance_percentage' => $current_day > 0 ? round( ( $attendance_count / $current_day ) * 100, 1 ) : 0,
					);
				}
			}

			$stats['attendance_count']      = $total_attendance;
			$stats['attendance_percentage'] = $total_possible > 0 ? round( ( $total_attendance / $total_possible ) * 100, 1 ) : 0;
		}

		return $stats;
	}

	/**
	 * Export data to CSV.
	 *
	 * @param int    $course_id Course ID.
	 * @param string $date_from Date from.
	 * @param string $date_to Date to.
	 */
	private function export_csv( $course_id, $date_from, $date_to ) {
		$filename = 'attendance-report-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';
		$filename = sanitize_file_name( $filename );

		// Set proper security headers.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: private, no-transform, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Content-Security-Policy: default-src \'self\'' );

		// Add BOM for UTF-8 compatibility
		echo "\xEF\xBB\xBF";

		$output = fopen( 'php://output', 'w' );

		// CSV headers.
		fputcsv(
			$output,
			array(
				__( 'Student Name', 'llms-attendance' ),
				__( 'Student Email', 'llms-attendance' ),
				__( 'Course Name', 'llms-attendance' ),
				__( 'Attendance Count', 'llms-attendance' ),
				__( 'Attendance Percentage', 'llms-attendance' ),
				__( 'Date Range', 'llms-attendance' ),
			)
		);

		// Get courses to export.
		$courses = array();
		if ( $course_id > 0 ) {
			$courses[] = $course_id;
		} else {
			$course_posts = get_posts(
				array(
					'post_type'      => 'course',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
			$courses      = $course_posts;
		}

		// Export data.
		foreach ( $courses as $course ) {
			$enrolled_students = llms_get_enrolled_students( $course );
			$course_name       = get_the_title( $course );

			foreach ( $enrolled_students as $student_id ) {
				$user = get_userdata( $student_id );
				if ( ! $user ) {
					continue;
				}

				// Calculate attendance for date range.
				$attendance_count      = $this->calculate_attendance_in_range( $student_id, $course, $date_from, $date_to );
				$total_days            = $this->get_total_days_in_range( $date_from, $date_to );
				$attendance_percentage = $total_days > 0 ? round( ( $attendance_count / $total_days ) * 100, 1 ) : 0;

				fputcsv(
					$output,
					array(
						sanitize_text_field( $user->display_name ),
						sanitize_email( $user->user_email ),
						sanitize_text_field( $course_name ),
						$attendance_count,
						$attendance_percentage . '%',
						sanitize_text_field( $date_from . ' to ' . $date_to ),
					)
				);
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export data to PDF (basic implementation)
	 *
	 * @param int    $course_id Course ID.
	 * @param string $date_from Date from.
	 * @param string $date_to Date to.
	 */
	private function export_pdf( $course_id, $date_from, $date_to ) {
		// For now, we'll create a simple HTML-based PDF.
		// In a production environment, you might want to use a proper PDF library like TCPDF or mPDF.

		$filename = 'attendance-report-' . gmdate( 'Y-m-d-H-i-s' ) . '.html';
		$filename = sanitize_file_name( $filename );

		// Set proper security headers
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: private, no-transform, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Content-Security-Policy: default-src \'self\'' );

		echo '<!DOCTYPE html><html><head><title>Attendance Report</title>';
		echo '<style>body{font-family:Arial,sans-serif;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>';
		echo '</head><body>';
		echo '<h1>Attendance Report</h1>';
		echo '<p>Generated on: ' . esc_html( gmdate( 'Y-m-d H:i:s' ) ) . '</p>';
		echo '<p>Date Range: ' . esc_html( $date_from . ' to ' . $date_to ) . '</p>';

		// Get courses to export.
		$courses = array();
		if ( $course_id > 0 ) {
			$courses[] = $course_id;
		} else {
			$course_posts = get_posts(
				array(
					'post_type'      => 'course',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
			$courses      = $course_posts;
		}

		echo '<table>';
		echo '<tr><th>Student Name</th><th>Student Email</th><th>Course Name</th><th>Attendance Count</th><th>Attendance Percentage</th></tr>';

		foreach ( $courses as $course ) {
			$enrolled_students = llms_get_enrolled_students( $course );
			$course_name       = get_the_title( $course );

			foreach ( $enrolled_students as $student_id ) {
				$user = get_userdata( $student_id );
				if ( ! $user ) {
					continue;
				}

				$attendance_count      = $this->calculate_attendance_in_range( $student_id, $course, $date_from, $date_to );
				$total_days            = $this->get_total_days_in_range( $date_from, $date_to );
				$attendance_percentage = $total_days > 0 ? round( ( $attendance_count / $total_days ) * 100, 1 ) : 0;

				echo '<tr>';
				echo '<td>' . esc_html( $user->display_name ) . '</td>';
				echo '<td>' . esc_html( $user->user_email ) . '</td>';
				echo '<td>' . esc_html( $course_name ) . '</td>';
				echo '<td>' . $attendance_count . '</td>';
				echo '<td>' . $attendance_percentage . '%</td>';
				echo '</tr>';
			}
		}

		echo '</table></body></html>';
		exit;
	}

	/**
	 * Calculate attendance count in date range
	 */
	private function calculate_attendance_in_range( $student_id, $course_id, $date_from, $date_to ) {
		$count        = 0;
		$current_date = new DateTime( $date_from );
		$end_date     = new DateTime( $date_to );

		while ( $current_date <= $end_date ) {
			$attendance = $this->hybrid_manager->has_attendance( $student_id, $course_id, $current_date->format( 'Y-m-d' ) );
			if ( $attendance ) {
				++$count;
			}
			$current_date->add( new DateInterval( 'P1D' ) );
		}

		return $count;
	}

	/**
	 * Get total days in date range
	 */
	private function get_total_days_in_range( $date_from, $date_to ) {
		$start    = new DateTime( $date_from );
		$end      = new DateTime( $date_to );
		$interval = $start->diff( $end );
		return $interval->days + 1;
	}

	/**
	 * Render the reporting dashboard page
	 */
	public function reporting_dashboard_page() {
		?>
		<div class="wrap llmsat-reporting-dashboard">
			<h1><?php echo esc_html__( 'Attendance Reports Dashboard', 'llms-attendance' ); ?></h1>
			
			<div class="llmsat-dashboard-header">
				<div class="llmsat-filters">
					<label for="course-filter"><?php echo esc_html__( 'Course:', 'llms-attendance' ); ?></label>
					<select id="course-filter">
						<option value="0"><?php echo esc_html__( 'All Courses', 'llms-attendance' ); ?></option>
						<?php
						$courses = get_posts(
							array(
								'post_type'      => 'course',
								'post_status'    => 'publish',
								'posts_per_page' => -1,
							)
						);
						foreach ( $courses as $course ) {
							echo '<option value="' . $course->ID . '">' . esc_html( $course->post_title ) . '</option>';
						}
						?>
					</select>

					<label for="period-filter"><?php echo esc_html__( 'Period:', 'llms-attendance' ); ?></label>
					<select id="period-filter">
						<option value="daily"><?php echo esc_html__( 'Daily', 'llms-attendance' ); ?></option>
						<option value="weekly"><?php echo esc_html__( 'Weekly', 'llms-attendance' ); ?></option>
						<option value="monthly" selected><?php echo esc_html__( 'Monthly', 'llms-attendance' ); ?></option>
					</select>

					<label for="date-from"><?php echo esc_html__( 'From:', 'llms-attendance' ); ?></label>
					<input type="date" id="date-from" value="<?php echo date( 'Y-m-01' ); ?>">

					<label for="date-to"><?php echo esc_html__( 'To:', 'llms-attendance' ); ?></label>
					<input type="date" id="date-to" value="<?php echo date( 'Y-m-t' ); ?>">

					<button id="update-chart" class="button button-primary">
						<?php echo esc_html__( 'Update Chart', 'llms-attendance' ); ?>
					</button>
				</div>

				<div class="llmsat-export-options">
					<button id="export-csv" class="button">
						<?php echo esc_html__( 'Export CSV', 'llms-attendance' ); ?>
					</button>
					<button id="export-pdf" class="button">
						<?php echo esc_html__( 'Export PDF', 'llms-attendance' ); ?>
					</button>
				</div>
			</div>

			<div class="llmsat-dashboard-content">
				<div class="llmsat-chart-container">
					<canvas id="attendance-chart" width="800" height="400"></canvas>
				</div>

				<div class="llmsat-stats-grid">
					<div class="llmsat-stat-card">
						<h3><?php echo esc_html__( 'Course Statistics', 'llms-attendance' ); ?></h3>
						<div id="course-stats">
							<p><?php echo esc_html__( 'Loading...', 'llms-attendance' ); ?></p>
						</div>
					</div>

					<div class="llmsat-stat-card">
						<h3><?php echo esc_html__( 'Top Performers', 'llms-attendance' ); ?></h3>
						<div id="top-performers">
							<p><?php echo esc_html__( 'Loading...', 'llms-attendance' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check for low attendance and send email alerts
	 */
	public function check_low_attendance() {
		// Only run if email alerts are enabled.
		if ( 'yes' !== get_option( 'llms_integration_email_alerts_enabled', 'no' ) ) {
			return;
		}

		$threshold     = intval( get_option( 'llms_integration_low_attendance_threshold', 70 ) );
		$current_month = date( 'Y-n' );
		$current_day   = date( 'j' );

		// Get all courses.
		$courses = get_posts(
			array(
				'post_type'      => 'course',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $courses as $course ) {
			$enrolled_students       = llms_get_enrolled_students( $course->ID );
			$low_attendance_students = array();

			foreach ( $enrolled_students as $student_id ) {
				$user = get_userdata( $student_id );
				if ( ! $user ) {
					continue;
				}

				// Calculate attendance percentage using hybrid manager.
				$attendance_count = $this->hybrid_manager->get_attendance_data( $student_id, $course->ID, $current_month . '-01', $current_month . '-31' );
				$attendance_count = intval( $attendance_count );

				$attendance_percentage = $current_day > 0 ? ( $attendance_count / $current_day ) * 100 : 0;

				if ( $attendance_percentage < $threshold ) {
					$low_attendance_students[] = array(
						'student'               => $user,
						'attendance_percentage' => round( $attendance_percentage, 1 ),
						'attendance_count'      => $attendance_count,
					);
				}
			}

			// Send email if there are students with low attendance.
			if ( ! empty( $low_attendance_students ) ) {
				$this->send_low_attendance_email( $course, $low_attendance_students, $threshold );
			}
		}
	}

	/**
	 * Send low attendance email alert
	 */
	private function send_low_attendance_email( $course, $low_attendance_students, $threshold ) {
		$course_name = $course->post_title;
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		$subject = sprintf(
			__( '[%1$s] Low Attendance Alert - %2$s', 'llms-attendance' ),
			$site_name,
			$course_name
		);

		$message = sprintf(
			__( 'Low attendance alert for course: %s', 'llms-attendance' ),
			$course_name
		) . "\n\n";

		$message .= sprintf(
			__( 'The following students have attendance below %d%%:', 'llms-attendance' ),
			$threshold
		) . "\n\n";

		foreach ( $low_attendance_students as $student_data ) {
			$message .= sprintf(
				__( '• %1$s: %2$d%% (%3$d days)', 'llms-attendance' ),
				$student_data['student']->display_name,
				$student_data['attendance_percentage'],
				$student_data['attendance_count']
			) . "\n";
		}

		$message .= "\n" . __( 'Please check the attendance reports dashboard for more details.', 'llms-attendance' );
		$message .= "\n" . admin_url( 'edit.php?post_type=course&page=llms-attendance-reports' );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		wp_mail( $admin_email, $subject, $message, $headers );
	}
}

return new LLMS_AT_Reporting();
