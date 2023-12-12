<?php
/**
 * Generates The User Grade Listing for Admin
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class LLMS_Attendance_List_Table_Class extends WP_List_Table {
	// define dataset for WP_List_Table => data

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => esc_html__( 'LLMS Student', 'llms-attendance' ), // singular name of the listed records
				'plural'   => esc_html__( 'LLMS Students', 'llms-attendance' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}


	/**
	 * Function to filter data based on order , order_by & searched items
	 *
	 * @param string $orderby
	 * @param string $order
	 * @param string $search_term
	 * @return array $users_array()
	 */
	public function list_table_data_fun( $orderby = '', $order = '', $search_term = '' ) {

		$users_array = array();
		$args        = array();
		$users       = '';
		if ( ! empty( $search_term ) ) {
			$searchcol = array(
				'ID',
				'user_email',
				'user_login',
				'user_nicename',
				'user_url',
				'display_name',
			);

			$args = array(
				'fields'         => 'ID',
				'orderby'        => $orderby,
				'order'          => $order,
				'search'         => intval( sanitize_text_field( $_REQUEST['s'] ) ),
				'search_columns' => $searchcol,
			);
		} else {
			if ( $order == 'asc' && $orderby == 'id' ) {
				$args = array(
					'orderby' => 'ID',
					'order'   => 'ASC',
					'fields'  => 'ID',
				);
			} elseif ( $order == 'desc' && $orderby == 'id' ) {
					$args = array(
						'orderby' => 'ID',
						'order'   => 'DESC',
						'fields'  => 'ID',
					);

			} elseif ( $order == 'desc' && $orderby == 'title' ) {
					$args = array(
						'orderby' => 'name',
						'order'   => 'DESC',
						'fields'  => 'ID',
					);
			} elseif ( $order == 'asc' && $orderby == 'title' ) {
				$args = array(
					'orderby' => 'name',
					'order'   => 'ASC',
					'fields'  => 'ID',
				);
			} else {
				$args = array(
					'orderby' => 'ID',
					'order'   => 'DESC',
					'fields'  => 'ID',
				);
			}
		}

		$course_id = get_the_ID();
		$disallow  = get_post_meta( $course_id, 'llmsatck1', true );
		$course    = llms_get_post( $course_id );
		$enrolled  = llms_get_enrolled_students( $course_id );
		$students = $users = get_users( $args );

		
		if ( count( $users ) > 0 ) {
			foreach ( $users as $student ) {
				$user_id = absint( $student );
				if ( in_array( $user_id, $enrolled ) ) {
					$blogtime = current_time( 'mysql' );
					list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = preg_split( '([^0-9])', $blogtime );
					$key            = $today_year . '-' . $today_month . '-' . $today_day . '-' . $course_id;
					$attendance     = get_user_meta( $user_id, $key, true );
					//delete_user_meta( $user_id, $key );
					$student        = llms_get_student( $user_id );
					$has_access     = $student->is_enrolled( $course->get( 'id' ) );
					$dateObj        = DateTime::createFromFormat( '!m', $today_month );
					$monthName      = $dateObj->format( 'F' );
					$author_info    = get_userdata( $user_id );
					$days           = cal_days_in_month( CAL_GREGORIAN, $today_month, $today_year );
					$meta_key_count = $today_year . '-' . $today_month . '-' . $course_id;
					if ( null !== get_user_meta( $user_id, $meta_key_count, true ) && $user_id != 0 && $has_access && $disallow != 'on' && 'yes' === get_option( 'llms_integration_global_attendance_enabled', 'no' ) ) {
						$count      = get_user_meta( $user_id, $meta_key_count, true );
						$count      = intval( $count );
						$days       = intval( $days );
						$attendance = $count / $today_day * 100;
						//delete_user_meta( $user_id, $meta_key_count );
						$users_array[] = array(
							'id'                => $user_id,
							'title'             => '<b><a href="' . get_author_posts_url( $user_id ) . '"> ' . $author_info->display_name . '</a></b>',
							'attendance_count'  => $count,
							'attendance_percen' => round( $attendance ) . '%',
						);
					}
					// code...
				}
			}
		}

		return $users_array;
	}
	// prepare_items
	public function prepare_items() {

		$orderby = sanitize_text_field( isset( $_GET['orderby'] ) ? trim( $_GET['orderby'] ) : '' );
		$order   = sanitize_text_field( isset( $_GET['order'] ) ? trim( $_GET['order'] ) : '' );

		$search_term = sanitize_text_field( isset( $_POST['s'] ) ? trim( $_POST['s'] ) : '' );
		if ( $search_term == '' ) {

			$search_term = sanitize_text_field( isset( $_GET['s'] ) ? trim( $_GET['s'] ) : '' );
		}

		$datas = $this->list_table_data_fun( $orderby, $order, $search_term );

		$per_page     = 30;
		$current_page = $this->get_pagenum();
		$total_items  = count( $datas );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = array_slice( $datas, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
	}
		// get_columns
	public function get_columns() {

		$columns = array(
			'cb'                => "<input type='checkbox'/>",
			'id'                => __( 'ID', 'llms-attendance' ),
			'title'             => __( 'Enrolled Students', 'llms-attendance' ),
			'attendance_count'  => __( 'Attendance Count', 'llms-attendance' ),
			'attendance_percen' => __( 'Attendance Percentage', 'llms-attendance' ),
		);

		return $columns;
	}

	public function get_hidden_columns() {
		return array( '' );
	}

	public function get_sortable_columns() {
			return array(
				'title' => array( 'title', true ),
				'id'    => array( 'id', true ),
			);

	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

		// REMOVED NONCE -- INTERFERING WITH SAVING POSTS ON METABOXES
		// Add better detection if this class is used on meta box or not.
		/*
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		*/

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php
	}

	// column_default
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'title':
			case 'attendance_count':
			case 'attendance_percen':
				return $item[ $column_name ];

			default:
				return 'no value';

		}

	}

}

/**
 * Shows the List table
 *
 * @return void
 */
function llms_at_list_table_layout() {
	$myRequestTable = new LLMS_Attendance_List_Table_Class();
	global $pagenow;
	?>
	<form method="get">
	<input type="hidden" name="page" value="<?php echo $pagenow; ?>" />
	<?php if ( isset( $myRequestTable ) ) : ?>
		<?php $myRequestTable->prepare_items(); ?>
		<?php $myRequestTable->search_box( __( 'Search Students By ID' ), 'students' ); // Needs To be called after $myRequestTable->prepare_items() ?>
		<?php $myRequestTable->display(); ?>
	<?php endif; ?>
	</form> 
	<?php

}

llms_at_list_table_layout();
