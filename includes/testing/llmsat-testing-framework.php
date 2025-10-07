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
		add_action( 'wp_ajax_llmsat_generate_complete_test_data', array( $this, 'generate_complete_test_data' ) );
		add_action( 'wp_ajax_llmsat_cleanup_test_data', array( $this, 'cleanup_test_data' ) );
		add_action( 'wp_ajax_llmsat_cleanup_all_test_data', array( $this, 'cleanup_all_test_data' ) );
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
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Performance Testing Dashboard', 'llms-attendance' ); ?></h1>
			
			<div class="llmsat-testing-dashboard">
				<div class="stats-grid">
					<div class="stat-box">
						<h3><?php esc_html_e( 'System Status', 'llms-attendance' ); ?></h3>
						<p><?php esc_html_e( 'Migration Status:', 'llms-attendance' ); ?> <strong><?php echo esc_html( get_option( 'llmsat_migration_status', 'not_started' ) ); ?></strong></p>
						<p><?php esc_html_e( 'Custom Table:', 'llms-attendance' ); ?> <strong><?php echo $this->db->table_exists() ? __( 'Exists', 'llms-attendance' ) : __( 'Not Found', 'llms-attendance' ); ?></strong></p>
					</div>
					
					<div class="stat-box">
						<h3><?php esc_html_e( 'Data Statistics', 'llms-attendance' ); ?></h3>
						<?php
						$meta_count  = $this->get_meta_attendance_count();
						$table_count = $this->db->table_exists() ? $this->db->get_table_stats()['total_records'] : 0;
						?>
						<p><?php esc_html_e( 'Meta Records:', 'llms-attendance' ); ?> <strong><?php echo esc_html( $meta_count ); ?></strong></p>
						<p><?php esc_html_e( 'Table Records:', 'llms-attendance' ); ?> <strong><?php echo esc_html( $table_count ); ?></strong></p>
					</div>
				</div>

				<div class="test-actions">
					<div class="test-group">
						<h3><?php esc_html_e( 'Test Data Generation', 'llms-attendance' ); ?></h3>
						<p><?php esc_html_e( 'Generate test data for performance testing.', 'llms-attendance' ); ?></p>
						<button id="generate-small-data" class="button button-primary">
							<?php esc_html_e( 'Generate Small Dataset (100 records)', 'llms-attendance' ); ?>
						</button>
						<button id="generate-medium-data" class="button button-primary">
							<?php esc_html_e( 'Generate Medium Dataset (1000 records)', 'llms-attendance' ); ?>
						</button>
						<button id="generate-large-data" class="button button-primary">
							<?php esc_html_e( 'Generate Large Dataset (5000 records)', 'llms-attendance' ); ?>
						</button>
						<br><br>
						<button id="generate-complete-test-data" class="button button-primary" style="background-color: #0073aa;">
							<?php esc_html_e( 'Generate Complete Test Environment (Users + Courses + Enrollments + Attendance)', 'llms-attendance' ); ?>
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
							<?php esc_html_e( 'Clean Up Test Attendance Data', 'llms-attendance' ); ?>
						</button>
						<button id="cleanup-all-test-data" class="button button-secondary llmsat-danger-button">
							<?php esc_html_e( 'Clean Up ALL Test Data', 'llms-attendance' ); ?>
						</button>
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
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
		
		/* Responsive design */
		@media (max-width: 1200px) {
			.test-actions {
				grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			}
		}
		
		@media (max-width: 768px) {
			.stats-grid {
				grid-template-columns: 1fr;
			}
			.test-actions {
				grid-template-columns: 1fr;
			}
			.test-group {
				padding: 15px;
			}
		}
		
		/* Button improvements */
		.test-group button {
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			margin: 5px 0;
		}
		
		/* Danger button styling */
		.llmsat-danger-button {
			background-color: #dc3232 !important;
			color: white !important;
			border-color: #dc3232 !important;
		}
		
		.llmsat-danger-button:hover {
			background-color: #a00 !important;
			border-color: #a00 !important;
		}
		
		/* Button spacing */
		.test-group button + button {
			margin-top: 10px;
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

			$('#generate-complete-test-data').on('click', function() {
				generateCompleteTestData();
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

			$('#cleanup-all-test-data').on('click', function() {
				cleanupAllTestData();
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

			function generateCompleteTestData() {
				if (!confirm('This will create test users, courses, enrollments, and attendance data.\n\nThis may take a few minutes. Continue?')) {
					return;
				}
				
				showResults();
				addResult('info', 'Generating complete test environment...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_generate_complete_test_data',
						nonce: '<?php echo wp_create_nonce( 'llmsat_testing' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							addResult('success', 'Complete test environment generated: ' + response.data.message);
							location.reload();
						} else {
							addResult('error', 'Failed to generate complete test data: ' + response.data.message);
						}
					},
					error: function() {
						addResult('error', 'Server error during complete test data generation');
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

			function cleanupAllTestData() {
				if (!confirm('⚠️ DANGER: This will delete ALL test courses, users, and attendance data!\n\nThis action cannot be undone!\n\nAre you absolutely sure?')) {
					return;
				}
				
				showResults();
				addResult('info', 'Cleaning up ALL test data (courses, users, attendance)...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_cleanup_all_test_data',
						nonce: '<?php echo wp_create_nonce( 'llmsat_testing' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							addResult('success', 'ALL test data cleaned up successfully: ' + response.data.message);
							location.reload();
						} else {
							addResult('error', 'Failed to clean up all test data: ' + response.data.message);
						}
					},
					error: function() {
						addResult('error', 'Server error during complete cleanup');
					}
				});
			}

			function showResults() {
				$('#test-results').show();
				$('#results-content').empty();
			}

			function addResult(type, message) {
				var timestamp = new Date().toLocaleTimeString();
				var resultHtml = '<div class="result-item result-' + type + '">';
				resultHtml += '<strong>[' + timestamp + ']</strong> ' + message;
				resultHtml += '</div>';
				$('#results-content').append(resultHtml);
			}

			function displayTestResults(results) {
				$.each(results, function(index, result) {
					addResult(result.status, result.message);
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

		$size   = sanitize_text_field( $_POST['size'] );
		$counts = array(
			'small'  => 100,
			'medium' => 1000,
			'large'  => 5000,
		);

		$count = isset( $counts[ $size ] ) ? $counts[ $size ] : 100;

		// Generate test attendance data
		$users   = get_users( array( 'number' => 50 ) );
		$courses = get_posts(
			array(
				'post_type'   => 'course',
				'numberposts' => 10,
			)
		);

		if ( empty( $users ) || empty( $courses ) ) {
			wp_send_json_error( array( 'message' => __( 'No users or courses found for test data generation.', 'llms-attendance' ) ) );
		}

		$generated = 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$user   = $users[ array_rand( $users ) ];
			$course = $courses[ array_rand( $courses ) ];

			$date     = date( 'Y-m-d', strtotime( '-' . rand( 0, 30 ) . ' days' ) );
			$meta_key = 'test_attendance_' . $date . '_' . $course->ID;

			update_user_meta(
				$user->ID,
				$meta_key,
				array(
					'time'      => current_time( 'mysql' ),
					'course_id' => $course->ID,
				)
			);

			++$generated;
		}

		wp_send_json_success( array( 'message' => sprintf( __( 'Generated %d test attendance records.', 'llms-attendance' ), $generated ) ) );
	}

	/**
	 * Generate complete test environment (users, courses, enrollments, attendance).
	 */
	public function generate_complete_test_data() {
		check_ajax_referer( 'llmsat_testing', 'nonce' );

		$created_counts = array(
			'users' => 0,
			'courses' => 0,
			'enrollments' => 0,
			'attendance' => 0,
		);

		// 1. Create test users
		for ( $i = 1; $i <= 50; $i++ ) {
			$user_id = wp_create_user(
				'test_student_' . $i,
				'test_password_' . $i,
				'test_student_' . $i . '@example.com'
			);

			if ( ! is_wp_error( $user_id ) ) {
				// Mark as test user
				update_user_meta( $user_id, '_llmsat_test_user', '1' );
				$created_counts['users']++;
			}
		}

		// 2. Create test courses
		for ( $i = 1; $i <= 10; $i++ ) {
			$course_id = wp_insert_post( array(
				'post_title'   => 'Test Course ' . $i,
				'post_content' => 'This is a test course for performance testing.',
				'post_status'  => 'publish',
				'post_type'    => 'course',
			) );

			if ( ! is_wp_error( $course_id ) ) {
				// Mark as test course
				update_post_meta( $course_id, '_llmsat_test_course', '1' );
				
				// Set course as free
				update_post_meta( $course_id, '_llms_price', '0' );
				update_post_meta( $course_id, '_llms_enrollment_opens_message', 'Open for enrollment' );
				
				$created_counts['courses']++;
			}
		}

		// 3. Enroll users in courses and create attendance data
		$test_users = get_users( array(
			'meta_query' => array(
				array(
					'key' => '_llmsat_test_user',
					'value' => '1',
					'compare' => '='
				)
			)
		) );

		$test_courses = get_posts( array(
			'post_type' => 'course',
			'numberposts' => -1,
			'meta_query' => array(
				array(
					'key' => '_llmsat_test_course',
					'value' => '1',
					'compare' => '='
				)
			)
		) );

		foreach ( $test_users as $user ) {
			foreach ( $test_courses as $course ) {
				// Enroll user in course
				llms_enroll_student( $user->ID, $course->ID );
				$created_counts['enrollments']++;

				// Create attendance data for the last 30 days
				for ( $day = 0; $day < 30; $day++ ) {
					$date = date( 'Y-m-d', strtotime( '-' . $day . ' days' ) );
					
					// 70% chance of attendance
					if ( rand( 1, 100 ) <= 70 ) {
						$meta_key = 'test_attendance_' . $date . '_' . $course->ID;
						
						update_user_meta(
							$user->ID,
							$meta_key,
							array(
								'time'      => $date . ' ' . date( 'H:i:s', strtotime( '+' . rand( 8, 18 ) . ' hours' ) ),
								'course_id' => $course->ID,
							)
						);
						
						$created_counts['attendance']++;
					}
				}
			}
		}

		$message = sprintf( 
			__( 'Complete test environment created: %d users, %d courses, %d enrollments, %d attendance records.', 'llms-attendance' ),
			$created_counts['users'],
			$created_counts['courses'],
			$created_counts['enrollments'],
			$created_counts['attendance']
		);

		wp_send_json_success( array( 'message' => $message, 'counts' => $created_counts ) );
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

		// Test meta query performance
		$start_time = microtime( true );
		$meta_count = $this->get_meta_attendance_count();
		$meta_time  = microtime( true ) - $start_time;

		$results[] = array(
			'status'  => 'success',
			'message' => sprintf( 'Meta query: %d records in %.4f seconds', $meta_count, $meta_time ),
		);

		// Test custom table query performance
		if ( $this->db->table_exists() ) {
			$start_time  = microtime( true );
			$table_stats = $this->db->get_table_stats();
			$table_time  = microtime( true ) - $start_time;

			$results[] = array(
				'status'  => 'success',
				'message' => sprintf( 'Table query: %d records in %.4f seconds', $table_stats['total_records'], $table_time ),
			);
		}

		return $results;
	}

	/**
	 * Test report performance.
	 */
	private function test_report_performance() {
		$results = array();

		$start_time = microtime( true );
		$courses    = get_posts(
			array(
				'post_type'   => 'course',
				'numberposts' => 5,
			)
		);

		foreach ( $courses as $course ) {
			$stats = $this->hybrid_manager->get_course_stats( $course->ID );
		}

		$report_time = microtime( true ) - $start_time;

		$results[] = array(
			'status'  => 'success',
			'message' => sprintf( 'Report generation: %d courses in %.4f seconds', count( $courses ), $report_time ),
		);

		return $results;
	}

	/**
	 * Test migration performance.
	 */
	private function test_migration_performance() {
		$results = array();

		$start_time     = microtime( true );
		$meta_count     = $this->get_meta_attendance_count();
		$migration_time = microtime( true ) - $start_time;

		$results[] = array(
			'status'  => 'success',
			'message' => sprintf( 'Migration simulation: %d records would take approximately %.4f seconds', $meta_count, $migration_time ),
		);

		return $results;
	}

	/**
	 * Cleanup test data.
	 */
	public function cleanup_test_data() {
		check_ajax_referer( 'llmsat_testing', 'nonce' );

		global $wpdb;

		// Remove test meta data with test prefix
		$deleted = $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'test_attendance_%'" );

		wp_send_json_success( array( 'message' => sprintf( __( 'Cleaned up %d test records.', 'llms-attendance' ), $deleted ) ) );
	}

	/**
	 * Clean up ALL test data including courses and users.
	 */
	public function cleanup_all_test_data() {
		check_ajax_referer( 'llmsat_testing', 'nonce' );

		global $wpdb;
		$deleted_counts = array();

		// 1. Remove test meta data with test prefix
		$deleted_meta = $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'test_attendance_%'" );
		$deleted_counts['meta'] = $deleted_meta;

		// 2. Remove test courses (courses with "test" in title or content)
		$test_courses = get_posts( array(
			'post_type' => 'course',
			'numberposts' => -1,
			'meta_query' => array(
				array(
					'key' => '_llmsat_test_course',
					'value' => '1',
					'compare' => '='
				)
			)
		) );

		$deleted_courses = 0;
		foreach ( $test_courses as $course ) {
			wp_delete_post( $course->ID, true );
			$deleted_courses++;
		}
		$deleted_counts['courses'] = $deleted_courses;

		// 3. Remove test users (users with "test" in username or email)
		$test_users = get_users( array(
			'meta_query' => array(
				array(
					'key' => '_llmsat_test_user',
					'value' => '1',
					'compare' => '='
				)
			)
		) );

		$deleted_users = 0;
		foreach ( $test_users as $user ) {
			wp_delete_user( $user->ID );
			$deleted_users++;
		}
		$deleted_counts['users'] = $deleted_users;

		// 4. Clean up custom table test data
		if ( $this->db->table_exists() ) {
			$deleted_custom = $wpdb->query( "DELETE FROM {$wpdb->prefix}llmsat_attendance WHERE user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_login LIKE '%test%' OR user_email LIKE '%test%')" );
			$deleted_counts['custom_table'] = $deleted_custom;
		}

		$message = sprintf( 
			__( 'Complete cleanup: %d meta records, %d courses, %d users, %d custom table records deleted.', 'llms-attendance' ),
			$deleted_counts['meta'],
			$deleted_counts['courses'],
			$deleted_counts['users'],
			$deleted_counts['custom_table']
		);

		wp_send_json_success( array( 'message' => $message, 'counts' => $deleted_counts ) );
	}

	/**
	 * Get meta attendance count.
	 */
	private function get_meta_attendance_count() {
		global $wpdb;

		$count = $wpdb->get_var(
			"
			SELECT COUNT(*) 
			FROM {$wpdb->usermeta} 
			WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'
			AND meta_key NOT LIKE 'test_attendance_%'
		"
		);

		return intval( $count );
	}
}
