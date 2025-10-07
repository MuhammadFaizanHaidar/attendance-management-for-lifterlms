<?php
/**
 * Hybrid Attendance Manager - Supports both Meta and Custom Table
 *
 * @package  Attendance Management For LifterLMS
 * @author   Muhammad Faizan Haidar
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hybrid attendance manager class.
 */
class LLMS_AT_Hybrid_Manager {

	/**
	 * Database instance.
	 */
	private $db;

	/**
	 * Use custom table flag.
	 */
	private $use_custom_table;

	/**
	 * Initialize hybrid manager.
	 */
	public function __construct() {
		$this->db = new LLMS_AT_Database();
		$this->use_custom_table = get_option( 'llmsat_use_custom_table', false );
		
		// Hook into existing attendance system.
		add_action( 'lifterlms_mark_attendance', array( $this, 'handle_attendance_mark' ), 10, 4 );
	}

	/**
	 * Handle attendance marking (supports both systems).
	 */
	public function handle_attendance_mark( $user_id, $course_id, $attendance_percentage, $count ) {
		// Always save to custom table if available.
		if ( $this->use_custom_table ) {
			$this->db->insert_attendance( $user_id, $course_id );
		}
		
		// Keep meta system for backward compatibility.
		// This ensures existing code continues to work.
	}

	/**
	 * Get attendance data (tries custom table first, falls back to meta).
	 */
	public function get_attendance_data( $user_id, $course_id, $date_from = null, $date_to = null ) {
		if ( $this->use_custom_table ) {
			return $this->get_from_custom_table( $user_id, $course_id, $date_from, $date_to );
		} else {
			return $this->get_from_meta( $user_id, $course_id, $date_from, $date_to );
		}
	}

	/**
	 * Get data from custom table.
	 */
	private function get_from_custom_table( $user_id, $course_id, $date_from, $date_to ) {
		// Implementation using custom table.
		return $this->db->get_attendance_count( $user_id, $course_id, $date_from, $date_to );
	}

	/**
	 * Get data from meta (existing implementation).
	 */
	private function get_from_meta( $user_id, $course_id, $date_from, $date_to ) {
		// Existing meta-based implementation.
		$current_month = date( 'Y-m' );
		$monthly_key = $current_month . '-' . $course_id;
		return get_user_meta( $user_id, $monthly_key, true );
	}

	/**
	 * Check if user has attendance (hybrid approach).
	 */
	public function has_attendance( $user_id, $course_id, $date = null ) {
		if ( $this->use_custom_table ) {
			return $this->db->has_attendance( $user_id, $course_id, $date );
		} else {
			// Meta-based check.
			if ( ! $date ) {
				$date = current_time( 'Y-m-d' );
			}
			$meta_key = $date . '-' . $course_id;
			return ! empty( get_user_meta( $user_id, $meta_key, true ) );
		}
	}

	/**
	 * Get course statistics (hybrid approach).
	 */
	public function get_course_stats( $course_id ) {
		if ( $this->use_custom_table ) {
			return $this->db->get_course_attendance_stats( $course_id );
		} else {
			// Use existing meta-based implementation.
			$reporting = new LLMS_AT_Reporting();
			return $reporting->get_course_attendance_stats( $course_id );
		}
	}

	/**
	 * Enable custom table usage.
	 */
	public function enable_custom_table() {
		update_option( 'llmsat_use_custom_table', true );
		$this->use_custom_table = true;
	}

	/**
	 * Disable custom table usage (fallback to meta).
	 */
	public function disable_custom_table() {
		update_option( 'llmsat_use_custom_table', false );
		$this->use_custom_table = false;
	}

	/**
	 * Get system status.
	 */
	public function get_system_status() {
		return array(
			'use_custom_table' => $this->use_custom_table,
			'table_exists' => $this->db->get_table_stats(),
			'migration_status' => get_option( 'llmsat_migration_status', 'not_started' ),
		);
	}
}
