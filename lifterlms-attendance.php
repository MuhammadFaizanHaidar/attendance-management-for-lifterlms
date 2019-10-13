<?php
/**
 * Plugin Name: LifterLMS Attendance Management Addon
 * Plugin URI:  https://github.com/MuhammadFaizanHaidar/lifterlms-attendance-management-addon
 * Description: This addon will provide the Attendance functionality for LifterLMS registered users
 * Version:     1.0
 * Author:      Muhammad Faizan Haidar
 * Author URI:  https://profiles.wordpress.org/muhammadfaizanhaidar/
 * Text Domain: llms-attendance
 * License: GNU AGPL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, [ 'LLMS_Attendance', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'LLMS_Attendance', 'deactivation' ] );

/**
 * Class LLMS_Attendance
 */
class LLMS_Attendance {

	const VERSION = '1.0';

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @return LLMS_Attendance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof LLMS_Attendance ) ) {
			self::$instance = new self;
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Activation function hook
	 *
	 * @return void
	 */
	public function activation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		update_option( 'llmsat_version', self::VERSION );
		$default_values = get_option( 'llmsat_version' );
		if ( empty( $default_values ) ) {
			$form_data = [];
			update_option( 'llmsat_version', $form_data );
		}
	}

	/**
	 * Deactivation function hook
	 *
	 * @return void
	 */
	public function deactivation() {
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
	 * Pugin Include Required Files
	 */
	private function includes() {

		if ( 'yes' === get_option( 'llms_integration_lifterlms_attendance_enabled', 'no' ) ) {

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-core-attendace.php' ) ) {

				require_once( LLMS_At_INCLUDES_DIR . 'integration/llmsat-core-attendace.php' );
			}

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-metabox.php' ) ) {

				require_once( LLMS_At_INCLUDES_DIR . 'integration/llmsat-metabox.php' );
			}


			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-shortcodes.php' ) ) {

				require_once( LLMS_At_INCLUDES_DIR . 'integration/llmsat-shortcodes.php' );
			}
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-settings.php' ) ) {

			require_once( LLMS_At_INCLUDES_DIR . 'integration/llmsat-settings.php' );
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-allow-integration.php' ) ) {

			require_once( LLMS_At_INCLUDES_DIR . 'integration/llmsat-allow-integration.php' );
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'settings/options.php' ) ) {

			require_once( LLMS_At_INCLUDES_DIR . 'settings/options.php' );
		}

	}

	private function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts',    [ $this, 'frontend_enqueue_scripts' ], 11 );
		add_filter( 'plugin_action_links_' . LLMS_At_BASE_DIR, [ $this, 'settings_link' ], 10, 1 );
		add_action( 'plugins_loaded',        [ $this, 'upgrade' ] );
		add_action( 'plugins_loaded', add_filter( 'gettext', [$this, 'activation_message'], 99, 3 ) );
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
	 * Translate the "Plugin activated." string
	 *
	 * @param [type] $translated_text
	 * @param [type] $untranslated_text
	 * @param [type] $domain
	 *
	 * @return void
	 */

	public function activation_message( $translated_text, $untranslated_text, $domain ) {
		$old = [
			"Plugin <strong>activated</strong>.",
			"Selected plugins <strong>activated</strong>.",
		];

		$new = "The Core is stable and the Plugin is <strong>deactivated</strong>";

		if ( ! class_exists( 'LifterLMS' ) && in_array( __( $untranslated_text, LLMS_At_TEXT_DOMAIN ), __( $old, LLMS_At_TEXT_DOMAIN ), true ) ) {
			$translated_text = __( $new, LLMS_At_TEXT_DOMAIN );
			remove_filter( current_filter(), __FUNCTION__, 99 );
		}

		return $translated_text;
	}

	/**
	 * Enqueue scripts on admin
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {

		$screen          = get_current_screen();
		/**
		 * plugin's admin script
		 */
		wp_enqueue_script( 'llmsat-admin-script', LLMS_At_ASSETS_URL . 'js/llmsat-admin-script.js', [ 'jquery' ], self::VERSION, true );

		if( $screen->post_type == "course" ) {
			/**
			 * plugin's admin style
			 */
			wp_enqueue_style( 'llmsat-admin-style', LLMS_At_ASSETS_URL . 'css/llmsat-admin-style.css', self::VERSION, null );
		}
	}

	/**
	 * Enqueue scripts on frontend
	 */
	public function frontend_enqueue_scripts() {
		/**
		 * plugin's frontend script
		 */
		wp_enqueue_script( 'llmsat-front-script', LLMS_At_ASSETS_URL . 'js/llmsat-front-script.js', [ 'jquery' ], self::VERSION, true );
		wp_enqueue_style( 'llmsat-front-style', LLMS_At_ASSETS_URL . 'css/llmsat-front-style.css', self::VERSION, null );

		wp_localize_script( 'llmsat-front-script', 'llmsat_ajax_url',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Add settings link on plugin page
	 *
	 * @return void
	 */
	public function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=lifterlms-attendance-management-options">' . __( 'Settings', LLMS_At_TEXT_DOMAIN ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}
}

/**
 * Display admin notifications if dependency not found.
 */
function llmsat_ready() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! class_exists( 'LifterLMS' ) ) {
		$class   = 'notice is-dismissible error';
		$message = __( 'LifterLMS Attendance add-on requires <a href="https://wordpress.org/plugins/lifterlms/" target="_BLANK">LifterLMS</a> plugin to be activated.', 'llms-attendance' );
		printf( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	return true;
}

/**
 * @return bool
 */
function LLMS_Attendance() {
	if ( ! class_exists( 'LifterLMS' ) ) {
		add_action( 'admin_notices', 'llmsat_ready' );

		return false;
	}

	$GLOBALS['LLMS_Attendance'] = LLMS_Attendance::instance();
}

add_action( 'plugins_loaded', 'LLMS_Attendance' );
