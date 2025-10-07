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
		$meta_stats = $this->get_meta_stats();
		$table_stats = $this->db->get_table_stats();
		$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
		
		// Ensure we have valid stats arrays.
		if ( ! is_array( $meta_stats ) ) {
			$meta_stats = array(
				'total_records' => 0,
				'unique_users' => 0,
				'unique_courses' => 0,
			);
		}
		
		if ( ! is_array( $table_stats ) ) {
			$table_stats = array(
				'total_records' => 0,
				'unique_users' => 0,
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

			<div class="llmsat-migration-stats">
				<h2><?php esc_html_e( 'Migration Statistics', 'llms-attendance' ); ?></h2>
				
				<div class="stats-grid">
					<div class="stat-box">
						<h3><?php esc_html_e( 'Meta Storage', 'llms-attendance' ); ?></h3>
						<p><strong><?php echo esc_html( $meta_stats['total_records'] ); ?></strong> <?php esc_html_e( 'attendance records', 'llms-attendance' ); ?></p>
						<p><strong><?php echo esc_html( $meta_stats['unique_users'] ); ?></strong> <?php esc_html_e( 'unique students', 'llms-attendance' ); ?></p>
						<p><strong><?php echo esc_html( $meta_stats['unique_courses'] ); ?></strong> <?php esc_html_e( 'unique courses', 'llms-attendance' ); ?></p>
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
				</div>

				<div id="migration-progress" style="display: none;">
					<div class="progress-bar">
						<div class="progress-fill" id="progress-fill"></div>
					</div>
					<p id="progress-text"><?php esc_html_e( 'Preparing migration...', 'llms-attendance' ); ?></p>
				</div>

				<div class="migration-actions">
					<?php if ( 'completed' !== $migration_status ) : ?>
						<button id="start-migration" class="button button-primary">
							<?php esc_html_e( 'Start Migration', 'llms-attendance' ); ?>
						</button>
					<?php else : ?>
						<button id="cleanup-meta" class="button button-secondary">
							<?php esc_html_e( 'Clean Up Meta Data', 'llms-attendance' ); ?>
						</button>
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
		});
		</script>
		<?php
	}

	/**
	 * Start migration process.
	 */
	public function start_migration() {
		check_ajax_referer( 'llmsat_migration', 'nonce' );

		$offset = intval( $_POST['offset'] );
		$batch_size = 100;

		// Get meta records to migrate.
		$meta_records = $this->get_meta_records_batch( $offset, $batch_size );

		if ( empty( $meta_records ) ) {
			update_option( 'llmsat_migration_status', 'completed' );
			wp_send_json_success( array(
				'completed' => true,
				'progress' => 100,
				'message' => 'Migration completed successfully!'
			) );
		}

		// Migrate batch.
		$migrated = $this->migrate_batch( $meta_records );

		// Calculate progress.
		$total_meta = $this->get_total_meta_records();
		$progress = min( 100, ( ( $offset + $batch_size ) / $total_meta ) * 100 );

		wp_send_json_success( array(
			'completed' => false,
			'progress' => $progress,
			'message' => sprintf( 'Migrated %d records...', $migrated ),
			'next_offset' => $offset + $batch_size
		) );
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
				WHERE meta_key LIKE %s 
				ORDER BY user_id, meta_key 
				LIMIT %d OFFSET %d",
				'%-%-%-%',
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
			$meta_key = $record['meta_key'];
			$meta_value = maybe_unserialize( $record['meta_value'] );

			// Parse meta key: Y-m-d-course_id.
			if ( preg_match( '/^(\d{4}-\d{2}-\d{2})-(\d+)$/', $meta_key, $matches ) ) {
				$attendance_date = $matches[1];
				$course_id = $matches[2];
				$user_id = $record['user_id'];

				// Extract attendance time from meta value.
				$attendance_time = isset( $meta_value['time'] ) ? $meta_value['time'] : $attendance_date . ' 00:00:00';

				// Insert into custom table.
				$result = $this->db->insert_attendance( $user_id, $course_id, $attendance_date, $attendance_time );
				
				if ( $result ) {
					$migrated++;
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
			"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE '%-%-%-%'"
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
			WHERE meta_key LIKE 'llmsat_attendance_%'",
			ARRAY_A
		);

		// Ensure we return a valid array.
		if ( ! is_array( $stats ) ) {
			return array(
				'total_records' => 0,
				'unique_users' => 0,
				'unique_courses' => 0,
			);
		}

		return $stats;
	}
}
