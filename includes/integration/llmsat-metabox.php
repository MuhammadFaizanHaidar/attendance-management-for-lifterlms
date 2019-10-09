<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage MetaBox on the Edit course Page
 */
class LLMS_AT_Metabox {

	/**
	 * Constructor
	 */
	public function __construct() { 

        $this->hooks();
    }
    
    private function hooks() {

        add_action( 'add_meta_boxes', array( $this, 'register_attendance_meta_boxes' ) );
        add_action( 'save_post',      array( $this, 'save_meta_box' ) );
    }

    /**
     * Register the meta box for the attendance management system
     * @param void
     * @return void
     */
    public function register_attendance_meta_boxes() {
        $disallow_attendance_text = __( 'DisAllow Attendance ', LLMS_At_TEXT_DOMAIN );
        $disallow_attendance_text = apply_filters( 'llmsat_disallow_attendance_text', $disallow_attendance_text );
        $students_information_text = __( 'Students Attendance Information ', LLMS_At_TEXT_DOMAIN );
        $students_information_text = apply_filters( 'llmsat_students_attendance_information_text', $students_information_text );

        add_meta_box( 'llmsat-metabox-id', $disallow_attendance_text,           array( $this, 'show_attendance_meta_box' ), 'course', 'side', 'high');
        add_meta_box( 'llmsat-students-metabox-id', $students_information_text, array( $this, 'show_student_listing_meta_box' ), 'course', 'advanced', 'high');
    }

    public function show_student_listing_meta_box () {
        $course_id = get_the_ID();
        $course    = llms_get_post( $course_id );
        $students  = llms_get_enrolled_students( $course->get( 'id' ), 'enrolled' );
        $disallow  = get_post_meta( $course_id, 'llmsatck1', true );
        if ( $disallow == 'on' ) {
            echo '<div class="llmsat-error"><h2> Turn off the disallow attendance option to enlist enrolled students attendance information.</h2></div>';
            return;
        }
        ?>
        <div class="llmsat-sd-section llmsat-sd-grades">

        <?php do_action( 'llmsat_student_dashboard_before_my_attendance' ); ?>

        <table class="llmsat-table">
            <thead>
                <tr>
                    <th><?php _e( 'Enrolled Students'    , LLMS_At_TEXT_DOMAIN ); ?></a></th>
                    <th><?php _e( 'Attendance Count'     , LLMS_At_TEXT_DOMAIN ); ?></a></th>
                    <th><?php _e( 'Attendance Percentage', LLMS_At_TEXT_DOMAIN ); ?></a></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $students as $student ) : 
            $user_id   = absint( $student );
            $blogtime  = current_time( 'mysql' );
            list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
            $key               = $today_year."-".$today_month."-". $today_day."-".$course_id;
            $attendance        = get_user_meta( $user_id, $key, true );
            $llmsat_options    = get_option( 'llmsat_options', array() );
            $student           = llms_get_student( $user_id );
            $has_access        = $student->is_enrolled( $course->get( 'id' ) );
            $dateObj           = DateTime::createFromFormat('!m', $today_month );
            $monthName         = $dateObj->format('F');
            $author_info       = get_userdata( $user_id );
            $days              = cal_days_in_month( CAL_GREGORIAN, $today_month, $today_year );
            $meta_key_count    = $today_year. "-". $today_month."-".$course_id;
            if ( null !== get_user_meta( $user_id, $meta_key_count, true ) && $user_id != 0 && $has_access && $disallow != "on" && $llmsat_options['llmsat_global_attendance'] == "on" ) {
                $count         = get_user_meta( $user_id, $meta_key_count, true );
                $count         = intval( $count );
                $days          = intval( $days );
                $attendance    = $count/$today_day * 100; ?>
                <tr>
                    <td><b><a href = <?php echo get_author_posts_url( $user_id ); ?>><?php echo $author_info->display_name ;?> </a></b></td>
                    <td><?php echo $count; ?></td>
                    <td><?php echo round( $attendance ).'%';?></td>
                </tr>
            <?php } endforeach; ?>
            </tbody>
        </table>

        <?php do_action( 'llmsat_student_dashboard_after_my_attendance' ); ?>
        </div>
        <?php
    }

    /**
     * Display the Meta box the Course Edit page
     * @param void
     */
    public function show_attendance_meta_box() {

        $post_id  = absint( sanitize_text_field( $_REQUEST['post'] ) );
        $disallow = get_post_meta( $post_id, 'llmsatck1', true );
        if ( $disallow == 'on' ) {
            $disallow = true;
        }
        ?>
        <div>
            <input type="checkbox" name="llmsatck1" <?php if( $disallow == true ) { ?>checked="checked"<?php } ?> />  Disallow Attendance
        </div>
        <?php 
    }

    /**
     * Saves the meta box post
     * @param $post_id post_id where metabox is to be saved
     */
    public function save_meta_box( $post_id ) {

        $post_type          = get_post_type( $post_id );
        $meta_field_value_1 = $_POST['llmsatck1'];
        if( trim( $post_type ) == 'course' ) {
            update_post_meta( $post_id, 'llmsatck1', $meta_field_value_1 );
        }
    }
}

return new LLMS_AT_Metabox();