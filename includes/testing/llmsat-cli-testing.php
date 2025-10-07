<?php
/**
 * CLI Testing Tool for Attendance Management For LifterLMS
 *
 * @package  Attendance Management For LifterLMS
 * @author   Muhammad Faizan Haidar
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CLI testing tool class.
 */
class LLMS_AT_CLI_Testing {

	/**
	 * Test suite instance.
	 */
	private $test_suite;

	/**
	 * Initialize CLI testing.
	 */
	public function __construct() {
		$this->test_suite = new LLMS_AT_Test_Suite();
		
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'llmsat test', array( $this, 'run_tests' ) );
			WP_CLI::add_command( 'llmsat performance', array( $this, 'run_performance_tests' ) );
			WP_CLI::add_command( 'llmsat generate-data', array( $this, 'generate_test_data' ) );
			WP_CLI::add_command( 'llmsat cleanup', array( $this, 'cleanup_test_data' ) );
		}
	}

	/**
	 * Run all tests.
	 */
	public function run_tests( $args, $assoc_args ) {
		WP_CLI::log( 'Running Attendance Management For LifterLMS Test Suite...' );
		
		$results = $this->test_suite->run_all_tests();
		$summary = $this->test_suite->get_test_summary();
		
		// Display results.
		foreach ( $results as $result ) {
			$status_icon = $this->get_status_icon( $result['status'] );
			WP_CLI::log( sprintf( '%s %s: %s', $status_icon, $result['test_name'], $result['message'] ) );
		}
		
		// Display summary.
		WP_CLI::log( '' );
		WP_CLI::log( 'Test Summary:' );
		WP_CLI::log( sprintf( 'Total: %d | Passed: %d | Failed: %d | Warnings: %d | Skipped: %d', 
			$summary['total'], 
			$summary['passed'], 
			$summary['failed'], 
			$summary['warnings'], 
			$summary['skipped'] 
		) );
		
		// Exit with appropriate code.
		if ( $summary['failed'] > 0 ) {
			WP_CLI::error( 'Some tests failed!' );
		} elseif ( $summary['warnings'] > 0 ) {
			WP_CLI::warning( 'Some tests had warnings.' );
		} else {
			WP_CLI::success( 'All tests passed!' );
		}
	}

	/**
	 * Run performance tests.
	 */
	public function run_performance_tests( $args, $assoc_args ) {
		WP_CLI::log( 'Running Performance Tests...' );
		
		$db = new LLMS_AT_Database();
		
		// Test 1: Table statistics.
		$start_time = microtime( true );
		$stats = $db->get_table_stats();
		$end_time = microtime( true );
		
		WP_CLI::log( sprintf( 'Table Statistics Query: %ss (Records: %d)', 
			round( ( $end_time - $start_time ), 4 ), 
			$stats['total_records'] 
		) );
		
		// Test 2: Course statistics.
		$course_id = $this->get_random_course_id();
		if ( $course_id ) {
			$start_time = microtime( true );
			$course_stats = $db->get_course_attendance_stats( $course_id );
			$end_time = microtime( true );
			
			WP_CLI::log( sprintf( 'Course Statistics Query: %ss (Students: %d)', 
				round( ( $end_time - $start_time ), 4 ), 
				$course_stats['total_students'] 
			) );
		}
		
		// Test 3: Top performers.
		if ( $course_id ) {
			$start_time = microtime( true );
			$performers = $db->get_top_performers( $course_id, 10 );
			$end_time = microtime( true );
			
			WP_CLI::log( sprintf( 'Top Performers Query: %ss (Results: %d)', 
				round( ( $end_time - $start_time ), 4 ), 
				count( $performers ) 
			) );
		}
		
		WP_CLI::success( 'Performance tests completed!' );
	}

	/**
	 * Generate test data.
	 */
	public function generate_test_data( $args, $assoc_args ) {
		$size = isset( $assoc_args['size'] ) ? $assoc_args['size'] : 'small';
		
		WP_CLI::log( sprintf( 'Generating %s test dataset...', $size ) );
		
		$config = $this->get_test_config( $size );
		$generated = $this->create_test_data( $config );
		
		WP_CLI::log( sprintf( 'Generated %d attendance records', $generated ) );
		WP_CLI::success( 'Test data generation completed!' );
	}

	/**
	 * Cleanup test data.
	 */
	public function cleanup_test_data( $args, $assoc_args ) {
		WP_CLI::log( 'Cleaning up test data...' );
		
		global $wpdb;
		$db = new LLMS_AT_Database();
		
		// Delete test courses.
		$deleted_courses = $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_type = 'course' AND post_title LIKE 'Test Course%'"
		);
		
		// Delete test users.
		$deleted_users = $wpdb->query(
			"DELETE FROM {$wpdb->users} WHERE user_login LIKE 'test_student_%'"
		);
		
		// Delete test attendance records.
		$table_name = $db->get_table_name();
		$deleted_attendance = $wpdb->query(
			"DELETE FROM $table_name WHERE user_id IN (
				SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'test_student_%'
			)"
		);
		
		WP_CLI::log( sprintf( 'Cleaned up: %d courses, %d users, %d attendance records', 
			$deleted_courses, 
			$deleted_users, 
			$deleted_attendance 
		) );
		
		WP_CLI::success( 'Test data cleanup completed!' );
	}

	/**
	 * Get test configuration.
	 */
	private function get_test_config( $size ) {
		$configs = array(
			'small' => array(
				'students' => 100,
				'courses' => 10,
				'days' => 30
			),
			'medium' => array(
				'students' => 500,
				'courses' => 25,
				'days' => 60
			),
			'large' => array(
				'students' => 1000,
				'courses' => 50,
				'days' => 90
			)
		);
		
		return $configs[ $size ];
	}

	/**
	 * Create test data.
	 */
	private function create_test_data( $config ) {
		$generated = 0;
		$db = new LLMS_AT_Database();
		
		// Create test courses.
		$course_ids = array();
		for ( $i = 1; $i <= $config['courses']; $i++ ) {
			$course_id = wp_insert_post( array(
				'post_title' => 'Test Course ' . $i,
				'post_type' => 'course',
				'post_status' => 'publish'
			) );
			
			if ( $course_id ) {
				$course_ids[] = $course_id;
			}
		}
		
		// Create test users.
		$user_ids = array();
		for ( $i = 1; $i <= $config['students']; $i++ ) {
			$user_id = wp_insert_user( array(
				'user_login' => 'test_student_' . $i,
				'user_email' => 'test_student_' . $i . '@example.com',
				'user_pass' => 'test_password',
				'display_name' => 'Test Student ' . $i
			) );
			
			if ( ! is_wp_error( $user_id ) ) {
				$user_ids[] = $user_id;
			}
		}
		
		// Generate attendance records.
		$start_date = date( 'Y-m-d', strtotime( '-' . $config['days'] . ' days' ) );
		
		foreach ( $user_ids as $user_id ) {
			foreach ( $course_ids as $course_id ) {
				// Randomly generate attendance (70% chance per day).
				for ( $day = 0; $day < $config['days']; $day++ ) {
					if ( wp_rand( 1, 100 ) <= 70 ) { // 70% attendance rate.
						$attendance_date = date( 'Y-m-d', strtotime( $start_date . ' +' . $day . ' days' ) );
						$attendance_time = $attendance_date . ' ' . sprintf( '%02d:%02d:%02d', wp_rand( 8, 18 ), wp_rand( 0, 59 ), wp_rand( 0, 59 ) );
						
						$result = $db->insert_attendance( $user_id, $course_id, $attendance_date, $attendance_time );
						
						if ( $result ) {
							$generated++;
						}
					}
				}
			}
		}
		
		return $generated;
	}

	/**
	 * Get random course ID.
	 */
	private function get_random_course_id() {
		global $wpdb;
		
		$course_id = $wpdb->get_var(
			"SELECT course_id FROM {$wpdb->prefix}llms_attendance ORDER BY RAND() LIMIT 1"
		);
		
		return $course_id ?: null;
	}

	/**
	 * Get status icon.
	 */
	private function get_status_icon( $status ) {
		$icons = array(
			'pass' => '✓',
			'fail' => '✗',
			'warning' => '⚠',
			'skip' => '○'
		);
		
		return $icons[ $status ] ?? '?';
	}
}
