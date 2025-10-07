<?php
/**
 * Database Schema and Management for Attendance Management For LifterLMS
 *
 * @package  Attendance Management For LifterLMS
 * @author   Muhammad Faizan Haidar
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database management class for attendance data.
 */
class LLMS_AT_Database {

	/**
	 * Table name for attendance records.
	 */
	const TABLE_NAME = 'llms_attendance';

	/**
	 * Current database version.
	 */
	const DB_VERSION = '2.0';

	/**
	 * Initialize database operations.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'check_database_version' ) );
		add_action( 'wp_ajax_llmsat_migrate_data', array( $this, 'migrate_from_meta' ) );
	}

	/**
	 * Check if database needs updating.
	 */
	public function check_database_version() {
		$installed_version = get_option( 'llmsat_db_version', '1.0' );
		
		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			$this->create_tables();
			update_option( 'llmsat_db_version', self::DB_VERSION );
		}
	}

	/**
	 * Create attendance table.
	 */
	public function create_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		error_log( 'LLMS Attendance: Creating table - ' . $table_name );

		$charset_collate = $wpdb->get_charset_collate();
		error_log( 'LLMS Attendance: Charset collate - ' . $charset_collate );

		$sql = "CREATE TABLE $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			course_id BIGINT UNSIGNED NOT NULL,
			attendance_date DATE NOT NULL,
			attendance_time DATETIME NOT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent TEXT DEFAULT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_daily_attendance (user_id, course_id, attendance_date),
			INDEX idx_user_course (user_id, course_id),
			INDEX idx_course_date (course_id, attendance_date),
			INDEX idx_attendance_date (attendance_date),
			INDEX idx_user_date (user_id, attendance_date),
			INDEX idx_created_at (created_at)
		) $charset_collate;";

		error_log( 'LLMS Attendance: SQL to execute - ' . $sql );

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		error_log( 'LLMS Attendance: About to call dbDelta...' );
		
		$result = dbDelta( $sql );
		error_log( 'LLMS Attendance: dbDelta result - ' . print_r( $result, true ) );

		// Check if table was actually created.
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		error_log( 'LLMS Attendance: Table exists check - ' . ( $table_exists ? 'YES' : 'NO' ) );

		// Log table creation.
		error_log( 'LLMS Attendance: Custom table created successfully' );
	}

	/**
	 * Get table name with prefix.
	 */
	public function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Check if the attendance table exists.
	 *
	 * @return bool
	 */
	public function table_exists() {
		global $wpdb;
		$table_name = $this->get_table_name();
		
		$result = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) );
		
		return $result === $table_name;
	}

	/**
	 * Insert attendance record.
	 */
	public function insert_attendance( $user_id, $course_id, $attendance_date = null, $attendance_time = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		// Use current date/time if not provided.
		if ( ! $attendance_date ) {
			$attendance_date = current_time( 'Y-m-d' );
		}
		if ( ! $attendance_time ) {
			$attendance_time = current_time( 'mysql' );
		}

		// Get client IP and user agent.
		$ip_address = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'         => $user_id,
				'course_id'       => $course_id,
				'attendance_date' => $attendance_date,
				'attendance_time' => $attendance_time,
				'ip_address'      => $ip_address,
				'user_agent'      => $user_agent,
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Check if user has attendance for specific date.
	 */
	public function has_attendance( $user_id, $course_id, $attendance_date = null ) {
		global $wpdb;

		if ( ! $attendance_date ) {
			$attendance_date = current_time( 'Y-m-d' );
		}

		$table_name = $this->get_table_name();

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name 
				WHERE user_id = %d AND course_id = %d AND attendance_date = %s",
				$user_id,
				$course_id,
				$attendance_date
			)
		);

		return $count > 0;
	}

	/**
	 * Get attendance count for user in date range.
	 */
	public function get_attendance_count( $user_id, $course_id, $date_from = null, $date_to = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$where_conditions = array(
			$wpdb->prepare( 'user_id = %d', $user_id ),
			$wpdb->prepare( 'course_id = %d', $course_id ),
		);

		if ( $date_from ) {
			$where_conditions[] = $wpdb->prepare( 'attendance_date >= %s', $date_from );
		}
		if ( $date_to ) {
			$where_conditions[] = $wpdb->prepare( 'attendance_date <= %s', $date_to );
		}

		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name $where_clause"
		);

		return intval( $count );
	}

	/**
	 * Get attendance statistics for course.
	 */
	public function get_course_attendance_stats( $course_id, $date_from = null, $date_to = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$where_conditions = array(
			$wpdb->prepare( 'course_id = %d', $course_id ),
		);

		if ( $date_from ) {
			$where_conditions[] = $wpdb->prepare( 'attendance_date >= %s', $date_from );
		}
		if ( $date_to ) {
			$where_conditions[] = $wpdb->prepare( 'attendance_date <= %s', $date_to );
		}

		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );

		$stats = $wpdb->get_row(
			"SELECT 
				COUNT(DISTINCT user_id) as total_students,
				COUNT(DISTINCT CASE WHEN attendance_date = CURDATE() THEN user_id END) as present_today,
				COUNT(DISTINCT CASE WHEN attendance_date >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN user_id END) as present_this_month,
				COUNT(*) as total_attendance_records
			FROM $table_name $where_clause",
			ARRAY_A
		);

		return $stats;
	}

	/**
	 * Get top performing students.
	 */
	public function get_top_performers( $course_id, $limit = 5, $date_from = null, $date_to = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$where_conditions = array(
			$wpdb->prepare( 'course_id = %d', $course_id ),
		);

		if ( $date_from ) {
			$where_conditions[] = $wpdb->prepare( 'attendance_date >= %s', $date_from );
		}
		if ( $date_to ) {
			$where_conditions[] = $wpdb->prepare( 'attendance_date <= %s', $date_to );
		}

		$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );

		$performers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					user_id,
					COUNT(*) as attendance_count,
					COUNT(DISTINCT attendance_date) as unique_days,
					MIN(attendance_date) as first_attendance,
					MAX(attendance_date) as last_attendance
				FROM $table_name 
				$where_clause
				GROUP BY user_id
				ORDER BY attendance_count DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $performers;
	}

	/**
	 * Get attendance data for charts.
	 */
	public function get_chart_data( $course_id, $date_from, $date_to, $period = 'daily' ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$date_format = $this->get_date_format( $period );

		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					DATE_FORMAT(attendance_date, %s) as period_label,
					COUNT(DISTINCT user_id) as present_students,
					COUNT(*) as total_records
				FROM $table_name 
				WHERE course_id = %d 
				AND attendance_date BETWEEN %s AND %s
				GROUP BY DATE_FORMAT(attendance_date, %s)
				ORDER BY attendance_date ASC",
				$date_format,
				$course_id,
				$date_from,
				$date_to,
				$date_format
			),
			ARRAY_A
		);

		return $data;
	}

	/**
	 * Get date format for grouping.
	 */
	private function get_date_format( $period ) {
		switch ( $period ) {
			case 'daily':
				return '%Y-%m-%d';
			case 'weekly':
				return '%Y-%u';
			case 'monthly':
				return '%Y-%m';
			default:
				return '%Y-%m-%d';
		}
	}

	/**
	 * Get client IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}
		
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}

	/**
	 * Clean up old attendance records.
	 */
	public function cleanup_old_records( $days_to_keep = 365 ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$cutoff_date = date( 'Y-m-d', strtotime( "-$days_to_keep days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE attendance_date < %s",
				$cutoff_date
			)
		);

		return $deleted;
	}

	/**
	 * Get table statistics.
	 */
	public function get_table_stats() {
		global $wpdb;

		$table_name = $this->get_table_name();
		
		// Check if table exists first.
		if ( ! $this->table_exists() ) {
			return array(
				'total_records' => 0,
				'unique_users' => 0,
				'unique_courses' => 0,
				'earliest_date' => null,
				'latest_date' => null,
			);
		}

		$stats = $wpdb->get_row(
			"SELECT 
				COUNT(*) as total_records,
				COUNT(DISTINCT user_id) as unique_users,
				COUNT(DISTINCT course_id) as unique_courses,
				MIN(attendance_date) as earliest_date,
				MAX(attendance_date) as latest_date
			FROM $table_name",
			ARRAY_A
		);

		return $stats;
	}
}
