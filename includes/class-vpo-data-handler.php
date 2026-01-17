<?php
/**
 * Data handler class for managing field groups and options.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data handler class.
 */
class VPO_Data_Handler {

	/**
	 * Option name for storing field groups.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'vpo_field_groups';

	/**
	 * Get all field groups.
	 *
	 * @return array All field groups.
	 */
	public static function get_all_field_groups() {
		$groups = get_option( self::OPTION_NAME, array() );
		return is_array( $groups ) ? $groups : array();
	}

	/**
	 * Get a single field group by ID.
	 *
	 * @param string $group_id Group ID.
	 * @return array|false Field group data or false if not found.
	 */
	public static function get_field_group( $group_id ) {
		$groups = self::get_all_field_groups();
		return isset( $groups[ $group_id ] ) ? $groups[ $group_id ] : false;
	}

	/**
	 * Get field groups for a product/variation.
	 *
	 * @param int $product_id Product ID.
	 * @param int $variation_id Optional variation ID.
	 * @return array Field groups that apply to this product/variation.
	 */
	public static function get_field_groups( $product_id, $variation_id = 0 ) {
		$all_groups = self::get_all_field_groups();
		$applicable_groups = array();

		foreach ( $all_groups as $group_id => $group ) {
			if ( ! isset( $group['rules'] ) || ! isset( $group['fields'] ) ) {
				continue;
			}

			$rules = $group['rules'];
			$applies = false;

			// Check if applies to all products.
			if ( isset( $rules['all_products'] ) && $rules['all_products'] ) {
				$applies = true;
			}

			// Check specific product IDs.
			if ( ! $applies && isset( $rules['product_ids'] ) && is_array( $rules['product_ids'] ) ) {
				if ( in_array( $product_id, $rules['product_ids'], true ) ) {
					$applies = true;
				}
			}

			// Check specific variation IDs.
			if ( ! $applies && $variation_id > 0 && isset( $rules['variation_ids'] ) && is_array( $rules['variation_ids'] ) ) {
				if ( in_array( $variation_id, $rules['variation_ids'], true ) ) {
					$applies = true;
				}
			}

			// For variable products, check if parent product matches.
			if ( ! $applies && $variation_id > 0 ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation && $variation->get_parent_id() === $product_id ) {
					if ( isset( $rules['product_ids'] ) && is_array( $rules['product_ids'] ) ) {
						if ( in_array( $product_id, $rules['product_ids'], true ) ) {
							$applies = true;
						}
					}
				}
			}

			if ( $applies ) {
				$applicable_groups[ $group_id ] = $group;
			}
		}

		return $applicable_groups;
	}

	/**
	 * Save field group.
	 *
	 * @param array $group_data Field group data.
	 * @return string|false Group ID on success, false on failure.
	 */
	public static function save_field_group( $group_data ) {
		error_log( 'VPO_Data_Handler::save_field_group() called' );

		// Sanitize and validate data.
		if ( ! isset( $group_data['group_id'] ) || empty( $group_data['group_id'] ) ) {
			$group_data['group_id'] = 'vpo_group_' . uniqid();
		}

		$group_id = sanitize_key( $group_data['group_id'] );

		// Sanitize rules.
		$rules = array(
			'all_products'  => isset( $group_data['rules']['all_products'] ) ? (bool) $group_data['rules']['all_products'] : false,
			'product_ids'    => isset( $group_data['rules']['product_ids'] ) ? array_map( 'absint', (array) $group_data['rules']['product_ids'] ) : array(),
			'variation_ids' => isset( $group_data['rules']['variation_ids'] ) ? array_map( 'absint', (array) $group_data['rules']['variation_ids'] ) : array(),
		);

		// Sanitize fields.
		$fields = array();
		if ( isset( $group_data['fields'] ) && is_array( $group_data['fields'] ) ) {
			error_log( 'VPO: Processing ' . count( $group_data['fields'] ) . ' fields' );
			foreach ( $group_data['fields'] as $field ) {
				$sanitized_field = self::sanitize_field( $field );
				if ( $sanitized_field ) {
					$fields[] = $sanitized_field;
				} else {
					error_log( 'VPO: Field sanitization failed for field: ' . wp_json_encode( $field ) );
				}
			}
		}

		// Prepare group data.
		$group = array(
			'group_id' => $group_id,
			'name'     => isset( $group_data['name'] ) ? sanitize_text_field( $group_data['name'] ) : '',
			'rules'    => $rules,
			'fields'   => $fields,
		);

		// Get all groups and update.
		$all_groups = self::get_all_field_groups();
		$all_groups[ $group_id ] = $group;

		error_log( 'VPO: Saving group ' . $group_id . ' with ' . count( $fields ) . ' fields' );

		// Save to database.
		try {
			// Always use update_option (it creates if doesn't exist, updates if it does).
			// Note: update_option returns false if value is unchanged, which is NOT an error.
			error_log( 'VPO: About to call update_option with ' . count( $all_groups ) . ' total groups' );
			error_log( 'VPO: Option data size: ' . strlen( wp_json_encode( $all_groups ) ) . ' bytes' );
			
			$db_result = update_option( self::OPTION_NAME, $all_groups );
			error_log( 'VPO: update_option returned: ' . ( $db_result ? 'true' : 'false' ) );
			
			// Verify the save actually worked by re-reading from DB.
			// Add a small delay to ensure database write completed
			usleep( 100000 );
			
			$verify = get_option( self::OPTION_NAME, array() );
			error_log( 'VPO: Re-read from DB, found ' . count( $verify ) . ' groups' );
			
			$verify_exists = isset( $verify[ $group_id ] );
			error_log( 'VPO: Verification - group ' . $group_id . ' exists in DB: ' . ( $verify_exists ? 'true' : 'false' ) );
			
			if ( $verify_exists ) {
				error_log( 'VPO: Group data in DB: ' . wp_json_encode( $verify[ $group_id ] ) );
			}
			
			if ( ! $verify_exists ) {
				error_log( 'VPO: CRITICAL ERROR - Group was not saved to database!' );
				error_log( 'VPO: All groups in DB: ' . wp_json_encode( array_keys( $verify ) ) );
				return false;
			}
			
			// Return group ID as success indicator (regardless of update_option return value).
			error_log( 'VPO: save_field_group returned: success (' . $group_id . ')' );
			return $group_id;
		} catch ( Exception $e ) {
			error_log( 'VPO: Exception during save: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Sanitize a field.
	 *
	 * @param array $field Field data.
	 * @return array|false Sanitized field or false if invalid.
	 */
	private static function sanitize_field( $field ) {
		if ( ! isset( $field['id'] ) || ! isset( $field['type'] ) || ! isset( $field['label'] ) ) {
			error_log( 'VPO: Field missing required keys. Field: ' . wp_json_encode( $field ) );
			return false;
		}

		$sanitized = array(
			'id'    => sanitize_key( $field['id'] ),
			'type'  => sanitize_key( $field['type'] ),
			'label' => sanitize_text_field( $field['label'] ),
		);

		// Add options for radio, radio_switch, select.
		if ( in_array( $field['type'], array( 'radio', 'radio_switch', 'select' ), true ) ) {
			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$sanitized['options'] = array();
				foreach ( $field['options'] as $option ) {
					// Handle both numeric values (from price fields) and string values
					$option_value = isset( $option['value'] ) ? $option['value'] : '';
					// Only sanitize as key if it's meant to be a key, otherwise keep as string
					$sanitized['options'][] = array(
						'label' => sanitize_text_field( $option['label'] ),
						'value' => sanitize_text_field( $option_value ),
						'price' => isset( $option['price'] ) ? floatval( $option['price'] ) : 0,
					);
				}
			}
		}

		// Add price for checkbox.
		if ( 'checkbox' === $field['type'] ) {
			$sanitized['price'] = isset( $field['price'] ) ? floatval( $field['price'] ) : 0;
		}

		// Add condition if present and has a field specified.
		if ( isset( $field['condition'] ) && is_array( $field['condition'] ) ) {
			$condition_field = isset( $field['condition']['field'] ) ? $field['condition']['field'] : '';
			$condition_value = isset( $field['condition']['value'] ) ? $field['condition']['value'] : '';
			
			// Only save condition if field is not empty (avoid saving empty conditions).
			if ( ! empty( $condition_field ) ) {
				$sanitized['condition'] = array(
					'field' => sanitize_text_field( $condition_field ),
					'value' => sanitize_text_field( $condition_value ),
				);
			}
		}

		// Add required flag.
		$sanitized['required'] = isset( $field['required'] ) ? (bool) $field['required'] : false;

		error_log( 'VPO: Sanitized field ' . $sanitized['id'] . ' of type ' . $sanitized['type'] );
		return $sanitized;
	}

	/**
	 * Delete field group.
	 *
	 * @param string $group_id Group ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_field_group( $group_id ) {
		$group_id = sanitize_key( $group_id );
		$all_groups = self::get_all_field_groups();

		if ( ! isset( $all_groups[ $group_id ] ) ) {
			return false;
		}

		unset( $all_groups[ $group_id ] );
		return update_option( self::OPTION_NAME, $all_groups );
	}
}
