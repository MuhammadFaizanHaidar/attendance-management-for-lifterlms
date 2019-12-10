<?php
/**
 * Attendance Management For LifterLMS Addon
 *
 * Displays the Attendance Management For LifterLMS Options.
 *
 * @author   Muhammad Faizan Haidar
 * @category Admin
 * @package  Attendance Management For LifterLMS/Plugin Options
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class LLMS_Attendance_Opions
 */
class LLMS_Attendance_Opions {

	private $license_class;

	public $page_tab;

    /**
     * LLMS_Attendance_Opions constructor.
     */
	public function __construct() {

	    $this->page_tab = sanitize_text_field( isset( $_GET['tab'] ) ? $_GET['tab'] : 'general' );
		add_action( 
			'admin_menu', 
			[ $this, 'llmsat_menu' ]
		);

		add_action( 
			'admin_notices', 
			[ $this, 'llmsat_admin_notices' ] 
		);

		add_action( 
			'admin_post_llmsat_admin_settings', 
			[ $this, 'llmsat_admin_settings_save' ] 
		);

        add_filter( 
			'admin_footer_text', 
			[ $this, 'remove_footer_admin' ] 
		);
	}

	
	/**
     * Display Notices
     */
    public function llmsat_admin_notices() {

		$screen   = get_current_screen();
		$updated  = false;
        if( $screen->base != 'lifterlms_page_lifterlms-attendance-management-options' ) {
            return;
		}
		
		if( isset( $_GET[ 'settings-updated' ] ) ) {
			$updated = sanitize_text_field( $_GET[ 'settings-updated' ] );
		}
        if( isset( $_POST['llmsat_settings_submit'] ) || $updated  == true ) {
            $class = 'notice notice-success is-dismissible';
            $message = __( 'Settings Saved', LLMS_At_TEXT_DOMAIN );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        }
    }

	/**
	 * Save Plugin's Settings
	 */
	public function llmsat_admin_settings_save() {
		if( isset( $_POST['llmsat_settings_submit'] ) ) {

            $llmsat_options  = array();

			$delete_settings = sanitize_text_field( isset( $_POST['llmsat_delete_attendance'] ) ? $_POST['llmsat_delete_attendance'] : 'no' );

			$llmsat_options['llmsat_delete_attendance'] = $delete_settings;


            update_option( 'llmsat_options', $llmsat_options );
			wp_safe_redirect( add_query_arg( 'settings-updated', 'true', sanitize_text_field( $_POST['_wp_http_referer'] ) ) );
			$class = 'llmsat-notice hidden notice notice-success is-dismissible';
			$message = __( 'Settings Updated.', LLMS_At_TEXT_DOMAIN );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			exit;
        }

	}

	/**
	 * Add plugin's menu
	 */
	public function llmsat_menu() {

		add_submenu_page(
			'lifterlms',
			__( 'Attendance Management For LifterLMS', LLMS_At_TEXT_DOMAIN ),
			__( 'Attendance Management For LifterLMS', LLMS_At_TEXT_DOMAIN ),
			'manage_options',
			'lifterlms-attendance-management-options',
			[ $this, 'LLMS_Attendance' ]
		);
	}

	/**
	 * Fields Generator
	 *
	 * @param string $label
	 * @param $name
	 * @param $field_type
	 * @param string $field_value
	 * @param string $hint
	 * @param string $before_text
	 * @param string $after_text
	 */
	public function create_fields( $label = '', $name, $field_type, $field_value = '', $checked = '', $hint = '', $before_text = '', $after_text = '' ) {

		if ( empty( $field_type ) || is_null( $field_type ) ) return;
		if ( empty( $name ) || is_null( $name ) ) return;

		if ( 'checkbox' === $field_type ) {

			if ( ! empty( $label ) ) {
				echo '<td>';
				echo '<label for="' . $name . '" class="label">' . $label . '</label>';
				echo '</td>';
			} else {
				echo '';
			}

			echo '<td>';
			echo $before_text . ' <input type="' . $field_type . '" ' . $checked . '  class="checkbox" id="' . $name . '" name="' . $name . '" /> ' . $after_text;
			if ( ! empty( $hint ) ) {
				echo '<span class="hint">' . $hint . '</span>';
			}
			echo '</td>';
		} elseif ( 'text' === $field_type || 'number' === $field_type ) {
			echo '<td>';
			if ( ! empty( $label ) ) {
				echo '<label for=" ' . $name . '" class="label">' . $label . '</label>';
			} else {
				echo '&nbsp;';
			}
			echo '</td>';
			echo '<td>';
			$description_text = ( empty( $field_value ) ? 'Quiz Content' : $field_value );
			echo $before_text . ' <input type="' . $field_type . '" id="' . $name . '" value="' . $description_text . '" name="' . $name . '" /> ' . $after_text;
			if ( ! empty( $hint ) ) {
				echo '<span class="hint">' . $hint . '</span>';
			}
			echo '</td>';
		} elseif ( 'textarea' === $field_type ) {
			if ( ! empty( $label ) ) {
				echo '<label for="' . $name . '" class="label-textarea">' . $label . '</label>';
			}
			echo $before_text . ' <textarea id="' . $name . '" cols="100" rows="7" name="' . $name . '" />' . $field_value . '</textarea> ' . $after_text;
			if ( ! empty( $hint ) ) {
				echo '<span class="hint">' . $hint . '</span>';
			}
		} elseif ( 'radio' === $field_type ) {
			echo $before_text . ' <input type="' . $field_type . '" ' . $checked . ' class="' . $name . '" value="' . $field_value . '" name="' . $name . '" /> ' . $after_text;
			if ( ! empty( $hint ) ) {
				echo '<span class="hint">' . $hint . '</span>';
			}
		}
	}

	/**
	 * Setting page data
	 */
	public function LLMS_Attendance() {

	    ?>
        <div id="wrap" class="llmsat-settings-wrapper">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php echo __( 'Attendance Management For LifterLMS Settings', LLMS_At_TEXT_DOMAIN ); ?></h1>

            <div class="nav-tab-wrapper">
                <?php
                $llmsat_settings_sections = $this->llmsat_get_setting_sections();
                foreach( $llmsat_settings_sections as $key => $llmsat_settings_section ) {
                    ?>
                    <a href="?page=lifterlms-attendance-management-options&tab=<?php echo $key; ?>"
                       class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
                        <i class="fa <?php echo $llmsat_settings_section['icon']; ?>" aria-hidden="true"></i>
                        <?php _e( $llmsat_settings_section['title'], LLMS_At_TEXT_DOMAIN ); ?>
                    </a>
                    <?php
                }
                ?>
            </div>

            <?php
            foreach( $llmsat_settings_sections as $key => $llmsat_settings_section ) {
                if( $this->page_tab == $key ) {
                    include( 'templates/' . $key . '.php' );
                }
            }
            ?>
        </div>
		<?php
	}

    /**
     * LLMS_Attendance Settings Sections
     *
     * @return mixed|void
     */
    public function llmsat_get_setting_sections() {

        $llmsat_settings_sections = array(
			'general' => array(
                'title' => __( 'General Option', LLMS_At_TEXT_DOMAIN ),
                'icon' => 'dashicons-admin-generic',
            ),
        );

        return apply_filters( 'llmsat_settings_sections', $llmsat_settings_sections );

    }
	

    /**
     * Add footer branding
     *
     * @param $footer_text
     * @return mixed
     */
    function remove_footer_admin ( $footer_text ) {
        if( isset( $_GET['page'] ) && ( sanitize_text_field( $_GET['page'] ) == 'lifterlms-attendance-management-options' ) ) {
			_e( 'Fueled by <a href="http://www.wordpress.org" target="_blank">WordPress</a> | developed and designed by 
			<a href="https:faizanhaidar.com/" target="_blank">Muhammad Faizan Haidar</a>
			</p>',
			LLMS_At_TEXT_DOMAIN 
		);

        } else {

            return $footer_text;
        }
    }
}

$GLOBALS['LLMS_Attendance_Opions'] = new LLMS_Attendance_Opions();