<?php
/**
 * LifterLMS Attendance Management Shortcode
 *
 * @student   Muhammad Faizan Haidar
 * @category Admin
 * @package  LifterLMS Attendance Management/Admin/shortcode
 * @version  1.0
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

class LLMS_At_Short_Code {
    /**
     * constructor.
     */
    public function __construct () {
        $this->hooks();
    }

    private function hooks() {
        
        add_shortcode( 
            'llmsat_top_attendant',
            [ $this, 'display_top_attendant' ] 
        );

        add_shortcode( 
            'llmsat_student_attendance',
            [ $this, 'display_student_attendance' ] 
        );
    }
    
    /**
     * short code callback function
     * @param $course_id, month, year
     * @return html output
     */
    public function display_top_attendant( $atts ) {
        $blogtime    = current_time( 'mysql' );
        list( $today_year_e, $today_month_e, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
        $atts = shortcode_atts( array(
            'course_id' => 0,
            'students'  => 1,
            'month'     => $today_month_e,
            'year'      => $today_year_e ,
        ), $atts, 'llmsat_top_attendant' );
    
        ob_start();

        $today_year  = absint( ltrim( rtrim( sanitize_text_field( $atts['year'] ) ) ) );
        $today_month = absint( ltrim( rtrim( sanitize_text_field( $atts['month'] ) ) ) );
        $course_id   = absint ( ltrim( rtrim( sanitize_text_field( $atts['course_id'] ) ) ) );
        $students_count   = absint( ltrim( rtrim( sanitize_text_field( $atts['students'] ) ) ) );   

        if ( empty( $today_year ) ) {
            $today_year = $today_year_e;
        }

        if ( empty( $today_month ) ) {
            $today_month = $today_month_e;
        }

        if ( empty( $students_count ) ) {
            $students_count = 1;
        }
        $keyname     = $today_year."-". $today_month."-".$course_id;
        $days        = cal_days_in_month( CAL_GREGORIAN, $today_month, $today_year );
        $dateObj     = DateTime::createFromFormat('!m', $today_month );
        $monthName   = $dateObj->format('F');
        
        $user_query  = new WP_User_Query( array ( 'orderby' => 'meta_value', 'order' => 'DESC', 'number' => 5, 'meta_key' => "$keyname"  ) );
        $students    = $user_query->get_results();
        $st_count    = 0;

        // Check for results
        if ( ! empty( $students ) ) { ?>
            <ul id=""> <?php
            // loop through each student
            foreach ( $students as $student ) {
                if ( null !== get_user_meta( absint( $student->ID ), $keyname, true ) ) {
                    $count      = intval ( get_user_meta( absint( $student->ID ), $keyname, true ) );
                    $attendance = $count/intval( $today_day ) * 100;
                } 
                ?>
                <li><span class=""><b><?php echo __( 'Number '. intval ( $st_count + 1 ) .' Attendant for the course '.get_the_title( $course_id ).' :', 'llms-attendance' );?> </b></span>
                    <ul class="llmsat-dicey">
                        <li><?php echo __( "<b>Student Name</b> : ". $student->display_name,  'llms-attendance' ); ?></li>
                        <li><?php echo __( "<b>Course Name</b>       : ". get_the_title( $course_id ),   'llms-attendance' ); ?></li>
                        <li><?php echo __( "<b>Attendance</b>   : ". round( $attendance )." %", 'llms-attendance' ); ?></li>
                        <li><?php echo __( "<b>Attendance For the Month Of </b> : ". $monthName." ".$today_year, 'llms-attendance' ); ?></li>
                    </ul>
        
                <?php
                $st_count = $st_count + 1;
                if ( intval ( $st_count ) >= intval ( $students_count ) ) {
                    break;
                }
            }
        } else {
            ?>
            <ul class="llmsat-dicey">
            <li> <?php  echo __('<b>No student found in this course</b>', 'llms-attendance' ); ?></li> 
            </ul>
            <?php
        }?>
                </li>
            </ul>
        <?php
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * short code callback function
     * @param $user_id, course_id, month, year
     * @return html output
     */
    public function display_student_attendance( $atts ) {
        $blogtime    = current_time( 'mysql' );
        list( $today_year_e, $today_month_e, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
        $user_ID = get_current_user_id();
        $atts    = shortcode_atts( array(
            'user_id'   => $user_ID,
            'course_id' => 0,
            'month'     => $today_month_e,
            'year'      => $today_year_e ,
        ), $atts, 'llmsat_student_attendance' );
    
        ob_start();
        $blogtime    = current_time( 'mysql' );
        $today_year  = absint( ltrim( rtrim( sanitize_text_field( $atts['year'] ) ) ) );
        $today_month = absint( ltrim( rtrim( sanitize_text_field( $atts['month'] ) ) ) );
        $course_id   = absint( ltrim( rtrim( sanitize_text_field( $atts['course_id'] ) ) ) );
        $user_id     = absint( ltrim( rtrim( sanitize_text_field( $atts['user_id'] ) ) ) );
        if( empty( $user_id ) ) {
            $user_id = absint( ltrim( rtrim( $user_ID ) ) );
        }

        if ( empty( $today_year ) ) {
            $today_year = absint( $today_year_e );
        }

        if ( empty( $today_month ) ) {
            $today_month = absint( $today_month_e );
        }

        $course      = new LLMS_Course( $course_id );
        $student     = llms_get_student( $user_id );
        $has_access  = $student->is_enrolled( $course->get( 'id' ) );
        $dateObj     = DateTime::createFromFormat('!m', $today_month );
        $monthName   = $dateObj->format('F');
        $user        = get_userdata( $user_id );
        $days        = cal_days_in_month( CAL_GREGORIAN, $today_month, $today_year );
        $meta_key_count = $today_year. "-". $today_month."-".$course_id;
        if ( null !== get_user_meta( $user_id, $meta_key_count, true ) && $user_id != 0 && $has_access ) {
            $count      = get_user_meta( $user_id, $meta_key_count, true );
            $count      = intval( $count );
            $days       = intval( $days );
            $attendance = $count/intval( $today_day ) * 100;
            ?>
            <ul id="">
            <li><span class=""><b><?php echo __( 'Attendance of '.$user->display_name ." :", 'llms-attendance' );?> </b></span>
                <ul class="llmsat-dicey">
    
                    <li><?php echo __( "<b>Student Name</b> : ". $user->display_name,         'llms-attendance' ); ?></li>
                    <li><?php echo __( "<b>Course Name</b>  : ". get_the_title( $course_id ), 'llms-attendance' ); ?></li>
                    <li><?php echo __( "<b>Attendance</b>   : ". round( $attendance )." %",   'llms-attendance' ); ?></li>
                    <li><?php echo __( "<b>Attendance For The Month Of </b> : ". $monthName." ".$today_year, 'llms-attendance' ); ?></li>
                </ul>
            </li>
            </ul>
            <?php
        } elseif( $user && $has_access ) {
            ?>
            <ul id="">
                <li><span class=""><b><?php echo __( 'Attendance of '.$user->display_name ." :", 'llms-attendance' );?> </b></span>
                    <ul class="llmsat-dicey">
                        <li><?php echo __( "<b>Student Name </b>: ". $user->display_name,        'llms-attendance' ); ?></li>
                        <li><?php echo __( "<b>Course Name </b> : ". get_the_title( $course_id ),'llms-attendance' ); ?></li>
                        <li><?php echo __( "<b>Attendance </b>  : 0 %", 'llms-attendance' ); ?></li>
                        <li><?php echo __( "<b>Attendance For The month of ". $monthName." ".$today_year, 'llms-attendance' ); ?></li>
                    </ul>
                </li>
            </ul>
            <?php
        } else {
            ?>
            <ul id="">
                <li><span class=""><b><?php echo __( 'Invalid student ID or course ID :', 'llms-attendance' );?> </b></span>
                    <ul class="llmsat-dicey">
                        <li><?php if( !$user ) { echo __( "<b>Student Name </b>: Student does not exist",        'llms-attendance' );}else {
                            echo __( "<b>Student Name </b>: ".$user->display_name, 'llms-attendance' );
                        } ?></li>
                        <li><?php if( $course_id ) { echo __( "<b>Course Name </b>: ". get_the_title( $course_id ), 'llms-attendance' ); } else {
                            echo __( "<b>Course Name </b>:Course does not exist", 'llms-attendance' );
                        }?></li>
                        <li><?php echo __( "<b>Attendance </b>: Invalid data ",   'llms-attendance' ); if( $course_id && !$has_access ) { ?></li>
                        <li><?php echo __( "<b>Enrollment    : </b>Student is not enrolled in this course", 'llms-attendance' ); }?></li>
                    </ul>
                </li>
            </ul>
            <?php
        }
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
return new LLMS_At_Short_Code();