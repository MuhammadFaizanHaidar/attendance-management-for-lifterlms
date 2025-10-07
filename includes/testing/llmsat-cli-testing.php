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
		WP_CLI::log(
			sprintf(
				'Total: %d | Passed: %d | Failed: %d | Warnings: %d | Skipped: %d',
				$summary['total'],
				$summary['passed'],
				$summary['failed'],
				$summary['warnings'],
				$summary['skipped']
			)
		);

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

		$testing_framework = new LLMS_AT_Testing_Framework();

		// Run query performance test
		WP_CLI::log( 'Testing query performance...' );
		$start_time = microtime( true );
		global $wpdb;
		$count      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'" );
		$query_time = microtime( true ) - $start_time;

		WP_CLI::log( sprintf( 'Query Performance: %d records in %.4f seconds', $count, $query_time ) );

		WP_CLI::success( 'Performance tests completed!' );
	}

	/**
	 * Generate test data.
	 */
	public function generate_test_data( $args, $assoc_args ) {
		$size = isset( $assoc_args['size'] ) ? $assoc_args['size'] : 'small';

		WP_CLI::log( sprintf( 'Generating %s test dataset...', $size ) );

		$testing_framework = new LLMS_AT_Testing_Framework();

		// Simulate AJAX call
		$_POST['size']  = $size;
		$_POST['nonce'] = wp_create_nonce( 'llmsat_testing' );

		ob_start();
		$testing_framework->generate_test_data();
		$response = ob_get_clean();

		WP_CLI::success( 'Test data generated successfully!' );
	}

	/**
	 * Cleanup test data.
	 */
	public function cleanup_test_data( $args, $assoc_args ) {
		WP_CLI::log( 'Cleaning up test data...' );

		$testing_framework = new LLMS_AT_Testing_Framework();

		// Simulate AJAX call
		$_POST['nonce'] = wp_create_nonce( 'llmsat_testing' );

		ob_start();
		$testing_framework->cleanup_test_data();
		$response = ob_get_clean();

		WP_CLI::success( 'Test data cleaned up successfully!' );
	}

	/**
	 * Get status icon for CLI output.
	 */
	private function get_status_icon( $status ) {
		$icons = array(
			'passed'  => '✓',
			'failed'  => '✗',
			'warning' => '⚠',
			'skipped' => '○',
		);

		return isset( $icons[ $status ] ) ? $icons[ $status ] : '?';
	}
}
