<?php
/**
 * Uninstall file, which would delete all user metadata and configuration settings
 *
 * @since 1.0
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

global $wpdb;

$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%llmsat%';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name='llmsat_version';");

$llmsat_options      = get_option( 'llmsat_options', array() );
 
if( $llmsat_options['llmsat_delete_attendance'] == "on" ) {
    $courses = get_posts( array(
		'post_type' => 'course',
		'post_status' => 'publish',
		'posts_per_page' => -1
    ) );
    foreach ( $courses as $course ) {
        $llms_course = new LLMS_Course( $course->ID );
        $students    = llms_get_enrolled_students( $llms_course->get( 'id' ), 'enrolled' );
        $blogtime    = current_time( 'mysql' );
        $course_id   = $course->ID; 
        list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
        foreach( $students as $student ) {
            $user_id        = $student;
            $first_mark_key = "first_mark"."-".$course_id;
            if ( null != get_user_meta( $user_id, $first_mark_key , true ) ) {
                $user_time = get_user_meta( $user_id, $first_mark_key , true ); 
                list( $user_year, $user_month, $user_day, $u_course_id ) = preg_split( '([^0-9])', $user_time );
                if ( $u_course_id != $course_id ) {
                    break;
                }
                //Delete data for current date
                $meta_key    = $today_year."-".$today_month."-".$today_day."-".$course_id;
                $count_key   = $today_year."-".$today_month."-".$course_id;
                $meta_value  = get_user_meta( $user_id, $meta_key, true );
                $count_value = get_user_meta( $user_id, $count_key, true );
                if ( $meta_value != null ) {
                    delete_user_meta( $user_id, $meta_key, $meta_value );
                }
                if ( null != $count_value ) {
                    delete_user_meta( $user_id, $count_key, $count_value );
                }
                while ( $today_month != $user_month && $user_year != $today_year && $user_day != $today_day ) {
                    $days        = intval ( cal_days_in_month( CAL_GREGORIAN, $user_month, $user_year ) );
                    $meta_key    = $user_year."-".$user_month."-".$user_day."-".$course_id;
                    $count_key   = $user_year."-".$user_month."-".$course_id;
                    $meta_value  = get_user_meta( $user_id, $meta_key, true );
                    $count_value = get_user_meta( $user_id, $count_key, true );
                    if ( $meta_value != null ) {
                        delete_user_meta( $user_id, $meta_key, $meta_value );
                    }
                    if ( null != $count_value ) {
                        delete_user_meta( $user_id, $count_key, $count_value );
                    }
                    $user_day = $user_day + 1;
                    if ( $user_day > $days  ) {
                        $user_day   = 1;
                        $user_month = $user_month + 1;
                        if ( $user_month > 12 ){
                            $user_month = 1;
                            $user_year  = $user_year + 1;
                        }
                    }
                    if ( $user_year > $today_year ) {
                        break;
                    }
                }
            }
        }
    }
}
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name='llmsat_options';");