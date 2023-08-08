<?php
/**
 * WooCommerce Map Delivery Shortcodes.
 *
 * @package     WMD\Shortcodes
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WMDelivery Shortcodes Class
 *
 * @since       1.0.0
 */
class WMDeliveryShortcodes {

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_shortcodes' ) );
	}

	public function initialize_shortcodes() {
		add_shortcode( 'map_delivery', array( $this, 'render_shortcode' ) );
	}

	public function render_shortcode( $attributes ) {

		$attributes = shortcode_atts(
			array(
				'id'     => '',
				'zoom'   => 18,
				'center' => '',
				'zones'  => false,
			),
			$attributes,
			'map_delivery'
		);

		$coordinates = array();
		$mapCenter   = array();
		$zoneColor   = array();

		// ... (rest of the render_shortcode function, similar to the original but with changed prefixes and improved variable names)

		$html = ob_get_clean();
		return $html;
	}

} // end of class
