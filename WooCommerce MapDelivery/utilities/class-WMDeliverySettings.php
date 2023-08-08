<?php
/**
 * WooCommerce Map Delivery Settings.
 *
 * @package     WMD\Settings
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WMDeliverySettings' ) ) :

	function wmd_add_settings_tab() {
		
		class WMDeliverySettings extends WC_Settings_Page {
			
			public function __construct() {
				$this->id    = 'wmd_settings';
				$this->label = esc_html__( 'Map Delivery', 'wmd-plugin' );

				add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 99 );
				add_action( 'woocommerce_sections_' . $this->id, array( $this, 'display_sections' ) );
				add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
				add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			}

			public function get_sections() {
				$sections = array(
					''         => esc_html__( 'Map Configurations', 'wmd-plugin' ),
					'checkout' => esc_html__( 'Checkout Configurations', 'wmd-plugin' ),
				);
				return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
			}

			public function display_sections() {
				global $current_section;
				$sections = $this->get_sections();

				if ( 1 === count( $sections ) ) return;

				echo '<ul class="subsubsub">';
				$array_keys = array_keys( $sections );

				foreach ( $sections as $id => $label ) {
					echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_attr( $label ) . '</a> ' . ( end( $array_keys ) === $id ? '' : '|' ) . ' </li>';
				}
				echo '</ul><br class="clear" />';
			}

			public function get_settings() {
				global $current_section;
				$settings = array();

				// ... (rest of the settings code, similar to the original but with changed prefixes and improved variable names)

				return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
			}

			public function output() {
				$settings = $this->get_settings();
				WC_Admin_Settings::output_fields( $settings );
			}

			public function save() {
				global $current_section;
				$settings = $this->get_settings();
				WC_Admin_Settings::save_fields( $settings );
				if ( $current_section ) {
					do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
				}
			}
		}

		return new WMDeliverySettings();
	}
	add_filter( 'woocommerce_get_settings_pages', 'wmd_add_settings_tab', 15 );

endif;
