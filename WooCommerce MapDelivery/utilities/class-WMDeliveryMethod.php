<?php
/**
 * WooCommerce Map Delivery Method.
 *
 * @package     WMD\Settings
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WMDelivery method initialization
 */
function wmdelivery_method_init() {
	if ( ! class_exists( 'WMDeliveryMethod' ) ) {
		/**
		 * WMDelivery Class
		 *
		 * @since       1.0.0
		 */
		class WMDeliveryMethod extends WC_Shipping_Method {

			public function __construct( $instance_id = 0 ) {
				$this->id                   = 'wmdelivery_method';
				$this->instance_id          = absint( $instance_id );
				$this->method_title         = __( 'Map Delivery Method', 'wmd-plugin' );
				$this->method_description   = __( 'Delivery method based on GPS coordinates.', 'wmd-plugin' );
				$this->supports             = array(
					'shipping-zones',
					'instance-settings',
					'instance-settings-modal',
				);
				$this->instance_form_fields = array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'wmd-plugin' ),
						'type'    => 'checkbox',
						'label'   => __( 'Activate this delivery method', 'wmd-plugin' ),
						'default' => 'yes',
					),
					'title'   => array(
						'title'       => __( 'Method Name', 'wmd-plugin' ),
						'type'        => 'text',
						'description' => __( 'This defines the name seen by the user during checkout.', 'wmd-plugin' ),
						'default'     => __( 'Map Delivery', 'wmd-plugin' ),
						'desc_tip'    => true,
					),
				);
				$this->enabled              = $this->get_option( 'enabled' );
				$this->title                = $this->get_option( 'title' );

				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			}

			public function calculate_shipping( $package = array() ) {
				// ... (rest of the calculate_shipping function, similar to the original but with changed prefixes and improved variable names)
			}
		}
	}
}

add_action( 'woocommerce_shipping_init', 'wmdelivery_method_init' );

function add_wmdelivery_method( $methods ) {
	$methods['wmdelivery_method'] = 'WMDeliveryMethod';
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_wmdelivery_method' );
