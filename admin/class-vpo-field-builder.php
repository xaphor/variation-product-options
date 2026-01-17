<?php
/**
 * Field builder class for admin interface.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field builder class.
 */
class VPO_Field_Builder {

	/**
	 * Render field builder interface.
	 *
	 * @param array $group Group data.
	 */
	public static function render( $group ) {
		$group_id = isset( $group['group_id'] ) ? $group['group_id'] : '';
		$group_name = isset( $group['name'] ) ? $group['name'] : '';
		$rules = isset( $group['rules'] ) ? $group['rules'] : array();
		$fields = isset( $group['fields'] ) ? $group['fields'] : array();

		$all_products = isset( $rules['all_products'] ) && $rules['all_products'];
		$product_ids = isset( $rules['product_ids'] ) ? $rules['product_ids'] : array();
		$variation_ids = isset( $rules['variation_ids'] ) ? $rules['variation_ids'] : array();

		// Get all products for selection.
		$products = wc_get_products(
			array(
				'limit'  => -1,
				'status' => 'publish',
			)
		);

		// Get all variations.
		$variations = array();
		foreach ( $products as $product ) {
			if ( $product->is_type( 'variable' ) ) {
				$product_variations = $product->get_children();
				foreach ( $product_variations as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation ) {
						$variations[ $variation_id ] = $variation->get_formatted_name();
					}
				}
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo $group_id ? esc_html__( 'Edit Field Group', 'variation-product-options' ) : esc_html__( 'Add New Field Group', 'variation-product-options' ); ?></h1>

			<?php if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Field group saved successfully.', 'variation-product-options' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="vpo-field-group-form">
				<?php wp_nonce_field( 'vpo_save_group' ); ?>
				<input type="hidden" name="action" value="vpo_save_group" />
				<input type="hidden" name="group_id" value="<?php echo esc_attr( $group_id ); ?>" />

				<div class="vpo-form-section">
					<h2><?php esc_html_e( 'Group Settings', 'variation-product-options' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="group_name"><?php esc_html_e( 'Group Name', 'variation-product-options' ); ?></label>
							</th>
							<td>
								<input type="text" id="group_name" name="group_name" value="<?php echo esc_attr( $group_name ); ?>" class="regular-text" required />
								<p class="description"><?php esc_html_e( 'A descriptive name for this field group.', 'variation-product-options' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="vpo-form-section">
					<h2><?php esc_html_e( 'Assignment Rules', 'variation-product-options' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Apply To', 'variation-product-options' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="all_products" value="1" <?php checked( $all_products ); ?> />
									<?php esc_html_e( 'All Products', 'variation-product-options' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'If checked, this field group will apply to all products.', 'variation-product-options' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="product_ids"><?php esc_html_e( 'Specific Products', 'variation-product-options' ); ?></label>
							</th>
							<td>
								<select id="product_ids" name="product_ids[]" multiple class="vpo-select2" style="width: 100%;" data-placeholder="<?php esc_attr_e( 'Select products...', 'variation-product-options' ); ?>">
									<?php foreach ( $products as $product ) : ?>
										<option value="<?php echo esc_attr( $product->get_id() ); ?>" <?php selected( in_array( $product->get_id(), $product_ids, true ) ); ?>>
											<?php echo esc_html( $product->get_formatted_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select specific products to apply this field group to.', 'variation-product-options' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="variation_ids"><?php esc_html_e( 'Specific Variations', 'variation-product-options' ); ?></label>
							</th>
							<td>
								<select id="variation_ids" name="variation_ids[]" multiple class="vpo-select2" style="width: 100%;" data-placeholder="<?php esc_attr_e( 'Select variations...', 'variation-product-options' ); ?>">
									<?php foreach ( $variations as $var_id => $var_name ) : ?>
										<option value="<?php echo esc_attr( $var_id ); ?>" <?php selected( in_array( $var_id, $variation_ids, true ) ); ?>>
											<?php echo esc_html( $var_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select specific variations to apply this field group to.', 'variation-product-options' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="vpo-form-section">
					<h2>
						<?php esc_html_e( 'Fields', 'variation-product-options' ); ?>
						<button type="button" class="button button-secondary vpo-add-field" style="margin-left: 10px;">
							<?php esc_html_e( 'Add Field', 'variation-product-options' ); ?>
						</button>
					</h2>

					<div id="vpo-fields-container">
						<?php
						if ( ! empty( $fields ) ) {
							foreach ( $fields as $index => $field ) {
								self::render_field_row( $index, $field, $fields );
							}
						}
						?>
					</div>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<?php esc_html_e( 'Save Field Group', 'variation-product-options' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=vpo-product-options' ) ); ?>" class="button button-large">
						<?php esc_html_e( 'Cancel', 'variation-product-options' ); ?>
					</a>
				</p>
			</form>
		</div>

		<script type="text/template" id="vpo-field-row-template">
			<?php self::render_field_row( '{{INDEX}}', array(), array() ); ?>
		</script>
		<?php
	}

	/**
	 * Render a single field row.
	 *
	 * @param int   $index Field index.
	 * @param array $field Field data.
	 * @param array $all_fields All fields (for condition dropdown).
	 */
	private static function render_field_row( $index, $field = array(), $all_fields = array() ) {
		$field_id = isset( $field['id'] ) ? $field['id'] : '';
		$field_type = isset( $field['type'] ) ? $field['type'] : 'radio';
		$field_label = isset( $field['label'] ) ? $field['label'] : '';
		$field_required = isset( $field['required'] ) && $field['required'];
		$field_options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
		$field_price = isset( $field['price'] ) ? $field['price'] : 0;
		$condition_field = isset( $field['condition']['field'] ) ? $field['condition']['field'] : '';
		$condition_value = isset( $field['condition']['value'] ) ? $field['condition']['value'] : '';

		$field_types = VPO_Field_Types::get_field_types();
		?>
		<div class="vpo-field-row" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="vpo-field-header">
				<span class="vpo-field-handle dashicons dashicons-menu"></span>
				<strong class="vpo-field-title"><?php echo esc_html( $field_label ? $field_label : __( 'New Field', 'variation-product-options' ) ); ?></strong>
				<button type="button" class="button-link vpo-remove-field" style="color: #b32d2e;">
					<?php esc_html_e( 'Remove', 'variation-product-options' ); ?>
				</button>
			</div>
			<div class="vpo-field-content">
				<table class="form-table">
					<tr>
						<th><label><?php esc_html_e( 'Field ID', 'variation-product-options' ); ?></label></th>
						<td>
							<input type="text" name="fields[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $field_id ); ?>" class="regular-text vpo-field-id" placeholder="field_id" required />
							<p class="description"><?php esc_html_e( 'Unique identifier (lowercase, no spaces).', 'variation-product-options' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Field Type', 'variation-product-options' ); ?></label></th>
						<td>
							<select name="fields[<?php echo esc_attr( $index ); ?>][type]" class="vpo-field-type">
								<?php foreach ( $field_types as $type => $label ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field_type, $type ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Label', 'variation-product-options' ); ?></label></th>
						<td>
							<input type="text" name="fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field_label ); ?>" class="regular-text vpo-field-label" required />
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Required', 'variation-product-options' ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" name="fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( $field_required ); ?> />
								<?php esc_html_e( 'This field is required', 'variation-product-options' ); ?>
							</label>
						</td>
					</tr>

					<!-- Options for radio, radio_switch, select -->
					<tr class="vpo-options-row" style="<?php echo in_array( $field_type, array( 'radio', 'radio_switch', 'select' ), true ) ? '' : 'display:none;'; ?>">
						<th><label><?php esc_html_e( 'Options', 'variation-product-options' ); ?></label></th>
						<td>
							<div class="vpo-options-container">
								<?php
								if ( ! empty( $field_options ) ) {
									foreach ( $field_options as $opt_index => $option ) {
										self::render_option_row( $index, $opt_index, $option );
									}
								} else {
									// Default two options.
									self::render_option_row( $index, 0, array( 'label' => '', 'value' => '', 'price' => 0 ) );
									self::render_option_row( $index, 1, array( 'label' => '', 'value' => '', 'price' => 0 ) );
								}
								?>
							</div>
							<button type="button" class="button button-small vpo-add-option"><?php esc_html_e( 'Add Option', 'variation-product-options' ); ?></button>
						</td>
					</tr>

					<!-- Price for checkbox -->
					<tr class="vpo-price-row" style="<?php echo 'checkbox' === $field_type ? '' : 'display:none;'; ?>">
						<th><label><?php esc_html_e( 'Price', 'variation-product-options' ); ?></label></th>
						<td>
							<input type="number" name="fields[<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $field_price ); ?>" step="0.01" min="0" class="small-text" />
							<p class="description"><?php esc_html_e( 'Additional price when this option is selected.', 'variation-product-options' ); ?></p>
						</td>
					</tr>

					<!-- Conditional Logic -->
					<tr>
						<th><label><?php esc_html_e( 'Conditional Logic', 'variation-product-options' ); ?></label></th>
						<td>
							<label>
								<?php esc_html_e( 'Show this field only if:', 'variation-product-options' ); ?>
							</label>
							<select name="fields[<?php echo esc_attr( $index ); ?>][condition][field]" class="vpo-condition-field">
								<option value=""><?php esc_html_e( 'No condition', 'variation-product-options' ); ?></option>
								<?php
								foreach ( $all_fields as $other_index => $other_field ) {
									if ( $other_index === $index || ! isset( $other_field['id'] ) ) {
										continue;
									}
									$other_field_id = $other_field['id'];
									$other_field_label = isset( $other_field['label'] ) ? $other_field['label'] : $other_field_id;
									?>
									<option value="<?php echo esc_attr( $other_field_id ); ?>" <?php selected( $condition_field, $other_field_id ); ?>>
										<?php echo esc_html( $other_field_label ); ?>
									</option>
									<?php
								}
								?>
							</select>
							<span class="vpo-condition-value-wrapper" style="<?php echo $condition_field ? '' : 'display:none;'; ?>">
								<?php esc_html_e( 'equals', 'variation-product-options' ); ?>
								<input type="text" name="fields[<?php echo esc_attr( $index ); ?>][condition][value]" value="<?php echo esc_attr( $condition_value ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Value', 'variation-product-options' ); ?>" />
							</span>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render option row.
	 *
	 * @param int   $field_index Field index.
	 * @param int   $option_index Option index.
	 * @param array $option Option data.
	 */
	private static function render_option_row( $field_index, $option_index, $option = array() ) {
		$label = isset( $option['label'] ) ? $option['label'] : '';
		$value = isset( $option['value'] ) ? $option['value'] : '';
		$price = isset( $option['price'] ) ? $option['price'] : 0;
		?>
		<div class="vpo-option-row">
			<input type="text" name="fields[<?php echo esc_attr( $field_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Label', 'variation-product-options' ); ?>" class="regular-text" />
			<input type="text" name="fields[<?php echo esc_attr( $field_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][value]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Value', 'variation-product-options' ); ?>" class="regular-text" />
			<input type="number" name="fields[<?php echo esc_attr( $field_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Price', 'variation-product-options' ); ?>" class="small-text" />
			<button type="button" class="button-link vpo-remove-option" style="color: #b32d2e;"><?php esc_html_e( 'Remove', 'variation-product-options' ); ?></button>
		</div>
		<?php
	}
}
