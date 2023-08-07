<?php
/**
 * Local Delivery Shipping Settings.
 *
 * @package     LDS\Settings
 * @since       1.0.0
 */

defined( 'ABSPATH' ) || die( 'Nope, not accessing this' );

if ( ! class_exists( 'LDS_Settings' ) ) :
	/**
	 * LDS Add settings tab
	 */
	function lds_add_tab() {
		/**
		 * LDS Settings class
		 *
		 * @since       1.0.0
		 */
		class LDS_Settings extends WC_Settings_Page {
			/**
			 * LDS Settings Hooks
			 */
			public function __construct() {
				$this->id    = 'lds_settings';
				$this->label = esc_html__( 'Local Delivery', 'lds-plugin' );
				// Add the tab to the tabs array.
				add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 99 );
				// Add new section to the page.
				add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
				// Add settings.
				add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
				// Process/save the settings.
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			}

			/**
			 *  Get sections
			 *
			 *  @return array
			 */
			public function get_sections() {

				// Must contain more than one section to display the links.
				// Make first element's key empty ('').
				$sections = array(
					''         => esc_html__( 'Map Settings', 'lds-plugin' ),
					'checkout' => esc_html__( 'Checkout Settings', 'lds-plugin' ),
				);

				return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
			}

			/**
			 *  Output sections
			 */
			public function output_sections() {

				global $current_section;

				$sections = $this->get_sections();

				if ( empty( $sections ) || 1 === count( $sections ) ) {
					return;
				}

				echo '<ul class="subsubsub">';

				$array_keys = array_keys( $sections );

				foreach ( $sections as $id => $label ) {
					echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_attr( $label ) . '</a> ' . ( end( $array_keys ) === $id ? '' : '|' ) . ' </li>';
				}

				echo '</ul><br class="clear" />';
			}

			/**
			 *  Get settings array
			 *
			 *  @return array
			 */
			public function get_settings() {

				global $current_section;

				$settings = array();

				if ( '' === $current_section ) {

					$settings = array(
						'section_title' => array(
							'name' => esc_html__( 'Google Map Settings', 'lds-plugin' ),
							'type' => 'title',
							'desc' => '',
							'id'   => 'wc_settings_lds_settings_map',
						),
						'key'           => array(
							'name'     => esc_html__( 'API Key', 'lds-plugin' ),
							'type'     => 'text',
							'desc_tip' => esc_html__( 'Google API Key, Geolocation API + Maps JavaScript API + Geocoding API', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_key',
						),
						'url'           => array(
							'name'     => esc_html__( 'KML Map URL', 'lds-plugin' ),
							'type'     => 'text',
							'desc_tip' => esc_html__( 'Google Earth delivery zones polygon map', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_url',
						),
						'geocode'       => array(
							'title'    => esc_html__( 'Geocode City, Country', 'lds-plugin' ),
							'type'     => 'text',
							'desc_tip' => esc_html__( 'Help API to locate address on the map at checkout by adding city name, country.', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_geocode',
						),
						'zoom'          => array(
							'name'     => esc_html__( 'Checkout Map Zoom', 'lds-plugin' ),
							'type'     => 'text',
							'desc_tip' => esc_html__( 'Optional, This controls the zoom of the delivery zone on the map.', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_zoom',
						),
						'center'        => array(
							'name'     => esc_html__( 'Checkout Map Center', 'lds-plugin' ),
							'type'     => 'text',
							'desc_tip' => esc_html__( 'Optional, This controls the center of the checkout delivery map. lat, long', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_center',
						),
						'position'      => array(
							'name'     => esc_html__( 'Checkout Map Position', 'lds-plugin' ),
							'type'     => 'select',
							'desc_tip' => esc_html__( 'This controls the position of the checkout delivery map.', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_position',
							'default'  => 'billing',
							'options'  => array(
								'billing'     => __( 'After billing form', 'lds-plugin' ),
								'shipping'    => __( 'After shipping form', 'lds-plugin' ),
								'order_notes' => __( 'After order notes', 'lds-plugin' ),
							),
						),
						'section_end'   => array(
							'type' => 'sectionend',
							'id'   => 'wc_settings_lds_settings_map_end',
						),
					);

				} elseif ( 'checkout' === $current_section ) {

					$settings = array(
						array(
							'name' => esc_html__( 'Checkout Settings', 'lds-plugin' ),
							'type' => 'title',
							'desc' => esc_html__( 'Exclude fields at Checkout form and Account page', 'lds-plugin' ),
							'id'   => 'wc_settings_lds_settings_checkout',

						),
						'lds_country'  => array(
							'name' => esc_html__( 'Exclude Country', 'lds-plugin' ),
							'type' => 'checkbox',
							'id'   => 'wc_settings_lds_settings_lds_country',
						),
						'lds_state'    => array(
							'name' => esc_html__( 'Exclude State', 'lds-plugin' ),
							'type' => 'checkbox',
							'id'   => 'wc_settings_lds_settings_lds_state',
						),
						'lds_city'     => array(
							'name' => esc_html__( 'Exclude City', 'lds-plugin' ),
							'type' => 'checkbox',
							'id'   => 'wc_settings_lds_settings_lds_city',
						),
						'lds_postcode' => array(
							'name' => esc_html__( 'Exclude Postcode', 'lds-plugin' ),
							'type' => 'checkbox',
							'id'   => 'wc_settings_lds_settings_lds_postcode',
						),
						'lds_company'  => array(
							'name' => esc_html__( 'Exclude Company', 'lds-plugin' ),
							'type' => 'checkbox',
							'id'   => 'wc_settings_lds_settings_lds_company',
						),
						'lds_adress'   => array(
							'name' => esc_html__( 'Exclude Adress 2', 'lds-plugin' ),
							'type' => 'checkbox',
							'id'   => 'wc_settings_lds_settings_lds_adress',
						),
						'lds_cart_col' => array(
							'name'     => esc_html__( 'Exclude Cart Shipping', 'lds-plugin' ),
							'type'     => 'checkbox',
							'desc_tip' => esc_html__( 'Remove shipping calculator on cart page', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_settings_lds_cart_col',
						),
						'lds_hide_map' => array(
							'name'     => esc_html__( 'Hide Map', 'lds-plugin' ),
							'type'     => 'checkbox',
							'id'       => 'wc_settings_lds_hide_map',
							'desc_tip' => esc_html__( 'Hide map for other shipping methods', 'lds-plugin' ),
						),
						'lds_ship_tag' => array(
							'name'     => esc_html__( 'Shipping Label', 'lds-plugin' ),
							'type'     => 'text',
							'desc_tip' => esc_html__( 'Shipping label at checkout', 'lds-plugin' ),
							'id'       => 'wc_settings_lds_ship_tag',
						),
						'lds_free_wcopun' => array(
							'name'     => esc_html__( 'Free Shipping + Coupons', 'lds-plugin' ),
							'type'     => 'checkbox',
							'id'       => 'wc_settings_lds_free_wcopun',
							'desc_tip' => esc_html__( 'Apply free shipping minimum order rule before discount coupon', 'lds-plugin' ),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'wc_settings_lds_settings_checkout',
						),
					);

				} else {

					// Overview.
					$settings = array();
				}

				return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
			}

			/**
			 *  Output the settings
			 */
			public function output() {
				$settings = $this->get_settings();
				WC_Admin_Settings::output_fields( $settings );
			}

			/**
			 *  Process save
			 */
			public function save() {

				global $current_section;

				$settings = $this->get_settings();

				WC_Admin_Settings::save_fields( $settings );

				if ( $current_section ) {
					do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
				}
			}
		}

		return new LDS_Settings();
	}
	add_filter( 'woocommerce_get_settings_pages', 'lds_add_tab', 15 );

endif;
