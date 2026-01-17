2		<?php
/**
 * Cart handler class for managing cart item data and pricing.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart handler class.
 */
class VPO_Cart_Handler {

	/**
	 * Instance of this class.
	 *
	 * @var VPO_Cart_Handler
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return VPO_Cart_Handler
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
		$this->init();
	}

	/**
	 * Initialize.
	 */
	private function init() {
		// Add custom data to cart item.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

		// Modify cart item price.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_cart_item_price' ), 10, 1 );

		// Display options in cart.
		add_filter( 'woocommerce_cart_item_name', array( $this, 'display_cart_item_options' ), 10, 3 );

		// Display options in checkout.
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'display_checkout_item_options' ), 10, 3 );

		// Save options to order meta.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_meta' ), 10, 4 );
	}

	/**
	 * Add custom data to cart item.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id Product ID.
	 * @param int   $variation_id Variation ID.
	 * @return array Modified cart item data.
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( ! isset( $_POST['vpo'] ) || ! is_array( $_POST['vpo'] ) ) {
			return $cart_item_data;
		}

		// Sanitize all option values.
		$options = array();
		foreach ( $_POST['vpo'] as $field_id => $value ) {
			$field_id = sanitize_key( $field_id );
			if ( is_array( $value ) ) {
				$value = array_map( 'sanitize_text_field', $value );
			} else {
				$value = sanitize_text_field( $value );
			}
			$options[ $field_id ] = $value;
		}

		if ( ! empty( $options ) ) {
			$cart_item_data['vpo_options'] = $options;
		}

		return $cart_item_data;
	}

	/**
	 * Calculate cart item price with options.
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public function calculate_cart_item_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! isset( $cart_item['vpo_options'] ) || ! is_array( $cart_item['vpo_options'] ) ) {
				continue;
			}

			$product_id = $cart_item['product_id'];
			$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;
			$selected_options = $cart_item['vpo_options'];

			error_log( 'VPO Cart Handler: Processing cart item. Product ID: ' . $product_id . ', Variation ID: ' . $variation_id );
			error_log( 'VPO Cart Handler: Selected options: ' . wp_json_encode( $selected_options ) );

			// Get field groups.
			$field_groups = VPO_Data_Handler::get_field_groups( $product_id, $variation_id );
			
			error_log( 'VPO Cart Handler: Found ' . count( $field_groups ) . ' field groups for product ' . $product_id . ', variation ' . $variation_id );

			$additional_price = 0;

			foreach ( $field_groups as $group ) {
				if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
					continue;
				}

				foreach ( $group['fields'] as $field ) {
					$field_id = $field['id'];
					
					error_log( 'VPO Cart Handler: Processing field: ' . $field_id . ', type: ' . $field['type'] );
					error_log( 'VPO Cart Handler: Field data: ' . wp_json_encode( $field ) );

					// Skip if field has condition and condition is not met.
					if ( isset( $field['condition'] ) && is_array( $field['condition'] ) ) {
						$condition_field = isset( $field['condition']['field'] ) ? $field['condition']['field'] : '';
						$condition_value = isset( $field['condition']['value'] ) ? $field['condition']['value'] : '';
						
						// Only apply condition if field is not empty.
						if ( ! empty( $condition_field ) ) {
							error_log( 'VPO Cart Handler: Checking condition. Field: ' . $condition_field . ', Value: ' . $condition_value );
							error_log( 'VPO Cart Handler: Condition field set? ' . ( isset( $selected_options[ $condition_field ] ) ? 'yes' : 'no' ) );
							if ( isset( $selected_options[ $condition_field ] ) ) {
								error_log( 'VPO Cart Handler: Condition field value: ' . wp_json_encode( $selected_options[ $condition_field ] ) );
								error_log( 'VPO Cart Handler: Match? ' . ( $selected_options[ $condition_field ] === $condition_value ? 'yes' : 'no' ) );
							}
							if ( ! isset( $selected_options[ $condition_field ] ) || $selected_options[ $condition_field ] !== $condition_value ) {
								error_log( 'VPO Cart Handler: Field ' . $field_id . ' skipped due to condition' );
								continue;
							}
						}
					}

					if ( ! isset( $selected_options[ $field_id ] ) ) {
						error_log( 'VPO Cart Handler: Field ' . $field_id . ' not in selected options' );
						continue;
					}

					$field_price = 0;
					
					error_log( 'VPO Cart Handler: Field ' . $field_id . ' selected with value: ' . wp_json_encode( $selected_options[ $field_id ] ) );

					switch ( $field['type'] ) {
						case 'radio':
						case 'radio_switch':
						case 'select':
							if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
								foreach ( $field['options'] as $option ) {
									if ( $option['value'] === $selected_options[ $field_id ] ) {
										$field_price = floatval( $option['price'] );
										error_log( 'VPO Cart Handler: Found matching option, price: ' . $field_price );
										break;
									}
								}
							}
							break;

						case 'checkbox':
							if ( '1' === $selected_options[ $field_id ] || true === $selected_options[ $field_id ] ) {
								$field_price = isset( $field['price'] ) ? floatval( $field['price'] ) : 0;
								error_log( 'VPO Cart Handler: Checkbox selected, stored price: ' . ( isset( $field['price'] ) ? $field['price'] : 'NOT SET' ) . ', calculated: ' . $field_price );
							}
							break;
					}

					error_log( 'VPO Cart Handler: Field price: ' . $field_price . ', additional_price so far: ' . $additional_price );
					$additional_price += $field_price;
				}
			}

			error_log( 'VPO Cart Handler: Final additional_price: ' . $additional_price );
			
			if ( $additional_price > 0 ) {
				$product = $cart_item['data'];
				$current_price = $product->get_price();
				error_log( 'VPO Cart Handler: Updating price. Current: ' . $current_price . ', Adding: ' . $additional_price . ', New: ' . ( $current_price + $additional_price ) );
				$product->set_price( $current_price + $additional_price );
			} else {
				error_log( 'VPO Cart Handler: No additional price to add (additional_price = ' . $additional_price . ')' );
			}
		}
	}

	/**
	 * Display options in cart item name.
	 *
	 * @param string $name Product name.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string Modified name.
	 */
	public function display_cart_item_options( $name, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['vpo_options'] ) || ! is_array( $cart_item['vpo_options'] ) ) {
			return $name;
		}

		$product_id = $cart_item['product_id'];
		$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;
		$selected_options = $cart_item['vpo_options'];

		// Get field groups to get field labels.
		$field_groups = VPO_Data_Handler::get_field_groups( $product_id, $variation_id );

		$options_html = array();

		foreach ( $field_groups as $group ) {
			if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field ) {
				$field_id = $field['id'];

				if ( ! isset( $selected_options[ $field_id ] ) ) {
					continue;
				}

				$value = $selected_options[ $field_id ];
				$label = $field['label'];
				$display_value = '';
				$price_display = '';

				switch ( $field['type'] ) {
					case 'radio':
					case 'radio_switch':
					case 'select':
						if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
							foreach ( $field['options'] as $option ) {
								if ( $option['value'] === $value ) {
									$display_value = $option['label'];
									$price = floatval( $option['price'] );
									if ( $price > 0 ) {
										$price_display = ' (+' . wc_price( $price ) . ')';
									}
									break;
								}
							}
						}
						break;

					case 'checkbox':
						if ( '1' === $value || true === $value ) {
							$display_value = __( 'Yes', 'variation-product-options' );
							$price = isset( $field['price'] ) ? floatval( $field['price'] ) : 0;
							if ( $price > 0 ) {
								$price_display = ' (+' . wc_price( $price ) . ')';
							}
						}
						// If checkbox is not checked, skip adding to options_html (handled below).
						break;

					case 'datepicker':
						$display_value = $value;
						break;
				}

				// Only add to options if we have a display value.
				if ( $display_value ) {
					$options_html[] = '<div class="vpo-cart-option"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $display_value ) . $price_display . '</div>';
				}
			}
		}

		if ( ! empty( $options_html ) ) {
			$name .= '<div class="vpo-cart-options">' . implode( '', $options_html ) . '</div>';
		}

		return $name;
	}

	/**
	 * Display options in checkout.
	 *
	 * @param string $quantity Quantity HTML.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string Modified quantity HTML.
	 */
	public function display_checkout_item_options( $quantity, $cart_item, $cart_item_key ) {
		// Options are already displayed in cart item name, so we can return as is.
		// Or we can add additional display here if needed.
		return $quantity;
	}

	/**
	 * Save options to order item meta.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values Cart item values.
	 * @param WC_Order              $order Order object.
	 */
	public function save_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( ! isset( $values['vpo_options'] ) || ! is_array( $values['vpo_options'] ) ) {
			return;
		}

		$product_id = $item->get_product_id();
		$variation_id = $item->get_variation_id();
		$selected_options = $values['vpo_options'];

		// Get field groups to get field labels.
		$field_groups = VPO_Data_Handler::get_field_groups( $product_id, $variation_id );

		foreach ( $field_groups as $group ) {
			if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field ) {
				$field_id = $field['id'];

				if ( ! isset( $selected_options[ $field_id ] ) ) {
					continue;
				}

				$value = $selected_options[ $field_id ];
				$label = $field['label'];
				$display_value = '';

				switch ( $field['type'] ) {
					case 'radio':
					case 'radio_switch':
					case 'select':
						if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
							foreach ( $field['options'] as $option ) {
								if ( $option['value'] === $value ) {
									$display_value = $option['label'];
									break;
								}
							}
						}
						break;

					case 'checkbox':
						if ( '1' === $value || true === $value ) {
							$display_value = __( 'Yes', 'variation-product-options' );
						}
						break;

					case 'datepicker':
						$display_value = $value;
						break;
				}

				if ( $display_value ) {
					$item->add_meta_data( $label, $display_value );
				}
			}
		}
	}
}
