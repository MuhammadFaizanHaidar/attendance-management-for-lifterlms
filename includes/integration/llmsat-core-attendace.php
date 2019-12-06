<?php
/**
 * Attendance Management For LifterLMS Core
 *
 * @author   Muhammad Faizan Haidar
 * @package  Attendance Management For LifterLMS Core
 * @version  1.0
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

/**
 * LLMS_AT_Core Class
 */
class LLMS_AT_Core {

    /**
     * Constructor
     */
    public function __construct () {

        $this->hooks();
    }

    private function hooks() {
        add_action( 'lifterlms_single_course_before_summary',           array( $this,'add_content_before_course_summary' ), 10, 0);
        add_action( 'wp_ajax_llmsat_attendance_btn_ajax_action',        array( $this, 'llmsat_attendance_btn_ajax_action' ), 10 );
        add_action( 'wp_ajax_nopriv_llmsat_attendance_btn_ajax_action', array( $this, 'llmsat_attendance_btn_ajax_action' ), 10);
    }

    /**
     * Add Content after course summary
     * @return void/string
     */
    public function add_content_before_course_summary() {

        if( ! is_singular( 'course' ) ) {
            return false;
        }

        if( ! is_user_logged_in() ) {
            return false;
        }

        $course_id = get_the_ID();
        $user_id   = get_current_user_id();
        if( ! $course_id || get_post_type( $course_id ) != 'course' ) {
            return false;
        }
        
        $course = new LLMS_Course( $course_id );
        if( $course->has_date_passed( 'end_date' ) ) {
            return;
        } 

        $student           = llms_get_student( $user_id );
        $blogtime          = current_time( 'mysql' );
        list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
        $key               = $today_year."-".$today_month."-". $today_day."-".$course_id;
        $attendance        = get_user_meta( $user_id, $key, true );
        $disallow          = get_post_meta( $course_id, 'llmsatck1', true );
        $has_access        = $student->is_enrolled( $course->get( 'id' ) );
        // $meta_key = $today_year."-".$today_month."-".$today_day."-".$course_id;
        // $count_key = $today_year."-".$today_month."-".$course_id;
        // $meta_value = get_user_meta( $user_id, $meta_key, true );
        // $count_value = get_user_meta( $user_id, $count_key, true );
        // if ( $meta_value != null ) {
        //     delete_user_meta( $user_id, $meta_key, $meta_value );
        // }
        // if ( null != $count_value ) {
        //     delete_user_meta( $user_id, $count_key, $count_value );
        // }
        $attendance_button_text = __( "Mark Present", LLMS_At_TEXT_DOMAIN );
        $attendance_button_text = apply_filters( "llms_attendance_button_text", $attendance_button_text );
        $output = "";

        if ( $disallow != "on" && 'yes' === get_option( 'llms_integration_global_attendance_enabled', 'no' ) && null == $attendance && $has_access ) {
            $output .= '<input type="submit" value="'.$attendance_button_text.'" href="javascript:;" onclick="llmsat_attendance_btn_ajax('.$course_id.', '.$user_id.')" class="llmsat-attendance-btn llmsat-btn"/>';   
            $output .= '<div id="llmsat-ajax-response-id" class="llmsat-ajax-response"><span></span></div>'; 
        }   

        echo $output;
    }

    /**
     * Ajax action to mark attendance
     *
     * @return void
     */
    public function llmsat_attendance_btn_ajax_action() {
        
        $user_id   = sanitize_text_field( $_POST['uid'] );
        $course_id = sanitize_text_field( $_POST['pid'] );
        if ( $user_id && $course_id ) {
            
            $blogtime     = current_time( 'mysql' );
            $student_data = array(
                "time"      => $blogtime,
                "course_id" => intval( $course_id )
            );  
            list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
            $meta_key         = $today_year."-".$today_month."-". $today_day."-".$course_id;
            $meta_key_count   = $today_year. "-". $today_month."-".$course_id;
            $first_mark_key   = "first_mark"."-".$course_id;
            $first_mark_value = $today_year. "-". $today_month."-".$today_day."-".$course_id;
            $days             = cal_days_in_month( CAL_GREGORIAN, $today_month, $today_year );
            $attendance       = get_user_meta( $user_id, $meta_key, true );

            if ( null !== get_user_meta( $user_id, $meta_key_count, true ) ) {
                $count = get_user_meta( $user_id, $meta_key_count, true );
                $count = $count + 1;
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
                do_action( 'lifterlms_mark_attendance', $user_id, $course_id, $user_attendance, $count );
                update_user_meta( $user_id, $meta_key, $student_data );
                update_user_meta( $user_id, $meta_key_count, $count );
                $success_message =  __( "Attendance marked successfully", LLMS_At_TEXT_DOMAIN );
                echo apply_filters( "llms_attendance_success_message", $success_message )."1";
                //$this->show_notification( 'marked_successfully' );
                exit;
            }

        } else {
            $failed_message = __( "Attendance was not marked successfully", LLMS_At_TEXT_DOMAIN );
            echo apply_filters( "llms_attendance_failed_message", $failed_message )."2";
            //$this->show_notification( 'attendance_failed' );
            exit;
        }
        $already_marked = __( "You are already marked present", LLMS_At_TEXT_DOMAIN );
        echo apply_filters( "llms_attendance_already_marked_message", $already_marked )."3";
        //$this->show_notification( 'already_marked' );
        exit;
    }

    /**
     * @param string $error_code
     */
    function show_notification( $error_code = '' ) {

        $redirect_url = add_query_arg( 'bos-llms-gw-error', $error_code, $_POST['_wp_http_referer'] );
        wp_safe_redirect( $redirect_url, 302 );
        exit;
    }

    function alert_error() {

        if ( ( isset( $_GET['bos-llms-gw-error'] ) ) && ( !empty( $_GET['bos-llms-gw-error'] ) ) ) {

            $message = '';


        if( $_GET['bos-llms-gw-error'] == 'marked_successfully' || $_GET['bos-llms-gw-error'] == 'already_marked' ) {
            $message = __( 'The Point Type you have chosen, has not enough points to unlock this course', LLMS_At_TEXT_DOMAIN );
        }
        ?>

        <script type="text/javascript">
            jQuery( document ).ready( function() {
                console.log("ok");

                jQuery( '<p class="llmsat-error"><?php echo $message; ?></p>' ).insertBefore( '.llmsat-attendance-btn' );
    
            });
        </script>
        <?php }
        echo "success";
    }
}
return new LLMS_AT_Core();