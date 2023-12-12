<?php
/**
 * Attendance Management For LifterLMS WordPress Plugin
 *
 * @package Attendance Management For LifterLMS/Main
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * Plugin Name: Attendance Management For LifterLMS
 * Plugin URI:  https://github.com/MuhammadFaizanHaidar/attendance-management-for-lifterlms
 * Description: This addon provides the Attendance functionality for LifterLMS registered users
 * Version:     1.0.2
 * Author:      Muhammad Faizan Haidar
 * Author URI:  https://faizanhaidar.com
 * Text Domain: llms-attendance
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 4.8
 * Tested up to: 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, array( 'LLMS_Attendance', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'LLMS_Attendance', 'deactivation' ) );

/**
 * Class LLMS_Attendance
 */
class LLMS_Attendance {

	/**
	 * Attendance Management For LifterLMS Addon Version
	 *
	 * @var const string
	 */
	const VERSION = '1.0.2';

	/**
	 * @var self
	 */
	private static $instance = null;

	
	/**
	 * Self Instance
	 *
	 * @return LLMS_Attendance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof LLMS_Attendance ) ) {
			self::$instance = new self();
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Activation function hook.
	 *
	 * @return void
	 */
	public static function activation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		update_option( 'llmsat_version', self::VERSION );
		$default_values = get_option( 'llmsat_version' );
		if ( empty( $default_values ) ) {
			$form_data = array();
			update_option( 'llmsat_version', $form_data );
		}
	}

	/**
	 * Deactivation function hook
	 *
	 * @return bool
	 */
	public static function deactivation() {
		delete_option( 'llmsat_version' );

		return false;
	}

	/**
	 * Upgrade function hook
	 *
	 * @return void
	 */
	public function upgrade() {
		if ( get_option( 'llmsat_version' ) != self::VERSION ) {
		}
	}

	/**
	 * Setup Constants
	 */
	private function setup_constants() {

		/**
		 * Plugin Text Domain
		 */
		define( 'LLMS_At_TEXT_DOMAIN', 'llms-attendance' );

		/**
		 * Plugin Directory
		 */
		define( 'LLMS_At_DIR', plugin_dir_path( __FILE__ ) );
		define( 'LLMS_At_DIR_FILE', LLMS_At_DIR . basename( __FILE__ ) );
		define( 'LLMS_At_INCLUDES_DIR', trailingslashit( LLMS_At_DIR . 'includes' ) );
		define( 'LLMS_At_TEMPLATES_DIR', trailingslashit( LLMS_At_DIR . 'templates' ) );
		define( 'LLMS_At_BASE_DIR', plugin_basename( __FILE__ ) );

		/**
		 * Plugin URLS
		 */
		define( 'LLMS_At_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
		define( 'LLMS_At_ASSETS_URL', trailingslashit( LLMS_At_URL . 'assets' ) );
	}

	/**
	 * Pugin Include Required Files.
	 *
	 * @return void
	 */
	private function includes() {

		if ( 'yes' === get_option( 'llms_integration_lifterlms_attendance_enabled', 'no' ) ) {

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-core-attendace.php' ) ) {

				require_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-core-attendace.php';
			}

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-metabox.php' ) ) {

				require_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-metabox.php';
			}

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-shortcodes.php' ) ) {

				require_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-shortcodes.php';
			}
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-settings.php' ) ) {

			require_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-settings.php';
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-allow-integration.php' ) ) {

			require_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-allow-integration.php';
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'settings/options.php' ) ) {

			require_once LLMS_At_INCLUDES_DIR . 'settings/options.php';
		}

	}

	/**
	 * Hooks management
	 *
	 * @return void
	 */
	private function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ), 11 );
		add_filter( 'plugin_action_links_' . LLMS_At_BASE_DIR, array( $this, 'settings_link' ), 10, 1 );
		add_action( 'plugins_loaded', array( $this, 'upgrade' ) );
		add_filter( 'lifterlms_integrations', array( $this, 'register_integration' ), 10, 1 );
	}

	/**
	 * Register the integration with LifterLMS
	 *
	 * @param array $integrations
	 *
	 * @return   array
	 */
	public function register_integration( $integrations ) {
		$integrations[] = 'LifterLMS_Attendance_Integration';

		return $integrations;
	}

	/**
	 * Enqueue scripts on admin
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {

		$screen = get_current_screen();

		if ( $screen->post_type == 'course' ) {
			$active = 'no';
			if ( function_exists( 'has_blocks' ) ) {
				$active = 'yes';
			}
			/**
			 * plugin's admin style
			 */
			wp_enqueue_style(
				'llmsat-admin-style',
				LLMS_At_ASSETS_URL . 'css/llmsat-admin-style.css',
				self::VERSION,
				null
			);

			/**
			 * plugin's admin script
			 */
			wp_enqueue_script(
				'llmsat-admin-script',
				LLMS_At_ASSETS_URL . 'js/llmsat-admin-script.js',
				array( 'jquery' ),
				self::VERSION,
				true
			);

			wp_localize_script(
				'llmsat-admin-script',
				'llmsat_block_editor',
				array(
					'block_editor_active' => $active,
				)
			);
		}
	}

	
	/**
	 * Enqueue scripts on frontend
	 *
	 * @return void
	 */
	public function frontend_enqueue_scripts() {
		$active = 'no';
		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			$active = 'yes';
		}
		/**
		 * plugin's frontend script
		 */
		wp_enqueue_script(
			'llmsat-front-script',
			LLMS_At_ASSETS_URL . 'js/llmsat-front-script.js',
			array( 'jquery' ),
			self::VERSION,
			true
		);

		wp_enqueue_style(
			'llmsat-front-style',
			LLMS_At_ASSETS_URL . 'css/llmsat-front-style.css',
			self::VERSION,
			null
		);

		wp_localize_script(
			'llmsat-front-script',
			'llmsat_ajax_url',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'block_editor_active' => $active,
			)
		);
	}

	/**
	 * Add settings link on plugin page
	 *
	 * @return string
	 */
	public function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=lifterlms-attendance-management-options">'
			. esc_html__( 'Settings', 'llms-attendance' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}
}

/**
 * Display admin notifications if dependency not found.
 *
 * @return void
 */
function llmsat_ready() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! class_exists( 'LifterLMS' ) ) {
		$class   = 'notice is-dismissible error';
		$message = __(
			'Attendance Management For LifterLMS add-on requires <a href="https://wordpress.org/plugins/lifterlms/" 
			target="_BLANK">LifterLMS</a> plugin to be activated.',
			'llms-attendance'
		);
		printf( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	return true;
}


/**
 * Plugin Initiation.
 *
 * @return void/bool
 */
function LLMS_Attendance() {
	if ( ! class_exists( 'LifterLMS' ) ) {
		add_action( 'admin_notices', 'llmsat_ready' );

		return false;
	}

	$GLOBALS['LLMS_Attendance'] = LLMS_Attendance::instance();
}

add_action( 'plugins_loaded', 'LLMS_Attendance' );
