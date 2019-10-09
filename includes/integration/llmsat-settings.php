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
		add_filter( 'lifterlms_integrations_settings_lifterlms_attendance', array( $this, 'integration_settings' ) );
		add_action( 'lifterlms_settings_save_integrations',       array( $this, 'save' ), 10 );

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
			'class' =>'top'
		);

		$content[] = array(
			'title' => __( 'LifterLMS Attendance Management Addon General Settings', LLMS_At_TEXT_DOMAIN ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'lifterlms_attendance_options'
		);

		$content[] = array(
			'desc' 		=> __( 'Use LifterLMS Attendance Management Addon.', LLMS_At_TEXT_DOMAIN ),
			'default'	=> 'no',
			'id' 		=> 'llms_integration_lifterlms_attendance_enabled',
			'type' 		=> 'checkbox',
			'title'     => __( 'Enable / Disable', LLMS_At_TEXT_DOMAIN ),
		);
		
		$content[] = array(
			'desc' 		=> __( 'Allow global attendance for all courses.', LLMS_At_TEXT_DOMAIN ),
			'default'	=> 'yes',
			'id' 		=> 'llms_integration_global_attendance_enabled',
			'type' 		=> 'checkbox',
			'title'     => __( 'Allow / DisAllow', LLMS_At_TEXT_DOMAIN ),
		);
		
        $content[] = array(
			'type' => 'sectionend',
			'id'   => 'lifterlms_attendance_options'
		);

		$content[] = array(
			'type'  => 'sectionstart',
			'id'    => 'lifterlms_attendance_shortcodes',
			'class' =>'top'
		);

		$content[] = array(
			'title' => __( 'LifterLMS Attendance Management Addon Shortcodes', LLMS_At_TEXT_DOMAIN ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'lifterlms_attendance_shortcodes'
		);

		$content[] = array(
			'title' => __( 'Top "X" attendants shortcode', LLMS_At_TEXT_DOMAIN ),
			'type'  => 'text',
			'value' => '[llmsat_top_attendant course_id="y" students="x"]',
			'desc'  => '<br>'. __( 'It shows top "x" attendants in a given course id "y" by default it shows only 1 top attendant. Automatically retrieves current dates.', LLMS_At_TEXT_DOMAIN ). '</br>',
			'id'    => 'lifterlms_attendance_top_attendant'
		);

		$content[] = array(
			'title' => __( 'Student attendance shortcode', LLMS_At_TEXT_DOMAIN ),
			'type'  => 'text',
			'value' => '[llmsat_student_attendance course_id="x"]',
			'desc'  => '<br>'. __( 'It shows attendance of current login user for a given course id "x" automatically reterieves user id if not given. Automatically retrieves current dates.', LLMS_At_TEXT_DOMAIN ). '</br>',
			'id'    => 'lifterlms_attendance_student_attendance'
		);

		$content[] = array(
			'type' => 'sectionend',
			'id'   => 'lifterlms_attendance_shortcodes'
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