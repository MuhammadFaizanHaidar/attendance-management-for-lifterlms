<?php
/**
 * Data Migration Utility for Attendance Management For LifterLMS
 *
 * @package  Attendance Management For LifterLMS
 * @author   Muhammad Faizan Haidar
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration utility class.
 */
class LLMS_AT_Migration {

	/**
	 * Database instance.
	 */
	private $db;

	/**
	 * Initialize migration.
	 */
	public function __construct() {
		$this->db = new LLMS_AT_Database();
		add_action( 'admin_menu', array( $this, 'add_migration_page' ) );
		add_action( 'wp_ajax_llmsat_start_migration', array( $this, 'start_migration' ) );
		add_action( 'wp_ajax_llmsat_check_migration_status', array( $this, 'check_migration_status' ) );
		add_action( 'wp_ajax_llmsat_cleanup_meta', array( $this, 'cleanup_meta_data' ) );
		add_action( 'wp_ajax_llmsat_force_cleanup', array( $this, 'force_cleanup_meta_data' ) );
	}

	/**
	 * Add migration page to admin menu.
	 */
	public function add_migration_page() {
		add_submenu_page(
			'edit.php?post_type=course',
			__( 'Attendance Migration', 'llms-attendance' ),
			__( 'Attendance Migration', 'llms-attendance' ),
			'manage_options',
			'llms-attendance-migration',
			array( $this, 'migration_page' )
		);
	}

	/**
	 * Display migration page.
	 */
	public function migration_page() {
		$meta_stats       = $this->get_meta_stats();
		$table_stats      = $this->db->get_table_stats();
		$migration_status = get_option( 'llmsat_migration_status', 'not_started' );

		// Ensure we have valid stats arrays.
		if ( ! is_array( $meta_stats ) ) {
			$meta_stats = array(
				'total_records'  => 0,
				'unique_users'   => 0,
				'unique_courses' => 0,
			);
		}

		if ( ! is_array( $table_stats ) ) {
			$table_stats = array(
				'total_records'  => 0,
				'unique_users'   => 0,
				'unique_courses' => 0,
			);
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Attendance Data Migration', 'llms-attendance' ); ?></h1>
			
			<div class="notice notice-info">
				<p><strong><?php esc_html_e( 'Important:', 'llms-attendance' ); ?></strong> 
				<?php esc_html_e( 'This migration will move your attendance data from WordPress user meta to a custom database table for better performance and scalability.', 'llms-attendance' ); ?></p>
			</div>

			<?php if ( 'not_started' === $migration_status && $meta_stats['total_records'] > 0 ) : ?>
			<div class="notice notice-warning">
				<h3><?php esc_html_e( 'Migration Required', 'llms-attendance' ); ?></h3>
				<p><?php esc_html_e( 'You have attendance data that needs to be migrated to the new database structure. Follow these steps:', 'llms-attendance' ); ?></p>
				<ol style="margin-left: 20px;">
					<li><?php esc_html_e( 'Review the statistics below to see how many records will be migrated.', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Click the "Start Migration" button to begin the process.', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Wait for the migration to complete (you\'ll see a progress bar).', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'After migration completes, click "Clean Up Meta Data" to remove old data (optional but recommended).', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Verify your attendance data is working correctly in your courses and reports.', 'llms-attendance' ); ?></li>
				</ol>
				<p><strong><?php esc_html_e( 'Note:', 'llms-attendance' ); ?></strong> <?php esc_html_e( 'The migration is safe and non-destructive. Your data will remain in both locations until you choose to clean up. We recommend backing up your database before proceeding.', 'llms-attendance' ); ?></p>
			</div>
			<?php endif; ?>

			<div class="llmsat-migration-stats">
				<h2><?php esc_html_e( 'Migration Statistics', 'llms-attendance' ); ?></h2>
				
				<div class="stats-grid">
					<div class="stat-box">
						<h3><?php esc_html_e( 'Meta Storage', 'llms-attendance' ); ?></h3>
						<p><strong><?php echo esc_html( $meta_stats['total_records'] ); ?></strong> <?php esc_html_e( 'attendance records', 'llms-attendance' ); ?></p>
						<p><strong><?php echo esc_html( $meta_stats['unique_users'] ); ?></strong> <?php esc_html_e( 'unique students', 'llms-attendance' ); ?></p>
						<p><strong><?php echo esc_html( $meta_stats['unique_courses'] ); ?></strong> <?php esc_html_e( 'unique courses', 'llms-attendance' ); ?></p>
						
						<?php if ( $meta_stats['total_records'] > 0 ) : ?>
						<div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
							<h4>Debug: Remaining Meta Keys</h4>
							<?php
							global $wpdb;
							$remaining_keys = $wpdb->get_results(
								"SELECT meta_key, user_id, meta_value FROM {$wpdb->usermeta} 
								WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$' 
								ORDER BY meta_key LIMIT 10"
							);

							if ( $remaining_keys ) {
								echo '<ul>';
								foreach ( $remaining_keys as $key ) {
									echo '<li><strong>' . esc_html( $key->meta_key ) . '</strong> (User: ' . esc_html( $key->user_id ) . ', Value: ' . esc_html( $key->meta_value ) . ')</li>';
								}
								echo '</ul>';

								$total_remaining = $wpdb->get_var(
									"SELECT COUNT(*) FROM {$wpdb->usermeta} 
									WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'"
								);

								if ( $total_remaining > 10 ) {
									echo '<p><em>... and ' . esc_html( $total_remaining - 10 ) . ' more records</em></p>';
								}
							}
							?>
						</div>
						<?php endif; ?>
					</div>
					
					<div class="stat-box">
						<h3><?php esc_html_e( 'Custom Table', 'llms-attendance' ); ?></h3>
						<p><strong><?php echo esc_html( $table_stats['total_records'] ); ?></strong> <?php esc_html_e( 'attendance records', 'llms-attendance' ); ?></p>
						<p><strong><?php echo esc_html( $table_stats['unique_users'] ); ?></strong> <?php esc_html_e( 'unique students', 'llms-attendance' ); ?></p>
						<p><strong><?php echo esc_html( $table_stats['unique_courses'] ); ?></strong> <?php esc_html_e( 'unique courses', 'llms-attendance' ); ?></p>
					</div>
				</div>
			</div>

			<div class="llmsat-migration-controls">
				<h2><?php esc_html_e( 'Migration Controls', 'llms-attendance' ); ?></h2>
				
				<div id="migration-status">
					<p><strong><?php esc_html_e( 'Status:', 'llms-attendance' ); ?></strong> 
					<span id="status-text"><?php echo esc_html( ucfirst( $migration_status ) ); ?></span></p>
					<p><strong><?php esc_html_e( 'Debug Info:', 'llms-attendance' ); ?></strong> Migration Status = "<?php echo esc_html( $migration_status ); ?>"</p>
				</div>

				<div id="migration-progress" style="display: none;">
					<div class="progress-bar">
						<div class="progress-fill" id="progress-fill"></div>
					</div>
					<p id="progress-text"><?php esc_html_e( 'Preparing migration...', 'llms-attendance' ); ?></p>
				</div>

				<div class="migration-actions">
					<?php if ( 'completed' !== $migration_status && 'cleanup_completed' !== $migration_status ) : ?>
						<button id="start-migration" class="button button-primary">
							<?php esc_html_e( 'Start Migration', 'llms-attendance' ); ?>
						</button>
					<?php elseif ( 'completed' === $migration_status ) : ?>
						<button id="cleanup-meta" class="button button-secondary">
							<?php esc_html_e( 'Clean Up Meta Data', 'llms-attendance' ); ?>
						</button>
					<?php elseif ( 'cleanup_completed' === $migration_status ) : ?>
						<p class="notice notice-success">
							<?php esc_html_e( 'Migration and cleanup completed successfully! All data is now stored in the custom table.', 'llms-attendance' ); ?>
						</p>
						
						<?php if ( $meta_stats['total_records'] > 0 ) : ?>
						<div class="notice notice-warning">
							<p><strong><?php esc_html_e( 'Warning:', 'llms-attendance' ); ?></strong> 
							<?php esc_html_e( 'Some meta data is still present. You can force cleanup to remove it.', 'llms-attendance' ); ?></p>
							<button type="button" id="force-cleanup" class="button button-secondary">
								<?php esc_html_e( 'Force Cleanup Meta Data', 'llms-attendance' ); ?>
							</button>
						</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="llmsat-migration-info">
				<h2><?php esc_html_e( 'Migration Benefits', 'llms-attendance' ); ?></h2>
				<ul>
					<li><?php esc_html_e( 'Improved performance for large datasets', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Better database indexing and query optimization', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Enhanced reporting capabilities', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Reduced memory usage', 'llms-attendance' ); ?></li>
					<li><?php esc_html_e( 'Better scalability for thousands of students', 'llms-attendance' ); ?></li>
				</ul>
			</div>
		</div>

		<style>
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
		.progress-bar {
			width: 100%;
			height: 20px;
			background: #f0f0f0;
			border-radius: 10px;
			overflow: hidden;
			margin: 10px 0;
		}
		.progress-fill {
			height: 100%;
			background: #0073aa;
			width: 0%;
			transition: width 0.3s ease;
		}
		.migration-actions {
			margin: 20px 0;
		}
		.llmsat-migration-info ul {
			list-style-type: disc;
			margin-left: 20px;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			$('#start-migration').on('click', function() {
				startMigration();
			});

			$('#cleanup-meta').on('click', function() {
				cleanupMetaData();
			});

			$('#force-cleanup').on('click', function() {
				forceCleanupMetaData();
			});

			$('#reset-migration').on('click', function() {
				resetMigration();
			});

			$('#create-test-data').on('click', function() {
				createTestData();
			});

			$('#enroll-students').on('click', function() {
				enrollStudentsInCoursesWithAttendance();
			});

			$('#clear-test-data').on('click', function() {
				clearTestData();
			});

			$('#create-table').on('click', function() {
				createTable();
			});

			function startMigration() {
				$('#migration-progress').show();
				$('#start-migration').prop('disabled', true);
				
				performMigration(0);
			}

			function performMigration(offset) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_start_migration',
						offset: offset,
						nonce: '<?php echo wp_create_nonce( 'llmsat_migration' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							updateProgress(response.data.progress, response.data.message);
							
							if (response.data.completed) {
								$('#status-text').text('Completed');
								$('#start-migration').hide();
								$('#cleanup-meta').show();
							} else {
								performMigration(response.data.next_offset);
							}
						} else {
							alert('Migration failed: ' + response.data.message);
						}
					},
					error: function() {
						alert('Migration failed due to server error');
					}
				});
			}

			function updateProgress(percent, message) {
				$('#progress-fill').css('width', percent + '%');
				$('#progress-text').text(message);
			}

			function cleanupMetaData() {
				if (!confirm('Are you sure you want to delete all meta data? This action cannot be undone and will permanently remove all attendance data from user meta tables.')) {
					return;
				}

				$('#cleanup-meta').prop('disabled', true).text('Cleaning up...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_cleanup_meta',
						nonce: '<?php echo wp_create_nonce( 'llmsat_migration' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							$('#cleanup-meta').hide();
							$('#status-text').text('Cleanup Completed');
							// Refresh the page to update stats
							location.reload();
						} else {
							alert('Cleanup failed: ' + response.data.message);
							$('#cleanup-meta').prop('disabled', false).text('Clean Up Meta Data');
						}
					},
					error: function() {
						alert('Cleanup failed due to server error');
						$('#cleanup-meta').prop('disabled', false).text('Clean Up Meta Data');
					}
				});
			}

			function forceCleanupMetaData() {
				if (!confirm('Are you sure you want to force cleanup all remaining meta data? This action cannot be undone and will permanently remove all attendance data from user meta tables.')) {
					return;
				}

				$('#force-cleanup').prop('disabled', true).text('Force Cleaning...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'llmsat_force_cleanup',
						nonce: '<?php echo wp_create_nonce( 'llmsat_migration' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							$('#force-cleanup').hide();
							// Refresh the page to update stats
							location.reload();
						} else {
							alert('Force cleanup failed: ' + response.data.message);
							$('#force-cleanup').prop('disabled', false).text('Force Cleanup Meta Data');
						}
					},
					error: function() {
						alert('Force cleanup failed due to server error');
						$('#force-cleanup').prop('disabled', false).text('Force Cleanup Meta Data');
					}
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Start migration process.
	 */
	public function start_migration() {
		check_ajax_referer( 'llmsat_migration', 'nonce' );

		$offset     = intval( $_POST['offset'] );
		$batch_size = 100;

		// Get meta records to migrate.
		$meta_records = $this->get_meta_records_batch( $offset, $batch_size );

		if ( empty( $meta_records ) ) {
			update_option( 'llmsat_migration_status', 'completed' );

			// Enable custom table usage after successful migration.
			$hybrid_manager = new LLMS_AT_Hybrid_Manager();
			$hybrid_manager->enable_custom_table();

			wp_send_json_success(
				array(
					'completed' => true,
					'progress'  => 100,
					'message'   => 'Migration completed successfully! Custom table is now active.',
				)
			);
		}

		// Migrate batch.
		$migrated = $this->migrate_batch( $meta_records );

		// Calculate progress.
		$total_meta = $this->get_total_meta_records();
		$progress   = min( 100, ( ( $offset + $batch_size ) / $total_meta ) * 100 );

		wp_send_json_success(
			array(
				'completed'   => false,
				'progress'    => $progress,
				'message'     => sprintf( 'Migrated %d records...', $migrated ),
				'next_offset' => $offset + $batch_size,
			)
		);
	}

	/**
	 * Get meta records batch.
	 */
	private function get_meta_records_batch( $offset, $limit ) {
		global $wpdb;

		$meta_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key, meta_value 
				FROM {$wpdb->usermeta} 
				WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'
				ORDER BY user_id, meta_key 
				LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);

		return $meta_records;
	}

	/**
	 * Migrate batch of records.
	 */
	private function migrate_batch( $meta_records ) {
		$migrated = 0;

		foreach ( $meta_records as $record ) {
			$meta_key   = $record['meta_key'];
			$meta_value = maybe_unserialize( $record['meta_value'] );

			// Parse meta key: Y-m-d-course_id.
			if ( preg_match( '/^(\d{4}-\d{1,2}-\d{1,2})-(\d+)$/', $meta_key, $matches ) ) {
				$attendance_date = $matches[1];
				$course_id       = $matches[2];
				$user_id         = $record['user_id'];

				// Normalize date format to Y-m-d (with leading zeros).
				$attendance_date = date( 'Y-m-d', strtotime( $attendance_date ) );

				// Extract attendance time from meta value.
				$attendance_time = isset( $meta_value['time'] ) ? $meta_value['time'] : $attendance_date . ' 00:00:00';

				// Insert into custom table.
				$result = $this->db->insert_attendance( $user_id, $course_id, $attendance_date, $attendance_time );

				if ( $result ) {
					++$migrated;
				}
			}
		}

		return $migrated;
	}

	/**
	 * Get total meta records count.
	 */
	private function get_total_meta_records() {
		global $wpdb;

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'"
		);

		return intval( $count );
	}

	/**
	 * Get meta storage statistics.
	 */
	private function get_meta_stats() {
		global $wpdb;

		$stats = $wpdb->get_row(
			"SELECT 
				COUNT(*) as total_records,
				COUNT(DISTINCT user_id) as unique_users,
				COUNT(DISTINCT SUBSTRING_INDEX(meta_key, '-', -1)) as unique_courses
			FROM {$wpdb->usermeta} 
			WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'",
			ARRAY_A
		);

		// Ensure we return a valid array.
		if ( ! is_array( $stats ) ) {
			return array(
				'total_records'  => 0,
				'unique_users'   => 0,
				'unique_courses' => 0,
			);
		}

		return $stats;
	}

	/**
	 * Clean up meta data after successful migration.
	 */
	public function cleanup_meta_data() {
		check_ajax_referer( 'llmsat_migration', 'nonce' );

		// Check if migration is completed
		$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
		if ( 'completed' !== $migration_status ) {
			wp_send_json_error( array( 'message' => 'Migration must be completed before cleaning up meta data.' ) );
		}

		// Get meta records to delete
		global $wpdb;

		// Delete attendance meta records
		$deleted_attendance = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'"
		);

		// Delete monthly count meta records
		$deleted_monthly = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]+$'"
		);

		// Delete first mark meta records
		$deleted_first_mark = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'first_mark-%'"
		);

		$total_deleted = $deleted_attendance + $deleted_monthly + $deleted_first_mark;

		// Update migration status to indicate cleanup is done
		update_option( 'llmsat_migration_status', 'cleanup_completed' );

		// Enable custom table usage after cleanup
		update_option( 'llmsat_use_custom_table', true );

		wp_send_json_success(
			array(
				'message'            => sprintf( 'Meta data cleanup completed! Deleted %d meta records.', $total_deleted ),
				'deleted_attendance' => $deleted_attendance,
				'deleted_monthly'    => $deleted_monthly,
				'deleted_first_mark' => $deleted_first_mark,
				'total_deleted'      => $total_deleted,
			)
		);
	}

	/**
	 * Force cleanup meta data (bypasses migration status check).
	 */
	public function force_cleanup_meta_data() {
		check_ajax_referer( 'llmsat_migration', 'nonce' );

		// Get meta records to delete
		global $wpdb;

		// Delete attendance meta records
		$deleted_attendance = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}-[0-9]+$'"
		);

		// Delete monthly count meta records
		$deleted_monthly = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key REGEXP '^[0-9]{4}-[0-9]{1,2}-[0-9]+$'"
		);

		// Delete first mark meta records
		$deleted_first_mark = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'first_mark-%'"
		);

		$total_deleted = $deleted_attendance + $deleted_monthly + $deleted_first_mark;

		wp_send_json_success(
			array(
				'message'            => sprintf( 'Force cleanup completed! Deleted %d meta records.', $total_deleted ),
				'deleted_attendance' => $deleted_attendance,
				'deleted_monthly'    => $deleted_monthly,
				'deleted_first_mark' => $deleted_first_mark,
				'total_deleted'      => $total_deleted,
			)
		);
	}
}
