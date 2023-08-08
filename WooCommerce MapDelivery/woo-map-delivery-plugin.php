<?php
/**
 * Plugin Name: WooCommerce MapDelivery
 * Author:      Ronald Ortega Gantier - DigitalEra
 * Description: Map-based delivery shipping method for WooCommerce
 * Version:     1.0.0
 * Plugin URI:  [Your Plugin URL]
 * Author URI:  [Your Author URL]
 * Text Domain: wmd-plugin
 * Domain Path: /languages
 *
 * @package         WMD
 * @author          Your Name or Company
 * @copyright       Copyright (c) Your Name or Company
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Main WooCommerce MapDelivery class
 *
 * @since       1.0.0
 */
class WooCommerce_MapDelivery {
    // ... [rest of the code remains largely unchanged, but with function and class names updated]
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    include plugin_dir_path( __FILE__ ) . 'include/class-wmd-shipping-method.php';
    include plugin_dir_path( __FILE__ ) . 'include/class-wmd-point-in-polygon.php';
    include plugin_dir_path( __FILE__ ) . 'include/class-wmd-post-type.php';
    include plugin_dir_path( __FILE__ ) . 'include/class-wmd-settings.php';
    include plugin_dir_path( __FILE__ ) . 'include/class-wmd-shortcodes.php';
    $woo_map_delivery = new WooCommerce_MapDelivery();
}
