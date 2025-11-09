<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage settings forms on the LifterLMS Integrations Settings Page
 */
class LLMS_Attendance_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter(
			'lifterlms_integrations_settings_lifterlms_attendance',
			array( $this, 'integration_settings' )
		);

		add_action(
			'lifterlms_settings_save_integrations',
			array( $this, 'save' ),
			10
		);

	}

	/**
	 * This function adds the appropriate content to the array that makes up the settings page.
	 *
	 * @param    $content
	 * @return   array
	 */
	public function integration_settings( $content ) {

		/**
		 * General Settings
		 */
		$content[] = array(
			'type'  => 'sectionstart',
			'id'    => 'lifterlms_attendance_options',
			'class' => 'top',
		);

		$content[] = array(
			'title' => __( 'Attendance Management For LifterLMS Addon General Settings', 'llms-attendance' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'lifterlms_attendance_options',
		);

		$content[] = array(
			'desc'    => __( 'Use Attendance Management For LifterLMS Addon.', 'llms-attendance' ),
			'default' => 'no',
			'id'      => 'llms_integration_lifterlms_attendance_enabled',
			'type'    => 'checkbox',
			'title'   => __( 'Enable / Disable', 'llms-attendance' ),
		);

		$content[] = array(
			'desc'    => __( 'Allow global attendance for all courses.', 'llms-attendance' ),
			'default' => 'yes',
			'id'      => 'llms_integration_global_attendance_enabled',
			'type'    => 'checkbox',
			'title'   => __( 'Allow / DisAllow', 'llms-attendance' ),
		);

		$content[] = array(
			'title' => __( 'Roles That Can Mark Attendance for Students', 'llms-attendance' ),
			'type'  => 'multiselect',
			'desc'  => '<br>' . __( 'Select which user roles can mark attendance for students in courses. Hold Ctrl/Cmd to select multiple roles.', 'llms-attendance' ) . '</br>',
			'id'    => 'llms_integration_attendance_marking_roles',
			'options' => array(
				'instructor' => __( 'Instructor', 'llms-attendance' ),
				'instructors_assistant' => __( 'Instructor\'s Assistant', 'llms-attendance' ),
				'lms_manager' => __( 'LMS Manager', 'llms-attendance' ),
				'administrator' => __( 'Administrator', 'llms-attendance' ),
				'editor' => __( 'Editor', 'llms-attendance' ),
				'author' => __( 'Author', 'llms-attendance' ),
			),
			'default' => array( 'instructor', 'lms_manager', 'administrator' ),
		);

		$content[] = array(
			'type' => 'sectionend',
			'id'   => 'lifterlms_attendance_options',
		);

		$content[] = array(
			'type'  => 'sectionstart',
			'id'    => 'lifterlms_attendance_shortcodes',
			'class' => 'top',
		);

		$content[] = array(
			'title' => __( 'Attendance Management For LifterLMS Addon Shortcodes', 'llms-attendance' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'lifterlms_attendance_shortcodes',
		);

		$content[] = array(
			'title' => __( 'Top "X" attendants shortcode', 'llms-attendance' ),
			'type'  => 'text',
			'value' => '[llmsat_top_attendant course_id="y" students="x"]',
			'desc'  => '<br>' . __( 'It shows top "x" attendants in a given course id "y" by default it shows only 1 top attendant. Automatically retrieves current dates.', 'llms-attendance' ) . '</br>',
			'id'    => 'lifterlms_attendance_top_attendant',
		);

		$content[] = array(
			'title' => __( 'Student attendance shortcode', 'llms-attendance' ),
			'type'  => 'text',
			'value' => '[llmsat_student_attendance course_id="x"]',
			'desc'  => '<br>' . __( 'It shows attendance of current login user for a given course id "x" automatically reterieves user id if not given. Automatically retrieves current dates.', 'llms-attendance' ) . '</br>',
			'id'    => 'lifterlms_attendance_student_attendance',
		);

		$content[] = array(
			'type' => 'sectionend',
			'id'   => 'lifterlms_attendance_shortcodes',
		);

		$content[] = array(
			'type'  => 'sectionstart',
			'id'    => 'lifterlms_attendance_reporting',
			'class' => 'top',
		);

		$content[] = array(
			'title' => __( 'Attendance Reporting Settings', 'llms-attendance' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'lifterlms_attendance_reporting',
		);

		$content[] = array(
			'desc'    => __( 'Enable advanced reporting dashboard with charts and analytics.', 'llms-attendance' ),
			'default' => 'yes',
			'id'      => 'llms_integration_reporting_enabled',
			'type'    => 'checkbox',
			'title'   => __( 'Enable Reporting Dashboard', 'llms-attendance' ),
		);

		$content[] = array(
			'desc'    => __( 'Allow automatic data refresh in reporting dashboard.', 'llms-attendance' ),
			'default' => 'yes',
			'id'      => 'llms_integration_auto_refresh_enabled',
			'type'    => 'checkbox',
			'title'   => __( 'Auto Refresh Reports', 'llms-attendance' ),
		);

		$content[] = array(
			'desc'    => __( 'Enable email notifications for low attendance alerts.', 'llms-attendance' ),
			'default' => 'no',
			'id'      => 'llms_integration_email_alerts_enabled',
			'type'    => 'checkbox',
			'title'   => __( 'Email Alerts', 'llms-attendance' ),
		);

		$content[] = array(
			'title' => __( 'Low Attendance Threshold (%)', 'llms-attendance' ),
			'type'  => 'number',
			'value' => '70',
			'desc'  => '<br>' . __( 'Percentage below which attendance is considered low for alerts.', 'llms-attendance' ) . '</br>',
			'id'    => 'llms_integration_low_attendance_threshold',
		);

		$content[] = array(
			'type' => 'sectionend',
			'id'   => 'lifterlms_attendance_reporting',
		);

		return $content;

	}

	/**
	 * Flush rewrite rules when saving settings
	 *
	 * @return   void
	 */
	public function save() {

		$integration = LLMS()->integrations()->get_integration( 'lifterlms_attendance' );

		if ( $integration && $integration->is_available() ) {
			flush_rewrite_rules();
		}

	}

}

return new LLMS_Attendance_Settings();
