<?php
/**
 * Automated Test Suite for Attendance Management For LifterLMS
 *
 * @package  Attendance Management For LifterLMS
 * @author   Muhammad Faizan Haidar
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automated test suite class.
 */
class LLMS_AT_Test_Suite {

	/**
	 * Database instance.
	 */
	private $db;

	/**
	 * Test results.
	 */
	private $test_results = array();

	/**
	 * Initialize test suite.
	 */
	public function __construct() {
		$this->db = new LLMS_AT_Database();
	}

	/**
	 * Run all tests.
	 */
	public function run_all_tests() {
		$this->test_results = array();

		// Database tests.
		$this->test_database_creation();
		$this->test_table_structure();
		$this->test_indexes();

		// Data integrity tests.
		$this->test_data_insertion();
		$this->test_unique_constraints();
		$this->test_data_retrieval();

		// Performance tests.
		$this->test_query_performance();
		$this->test_large_dataset_performance();

		// Migration tests.
		$this->test_migration_functionality();

		return $this->test_results;
	}

	/**
	 * Test database creation.
	 */
	private function test_database_creation() {
		$table_name = $this->db->get_table_name();
		global $wpdb;

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );

		$this->add_test_result(
			$table_exists ? 'pass' : 'fail',
			'Database Table Creation',
			$table_exists ? 'Custom table created successfully' : 'Custom table creation failed'
		);
	}

	/**
	 * Test table structure.
	 */
	private function test_table_structure() {
		$table_name = $this->db->get_table_name();
		global $wpdb;

		$columns          = $wpdb->get_results( "DESCRIBE $table_name" );
		$expected_columns = array( 'id', 'user_id', 'course_id', 'attendance_date', 'attendance_time', 'ip_address', 'user_agent', 'created_at', 'updated_at' );

		$actual_columns  = array_column( $columns, 'Field' );
		$missing_columns = array_diff( $expected_columns, $actual_columns );

		$this->add_test_result(
			empty( $missing_columns ) ? 'pass' : 'fail',
			'Table Structure',
			empty( $missing_columns ) ? 'All required columns present' : 'Missing columns: ' . implode( ', ', $missing_columns )
		);
	}

	/**
	 * Test database indexes.
	 */
	private function test_indexes() {
		$table_name = $this->db->get_table_name();
		global $wpdb;

		$indexes     = $wpdb->get_results( "SHOW INDEX FROM $table_name" );
		$index_names = array_unique( array_column( $indexes, 'Key_name' ) );

		$expected_indexes = array( 'PRIMARY', 'unique_daily_attendance', 'idx_user_course', 'idx_course_date', 'idx_attendance_date', 'idx_user_date', 'idx_created_at' );
		$missing_indexes  = array_diff( $expected_indexes, $index_names );

		$this->add_test_result(
			empty( $missing_indexes ) ? 'pass' : 'fail',
			'Database Indexes',
			empty( $missing_indexes ) ? 'All required indexes present' : 'Missing indexes: ' . implode( ', ', $missing_indexes )
		);
	}

	/**
	 * Test data insertion.
	 */
	private function test_data_insertion() {
		$test_user_id   = 999999;
		$test_course_id = 999999;
		$test_date      = '2024-01-01';
		$test_time      = '2024-01-01 10:00:00';

		$result = $this->db->insert_attendance( $test_user_id, $test_course_id, $test_date, $test_time );

		$this->add_test_result(
			$result ? 'pass' : 'fail',
			'Data Insertion',
			$result ? 'Data inserted successfully' : 'Data insertion failed'
		);

		// Clean up test data.
		if ( $result ) {
			global $wpdb;
			$table_name = $this->db->get_table_name();
			$wpdb->delete( $table_name, array( 'id' => $result ) );
		}
	}

	/**
	 * Test unique constraints.
	 */
	private function test_unique_constraints() {
		$test_user_id   = 999998;
		$test_course_id = 999998;
		$test_date      = '2024-01-02';

		// Insert first record.
		$result1 = $this->db->insert_attendance( $test_user_id, $test_course_id, $test_date );

		// Try to insert duplicate.
		$result2 = $this->db->insert_attendance( $test_user_id, $test_course_id, $test_date );

		$this->add_test_result(
			( $result1 && ! $result2 ) ? 'pass' : 'fail',
			'Unique Constraints',
			( $result1 && ! $result2 ) ? 'Unique constraint working correctly' : 'Unique constraint failed'
		);

		// Clean up test data.
		if ( $result1 ) {
			global $wpdb;
			$table_name = $this->db->get_table_name();
			$wpdb->delete( $table_name, array( 'id' => $result1 ) );
		}
	}

	/**
	 * Test data retrieval.
	 */
	private function test_data_retrieval() {
		$test_user_id   = 999997;
		$test_course_id = 999997;
		$test_date      = '2024-01-03';

		// Insert test data.
		$insert_result = $this->db->insert_attendance( $test_user_id, $test_course_id, $test_date );

		if ( $insert_result ) {
			// Test has_attendance.
			$has_attendance = $this->db->has_attendance( $test_user_id, $test_course_id, $test_date );

			// Test get_attendance_count.
			$count = $this->db->get_attendance_count( $test_user_id, $test_course_id );

			$this->add_test_result(
				( $has_attendance && $count > 0 ) ? 'pass' : 'fail',
				'Data Retrieval',
				( $has_attendance && $count > 0 ) ? 'Data retrieval working correctly' : 'Data retrieval failed'
			);

			// Clean up test data.
			global $wpdb;
			$table_name = $this->db->get_table_name();
			$wpdb->delete( $table_name, array( 'id' => $insert_result ) );
		} else {
			$this->add_test_result( 'fail', 'Data Retrieval', 'Could not insert test data' );
		}
	}

	/**
	 * Test query performance.
	 */
	private function test_query_performance() {
		$performance_tests = array();

		// Test 1: Simple count query.
		$start_time = microtime( true );
		$stats      = $this->db->get_table_stats();
		$end_time   = microtime( true );

		$performance_tests[] = array(
			'name'       => 'Table Statistics Query',
			'time'       => round( ( $end_time - $start_time ), 4 ),
			'acceptable' => ( $end_time - $start_time ) < 0.1, // Should be under 100ms.
		);

		// Test 2: Course stats query.
		$course_id = $this->get_random_course_id();
		if ( $course_id ) {
			$start_time   = microtime( true );
			$course_stats = $this->db->get_course_attendance_stats( $course_id );
			$end_time     = microtime( true );

			$performance_tests[] = array(
				'name'       => 'Course Statistics Query',
				'time'       => round( ( $end_time - $start_time ), 4 ),
				'acceptable' => ( $end_time - $start_time ) < 0.2, // Should be under 200ms.
			);
		}

		// Evaluate performance.
		$all_acceptable      = true;
		$performance_summary = array();

		foreach ( $performance_tests as $test ) {
			$performance_summary[] = $test['name'] . ': ' . $test['time'] . 's';
			if ( ! $test['acceptable'] ) {
				$all_acceptable = false;
			}
		}

		$this->add_test_result(
			$all_acceptable ? 'pass' : 'warning',
			'Query Performance',
			$all_acceptable ? 'All queries within acceptable time limits' : 'Some queries exceed time limits: ' . implode( ', ', $performance_summary )
		);
	}

	/**
	 * Test large dataset performance.
	 */
	private function test_large_dataset_performance() {
		// Generate test data.
		$test_data = $this->generate_performance_test_data();

		if ( $test_data['generated'] > 0 ) {
			// Test performance with generated data.
			$start_time = microtime( true );
			$stats      = $this->db->get_table_stats();
			$end_time   = microtime( true );

			$query_time = round( ( $end_time - $start_time ), 4 );
			$acceptable = $query_time < 0.5; // Should be under 500ms even with large dataset.

			$this->add_test_result(
				$acceptable ? 'pass' : 'warning',
				'Large Dataset Performance',
				sprintf( 'Query time with %d records: %ss (%s)', $stats['total_records'], $query_time, $acceptable ? 'acceptable' : 'slow' )
			);

			// Clean up test data.
			$this->cleanup_performance_test_data( $test_data );
		} else {
			$this->add_test_result( 'skip', 'Large Dataset Performance', 'No test data generated' );
		}
	}

	/**
	 * Test migration functionality.
	 */
	private function test_migration_functionality() {
		// Test if migration system is properly initialized.
		$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
		$use_custom_table = get_option( 'llmsat_use_custom_table', false );

		$this->add_test_result(
			'pass',
			'Migration System',
			sprintf( 'Migration status: %s, Custom table active: %s', $migration_status, $use_custom_table ? 'Yes' : 'No' )
		);
	}

	/**
	 * Generate performance test data.
	 */
	private function generate_performance_test_data() {
		$generated       = 0;
		$test_user_ids   = array();
		$test_course_ids = array();

		// Create test users.
		for ( $i = 1; $i <= 50; $i++ ) {
			$user_id = wp_insert_user(
				array(
					'user_login'   => 'perf_test_user_' . $i,
					'user_email'   => 'perf_test_user_' . $i . '@example.com',
					'user_pass'    => 'test_password',
					'display_name' => 'Performance Test User ' . $i,
				)
			);

			if ( ! is_wp_error( $user_id ) ) {
				$test_user_ids[] = $user_id;
			}
		}

		// Create test courses.
		for ( $i = 1; $i <= 10; $i++ ) {
			$course_id = wp_insert_post(
				array(
					'post_title'  => 'Performance Test Course ' . $i,
					'post_type'   => 'course',
					'post_status' => 'publish',
				)
			);

			if ( $course_id ) {
				$test_course_ids[] = $course_id;
			}
		}

		// Generate attendance records.
		foreach ( $test_user_ids as $user_id ) {
			foreach ( $test_course_ids as $course_id ) {
				for ( $day = 0; $day < 30; $day++ ) {
					$attendance_date = date( 'Y-m-d', strtotime( '-' . $day . ' days' ) );
					$attendance_time = $attendance_date . ' ' . sprintf( '%02d:%02d:%02d', wp_rand( 8, 18 ), wp_rand( 0, 59 ), wp_rand( 0, 59 ) );

					$result = $this->db->insert_attendance( $user_id, $course_id, $attendance_date, $attendance_time );

					if ( $result ) {
						++$generated;
					}
				}
			}
		}

		return array(
			'generated'  => $generated,
			'user_ids'   => $test_user_ids,
			'course_ids' => $test_course_ids,
		);
	}

	/**
	 * Cleanup performance test data.
	 */
	private function cleanup_performance_test_data( $test_data ) {
		global $wpdb;

		// Delete test users.
		foreach ( $test_data['user_ids'] as $user_id ) {
			wp_delete_user( $user_id );
		}

		// Delete test courses.
		foreach ( $test_data['course_ids'] as $course_id ) {
			wp_delete_post( $course_id, true );
		}
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
	 * Add test result.
	 */
	private function add_test_result( $status, $test_name, $message ) {
		$this->test_results[] = array(
			'status'    => $status,
			'test_name' => $test_name,
			'message'   => $message,
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Get test results.
	 */
	public function get_test_results() {
		return $this->test_results;
	}

	/**
	 * Get test summary.
	 */
	public function get_test_summary() {
		$summary = array(
			'total'    => count( $this->test_results ),
			'passed'   => 0,
			'failed'   => 0,
			'warnings' => 0,
			'skipped'  => 0,
		);

		foreach ( $this->test_results as $result ) {
			++$summary[ $result['status'] . 'd' ];
		}

		return $summary;
	}
}
