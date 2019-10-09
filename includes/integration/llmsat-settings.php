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
			'title' => __( 'LifterLMS Attendance General Settings', LLMS_At_TEXT_DOMAIN ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'lifterlms_attendance_options'
		);

		$content[] = array(
			'desc' 		=> __( 'Use LifterLMS Attendance System', LLMS_At_TEXT_DOMAIN ),
			'default'	=> 'no',
			'id' 		=> 'llms_integration_lifterlms_attendance_enabled',
			'type' 		=> 'checkbox',
			'title'     => __( 'Enable / Disable', LLMS_At_TEXT_DOMAIN ),
		);
		
		$content[] = array(
			'desc' 		=> __( 'Allow global attendance for all courses', LLMS_At_TEXT_DOMAIN ),
			'default'	=> 'yes',
			'id' 		=> 'llms_integration_global_attendance_enabled',
			'type' 		=> 'checkbox',
			'title'     => __( 'Allow / DisAllow', LLMS_At_TEXT_DOMAIN ),
		);
		
        $content[] = array(
			'type' => 'sectionend',
			'id'   => 'lifterlms_attendance_options'
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