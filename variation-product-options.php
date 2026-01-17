<?php
/**
 * Plugin Name: Variation Product Options
 * Plugin URI: https://github.com/zaffarullah/variation-product-options
 * Description: A lightweight WooCommerce plugin that allows store owners to add custom options (fields) to products at both Product and Variation levels. Supports Radio buttons, Switches, Checkboxes, Dropdowns, Datepickers, conditional logic, and dynamic pricing.
 * Version: 1.0.2
 * Author: Zaffarullah
 * Author URI: mailto:xaphor.emam@gmail.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: variation-product-options
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 10.4
 *
 * @package VariationProductOptions
 * @version 1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'VPO_VERSION', '1.0.2' );
define( 'VPO_PLUGIN_FILE', __FILE__ );
define( 'VPO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VPO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VPO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare WooCommerce compatibility early (before WooCommerce initializes).
 * This must run on 'before_woocommerce_init' hook.
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', VPO_PLUGIN_FILE, true );
	}
} );

/**
 * Check if WooCommerce is active.
 */
function vpo_check_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Display notice if WooCommerce is not active.
 */
function vpo_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Variation Product Options', 'variation-product-options' ); ?>:</strong>
			<?php
			echo esc_html__(
				'This plugin requires WooCommerce to be installed and active.',
				'variation-product-options'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Main plugin class.
 */
class Variation_Product_Options {

	/**
	 * Plugin instance.
	 *
	 * @var Variation_Product_Options
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Variation_Product_Options
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Don't initialize immediately - wait for plugins_loaded.
		// This ensures WooCommerce is loaded first.
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies() {
		// Core includes.
		require_once VPO_PLUGIN_DIR . 'includes/class-vpo-core.php';
		require_once VPO_PLUGIN_DIR . 'includes/class-vpo-data-handler.php';
		require_once VPO_PLUGIN_DIR . 'includes/class-vpo-field-types.php';

		// Cart handler - always load (needed for cart operations from admin, frontend, and AJAX).
		require_once VPO_PLUGIN_DIR . 'frontend/class-vpo-cart-handler.php';

		// Admin includes.
		if ( is_admin() ) {
			require_once VPO_PLUGIN_DIR . 'admin/class-vpo-admin.php';
			require_once VPO_PLUGIN_DIR . 'admin/class-vpo-field-builder.php';
		}

		// Frontend includes - always load for AJAX, or on frontend.
		// This ensures AJAX handlers are available.
		if ( ! is_admin() || wp_doing_ajax() ) {
			require_once VPO_PLUGIN_DIR . 'frontend/class-vpo-frontend.php';
		}
	}

	/**
	 * Initialize plugin (called on plugins_loaded).
	 */
	public function init() {
		// Check WooCommerce dependency.
		if ( ! vpo_check_woocommerce_active() ) {
			add_action( 'admin_notices', 'vpo_woocommerce_missing_notice' );
			return;
		}

		// Load plugin files.
		$this->load_dependencies();

		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Load text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 20 );

		// Initialize core functionality on 'wp_loaded' (after init but early enough for wp_enqueue_scripts).
		// Priority 10 ensures it runs before most themes/plugins.
		add_action( 'wp_loaded', array( $this, 'init_core' ), 10 );
	}


	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'variation-product-options',
			false,
			dirname( VPO_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize core functionality.
	 */
	public function init_core() {
		// Debug: Log initialization.
		error_log( 'VPO: init_core() called. is_admin=' . ( is_admin() ? 'true' : 'false' ) . ', wp_doing_ajax=' . ( wp_doing_ajax() ? 'true' : 'false' ) );

		// Initialize core classes.
		VPO_Core::get_instance();
		
		// Initialize cart handler (always needed for cart operations).
		if ( class_exists( 'VPO_Cart_Handler' ) ) {
			VPO_Cart_Handler::get_instance();
		}
		
		// Initialize admin or frontend.
		if ( is_admin() && ! wp_doing_ajax() ) {
			if ( class_exists( 'VPO_Admin' ) ) {
				VPO_Admin::get_instance();
			}
		}
		
		// Always initialize frontend for AJAX requests FIRST (so handlers are registered early).
		if ( wp_doing_ajax() ) {
			if ( class_exists( 'VPO_Frontend' ) ) {
				error_log( 'VPO: Initializing VPO_Frontend for AJAX' );
				VPO_Frontend::get_instance();
			}
		}
		
		// Initialize frontend for non-AJAX frontend requests.
		if ( ! is_admin() && ! wp_doing_ajax() ) {
			if ( class_exists( 'VPO_Frontend' ) ) {
				error_log( 'VPO: Initializing VPO_Frontend for frontend' );
				VPO_Frontend::get_instance();
			}
		}
	}
}

/**
 * Plugin activation handler.
 */
function vpo_activate() {
	// Check WooCommerce on activation.
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		deactivate_plugins( VPO_PLUGIN_BASENAME );
		wp_die(
			esc_html__(
				'Variation Product Options requires WooCommerce to be installed and active. Please install and activate WooCommerce first.',
				'variation-product-options'
			)
		);
	}
}

/**
 * Plugin deactivation handler.
 */
function vpo_deactivate() {
	// Cleanup tasks if needed.
}

// Register activation and deactivation hooks.
register_activation_hook( VPO_PLUGIN_FILE, 'vpo_activate' );
register_deactivation_hook( VPO_PLUGIN_FILE, 'vpo_deactivate' );

/**
 * Initialize the plugin after WooCommerce is loaded.
 */
function vpo_init() {
	// Wait for WooCommerce to load before initializing.
	add_action( 'plugins_loaded', function() {
		$instance = Variation_Product_Options::get_instance();
		$instance->init();
	}, 20 ); // Priority 20 ensures WooCommerce loads first (default is 10).
}

// Start the plugin initialization.
vpo_init();
