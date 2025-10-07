<?php
/**
 * Testing Framework for Attendance Management For LifterLMS
 *
 * @package  Attendance Management For LifterLMS
 * @author   Muhammad Faizan Haidar
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Testing framework class for performance validation.
 */
class LLMS_AT_Testing_Framework {

	/**
	 * Database instance.
	 */
	private $db;

	/**
	 * Hybrid manager instance.
	 */
	private $hybrid_manager;

	/**
	 * Test results storage.
	 */
	private $test_results = array();

	/**
	 * Initialize testing framework.
	 */
	public function __construct() {
		$this->db             = new LLMS_AT_Database();
		$this->hybrid_manager = new LLMS_AT_Hybrid_Manager();

		add_action( 'admin_menu', array( $this, 'add_testing_page' ) );
		add_action( 'wp_ajax_llmsat_run_performance_test', array( $this, 'run_performance_test' ) );
		add_action( 'wp_ajax_llmsat_generate_test_data', array( $this, 'generate_test_data' ) );
		add_action( 'wp_ajax_llmsat_cleanup_test_data', array( $this, 'cleanup_test_data' ) );
	}

	/**
	 * Add testing page to admin menu.
	 */
	public function add_testing_page() {
		add_submenu_page(
			'edit.php?post_type=course',
			__( 'Performance Testing', 'llms-attendance' ),
			__( 'Performance Testing', 'llms-attendance' ),
			'manage_options',
			'llms-attendance-testing',
			array( $this, 'testing_page' )
		);
	}

	/**
	 * Display testing page.
	 */
	public function testing_page() {
		$table_stats   = $this->db->get_table_stats();
		$system_status = $this->hybrid_manager->get_system_status();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Performance Testing Framework', 'llms-attendance' ); ?></h1>
			
			<div class="notice notice-info">
				<p><strong><?php esc_html_e( 'Testing Framework:', 'llms-attendance' ); ?></strong> 
				<?php esc_html_e( 'This tool helps validate performance with large datasets and ensures the custom table architecture works correctly.', 'llms-attendance' ); ?></p>
			</div>

			<div class="llmsat-testing-dashboard">
				<div class="testing-stats">
					<h2><?php esc_html_e( 'Current System Status', 'llms-attendance' ); ?></h2>
					
					<div class="stats-grid">
						<div class="stat-box">
							<h3><?php esc_html_e( 'Database Table', 'llms-attendance' ); ?></h3>
							<p><strong><?php echo esc_html( $table_stats['total_records'] ); ?></strong> <?php esc_html_e( 'total records', 'llms-attendance' ); ?></p>
							<p><strong><?php echo esc_html( $table_stats['unique_users'] ); ?></strong> <?php esc_html_e( 'unique users', 'llms-attendance' ); ?></p>
							<p><strong><?php echo esc_html( $table_stats['unique_courses'] ); ?></strong> <?php esc_html_e( 'unique courses', 'llms-attendance' ); ?></p>
							<p><strong><?php echo esc_html( $table_stats['earliest_date'] ); ?></strong> <?php esc_html_e( 'earliest date', 'llms-attendance' ); ?></p>
							<p><strong><?php echo esc_html( $table_stats['latest_date'] ); ?></strong> <?php esc_html_e( 'latest date', 'llms-attendance' ); ?></p>
						</div>
						
						<div class="stat-box">
							<h3><?php esc_html_e( 'System Configuration', 'llms-attendance' ); ?></h3>
							<p><strong><?php echo esc_html( $system_status['use_custom_table'] ? 'Yes' : 'No' ); ?></strong> <?php esc_html_e( 'Custom Table Active', 'llms-attendance' ); ?></p>
							<p><strong><?php echo esc_html( ucfirst( $system_status['migration_status'] ) ); ?></strong> <?php esc_html_e( 'Migration Status', 'llms-attendance' ); ?></p>
							<p><strong><?php echo esc_html( $system_status['table_exists']['total_records'] ); ?></strong> <?php esc_html_e( 'Table Records', 'llms-attendance' ); ?></p>
						</div>
					</div>
				</div>

				<div class="testing-controls">
					<h2><?php esc_html_e( 'Test Controls', 'llms-attendance' ); ?></h2>
					
					<div class="test-actions">
						<div class="test-group">
							<h3><?php esc_html_e( 'Data Generation', 'llms-attendance' ); ?></h3>
							<p><?php esc_html_e( 'Generate dummy data for testing performance.', 'llms-attendance' ); ?></p>
							<button id="generate-small-data" class="button button-secondary">
								<?php esc_html_e( 'Generate Small Dataset (100 students, 10 courses)', 'llms-attendance' ); ?>
							</button>
							<button id="generate-medium-data" class="button button-secondary">
								<?php esc_html_e( 'Generate Medium Dataset (500 students, 25 courses)', 'llms-attendance' ); ?>
							</button>
							<button id="generate-large-data" class="button button-secondary">
								<?php esc_html_e( 'Generate Large Dataset (1000 students, 50 courses)', 'llms-attendance' ); ?>
							</button>
						</div>

						<div class="test-group">
							<h3><?php esc_html_e( 'Performance Tests', 'llms-attendance' ); ?></h3>
							<p><?php esc_html_e( 'Run performance tests to validate system speed.', 'llms-attendance' ); ?></p>
							<button id="run-query-test" class="button button-primary">
								<?php esc_html_e( 'Run Query Performance Test', 'llms-attendance' ); ?>
							</button>
							<button id="run-report-test" class="button button-primary">
								<?php esc_html_e( 'Run Report Generation Test', 'llms-attendance' ); ?>
							</button>
							<button id="run-migration-test" class="button button-primary">
								<?php esc_html_e( 'Run Migration Performance Test', 'llms-attendance' ); ?>
							</button>
						</div>

						<div class="test-group">
							<h3><?php esc_html_e( 'Cleanup', 'llms-attendance' ); ?></h3>
							<p><?php esc_html_e( 'Clean up test data after testing.', 'llms-attendance' ); ?></p>
							<button id="cleanup-test-data" class="button button-secondary">
								<?php esc_html_e( 'Clean Up All Test Data', 'llms-attendance' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div class="test-results" id="test-results" style="display: none;">
					<h2><?php esc_html_e( 'Test Results', 'llms-attendance' ); ?></h2>
					<div id="results-content"></div>
				</div>
			</div>
		</div>

		<style>
		.llmsat-testing-dashboard {
			margin: 20px 0;
		}
		.stats-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
			margin: 20px 0;
		}
		.stat-box {
			border: 1px solid #ddd;
			padding: 20px;
			border-radius: 4px;
			background: #f9f9f9;
		}
		.stat-box h3 {
			margin-top: 0;
			color: #0073aa;
		}
		.test-actions {
			display: grid;
			grid-template-columns: 1fr 1fr 1fr;
			gap: 20px;
			margin: 20px 0;
		}
		.test-group {
			border: 1px solid #ddd;
			padding: 20px;
			border-radius: 4px;
			background: #fff;
		}
		.test-group h3 {
			margin-top: 0;
			color: #0073aa;
		}
		.test-group button {
			margin: 5px 0;
			display: block;
			width: 100%;
		}
		.test-results {
			margin: 20px 0;
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 4px;
			background: #f9f9f9;
		}
		.result-item {
			margin: 10px 0;
			padding: 10px;
			border-left: 4px solid #0073aa;
			background: #fff;
		}
		.result-success {
			border-left-color: #46b450;
		}
		.result-warning {
			border-left-color: #ffb900;
		}
		.result-error {
			border-left-color: #dc3232;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Data generation handlers.
			$('#generate-small-data').on('click', function() {
				generateTestData('small');
			});
			$('#generate-medium-data').on('click', function() {
				generateTestData('medium');
			});
			$('#generate-large-data').on('click', function() {
				generateTestData('large');
			});

			// Performance test handlers.
			$('#run-query-test').on('click', function() {
				runPerformanceTest('query');
			});
			$('#run-report-test').on('click', function() {
				runPerformanceTest('report');
			});
			$('#run-migration-test').on('click', function() {
				runPerformanceTest('migration');
			});

			// Cleanup handler.
			$('#cleanup-test-data').on('click', function() {
				cleanupTestData();
			});

			function generateTestData(size) {
				showResults();
				addResult('info', 'Generating ' + size + ' dataset...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_generate_test_data',
						size: size,
						nonce: '<?php echo wp_create_nonce( 'llmsat_testing' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							addResult('success', 'Dataset generated successfully: ' + response.data.message);
							location.reload();
						} else {
							addResult('error', 'Failed to generate dataset: ' + response.data.message);
						}
					},
					error: function() {
						addResult('error', 'Server error during data generation');
					}
				});
			}

			function runPerformanceTest(type) {
				showResults();
				addResult('info', 'Running ' + type + ' performance test...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_run_performance_test',
						test_type: type,
						nonce: '<?php echo wp_create_nonce( 'llmsat_testing' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							displayTestResults(response.data);
						} else {
							addResult('error', 'Test failed: ' + response.data.message);
						}
					},
					error: function() {
						addResult('error', 'Server error during performance test');
					}
				});
			}

			function cleanupTestData() {
				if (!confirm('Are you sure you want to clean up all test data?')) {
					return;
				}
				
				showResults();
				addResult('info', 'Cleaning up test data...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_cleanup_test_data',
						nonce: '<?php echo wp_create_nonce( 'llmsat_testing' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							addResult('success', 'Test data cleaned up successfully');
							location.reload();
						} else {
							addResult('error', 'Failed to clean up test data: ' + response.data.message);
						}
					},
					error: function() {
						addResult('error', 'Server error during cleanup');
					}
				});
			}

			function showResults() {
				$('#test-results').show();
			}

			function addResult(type, message) {
				var timestamp = new Date().toLocaleTimeString();
				var resultClass = 'result-' + type;
				var resultHtml = '<div class="result-item ' + resultClass + '">' +
					'<strong>[' + timestamp + ']</strong> ' + message +
					'</div>';
				$('#results-content').append(resultHtml);
			}

			function displayTestResults(results) {
				$('#results-content').empty();
				
				results.forEach(function(result) {
					addResult(result.type, result.message);
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Generate test data.
	 */
	public function generate_test_data() {
		check_ajax_referer( 'llmsat_testing', 'nonce' );

		$size = sanitize_text_field( $_POST['size'] );

		$config = $this->get_test_config( $size );

		$start_time = microtime( true );
		$generated  = $this->create_test_data( $config );
		$end_time   = microtime( true );

		$execution_time = round( ( $end_time - $start_time ), 2 );

		wp_send_json_success(
			array(
				'message'           => sprintf(
					'Generated %d students, %d courses, %d attendance records in %s seconds',
					$config['students'],
					$config['courses'],
					$generated,
					$execution_time
				),
				'execution_time'    => $execution_time,
				'records_generated' => $generated,
			)
		);
	}

	/**
	 * Get test configuration.
	 */
	private function get_test_config( $size ) {
		$configs = array(
			'small'  => array(
				'students' => 100,
				'courses'  => 10,
				'days'     => 30,
			),
			'medium' => array(
				'students' => 500,
				'courses'  => 25,
				'days'     => 60,
			),
			'large'  => array(
				'students' => 1000,
				'courses'  => 50,
				'days'     => 90,
			),
		);

		return $configs[ $size ];
	}

	/**
	 * Create test data.
	 */
	private function create_test_data( $config ) {
		$generated = 0;

		// Create test courses.
		$course_ids = array();
		for ( $i = 1; $i <= $config['courses']; $i++ ) {
			$course_id = wp_insert_post(
				array(
					'post_title'  => 'Test Course ' . $i,
					'post_type'   => 'course',
					'post_status' => 'publish',
				)
			);

			if ( $course_id ) {
				$course_ids[] = $course_id;
			}
		}

		// Create test users.
		$user_ids = array();
		for ( $i = 1; $i <= $config['students']; $i++ ) {
			$user_id = wp_insert_user(
				array(
					'user_login'   => 'test_student_' . $i,
					'user_email'   => 'test_student_' . $i . '@example.com',
					'user_pass'    => 'test_password',
					'display_name' => 'Test Student ' . $i,
				)
			);

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

						$result = $this->db->insert_attendance( $user_id, $course_id, $attendance_date, $attendance_time );

						if ( $result ) {
							++$generated;
						}
					}
				}
			}
		}

		return $generated;
	}

	/**
	 * Run performance test.
	 */
	public function run_performance_test() {
		check_ajax_referer( 'llmsat_testing', 'nonce' );

		$test_type = sanitize_text_field( $_POST['test_type'] );
		$results   = array();

		switch ( $test_type ) {
			case 'query':
				$results = $this->test_query_performance();
				break;
			case 'report':
				$results = $this->test_report_performance();
				break;
			case 'migration':
				$results = $this->test_migration_performance();
				break;
		}

		wp_send_json_success( $results );
	}

	/**
	 * Test query performance.
	 */
	private function test_query_performance() {
		$results = array();

		// Test 1: Single user attendance count.
		$start_time = microtime( true );
		$user_id    = $this->get_random_user_id();
		$course_id  = $this->get_random_course_id();
		$count      = $this->db->get_attendance_count( $user_id, $course_id );
		$end_time   = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Single user query: %d records in %s seconds', $count, round( ( $end_time - $start_time ), 4 ) ),
		);

		// Test 2: Course statistics.
		$start_time = microtime( true );
		$stats      = $this->db->get_course_attendance_stats( $course_id );
		$end_time   = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Course stats query: %d students in %s seconds', $stats['total_students'], round( ( $end_time - $start_time ), 4 ) ),
		);

		// Test 3: Top performers.
		$start_time = microtime( true );
		$performers = $this->db->get_top_performers( $course_id, 10 );
		$end_time   = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Top performers query: %d results in %s seconds', count( $performers ), round( ( $end_time - $start_time ), 4 ) ),
		);

		return $results;
	}

	/**
	 * Test report performance.
	 */
	private function test_report_performance() {
		$results = array();

		$course_id = $this->get_random_course_id();
		$date_from = date( 'Y-m-01' ); // First day of current month.
		$date_to   = date( 'Y-m-t' ); // Last day of current month.

		// Test daily chart data.
		$start_time = microtime( true );
		$daily_data = $this->db->get_chart_data( $course_id, $date_from, $date_to, 'daily' );
		$end_time   = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Daily chart data: %d data points in %s seconds', count( $daily_data ), round( ( $end_time - $start_time ), 4 ) ),
		);

		// Test weekly chart data.
		$start_time  = microtime( true );
		$weekly_data = $this->db->get_chart_data( $course_id, $date_from, $date_to, 'weekly' );
		$end_time    = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Weekly chart data: %d data points in %s seconds', count( $weekly_data ), round( ( $end_time - $start_time ), 4 ) ),
		);

		// Test monthly chart data.
		$start_time   = microtime( true );
		$monthly_data = $this->db->get_chart_data( $course_id, $date_from, $date_to, 'monthly' );
		$end_time     = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Monthly chart data: %d data points in %s seconds', count( $monthly_data ), round( ( $end_time - $start_time ), 4 ) ),
		);

		return $results;
	}

	/**
	 * Test migration performance.
	 */
	private function test_migration_performance() {
		$results = array();

		// Test table statistics query.
		$start_time = microtime( true );
		$stats      = $this->db->get_table_stats();
		$end_time   = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Table statistics query: %d total records in %s seconds', $stats['total_records'], round( ( $end_time - $start_time ), 4 ) ),
		);

		// Test cleanup performance.
		$start_time = microtime( true );
		$deleted    = $this->db->cleanup_old_records( 365 );
		$end_time   = microtime( true );

		$results[] = array(
			'type'    => 'success',
			'message' => sprintf( 'Cleanup query: %d old records deleted in %s seconds', $deleted, round( ( $end_time - $start_time ), 4 ) ),
		);

		return $results;
	}

	/**
	 * Cleanup test data.
	 */
	public function cleanup_test_data() {
		check_ajax_referer( 'llmsat_testing', 'nonce' );

		global $wpdb;

		// Delete test courses.
		$deleted_courses = $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_type = 'course' AND post_title LIKE 'Test Course%'"
		);

		// Delete test users.
		$deleted_users = $wpdb->query(
			"DELETE FROM {$wpdb->users} WHERE user_login LIKE 'test_student_%'"
		);

		// Delete test attendance records.
		$table_name         = $this->db->get_table_name();
		$deleted_attendance = $wpdb->query(
			"DELETE FROM $table_name WHERE user_id IN (
				SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'test_student_%'
			)"
		);

		wp_send_json_success(
			array(
				'message' => sprintf(
					'Cleaned up: %d courses, %d users, %d attendance records',
					$deleted_courses,
					$deleted_users,
					$deleted_attendance
				),
			)
		);
	}

	/**
	 * Get random user ID.
	 */
	private function get_random_user_id() {
		global $wpdb;

		$user_id = $wpdb->get_var(
			"SELECT user_id FROM {$wpdb->prefix}llms_attendance ORDER BY RAND() LIMIT 1"
		);

		return $user_id ?: 1;
	}

	/**
	 * Get random course ID.
	 */
	private function get_random_course_id() {
		global $wpdb;

		$course_id = $wpdb->get_var(
			"SELECT course_id FROM {$wpdb->prefix}llms_attendance ORDER BY RAND() LIMIT 1"
		);

		return $course_id ?: 1;
	}
}
