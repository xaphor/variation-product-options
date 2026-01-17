<?php
/**
 * Frontend functionality class.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 */
class VPO_Frontend {

	/**
	 * Instance of this class.
	 *
	 * @var VPO_Frontend
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return VPO_Frontend
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
		// Register AJAX handlers immediately (before init hook).
		$this->register_ajax_handlers();
		
		// Initialize other hooks.
		$this->init();
	}

	/**
	 * Register AJAX handlers.
	 * These must be registered early so they're available when AJAX requests come in.
	 */
	private function register_ajax_handlers() {
		// AJAX handlers for dynamic pricing.
		add_action( 'wp_ajax_vpo_calculate_price', array( $this, 'ajax_calculate_price' ) );
		add_action( 'wp_ajax_nopriv_vpo_calculate_price', array( $this, 'ajax_calculate_price' ) );

		// AJAX handler for loading fields for variation.
		add_action( 'wp_ajax_vpo_get_variation_fields', array( $this, 'ajax_get_variation_fields' ) );
		add_action( 'wp_ajax_nopriv_vpo_get_variation_fields', array( $this, 'ajax_get_variation_fields' ) );
	}

	/**
	 * Initialize.
	 */
	private function init() {
		// Only add frontend-specific hooks if not doing AJAX.
		if ( ! wp_doing_ajax() ) {
			// Display fields on product page.
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_product_options' ), 10 );

			// Enqueue frontend assets.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		}
	}

	/**
	 * Display product options on product page.
	 */
	public function display_product_options() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$product_id = $product->get_id();
		$variation_id = 0;

		// For variable products, we need to handle this differently.
		// The container must be created on page load so JavaScript can populate it.
		if ( $product->is_type( 'variable' ) ) {
			// For variable products, render an empty container that JavaScript will populate
			// when a variation is selected. We need to check if there are any field groups
			// that apply to this product or any of its variations.
			$has_any_groups = $this->product_has_field_groups( $product_id );
			
			if ( $has_any_groups ) {
				// Render empty container - JavaScript will populate it via AJAX.
				$this->render_fields( array(), $product_id, $variation_id, true );
			}
			return;
		}

		// For simple products, get field groups normally.
		$field_groups = VPO_Data_Handler::get_field_groups( $product_id, $variation_id );

		if ( empty( $field_groups ) ) {
			return;
		}

		// Render fields.
		$this->render_fields( $field_groups, $product_id, $variation_id );
	}

	/**
	 * Check if a product has any field groups assigned to it or its variations.
	 *
	 * @param int $product_id Product ID.
	 * @return bool True if product has any field groups.
	 */
	private function product_has_field_groups( $product_id ) {
		$all_groups = VPO_Data_Handler::get_all_field_groups();
		
		foreach ( $all_groups as $group ) {
			if ( ! isset( $group['rules'] ) ) {
				continue;
			}
			
			$rules = $group['rules'];
			
			// Check if applies to all products.
			if ( isset( $rules['all_products'] ) && $rules['all_products'] ) {
				return true;
			}
			
			// Check specific product IDs.
			if ( isset( $rules['product_ids'] ) && is_array( $rules['product_ids'] ) ) {
				if ( in_array( $product_id, $rules['product_ids'], true ) ) {
					return true;
				}
			}
			
			// For variable products, check if any of its variations have field groups.
			if ( isset( $rules['variation_ids'] ) && is_array( $rules['variation_ids'] ) ) {
				// Get all variations of this product.
				$product = wc_get_product( $product_id );
				if ( $product && $product->is_type( 'variable' ) ) {
					$variation_ids = $product->get_children();
					foreach ( $variation_ids as $variation_id ) {
						if ( in_array( $variation_id, $rules['variation_ids'], true ) ) {
							return true;
						}
					}
				}
			}
		}
		
		return false;
	}

	/**
	 * Render fields HTML.
	 *
	 * @param array $field_groups Field groups.
	 * @param int   $product_id Product ID.
	 * @param int   $variation_id Variation ID.
	 * @param bool  $empty Whether this is an empty container for variable products.
	 */
	private function render_fields( $field_groups, $product_id, $variation_id, $empty = false ) {
		?>
		<div class="vpo-product-options" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
			<?php
			if ( ! $empty ) {
				foreach ( $field_groups as $group_id => $group ) {
					if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
						continue;
					}

					foreach ( $group['fields'] as $field ) {
						echo VPO_Field_Types::render_field( $field, $product_id, $variation_id );
					}
				}
			}
			?>
			<div class="vpo-total-price" style="display: none;">
				<strong><?php esc_html_e( 'Additional Options Total:', 'variation-product-options' ); ?></strong>
				<span class="vpo-price-amount"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_product() ) {
			return;
		}

		// Debug: Verify this function is running.
		error_log( 'VPO: enqueue_frontend_assets() called. Plugin URL: ' . VPO_PLUGIN_URL );

		// Enqueue jQuery UI Datepicker (required for date field).
		// WordPress includes jQuery UI in wp-admin, but not always on frontend.
		// We'll register our own if needed.
		if ( ! wp_script_is( 'jquery-ui-datepicker', 'registered' ) ) {
			wp_register_script(
				'jquery-ui-datepicker',
				'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js',
				array( 'jquery' ),
				'1.13.2',
				true
			);
		}
		if ( ! wp_style_is( 'jquery-ui-style', 'registered' ) ) {
			wp_register_style(
				'jquery-ui-style',
				'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css',
				array(),
				'1.13.2'
			);
		}

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-style' );

		// Enqueue frontend CSS.
		wp_enqueue_style(
			'vpo-frontend',
			VPO_PLUGIN_URL . 'assets/css/frontend.css',
			array( 'jquery-ui-style' ),
			VPO_VERSION
		);

		// Enqueue frontend JS.
		wp_enqueue_script(
			'vpo-frontend',
			VPO_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			VPO_VERSION,
			true
		);

		// Localize script for AJAX.
		wp_localize_script(
			'vpo-frontend',
			'vpoData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vpo-ajax-nonce' ),
			)
		);
	}

	/**
	 * AJAX handler for price calculation.
	 */
	public function ajax_calculate_price() {
		// Prevent any output before JSON
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		ob_start();
		
		// Verify nonce, but don't die on failure - return error instead.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vpo-ajax-nonce' ) ) {
			ob_end_clean();
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'variation-product-options' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$selected_options = isset( $_POST['options'] ) ? (array) $_POST['options'] : array();

		if ( ! $product_id ) {
			ob_end_clean();
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'variation-product-options' ) ) );
		}

		// Get field groups.
		$field_groups = VPO_Data_Handler::get_field_groups( $product_id, $variation_id );

		$total_price = 0;
		$price_breakdown = array();

		foreach ( $field_groups as $group ) {
			if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field ) {
				$field_id = $field['id'];

				// Skip if field has condition and condition is not met.
				if ( isset( $field['condition'] ) && is_array( $field['condition'] ) ) {
					$condition_field = isset( $field['condition']['field'] ) ? $field['condition']['field'] : '';
					$condition_value = isset( $field['condition']['value'] ) ? $field['condition']['value'] : '';
					
					// Only process condition if field name is not empty.
					if ( ! empty( $condition_field ) ) {
						if ( ! isset( $selected_options[ $condition_field ] ) || $selected_options[ $condition_field ] !== $condition_value ) {
							continue;
						}
					}
				}

				// Calculate price based on field type.
				if ( ! isset( $selected_options[ $field_id ] ) ) {
					continue;
				}

				$field_price = 0;

				switch ( $field['type'] ) {
					case 'radio':
					case 'radio_switch':
					case 'select':
						if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
							foreach ( $field['options'] as $option ) {
								if ( $option['value'] === $selected_options[ $field_id ] ) {
									$field_price = floatval( $option['price'] );
									break;
								}
							}
						}
						break;

					case 'checkbox':
						if ( '1' === $selected_options[ $field_id ] || true === $selected_options[ $field_id ] ) {
							$field_price = isset( $field['price'] ) ? floatval( $field['price'] ) : 0;
						}
						break;

					case 'datepicker':
						$field_price = 0; // Datepicker has no price impact.
						break;
				}

				if ( $field_price > 0 ) {
					$total_price += $field_price;
					$price_breakdown[] = array(
						'field' => $field['label'],
						'price' => $field_price,
					);
				}
			}
		}

		// Clean all output buffers before sending JSON
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		wp_send_json_success(
			array(
				'total_price'     => $total_price,
				'formatted_price' => wc_price( $total_price ),
				'breakdown'       => $price_breakdown,
			)
		);
	}

	/**
	 * AJAX handler for getting fields for a variation.
	 */
	public function ajax_get_variation_fields() {
		// Prevent any output before JSON
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		ob_start();
		
		// Verify nonce, but don't die on failure - return error instead.
		if ( ! isset( $_POST['nonce'] ) ) {
			ob_end_clean();
			wp_send_json_error( array( 'message' => __( 'Nonce not provided.', 'variation-product-options' ) ) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'vpo-ajax-nonce' ) ) {
			ob_end_clean();
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page.', 'variation-product-options' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		error_log( 'VPO AJAX: vpo_get_variation_fields called with product_id=' . $product_id . ', variation_id=' . $variation_id );

		if ( ! $product_id ) {
			ob_end_clean();
			error_log( 'VPO AJAX: Invalid product ID' );
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'variation-product-options' ) ) );
		}

		// Get field groups for this product/variation.
		$field_groups = VPO_Data_Handler::get_field_groups( $product_id, $variation_id );
		
		error_log( 'VPO AJAX: Found ' . count( $field_groups ) . ' field groups for product_id=' . $product_id . ', variation_id=' . $variation_id );

		ob_start();
		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $group_id => $group ) {
				if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
					continue;
				}

				foreach ( $group['fields'] as $field ) {
					echo VPO_Field_Types::render_field( $field, $product_id, $variation_id );
				}
			}
		}
		$fields_html = ob_get_clean();
		
		error_log( 'VPO AJAX: Returning HTML length: ' . strlen( $fields_html ) );

		// Clean all output buffers before sending JSON
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		wp_send_json_success(
			array(
				'html' => $fields_html,
			)
		);
	}
}
