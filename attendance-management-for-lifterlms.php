<?php
/**
 * Attendance Management For LifterLMS WordPress Plugin
 *
 * @package Attendance Management For LifterLMS/Main
 *
 * @since   1.0.0
 * @version 2.0.0
 *
 * Plugin Name: Attendance Management For LifterLMS
 * Plugin URI:  https://github.com/MuhammadFaizanHaidar/attendance-management-for-lifterlms
 * Description: Comprehensive attendance management system for LifterLMS with reporting dashboard, custom database tables, and role-based attendance marking
 * Version:     2.0.0
 * Author:      Muhammad Faizan Haidar
 * Author URI:  https://faizanhaidar.com
 * Text Domain: llms-attendance
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 4.8
 * Requires Plugins: lifterlms
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
	const VERSION = '1.0.3';

	/**
	 * Self Instance.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @var self
	 * @access private
	 * @static
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

		try {
			// Update plugin version.
			update_option( 'llmsat_version', self::VERSION );
			$default_values = get_option( 'llmsat_version' );
			if ( empty( $default_values ) ) {
				$form_data = array();
				update_option( 'llmsat_version', $form_data );
			}

			// Create database tables on activation.
			self::create_database_tables();

			// Set default migration status.
			if ( ! get_option( 'llmsat_migration_status' ) ) {
				update_option( 'llmsat_migration_status', 'not_started' );
			}

			// Set default database version.
			if ( ! get_option( 'llmsat_db_version' ) ) {
				update_option( 'llmsat_db_version', '1.0' );
			}
		} catch ( Exception $e ) {
			// Don't prevent activation, just log the error.
		}
	}

	/**
	 * Create database tables during activation.
	 *
	 * @return void
	 */
	private static function create_database_tables() {
		try {
			// Define the includes directory path directly since constants aren't set up yet.
			$includes_dir = plugin_dir_path( __FILE__ ) . 'includes/';

			// Check if the database file exists.
			$database_file = $includes_dir . 'database/llmsat-database.php';

			if ( ! file_exists( $database_file ) ) {
				return;
			}

			// Include the database class.
			include_once $database_file;

			// Check if the class exists.
			if ( ! class_exists( 'LLMS_AT_Database' ) ) {
				return;
			}

			// Create the database instance and tables.
			$database = new LLMS_AT_Database();

			$database->create_tables();
		} catch ( Exception $e ) {
			// Database creation error.
		} catch ( Error $e ) {
			// Database creation fatal error.
		}
	}

	/**
	 * Handle plugin upgrades and database migrations.
	 *
	 * @return void
	 */
	public function upgrade() {
		$installed_version = get_option( 'llmsat_version', '0.0.0' );
		$current_version   = self::VERSION;

		// If versions are the same, no upgrade needed.
		if ( version_compare( $installed_version, $current_version, '>=' ) ) {
			return;
		}

		// Include database class for upgrades.
		$includes_dir = plugin_dir_path( __FILE__ ) . 'includes/';
		include_once $includes_dir . 'database/llmsat-database.php';
		$database = new LLMS_AT_Database();

		// Check if database tables exist, create if not.
		if ( ! $database->table_exists() ) {
			$database->create_tables();
		}

		// Update version.
		update_option( 'llmsat_version', $current_version );

		// Set default options if not set.
		if ( ! get_option( 'llmsat_migration_status' ) ) {
			update_option( 'llmsat_migration_status', 'not_started' );
		}

		if ( ! get_option( 'llmsat_db_version' ) ) {
			update_option( 'llmsat_db_version', '1.0' );
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

				include_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-core-attendace.php';
			}

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-metabox.php' ) ) {

				include_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-metabox.php';
			}

			if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-shortcodes.php' ) ) {

				include_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-shortcodes.php';
			}
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-settings.php' ) ) {

			include_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-settings.php';
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-allow-integration.php' ) ) {

			include_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-allow-integration.php';
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-reporting.php' ) ) {

			include_once LLMS_At_INCLUDES_DIR . 'database/llmsat-database.php';
			include_once LLMS_At_INCLUDES_DIR . 'database/llmsat-hybrid-manager.php';
			include_once LLMS_At_INCLUDES_DIR . 'integration/llmsat-reporting.php';
			include_once LLMS_At_INCLUDES_DIR . 'database/llmsat-migration.php';
		}

		if ( file_exists( LLMS_At_INCLUDES_DIR . 'settings/options.php' ) ) {

			include_once LLMS_At_INCLUDES_DIR . 'settings/options.php';
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
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 1 );
		add_action( 'init', array( $this, 'init_migration_system' ) );
		add_filter( 'lifterlms_integrations', array( $this, 'register_integration' ), 10, 1 );
	}

	/**
	 * Load plugin text domain
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'llms-attendance',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Initialize migration system.
	 *
	 * @return void
	 */
	public function init_migration_system() {
		// Only initialize in admin area.
		if ( ! is_admin() ) {
			return;
		}

		// Initialize migration system.
		if ( class_exists( 'LLMS_AT_Migration' ) ) {
			new LLMS_AT_Migration();
		}

		// Initialize hybrid manager.
		if ( class_exists( 'LLMS_AT_Hybrid_Manager' ) ) {
			new LLMS_AT_Hybrid_Manager();
		}

		// Initialize database.
		if ( class_exists( 'LLMS_AT_Database' ) ) {
			new LLMS_AT_Database();
		}

		// Initialize testing framework only in development mode.
		if ( defined( 'LLMSAT_DEVELOPMENT_MODE' ) && LLMSAT_DEVELOPMENT_MODE ) {
			$this->init_testing_framework();
		}
	}

	/**
	 * Initialize testing framework (development only).
	 *
	 * @return void
	 */
	private function init_testing_framework() {
		// Load testing files only in development mode.
		if ( file_exists( LLMS_At_INCLUDES_DIR . 'testing/llmsat-testing-framework.php' ) ) {
			include_once LLMS_At_INCLUDES_DIR . 'testing/llmsat-testing-framework.php';
			include_once LLMS_At_INCLUDES_DIR . 'testing/llmsat-test-suite.php';
			include_once LLMS_At_INCLUDES_DIR . 'testing/llmsat-cli-testing.php';

			if ( class_exists( 'LLMS_AT_Testing_Framework' ) ) {
				new LLMS_AT_Testing_Framework();
			}
		}
	}

	/**
	 * Register the integration with LifterLMS
	 *
	 * @param array $integrations Integrations array.
	 *
	 * @return array
	 */
	public function register_integration( $integrations ) {
		$integrations[] = 'LifterLMS_Attendance_Integration';

		return $integrations;
	}

	/**
	 * Enqueue scripts on admin.
	 *
	 * @param string $hook Hook.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function admin_enqueue_scripts( $hook ) {

		$screen = get_current_screen();

		if ( $screen->post_type == 'course' ) {
			$active = 'no';
			if ( function_exists( 'has_blocks' ) ) {
				$active = 'yes';
			}
			/**
			 * plugin's admin style.
			 */
			wp_enqueue_style(
				'llmsat-admin-style',
				LLMS_At_ASSETS_URL . 'css/llmsat-admin-style.css',
				self::VERSION,
				null
			);

			/**
			 * plugin's admin script.
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
				'llmsat_admin',
				array(
					'nonce'               => wp_create_nonce( 'llmsat_admin_nonce' ),
					'block_editor_active' => $active,
				)
			);
		}
	}


	/**
	 * Enqueue scripts on frontend.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function frontend_enqueue_scripts() {
		$active = 'no';
		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			$active = 'yes';
		}
		/**
		 * plugin's frontend script.
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
				'nonce'               => wp_create_nonce( 'llmsat_frontend_nonce' ),
			)
		);
	}

	/**
	 * Add settings link on plugin page.
	 *
	 * @param array $links Links array.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
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
 *
 * @since 1.0.0
 * @version 1.0.0
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
 * @return bool
 *
 * @since 1.0.0
 * @version 1.0.0
 */
function LLMS_Attendance() {
	if ( ! class_exists( 'LifterLMS' ) ) {
		add_action( 'admin_notices', 'llmsat_ready' );

		return false;
	}

	$GLOBALS['LLMS_Attendance'] = LLMS_Attendance::instance();
}

add_action( 'init', 'LLMS_Attendance', 1 );
