<?php
/**
 * Attendance Management For LifterLMS Core
 *
 * @author   Muhammad Faizan Haidar
 * @package  Attendance Management For LifterLMS Core
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LLMS_AT_Core Class
 */
class LLMS_AT_Core {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->hooks();
	}

	private function hooks() {
		add_action(
			'lifterlms_single_course_before_summary',
			array( $this, 'add_content_before_course_summary' ),
			10,
			0
		);
		add_action(
			'lifterlms_single_course_after_summary',
			array( $this, 'add_content_before_course_summary' ),
			10,
			0
		);
		add_action(
			'lifterlms_before_main_content',
			array( $this, 'add_content_before_course_summary' ),
			10,
			0
		);
		add_action(
			'wp_ajax_llmsat_attendance_btn_ajax_action',
			array( $this, 'llmsat_attendance_btn_ajax_action' ),
			10
		);
		add_action(
			'wp_ajax_llmsat_instructor_mark_attendance',
			array( $this, 'llmsat_instructor_mark_attendance_ajax' ),
			10
		);

		add_action(
			'wp_ajax_nopriv_llmsat_attendance_btn_ajax_action',
			array( $this, 'llmsat_attendance_btn_ajax_action' ),
			10
		);
	}

	/**
	 * Add Content after course summary
	 *
	 * @return void/string
	 */
	public function add_content_before_course_summary() {
		if ( ! is_singular( 'course' ) ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$course_id = get_the_ID();
		$user_id   = get_current_user_id();
		if ( ! $course_id || get_post_type( $course_id ) != 'course' ) {
			return false;
		}

		$course = new LLMS_Course( $course_id );
		if ( $course->has_date_passed( 'end_date' ) ) {
			return;
		}

		$student  = llms_get_student( $user_id );
		$blogtime = current_time( 'mysql' );
		list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
		$key = $today_year . '-' . $today_month . '-' . $today_day . '-' . $course_id;

		// Use hybrid manager to check attendance.
		$hybrid_manager = new LLMS_AT_Hybrid_Manager();
		$attendance     = $hybrid_manager->has_attendance( $user_id, $course_id, $today_year . '-' . $today_month . '-' . $today_day );

		$disallow               = get_post_meta( $course_id, 'llmsatck1', true );
		$has_access             = $student->is_enrolled( $course->get( 'id' ) );
		$attendance_button_text = esc_html__( 'Mark Present', 'llms-attendance' );
		$attendance_button_text = apply_filters( 'llms_attendance_button_text', $attendance_button_text );
		$output                 = '';
		if ( $disallow != 'on' && 'yes' === get_option( 'llms_integration_global_attendance_enabled', 'no' ) && null == $attendance && $has_access ) {
			$output .= '<div id="llmsat-mark-present-id"> <input type="submit" value="' . $attendance_button_text . '" href="javascript:;" onclick="llmsat_attendance_btn_ajax(' . $course_id . ', ' . $user_id . ')" class="llms-button llms-button-primary llmsat-btn wp-block-llms-course-continue-button"/></div>';
			$output .= '<div id="llmsat-ajax-response-id" class="llmsat-ajax-response llmsat-btn"><span></span></div>';
		}

		echo wp_kses(
			$output,
			array(
				'div'   => array(
					'id'    => array(),
					'class' => array(),
				),
				'input' => array(
					'type'    => array(),
					'value'   => array(),
					'href'    => array(),
					'onclick' => array(),
					'class'   => array(),
				),
				'span'  => array(),
			)
		);
	}

	/**
	 * Ajax action to mark attendance
	 *
	 * @return void
	 */
	public function llmsat_attendance_btn_ajax_action() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'llmsat_frontend_nonce' ) ) {
			echo apply_filters( 'llms_attendance_failed_message', __( 'Security check failed.', 'llms-attendance' ) ) . '2';
			exit;
		}

		$user_id   = isset( $_POST['uid'] ) ? intval( sanitize_text_field( $_POST['uid'] ) ) : 0;
		$course_id = isset( $_POST['pid'] ) ? sanitize_text_field( $_POST['pid'] ) : '';

		if ( $user_id && $course_id ) {

			$blogtime     = current_time( 'mysql' );
			$student_data = array(
				'time'      => $blogtime,
				'course_id' => intval( $course_id ),
			);
			list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
			$meta_key         = $today_year . '-' . $today_month . '-' . $today_day . '-' . $course_id;
			$meta_key_count   = $today_year . '-' . $today_month . '-' . $course_id;
			$first_mark_key   = 'first_mark' . '-' . $course_id;
			$first_mark_value = $today_year . '-' . $today_month . '-' . $today_day . '-' . $course_id;
			$days             = cal_days_in_month( CAL_GREGORIAN, $today_month, $today_year );
			// Use hybrid manager to check attendance
			$hybrid_manager = new LLMS_AT_Hybrid_Manager();
			$attendance     = $hybrid_manager->has_attendance( $user_id, $course_id, $today_year . '-' . $today_month . '-' . $today_day );

			// Get current count and increment
			$current_count = $hybrid_manager->get_attendance_data( $user_id, $course_id );
			if ( null != $current_count ) {
				$count = $current_count + 1;
			} else {
				$count = 1;
			}

			/**
			 * Mark First Attendance Date
			 */
			if ( null == get_user_meta( $user_id, $first_mark_key, true ) ) {
				update_user_meta( $user_id, $first_mark_key, $first_mark_value );
			}

			/**
			 * Check if attendacne is not marked double
			 */
			if ( $attendance == null ) {
				$user_attendance = round( $count / $today_day * 100 );

				// Use hybrid manager to handle attendance marking
				$hybrid_manager->handle_attendance_mark( $user_id, $course_id, $user_attendance, $count );

				// Only update meta if migration is not completed (for backward compatibility)
				$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
				if ( 'completed' !== $migration_status && 'cleanup_completed' !== $migration_status ) {
					update_user_meta( $user_id, $meta_key, $student_data );
					update_user_meta( $user_id, $meta_key_count, $count );
				}

				$success_message = __( 'Attendance marked successfully', 'llms-attendance' );

				echo apply_filters( 'llms_attendance_success_message', $success_message ) . '1';
				exit;
			}
		} else {
			$failed_message = __( 'Attendance was not marked successfully', 'llms-attendance' );

			echo apply_filters( 'llms_attendance_failed_message', $failed_message ) . '2';
			exit;
		}
		$already_marked = __( 'You are already marked present', 'llms-attendance' );

		echo apply_filters( 'llms_attendance_already_marked_message', $already_marked ) . '3';

		exit;
	}

	/**
	 * AJAX handler for instructor marking attendance for students.
	 */
	public function llmsat_instructor_mark_attendance_ajax() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'llmsat_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'llms-attendance' ) ) );
		}

		// Check if user has permission to mark attendance.
		$current_user  = wp_get_current_user();
		$allowed_roles = get_option( 'llms_integration_attendance_marking_roles', array( 'instructor', 'lms_manager', 'administrator' ) );

		$has_permission = false;
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				$has_permission = true;
				break;
			}
		}

		if ( ! $has_permission ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to mark attendance.', 'llms-attendance' ) ) );
		}

		$user_id   = intval( $_POST['user_id'] );
		$course_id = intval( $_POST['course_id'] );

		if ( ! $user_id || ! $course_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user or course ID.', 'llms-attendance' ) ) );
		}

		// Check if user is enrolled in the course
		$student = llms_get_student( $user_id );
		if ( ! $student->is_enrolled( $course_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Student is not enrolled in this course.', 'llms-attendance' ) ) );
		}

		// Mark attendance using the same logic as student self-marking.
		$blogtime = current_time( 'mysql' );
		list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
		$meta_key         = $today_year . '-' . $today_month . '-' . $today_day . '-' . $course_id;
		$meta_key_count   = $today_year . '-' . $today_month . '-' . $course_id;
		$first_mark_key   = 'first_mark' . '-' . $course_id;
		$first_mark_value = $today_year . '-' . $today_month . '-' . $today_day . '-' . $course_id;

		// Use hybrid manager to check attendance.
		$hybrid_manager = new LLMS_AT_Hybrid_Manager();
		$attendance     = $hybrid_manager->has_attendance( $user_id, $course_id, $today_year . '-' . $today_month . '-' . $today_day );

		if ( $attendance == null ) {
			// Count attendance using hybrid manager
			$current_count = $hybrid_manager->get_attendance_data( $user_id, $course_id );
			if ( null != $current_count ) {
				$count = $current_count + 1;
			} else {
				$count = 1;
			}

			// Mark first attendance date
			if ( null == get_user_meta( $user_id, $first_mark_key, true ) ) {
				update_user_meta( $user_id, $first_mark_key, $first_mark_value );
			}

			$student_data = array(
				'time'      => $blogtime,
				'course_id' => intval( $course_id ),
			);

			$user_attendance = round( $count / $today_day * 100 );

			// Use hybrid manager to handle attendance marking.
			$hybrid_manager->handle_attendance_mark( $user_id, $course_id, $user_attendance, $count );

			// Only update meta if migration is not completed (for backward compatibility).
			$migration_status = get_option( 'llmsat_migration_status', 'not_started' );
			if ( 'completed' !== $migration_status && 'cleanup_completed' !== $migration_status ) {
				update_user_meta( $user_id, $meta_key, $student_data );
				update_user_meta( $user_id, $meta_key_count, $count );
			}

			wp_send_json_success(
				array(
					'message'    => __( 'Attendance marked successfully by instructor.', 'llms-attendance' ),
					'count'      => $count,
					'percentage' => round( $user_attendance, 1 ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Student is already marked present today.', 'llms-attendance' ) ) );
		}
	}
}
return new LLMS_AT_Core();
