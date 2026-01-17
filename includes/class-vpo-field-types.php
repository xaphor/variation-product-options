<?php
/**
 * Field types handler class.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field types class.
 */
class VPO_Field_Types {

	/**
	 * Get available field types.
	 *
	 * @return array Field types.
	 */
	public static function get_field_types() {
		return array(
			'radio'        => __( 'Radio Buttons', 'variation-product-options' ),
			'radio_switch' => __( 'Radio Switch/Toggle', 'variation-product-options' ),
			'checkbox'     => __( 'Checkbox', 'variation-product-options' ),
			'select'       => __( 'Dropdown Select', 'variation-product-options' ),
			'datepicker'   => __( 'Datepicker', 'variation-product-options' ),
		);
	}

	/**
	 * Get an icon based on keywords in the label.
	 *
	 * @param string $label The field label.
	 * @return string SVG icon HTML or empty string.
	 */
	private static function get_icon_for_label( $label ) {
		$label = strtolower( $label );
		$icon  = '';

		// Feather Icons SVG Library
		$icons = array(
			'color'    => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-droplet"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>',
			'size'     => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-maximize"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>',
			'text'     => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-type"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>',
			'date'     => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
			'gift'     => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-gift"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>',
			'style'    => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-award"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 17 17 23 15.79 13.88"></polyline></svg>',
			'material' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
			'upload'   => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-upload-cloud"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path><polyline points="16 16 12 12 8 16"></polyline></svg>',
			'image'    => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-image"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
		);

		foreach ( $icons as $keyword => $svg ) {
			if ( strpos( $label, $keyword ) !== false ) {
				$icon = $svg;
				break;
			}
		}

		return $icon;
	}

	/**
	 * Render field HTML.
	 *
	 * @param array $field Field data.
	 * @param int   $product_id Product ID.
	 * @param int   $variation_id Variation ID.
	 * @return string Field HTML.
	 */
	public static function render_field( $field, $product_id, $variation_id = 0 ) {
		if ( ! isset( $field['id'] ) || ! isset( $field['type'] ) || ! isset( $field['label'] ) ) {
			return '';
		}

		$field_id       = esc_attr( $field['id'] );
		$field_type     = esc_attr( $field['type'] );
		$field_label    = esc_html( $field['label'] );
		$required       = isset( $field['required'] ) && $field['required'] ? 'required' : '';
		$has_condition  = isset( $field['condition'], $field['condition']['field'], $field['condition']['value'] ) && ! empty( $field['condition']['field'] );
		$condition_attr = '';
		$icon_svg       = self::get_icon_for_label( $field_label );

		if ( $has_condition ) {
			$condition_field = esc_attr( $field['condition']['field'] );
			$condition_value = esc_attr( $field['condition']['value'] );
			$condition_attr  = sprintf(
				' data-vpo-condition-field="%s" data-vpo-condition-value="%s"',
				$condition_field,
				$condition_value
			);
		}

		$wrapper_class = 'vpo-field vpo-field-' . $field_type;
		if ( $has_condition ) {
			$wrapper_class .= ' vpo-conditional-field';
		}
		if ( ! empty( $icon_svg ) ) {
			$wrapper_class .= ' vpo-has-icon';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>"<?php echo $condition_attr; ?>>
			<div class="vpo-field-header">
				<?php if ( ! empty( $icon_svg ) ) : ?>
					<div class="vpo-field-icon"><?php echo $icon_svg; ?></div>
				<?php endif; ?>
				<label for="vpo_<?php echo esc_attr( $field_id ); ?>" class="vpo-field-label">
					<?php echo $field_label; ?>
					<?php if ( $required ) : ?>
						<span class="required">*</span>
					<?php endif; ?>
				</label>
			</div>
			<div class="vpo-field-input">
				<?php
				switch ( $field_type ) {
					case 'radio':
						self::render_radio( $field, $field_id, $required );
						break;
					case 'radio_switch':
						self::render_radio_switch( $field, $field_id, $required );
						break;
					case 'checkbox':
						self::render_checkbox( $field, $field_id, $required );
						break;
					case 'select':
						self::render_select( $field, $field_id, $required );
						break;
					case 'datepicker':
						self::render_datepicker( $field, $field_id, $required );
						break;
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}


	/**
	 * Render radio buttons.
	 *
	 * @param array  $field Field data.
	 * @param string $field_id Field ID.
	 * @param string $required Required attribute.
	 */
	private static function render_radio( $field, $field_id, $required ) {
		if ( ! isset( $field['options'] ) || ! is_array( $field['options'] ) ) {
			return;
		}

		$name = 'vpo[' . esc_attr( $field_id ) . ']';
		echo '<div class="vpo-radio-group">';
		foreach ( $field['options'] as $index => $option ) {
			$option_id     = $field_id . '_' . $index;
			$value         = esc_attr( $option['value'] );
			$label         = esc_html( $option['label'] );
			$price         = isset( $option['price'] ) ? floatval( $option['price'] ) : 0;
			$price_display = $price > 0 ? ' <span class="vpo-price-suffix">(+' . wc_price( $price ) . ')</span>' : '';
			?>
			<label class="vpo-radio-option" for="vpo_<?php echo esc_attr( $option_id ); ?>">
				<input
					type="radio"
					name="<?php echo esc_attr( $name ); ?>"
					id="vpo_<?php echo esc_attr( $option_id ); ?>"
					value="<?php echo $value; ?>"
					data-vpo-price="<?php echo esc_attr( $price ); ?>"
					<?php echo $required && 0 === $index ? 'required' : ''; ?>
				/>
				<span class="vpo-radio-label"><?php echo $label; ?></span>
				<?php echo $price_display; ?>
			</label>
			<?php
		}
		echo '</div>';
	}

	/**
	 * Render radio switch/toggle.
	 *
	 * @param array  $field Field data.
	 * @param string $field_id Field ID.
	 * @param string $required Required attribute.
	 */
	private static function render_radio_switch( $field, $field_id, $required ) {
		if ( ! isset( $field['options'] ) || ! is_array( $field['options'] ) ) {
			echo '<p class="vpo-error">' . esc_html__( 'No options for this switch.', 'variation-product-options' ) . '</p>';
			return;
		}

		if ( count( $field['options'] ) < 2 ) {
			echo '<p class="vpo-error">' . esc_html__( 'Switch requires 2 options.', 'variation-product-options' ) . '</p>';
			return;
		}

		$name    = 'vpo[' . esc_attr( $field_id ) . ']';
		$options = array_slice( $field['options'], 0, 2 );
		?>
		<div class="vpo-switch-wrapper">
			<div class="vpo-switch-inner">
				<?php
				foreach ( $options as $index => $option ) {
					$option_id = $field_id . '_' . $index;
					$value     = esc_attr( $option['value'] );
					$label     = esc_html( $option['label'] );
					$price     = isset( $option['price'] ) ? floatval( $option['price'] ) : 0;
					?>
					<label class="vpo-switch-option" for="vpo_<?php echo esc_attr( $option_id ); ?>">
						<input
							type="radio"
							name="<?php echo esc_attr( $name ); ?>"
							id="vpo_<?php echo esc_attr( $option_id ); ?>"
							value="<?php echo $value; ?>"
							data-vpo-price="<?php echo esc_attr( $price ); ?>"
							<?php echo $required && 0 === $index ? 'required' : ''; ?>
						/>
						<span class="vpo-switch-label"><?php echo $label; ?></span>
					</label>
					<?php
				}
				?>
				<span class="vpo-switch-slider"></span>
			</div>
		</div>
		<?php
	}


	/**
	 * Render checkbox.
	 *
	 * @param array  $field Field data.
	 * @param string $field_id Field ID.
	 * @param string $required Required attribute.
	 */
	private static function render_checkbox( $field, $field_id, $required ) {
		$name          = 'vpo[' . esc_attr( $field_id ) . ']';
		$price         = isset( $field['price'] ) ? floatval( $field['price'] ) : 0;
		$price_display = $price > 0 ? ' <span class="vpo-price-suffix">(+' . wc_price( $price ) . ')</span>' : '';
		$field_label   = esc_html( $field['label'] );
		?>
		<label class="vpo-checkbox-option" for="vpo_<?php echo esc_attr( $field_id ); ?>">
			<input
				type="checkbox"
				name="<?php echo esc_attr( $name ); ?>"
				id="vpo_<?php echo esc_attr( $field_id ); ?>"
				value="1"
				data-vpo-price="<?php echo esc_attr( $price ); ?>"
				<?php echo $required; ?>
			/>
			<span class="vpo-checkbox-label"><?php echo $field_label; ?></span>
			<?php echo $price_display; ?>
		</label>
		<?php
	}


	/**
	 * Render select dropdown.
	 *
	 * @param array  $field Field data.
	 * @param string $field_id Field ID.
	 * @param string $required Required attribute.
	 */
	private static function render_select( $field, $field_id, $required ) {
		if ( ! isset( $field['options'] ) || ! is_array( $field['options'] ) ) {
			return;
		}

		$name = 'vpo[' . esc_attr( $field_id ) . ']';
		?>
		<div class="vpo-select-wrapper">
			<select
				name="<?php echo esc_attr( $name ); ?>"
				id="vpo_<?php echo esc_attr( $field_id ); ?>"
				class="vpo-select"
				<?php echo $required; ?>
			>
				<option value=""><?php esc_html_e( 'Select an option...', 'variation-product-options' ); ?></option>
				<?php
				foreach ( $field['options'] as $option ) {
					$value         = esc_attr( $option['value'] );
					$label         = esc_html( $option['label'] );
					$price         = isset( $option['price'] ) ? floatval( $option['price'] ) : 0;
					$price_display = $price > 0 ? ' (+' . wc_price( $price ) . ')' : '';
					?>
					<option value="<?php echo $value; ?>" data-vpo-price="<?php echo esc_attr( $price ); ?>">
						<?php echo $label . $price_display; ?>
					</option>
					<?php
				}
				?>
			</select>
		</div>
		<?php
	}


	/**
	 * Render datepicker.
	 *
	 * @param array  $field Field data.
	 * @param string $field_id Field ID.
	 * @param string $required Required attribute.
	 */
	private static function render_datepicker( $field, $field_id, $required ) {
		$name = 'vpo[' . esc_attr( $field_id ) . ']';
		?>
		<div class="vpo-datepicker-wrapper">
			<input
				type="text" 
				name="<?php echo esc_attr( $name ); ?>"
				id="vpo_<?php echo esc_attr( $field_id ); ?>"
				class="vpo-datepicker"
				placeholder="<?php esc_attr_e( 'Select a date...', 'variation-product-options' ); ?>"
				data-vpo-price="0"
				<?php echo $required; ?>
				readonly="readonly" 
			/>
		</div>
		<?php
	}
}