<?php
/**
 * Core functionality class.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core class.
 */
class VPO_Core {

	/**
	 * Instance of this class.
	 *
	 * @var VPO_Core
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return VPO_Core
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
		// Core hooks will be added here.
	}
}
