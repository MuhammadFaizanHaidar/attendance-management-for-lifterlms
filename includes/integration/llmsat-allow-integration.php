<?php
defined( 'ABSPATH' ) || exit;

/**
 * LifterLMS Attendance Integration Class
 */
class LifterLMS_Attendance_Integration extends LLMS_Abstract_Integration {

	public $id          = 'lifterlms_attendance';
	public $title       = '';
	protected $priority = 5; 

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->title       = __( 'LifterLMS Attendance', LLMS_At_TEXT_DOMAIN );
		$this->description = sprintf( __( 'Allows Attendance facility on lifterlms courses', LLMS_At_TEXT_DOMAIN ), '<a href="https://lifterlms.com/docs/lifterlms-and-lifterlms_attendance/" target="_blank">', '</a>' );
		
	}
	
	/**
	 * Integration Configuration
	 */
	public function configure() {
	
		$this->title       = __( 'LifterLMS Attendance Options', LLMS_At_TEXT_DOMAIN );
		$this->description = sprintf( __( 'Allows Attendance facility on lifterlms courses', 'lifterlms' ), '<a href="https://lifterlms.com/docs/lifterlms-and-lifter_attendance/" target="_blank">', '</a>' );
		
	}
	
}
return new LifterLMS_Attendance_Integration();