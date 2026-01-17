/**
 * Frontend JavaScript for Variation Product Options.
 *
 * @package VariationProductOptions
 * @since 1.1.0
 */

(function($) {
	'use strict';

	// Helper for debouncing function calls
	function debounce(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	}

	$(document).ready(function() {
		// Ensure WordPress's datepicker is loaded if not already
		if (typeof $.fn.datepicker === 'undefined' && typeof wp.date !== 'undefined') {
			// This is a fallback if the script isn't correctly enqueued.
			// It assumes `wp-date` script is available.
		}
		initProductOptions();
	});

	/**
	 * Initialize all product option functionalities.
	 */
	function initProductOptions() {
		var $optionsContainer = $('.vpo-product-options');
		
		if (!$optionsContainer.length) {
			return;
		}

		var productId = $optionsContainer.data('product-id');
		var variationId = $optionsContainer.data('variation-id') || 0;

		// Debounced price calculation for better performance
		var debouncedCalculatePrice = debounce(function() {
			calculatePrice(productId, variationId);
		}, 250);

		// Handle all field changes.
		$optionsContainer.on('change input', 'input, select', function() {
			updateSwitchStates();
			debouncedCalculatePrice();
			handleConditionalFields();
		});
		
		// Initial setup on load
		setupAllFields();

		// --- Event Handlers for Variable Products ---
		$(document).on('found_variation', function(event, variation) {
			$optionsContainer = $('.vpo-product-options');
			if (!$optionsContainer.length) return;
			
			productId = $optionsContainer.data('product-id');
			variationId = variation.variation_id || 0;
			$optionsContainer.attr('data-variation-id', variationId);
			
			if (variationId > 0) {
				loadOptionsForVariation(productId, variationId);
			}
		});

		$(document).on('reset_data', function() {
			if (!$optionsContainer.length) return;
			
			variationId = 0;
			$optionsContainer.attr('data-variation-id', variationId);
			loadOptionsForVariation(productId, variationId);
		});
	}

	/**
	 * Setup fields on initial load or after AJAX load.
	 */
	function setupAllFields() {
		updateSwitchStates();
		handleConditionalFields();
		initDatepickers();
		
		// Initial price calculation
		var $optionsContainer = $('.vpo-product-options');
		var productId = $optionsContainer.data('product-id');
		var variationId = $optionsContainer.data('variation-id') || 0;
		calculatePrice(productId, variationId);
	}

	/**
	 * Initialize jQuery UI Datepickers on our fields.
	 */
	function initDatepickers() {
		$('.vpo-datepicker').each(function() {
			if (!$(this).hasClass('hasDatepicker')) {
				try {
					$(this).datepicker({
						dateFormat: 'yy-mm-dd',
						changeMonth: true,
						changeYear: true,
						beforeShow: function(input, inst) {
							$('#ui-datepicker-div').addClass('vpo-datepicker-popup');
						}
					});
				} catch (e) {
					console.warn('VPO: jQuery UI Datepicker not available. Date input will be text-only.', e);
				}
			}
		});
	}

	/**
	 * Calculate total price of selected options via AJAX.
	 */
	function calculatePrice(productId, variationId) {
		var $container = $('.vpo-product-options');
		var selectedOptions = {};

		$container.find('input, select').each(function() {
			var $field = $(this);
			var name = $field.attr('name');
			if (!name || !name.startsWith('vpo[')) return;

			var fieldIdMatch = name.match(/vpo\[([^\]]+)\]/);
			if (!fieldIdMatch) return;
			var fieldId = fieldIdMatch[1];

			var value = '';
			if ($field.is(':checkbox')) {
				value = $field.is(':checked') ? '1' : '';
			} else if ($field.is(':radio')) {
				if ($field.is(':checked')) value = $field.val();
			} else {
				value = $field.val();
			}

			if (value) {
				selectedOptions[fieldId] = value;
			}
		});

		if (typeof vpoData !== 'undefined') {
			$.ajax({
				url: vpoData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'vpo_calculate_price',
					nonce: vpoData.nonce,
					product_id: productId,
					variation_id: variationId,
					options: selectedOptions
				},
				success: function(response) {
					if (response.success && response.data) {
						updatePriceDisplay(response.data.total_price);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.warn('VPO AJAX Error Calculating Price:', {
						status: jqXHR.status,
						textStatus: textStatus,
						error: errorThrown
					});
					// Fallback: hide the price on error
					$('.vpo-total-price').hide();
				}
			});
		}
	}

	/**
	 * Update the total price display area.
	 */
	function updatePriceDisplay(totalPrice) {
		var $priceContainer = $('.vpo-total-price');
		var $priceAmount = $('.vpo-price-amount');
		var priceHtml = window.wc_price ? window.wc_price(totalPrice) : totalPrice;

		if (totalPrice > 0) {
			$priceAmount.html(priceHtml);
			$priceContainer.slideDown(200);
		} else {
			$priceContainer.slideUp(200);
		}
	}

	/**
	 * Handle conditional field visibility with animations.
	 */
	function handleConditionalFields() {
		$('.vpo-conditional-field').each(function() {
			var $conditionalField = $(this);
			var conditionFieldId = $conditionalField.data('vpo-condition-field');
			var conditionValue = String($conditionalField.data('vpo-condition-value'));

			if (!conditionFieldId) return;

			var $triggerField = $('input[name="vpo[' + conditionFieldId + ']"], select[name="vpo[' + conditionFieldId + ']"]');
			if (!$triggerField.length) {
				$conditionalField.slideUp(200).removeClass('vpo-field-visible');
				return;
			}

			var triggerValue = '';
			if ($triggerField.is(':radio')) {
				triggerValue = $triggerField.filter(':checked').val() || '';
			} else if ($triggerField.is(':checkbox')) {
				triggerValue = $triggerField.is(':checked') ? '1' : '';
			} else {
				triggerValue = $triggerField.val() || '';
			}

			if (triggerValue === conditionValue) {
				$conditionalField.slideDown(200).addClass('vpo-field-visible');
			} else {
				$conditionalField.slideUp(200).removeClass('vpo-field-visible');
			}
		});
	}

	/**
	 * Update switch active states and animate the slider.
	 */
	function updateSwitchStates() {
		$('.vpo-switch-inner').each(function() {
			var $switchInner = $(this);
			var $checkedOption = $switchInner.find('.vpo-switch-option input:checked');
			
			// Fallback for browsers without :has() support
			$switchInner.find('.vpo-switch-option').removeClass('active');
			if ($checkedOption.length) {
				$checkedOption.closest('.vpo-switch-option').addClass('active');
			}

			// Animate the slider
			var $slider = $switchInner.find('.vpo-switch-slider');
			if ($slider.length && $checkedOption.length) {
				var $activeLabel = $checkedOption.closest('.vpo-switch-option');
				var labelWidth = $activeLabel.outerWidth();
				var labelLeft = $activeLabel.position().left;
				
				$slider.css({
					'width': labelWidth + 'px',
					'left': labelLeft + 'px'
				});
			}
		});
	}

	/**
	 * Load options for a specific variation via AJAX.
	 */
	function loadOptionsForVariation(productId, variationId) {
		if (typeof vpoData === 'undefined' || !vpoData.nonce) return;

		var $container = $('.vpo-product-options');
		if (!$container.length) return;

		// Show a loading overlay
		$container.css('opacity', 0.5);

		$.ajax({
			url: vpoData.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'vpo_get_variation_fields',
				nonce: vpoData.nonce,
				product_id: productId,
				variation_id: variationId || 0
			},
			success: function(response) {
				var $totalPrice = $container.find('.vpo-total-price');
				
				// Remove old fields before inserting new ones
				$container.find('.vpo-field').remove();

				if (response.success && response.data.html) {
					// Insert new fields before the total price display
					if ($totalPrice.length) {
						$totalPrice.before(response.data.html);
					} else {
						$container.append(response.data.html);
					}
					// Re-initialize all fields
					setupAllFields();
				} else {
					// No fields, so just hide the price
					updatePriceDisplay(0);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.warn('VPO AJAX Error Loading Variation Fields:', {
					status: jqXHR.status,
					statusText: jqXHR.statusText,
					textStatus: textStatus,
					error: errorThrown
				});
				// Still remove loading overlay and hide price
				updatePriceDisplay(0);
			},
			complete: function() {
				// Remove loading overlay
				$container.css('opacity', 1);
			}
		});
	}

})(jQuery);