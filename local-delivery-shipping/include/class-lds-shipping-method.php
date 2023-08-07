<?php
/**
 * Local Delivery Shipping Method.
 *
 * @package     LDS\Settings
 * @since       1.0.0
 */

defined( 'ABSPATH' ) || die( 'Nope, not accessing this' );

/**
 * LDS Shipping method initiate
 */
function local_delivery_shipping_method_init() {
	if ( ! class_exists( 'LDS_Shipping_Method' ) ) {
		/**
		 * LDS Shipping Class
		 *
		 * @since       1.0.0
		 */
		class LDS_Shipping_Method extends WC_Shipping_Method {

			/**
			 * Constructor. The instance ID is passed to this.
			 *
			 * @param mixed $instance_id id.
			 */
			public function __construct( $instance_id = 0 ) {
				$this->id                   = 'local_delivery_shipping';
				$this->instance_id          = absint( $instance_id );
				$this->method_title         = __( 'Local Delivery Shipping', 'lds_plugin' );
				$this->method_description   = __( 'GPS Coordination based delivery shipping method.', 'lds_plugin' );
				$this->supports             = array(
					'shipping-zones',
					'instance-settings',
					'instance-settings-modal',
				);
				$this->instance_form_fields = array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'lds_plugin' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable this shipping method', 'lds_plugin' ),
						'default' => 'yes',
					),
					'title'   => array(
						'title'       => __( 'Method Title', 'lds_plugin' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'lds_plugin' ),
						'default'     => __( 'Local Delivery', 'lds_plugin' ),
						'desc_tip'    => true,
					),
				);
				$this->enabled              = $this->get_option( 'enabled' );
				$this->title                = $this->get_option( 'title' );

				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

			}

			/**
			 * Calculate_shipping function.
			 *
			 * @param array $package (default: array()).
			 */
			public function calculate_shipping( $package = array() ) {
				global $current_user;
				$free_copun       = get_option( 'wc_settings_lds_free_wcopun' );
				$zone_name        = '';
				$location_on_zone = 0;
				$lds_rate         = 0;
				$point_location   = new LDS_Point_In_Polygon();
				$lat              = WC()->session->get( 'lds_lat' );
				$long             = WC()->session->get( 'lds_long' );
				if ( ! isset( $lat ) && ! isset( $long ) ) {
					$lat  = get_user_meta( $current_user->ID, 'billing_gps_lat', true );
					$long = get_user_meta( $current_user->ID, 'billing_gps_long', true );
				}
				if ( $lat && $long ) {
					$latlong          = $lat . ' ' . $long;
					$point            = $latlong;
					WC()->session->set( 'lds_nolocation', false );
					// look for point inside delivery zones.
					$posts = get_posts(
						array(
							'post_type'   => 'lds_zones',
							'meta_key'    => 'lds_zones_placemark',
							'order'       => 'DESC',
							'orderby'     => 'meta_value_num',
							'numberposts' => -1,
						)
					);
					if ( $posts ) :
						foreach ( $posts as $post ) :
							$polygon = get_post_meta( $post->ID, 'lds_zones_coords', true );

							$final_poly = array();
							foreach ( $polygon as $polygon_ar ) {
								$final_poly[] = $polygon_ar[0] . ' ' . $polygon_ar[1];
							}
							array_push( $final_poly, $final_poly[0] );

							if ( ( $point_location->pointInPolygon( $point, $final_poly ) ) !== 'outside' ) {
								$location_on_zone = $post->ID;
								if ( empty( $zone_name ) ) {
									$zone_name = get_the_title( $post->ID );
								}
								break;
							}
						endforeach;
					endif;

					if ( $location_on_zone ) {
						WC()->session->set( 'lds_free_shipping', false );
						WC()->session->set( 'lds_outofzone', false );
						$lds_rate      = get_post_meta( $location_on_zone, 'lds_zones_price', true );
						$free_shipping = get_post_meta( $location_on_zone, 'lds_zones_free', true );
						if ( ! empty( $free_shipping ) ) {
							$total = WC()->cart->get_displayed_subtotal();
							if ( WC()->cart->display_prices_including_tax() ) {
								$total = $total - WC()->cart->get_discount_tax();
							}
							if ( 'no' === $free_copun ) {
								$total = $total - WC()->cart->get_discount_total();
							}
							if ( $total >= $free_shipping ) {
								$lds_rate = 0;
							} else {
								$free_shipping_message = esc_attr__( 'Free delivery on orders over ', 'lds-plugin' ) . wc_price( $free_shipping );
								WC()->session->set( 'lds_free_shipping', $free_shipping_message );
							}
						}
					} else {
						WC()->session->set( 'lds_outofzone', true );
						WC()->session->set( 'lds_free_shipping', false );
					}
				} else {
					WC()->session->set( 'lds_nolocation', true );
					WC()->session->set( 'lds_free_shipping', false );
				}

				if ( ! empty( $zone_name ) ) {
					$label = $this->title . ' (' . $zone_name . ')';
				} else {
					$label = $this->title;
				}

				$this->add_rate(
					array(
						'package' => $package,
						'label'   => $label,
						'cost'    => $lds_rate ,
					)
				);
			}

		}
	}
}

add_action( 'woocommerce_shipping_init', 'local_delivery_shipping_method_init' );
/**
 * LDS Add method.
 *
 * @param mixed $methods methods.
 */
function add_local_delivery_shipping_method( $methods ) {
	$methods['local_delivery_shipping'] = 'LDS_Shipping_Method';
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_local_delivery_shipping_method' );
