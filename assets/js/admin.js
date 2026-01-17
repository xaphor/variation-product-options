/**
 * Admin JavaScript for Variation Product Options.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

(function($) {
	'use strict';

	var fieldIndex = 0;

	$(document).ready(function() {
		// Initialize field builder.
		initFieldBuilder();
		
		// Initialize Select2 if available (WooCommerce includes it).
		if ($.fn.select2) {
			$('.vpo-select2').select2();
		}
	});

	/**
	 * Initialize field builder functionality.
	 */
	function initFieldBuilder() {
		// Count existing fields to set next index.
		fieldIndex = $('#vpo-fields-container .vpo-field-row').length;

		// Add field button.
		$(document).on('click', '.vpo-add-field', function(e) {
			e.preventDefault();
			addFieldRow();
		});

		// Remove field button.
		$(document).on('click', '.vpo-remove-field', function(e) {
			e.preventDefault();
			$(this).closest('.vpo-field-row').remove();
			updateFieldIndices();
		});

		// Field type change.
		$(document).on('change', '.vpo-field-type', function() {
			var $row = $(this).closest('.vpo-field-row');
			var fieldType = $(this).val();
			updateFieldRowForType($row, fieldType);
		});

		// Add option button.
		$(document).on('click', '.vpo-add-option', function(e) {
			e.preventDefault();
			var $container = $(this).siblings('.vpo-options-container');
			var $fieldRow = $(this).closest('.vpo-field-row');
			var fieldIndex = $fieldRow.data('index');
			var optionIndex = $container.find('.vpo-option-row').length;
			
			var $optionRow = $('<div class="vpo-option-row">' +
				'<input type="text" name="fields[' + fieldIndex + '][options][' + optionIndex + '][label]" placeholder="Label" class="regular-text" />' +
				'<input type="text" name="fields[' + fieldIndex + '][options][' + optionIndex + '][value]" placeholder="Value" class="regular-text" />' +
				'<input type="number" name="fields[' + fieldIndex + '][options][' + optionIndex + '][price]" step="0.01" min="0" placeholder="Price" class="small-text" />' +
				'<button type="button" class="button-link vpo-remove-option" style="color: #b32d2e;">Remove</button>' +
				'</div>');
			
			$container.append($optionRow);
		});

		// Remove option button.
		$(document).on('click', '.vpo-remove-option', function(e) {
			e.preventDefault();
			$(this).closest('.vpo-option-row').remove();
		});

		// Conditional logic field change.
		$(document).on('change', '.vpo-condition-field', function() {
			var $wrapper = $(this).siblings('.vpo-condition-value-wrapper');
			if ($(this).val()) {
				$wrapper.show();
			} else {
				$wrapper.hide();
			}
		});

		// Update field label in header when changed.
		$(document).on('input', '.vpo-field-label', function() {
			var $row = $(this).closest('.vpo-field-row');
			var label = $(this).val() || 'New Field';
			$row.find('.vpo-field-title').text(label);
		});
	}

	/**
	 * Add a new field row.
	 */
	function addFieldRow() {
		var template = $('#vpo-field-row-template').html();
		template = template.replace(/\{\{INDEX\}\}/g, fieldIndex);
		
		var $row = $(template);
		$('#vpo-fields-container').append($row);
		
		fieldIndex++;
		updateFieldIndices();
	}

	/**
	 * Update field indices after adding/removing fields.
	 */
	function updateFieldIndices() {
		$('#vpo-fields-container .vpo-field-row').each(function(index) {
			$(this).attr('data-index', index);
			$(this).find('input, select').each(function() {
				var name = $(this).attr('name');
				if (name) {
					name = name.replace(/fields\[\d+\]/, 'fields[' + index + ']');
					$(this).attr('name', name);
				}
			});
		});
	}

	/**
	 * Update field row based on field type.
	 */
	function updateFieldRowForType($row, fieldType) {
		var $optionsRow = $row.find('.vpo-options-row');
		var $priceRow = $row.find('.vpo-price-row');

		// Show/hide options row for radio, radio_switch, select.
		if (['radio', 'radio_switch', 'select'].indexOf(fieldType) !== -1) {
			$optionsRow.show();
			$priceRow.hide();
		} else if (fieldType === 'checkbox') {
			$optionsRow.hide();
			$priceRow.show();
		} else {
			$optionsRow.hide();
			$priceRow.hide();
		}
	}

})(jQuery);
