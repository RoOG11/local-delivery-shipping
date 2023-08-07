<?php
/**
 * Plugin Name: Local Delivery Shipping (Woocommerce)
 * Author:      WP Experto
 * Description: Google map local delivery shipping method for Woocommerce
 * Version:     3.0.1
 * Plugin URI:  https://wpexperto.es/local-delivery-shipping-woocommerce/
 * Author URI:  https://wpexperto.es/
 * Text Domain: lds-plugin
 * Domain Path: /languages
 *
 * @package         LDS
 * @author          WP Experto
 * @copyright       Copyright (c) WP Experto
 *
 * Copyright (c) 2020 - WP Experto ( http://themeforest.net/licenses )
 */

defined( 'ABSPATH' ) || die( 'Nope, not accessing this' );

/**
 * Main Local Delivery Shipping class
 *
 * @since       1.0.0
 */
class Local_Delivery_Shipping {
	/**
	 * Local Delivery Shipping hooks
	 */
	public function __construct() {
		$lds_zones_post_type  = new LDS_Post_Type();
		$lds_zones_shortcodes = new LDS_Shortcodes();
		$map_position         = get_option( 'wc_settings_lds_settings_position' );
		if ( 'billing' === $map_position || ! $map_position ) {
			add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'action_woocommerce_after_edit_address_form_load_address' ), 10, 1 );
		} elseif ( 'shipping' === $map_position ) {
			add_action( 'woocommerce_before_order_notes', array( $this, 'action_woocommerce_after_edit_address_form_load_address' ), 10, 1 );
		} elseif ( 'order_notes' === $map_position ) {
			add_action( 'woocommerce_after_order_notes', array( $this, 'action_woocommerce_after_edit_address_form_load_address' ), 10, 1 );
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'shipping_scripts' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'my_account_menu_order' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_override_checkout_fields' ) );
		add_filter( 'woocommerce_billing_fields', array( $this, 'billing_phone_required' ), 20, 1 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'custom_override_billing_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'shipping_remove_fields' ) );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'add_custom_billing_field' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_rename_wc_checkout_fields' ) );
		add_action( 'woocommerce_after_edit_address_form_billing', array( $this, 'action_woocommerce_after_edit_address_form_load_address' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_check_address_delivery', array( $this, 'update_address_delivery' ) );
		add_action( 'wp_ajax_check_address_delivery', array( $this, 'update_address_delivery' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'refresh_shipping_methods' ), 10, 1 );
		add_filter( 'woocommerce_package_rates', array( $this, 'cart_lds_disable_shipping_methods' ), 20, 2 );
		add_action( 'woocommerce_checkout_process', array( $this, 'cart_lds_limit_submit_alert' ), 20, 1 );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'action_cart_calculate_totals' ), 30 );
		add_action( 'wp_head', array( $this, 'unset_country_field' ), 100 );
		add_filter( 'woocommerce_add_error', array( $this, 'customize_wc_errors' ) );
		add_action( 'woocommerce_email_customer_details', array( $this, 'lds_display_email_order_meta' ), 30, 3 );
		add_action( 'woocommerce_cart_collaterals', array( $this, 'remove_cart_totals' ), 9 );
		add_filter( 'woocommerce_shipping_package_name', array( $this, 'custom_shipping_package_name' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'lds_save_extra_checkout_fields' ), 10, 2 );
		add_action( 'woocommerce_thankyou', array( $this, 'lds_display_order_data' ), 20 );
		add_action( 'woocommerce_view_order', array( $this, 'lds_display_order_data' ), 20 );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'lds_display_order_data_in_admin' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'lds_save_extra_details' ), 45, 2 );
		add_action( 'init', array( $this, 'lds_load_textdomain' ), 0 );
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'action_after_shipping_rate' ), 20, 2 );
	}
	/**
	 * Add order meta, lat long of delivery location
	 *
	 * @param mixed $order order number.
	 * @param mixed $data lat long.
	 */
	public function lds_save_extra_checkout_fields( $order, $data ) {
		if ( isset( $data['billing_gps_lat'] ) ) {
			$order->update_meta_data( '_order_lat', sanitize_text_field( $data['billing_gps_lat'] ) );
		}
		if ( isset( $data['billing_gps_long'] ) ) {
			$order->update_meta_data( '_order_long', $data['billing_gps_long'] );
		}
	}
	/**
	 * Display delivery location order meta on thank you page and client view order
	 *
	 * @param mixed $order_id order number.
	 */
	public function lds_display_order_data( $order_id ) {
		$order         = wc_get_order( $order_id );
		$lds_zone_zoom = get_option( 'wc_settings_lds_settings_zoom' );
		if ( empty( $lds_zone_zoom ) ) {
			$lds_zone_zoom = 14;
		}
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
			$shipping_method_id = $shipping_item_obj->get_method_id();
		}
		if ( 'local_delivery_shipping' === $shipping_method_id ) {
			?>
			<h2><?php esc_html_e( 'Delivery Location', 'lds-plugin' ); ?></h2>
			<div class="mapcheckout"><div id="map" style="height:400px"></div></div>
			<script>
			function initMap() {
				"use strict";
				var myLatLng = {lat: <?php echo floatval( $order->get_meta( '_order_lat' ) ); ?>, lng: <?php echo floatval( $order->get_meta( '_order_long' ) ); ?>};
				var map = new google.maps.Map(document.getElementById('map'), {
					zoom: <?php echo $lds_zone_zoom; ?>,
					streetViewControl: false,
					scrollwheel: false,
					center: myLatLng
				});
				var marker = new google.maps.Marker({
					position: myLatLng,
					map: map
				});
			}
			google.maps.event.addDomListener(window, 'load', initMap);
			</script>
			<?php
		}
	}
	/**
	 * Display delivery location on each order for admin
	 *
	 * @param mixed $order order number.
	 */
	public function lds_display_order_data_in_admin( $order ) {
		foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
			$shipping_method_id = $shipping_item_obj->get_method_id();
		}
		if ( 'local_delivery_shipping' === $shipping_method_id ) {
			?>
			<div class="order_data_column">
				<h4><?php esc_html_e( 'Delivery Location', 'lds-plugin' ); ?><a href="#" class="edit_address"><?php esc_html_e( 'Edit', 'lds-plugin' ); ?></a></h4>
				<div class="address">
				<?php
					echo '<p><strong>' . esc_html__( 'Latitude', 'lds-plugin' ) . ':</strong>' . floatval( $order->get_meta( '_order_lat' ) ) . '</p>';
					echo '<p><strong>' . esc_html__( 'Longitude', 'lds-plugin' ) . ':</strong>' . floatval( $order->get_meta( '_order_long' ) ) . '</p>';
				?>
				</div>
				<div class="edit_address">
					<?php
					woocommerce_wp_text_input(
						array(
							'id'            => '_order_lat',
							'label'         => esc_html__( 'Latitude', 'lds-plugin' ),
							'wrapper_class' => '_billing_company_field',
						)
					);
					?>
					<?php
					woocommerce_wp_text_input(
						array(
							'id'            => '_order_long',
							'label'         => esc_html__( 'Longitude', 'lds-plugin' ),
							'wrapper_class' => '_billing_company_field',
						)
					);
					?>
				</div>
				<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=<?php echo floatval( $order->get_meta( '_order_lat' ) ); ?>,<?php echo floatval( $order->get_meta( '_order_long' ) ); ?>">Google Map Link</a>
			</div>
			<?php
		}
	}
	/**
	 * Admin edit delivery location of the order
	 *
	 * @param mixed $order_id order number.
	 * @param mixed $post post.
	 */
	public function lds_save_extra_details( $order_id, $post ) {
		if ( isset( $_POST['_order_lat'] ) && isset( $_POST['_order_long'] ) ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_order_lat', floatval( $_POST['_order_lat'] ) );
			$order->update_meta_data( '_order_long', floatval( $_POST['_order_long'] ) );
			$order->save_meta_data();
		}
	}
	/**
	 * Display delivery location for order emails
	 *
	 * @param mixed $order order number.
	 * @param mixed $sent_to_admin admin.
	 * @param mixed $plain_text text.
	 */
	public function lds_display_email_order_meta( $order, $sent_to_admin, $plain_text ) {
		foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
			$shipping_method_id = $shipping_item_obj->get_method_id();
		}
		if ( 'local_delivery_shipping' === $shipping_method_id ) {
			if ( $plain_text ) {
				echo esc_html__( 'Delivery Location', 'lds-plugin' ) . ':' . floatval( $order->get_meta( '_order_lat' ) ) . ', ' . floatval( $order->get_meta( '_order_long' ) );
			} else {
				echo '<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=' . floatval( $order->get_meta( '_order_lat' ) ) . ',' . floatval( $order->get_meta( '_order_long' ) ) . '">' . esc_html__( 'Delivery Location', 'lds-plugin' ) . '</a>';
			}
		}
	}
	/**
	 * Remove error of lat long fields
	 *
	 * @param mixed $error errors.
	 */
	public function customize_wc_errors( $error ) {
		if ( strpos( $error, esc_html__( 'Billing ', 'lds-plugin' ) ) !== false ) {
			$error = str_replace( esc_html__( 'Billing ', 'lds-plugin' ), '', $error );
		}
		if ( strpos( $error, esc_html__( 'Latitude', 'lds-plugin' ) ) !== false ) {
			$error = '';
		}
		if ( strpos( $error, esc_html__( 'Longitude', 'lds-plugin' ) ) !== false ) {
			$error = '';
		}
		return $error;
	}
	/**
	 * Change checkout shipping name to delivery
	 *
	 * @param mixed $name shipping name.
	 */
	public function custom_shipping_package_name( $name ) {
		$label = get_option( 'wc_settings_lds_ship_tag' );
		if ( $label ) {
			return esc_html( $label );
		} else {
			return $name;
		}
	}
	/**
	 * Remove shipping calculator on cart page
	 */
	public function remove_cart_totals() {
		if ( get_option( 'wc_settings_lds_settings_lds_cart_col' ) === 'yes' ) {
			// Remove cart totals block.
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10 );

			// Add back "Proceed to checkout" button (and hooks).
			echo '<div class="cart_totals">';
			do_action( 'woocommerce_before_cart_totals' );

			echo '<div class="wc-proceed-to-checkout">';
			do_action( 'woocommerce_proceed_to_checkout' );
			echo '</div>';

			do_action( 'woocommerce_after_cart_totals' );
			echo '</div>';
		}
	}
	/**
	 * Out of delivery zone error
	 *
	 * @param mixed $cart_object cart object.
	 */
	public function action_cart_calculate_totals( $cart_object ) {
		$chosen_shipping_method_id = WC()->session->get( 'chosen_shipping_methods' )[0];
		$chosen_shipping_method    = explode( ':', $chosen_shipping_method_id )[0];
		if ( ( WC()->session->get( 'lds_outofzone' ) || WC()->session->get( 'lds_nolocation' ) ) && ( $chosen_shipping_method == 'local_delivery_shipping' ) ) {
			$cart_object->total = 0;
		}
	}
	/**
	 * Out of delivery zone error
	 *
	 * @param mixed $rates shiping rate.
	 * @param mixed $package cart package.
	 */
	public function cart_lds_disable_shipping_methods( $rates, $package ) {
		if ( WC()->session->get( 'lds_outofzone' ) || WC()->session->get( 'lds_nolocation' ) ) {
			foreach ( $rates as $rate_id => $rate ) {
				if ( 'local_delivery_shipping' === $rate->method_id ) {
					$rates[ $rate_id ]->cost = 0;
					break;
				}
			}
		}

		return $rates;
	}
	/**
	 * Out of delivery zone top page error
	 */
	public function cart_lds_limit_submit_alert() {
		$chosen_shipping_method_id = WC()->session->get( 'chosen_shipping_methods' )[0];
		$chosen_shipping_method    = explode( ':', $chosen_shipping_method_id )[0];
		if ( WC()->session->get( 'lds_outofzone' ) && ( $chosen_shipping_method == 'local_delivery_shipping' ) ) {
			wc_add_notice( esc_html__( 'Delivery on this location is currently unavailable!', 'lds-plugin' ), 'error' );
		}
		if ( WC()->session->get( 'lds_nolocation' ) && ( $chosen_shipping_method == 'local_delivery_shipping' ) ) {
			wc_add_notice( esc_html__( 'Please locate yourself on the map!', 'lds-plugin' ), 'error' );
		}
	}
	/**
	 * Out of delivery zone shipping error
	 *
	 * @param mixed $method shiping methods.
	 * @param mixed $index cart index.
	 */
	public function action_after_shipping_rate( $method, $index ) {
		// Targeting checkout page only.
		$message = '';
		if ( WC()->session->get( 'lds_outofzone' ) ) {
			$message = esc_html__( 'Delivery on this location is currently unavailable!', 'lds-plugin' );
		}
		if ( WC()->session->get( 'lds_nolocation' ) ) {
			$message = esc_html__( 'Locate yourself to view delivery price!', 'lds-plugin' );
		}
		if ( WC()->session->get( 'lds_free_shipping' ) ) {
			$message = WC()->session->get( 'lds_free_shipping' );
		}
		if ( is_cart() ) {
			return; // Exit on cart page.
		}
		if ( 'local_delivery_shipping' === $method->method_id ) {
			if ( $message ) {
				echo '<p>' . $message . '</p>';
			}
		}
	}
	/**
	 * Recalculate shipping zones
	 *
	 * @param mixed $post_data cart package.
	 */
	public function refresh_shipping_methods( $post_data ) {
		// Mandatory to make it work with shipping methods.
		foreach ( WC()->cart->get_shipping_packages() as $package_key => $package ) {
			WC()->session->set( 'shipping_for_package_' . $package_key, false );
		}

		// check if lat long has value on order update.
		$arr = explode( '&', $post_data );
		foreach ( $arr as $field ) {
			list($k, $v)           = explode( '=', $field );
			$post_data_array[ $k ] = $v;
		}

		if ( ! $post_data_array['billing_gps_lat'] ) {
			WC()->session->__unset( 'lds_long' );
			WC()->session->__unset( 'lds_lat' );
		}

		// recalculate shipping.
		WC()->cart->calculate_shipping();
	}
	/**
	 * Ajax call location picker.
	 */
	public function update_address_delivery() {
		if ( ! check_ajax_referer( 'lds-nonce', 'security', false ) ) {
			wp_send_json_error( 'Invalid security token sent.' );
			wp_die();
		}
		if ( isset( $_POST['lat'] ) && isset( $_POST['long'] ) ) {
			$lat  = floatval( $_POST['lat'] );
			$long = floatval( $_POST['long'] );

			WC()->session->set( 'lds_lat', $lat );
			WC()->session->set( 'lds_long', $long );
		}
	}
	/**
	 * CSS integration frontend
	 */
	public function shipping_scripts() {
		if ( ! empty( get_option( 'wc_settings_lds_settings_key' ) ) ) {
			$api_key = '?key=' . get_option( 'wc_settings_lds_settings_key' );
		} else {
			$api_key = '';
		}
		wp_enqueue_style( 'lds', plugin_dir_url( __FILE__ ) . 'assets/lds.css', false, '1.1', 'all' );
		wp_enqueue_script( 'map-api', 'https://maps.googleapis.com/maps/api/js' . $api_key, array(), ' ', false );
		if ( is_wc_endpoint_url( 'edit-address' ) || is_checkout() ) {
			if ( 'yes' === get_option( 'wc_settings_lds_hide_map' ) ) {
				wp_enqueue_script( 'hide_map', plugin_dir_url( __FILE__ ) . 'assets/hide_map.js', array(), '1.0.0', true );
			}
			wp_enqueue_script( 'checkout_script', plugin_dir_url( __FILE__ ) . 'assets/delivery_map.js', array(), '1.0.0', true );
			wp_localize_script(
				'checkout_script',
				'lds_ajax',
				array(
					'url'      => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'lds-nonce' ),
				)
			);
			wp_localize_script( 'checkout_script', 'php_vars', $this->get_delivery_main_zone() );
		}
	}
	/**
	 * Main delivery zone location
	 */
	public function get_delivery_main_zone() {
		$args              = array(
			'post_type'   => 'lds_zones',
			'numberposts' => -1,
		);
		$posts             = get_posts( $args );
		$data_to_be_passed = array();
		$geo_helper        = get_option( 'wc_settings_lds_settings_geocode' );
		if ( $posts ) {
			foreach ( $posts as $post ) {
				$lds_zone_coords = get_post_meta( $post->ID, 'lds_zones_coords', true );
				$lds_zone_center = get_post_meta( $post->ID, 'lds_zones_center', true );
				$lds_zones_color = get_post_meta( $post->ID, 'lds_zones_color', true );
				if ( ! empty( get_option( 'wc_settings_lds_settings_zoom' ) ) ) {
					$lds_zone_zoom = get_option( 'wc_settings_lds_settings_zoom' );
				} else {
					$lds_zone_zoom = 18;
				}
				$final_array = array();

				foreach ( $lds_zone_coords as $coord ) {
					$final_array[] = (object) array(
						'lat' => floatval( $coord[0] ),
						'lng' => floatval( $coord[1] ),
					);
				}

				if ( ! empty( get_option( 'wc_settings_lds_settings_center' ) ) ) {
					$center = explode( ',', get_option( 'wc_settings_lds_settings_center' ) );
				} else {
					$center = get_post_meta( $post->ID, 'lds_zones_center', true );
				}

				$data_to_be_passed [] = array(
					'coords' => $final_array,
					'color'  => $lds_zones_color,
					'zoom'   => $lds_zone_zoom,
					'center' => $center,
					'geo'    => $geo_helper,
				);
			}
			return $data_to_be_passed;
		}

	}
	/**
	 * Client account menu order, remove download
	 */
	public function my_account_menu_order() {
		$menu_order = array(
			'dashboard'       => esc_html__( 'Dashboard', 'lds-plugin' ),
			'orders'          => esc_html__( 'Orders', 'lds-plugin' ),
			'edit-address'    => esc_html__( 'Addresses', 'lds-plugin' ),
			'edit-account'    => esc_html__( 'Account Details', 'lds-plugin' ),
			'customer-logout' => esc_html__( 'Logout', 'lds-plugin' ),
		);
		return $menu_order;
	}
	/**
	 * Unset country field
	 */
	public function unset_country_field() {
		if ( get_option( 'wc_settings_lds_settings_lds_country' ) === 'yes' ) {
			echo '<style>#billing_country_field{display: none;}</style>';
		}
	}
	/**
	 * Unset checkout fields
	 *
	 * @param mixed $fields checkout field.
	 */
	public function custom_override_checkout_fields( $fields ) {
		if ( get_option( 'wc_settings_lds_settings_lds_adress' ) === 'yes' ) {
			unset( $fields['billing']['billing_address_2'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_state' ) === 'yes' ) {
			unset( $fields['billing']['billing_state'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_postcode' ) === 'yes' ) {
			unset( $fields['billing']['billing_postcode'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_city' ) === 'yes' ) {
			unset( $fields['billing']['billing_city'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_company' ) === 'yes' ) {
			unset( $fields['billing']['billing_company'] );
		}
		return $fields;
	}
	/**
	 * Checkout Make phone required
	 *
	 * @param mixed $address_fields checkout field.
	 */
	public function billing_phone_required( $address_fields ) {
		$address_fields['billing_phone']['required'] = true;
		return $address_fields;
	}
	/**
	 * Rename Checkout Fields label
	 *
	 * @param mixed $fields checkout field.
	 */
	public function custom_rename_wc_checkout_fields( $fields ) {
		$fields['billing']['billing_phone']['label'] = esc_html__( 'Mobile', 'lds-plugin' );
		return $fields;
	}
	/**
	 * Rename Checkout Shipping Fields
	 *
	 * @param mixed $fields checkout field.
	 */
	public function shipping_remove_fields( $fields ) {
		unset( $fields['shipping_gps_lat'] );
		unset( $fields['shipping_gps_long'] );
		return $fields;

	}
	/**
	 * Remove billing fields
	 *
	 * @param mixed $fields checkout field.
	 */
	public function custom_override_billing_fields( $fields ) {
		if ( get_option( 'wc_settings_lds_settings_lds_adress' ) === 'yes' ) {
			unset( $fields['billing_address_2'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_state' ) === 'yes' ) {
			unset( $fields['billing_state'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_postcode' ) === 'yes' ) {
			unset( $fields['billing_postcode'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_city' ) === 'yes' ) {
			unset( $fields['billing_city'] );
		}
		if ( get_option( 'wc_settings_lds_settings_lds_company' ) === 'yes' ) {
			unset( $fields['billing_company'] );
		}
		return $fields;
	}
	/**
	 * Add new billing fields lat long
	 *
	 * @param mixed $fields checkout field.
	 */
	public function add_custom_billing_field( $fields ) {
		$fields['gps_lat'] = array(
			'label'    => esc_html__( 'Latitude', 'lds-plugin' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'priority' => 200,
		);

		$fields['gps_long'] = array(
			'label'    => esc_html__( 'Longitude', 'lds-plugin' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'priority' => 200,
		);
		return $fields;

	}
	/**
	 * Define the woocommerce_after_edit_address_form_<load_address> callback
	 */
	public function action_woocommerce_after_edit_address_form_load_address() {
		global $current_user;
		$gpslat  = get_user_meta( $current_user->ID, 'billing_gps_lat', true );
		$gpslong = get_user_meta( $current_user->ID, 'billing_gps_long', true );
		echo '<div class="lds_plugin">';
		if ( $gpslat && $gpslong ) {
			echo '<div class="locatemeholder"><a href="#" id="edd-purchase-button" class="locateme button">' . esc_html__( 'Relocate Me', 'lds-plugin' ) . '</a></div>';
		} else {
			echo '<div class="locatemeholder"><a href="#" id="edd-purchase-button" class="locateme button">' . esc_html__( 'Locate Me', 'lds-plugin' ) . '</a></div>';
		}
		echo '<div class="mapcheckout"><div id="delivery-map-canvas"></div></div></div>';
	}
	/**
	 * Debug
	 *
	 * @param mixed $log debug error.
	 */
	public function write_log( $log ) {
		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}
	/**
	 * Plugin translation function
	 */
	public function lds_load_textdomain() {
		load_plugin_textdomain( 'lds-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	include plugin_dir_path( __FILE__ ) . 'include/class-lds-shipping-method.php';
	include plugin_dir_path( __FILE__ ) . 'include/class-lds-point-in-polygon.php';
	include plugin_dir_path( __FILE__ ) . 'include/class-lds-post-type.php';
	include plugin_dir_path( __FILE__ ) . 'include/class-lds-settings.php';
	include plugin_dir_path( __FILE__ ) . 'include/class-lds-shortcodes.php';
	$local_delivery_shipping = new Local_Delivery_Shipping();
}
