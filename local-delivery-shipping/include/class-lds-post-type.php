<?php
/**
 * Local Delivery Shipping Zones.
 *
 * @package     LDS\Shipping Zones
 * @since       1.0.0
 */

defined( 'ABSPATH' ) || die( 'Nope, not accessing this' );
/**
 * LDS Post type class
 *
 * @since       1.0.0
 */
class LDS_Post_Type {
	/**
	 * LDS Shipping Zones Hooks
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_lds_zones_content_type' ) ); // register lds_zones content type.
		add_action( 'add_meta_boxes', array( $this, 'add_lds_zones_meta_boxes' ) ); // add meta boxes.
		add_action( 'save_post_lds_zones', array( $this, 'save_lds_zones' ) ); // save lds_zones.
		add_filter( 'upload_mimes', array( $this, 'enable_extended_upload' ) );
		add_action( 'edit_user_profile', array( $this, 'user_admin_fields' ), 30 ); // admin: edit other users.
		add_action( 'edit_user_profile_update', array( $this, 'save_extra_user_profile_fields' ) );
	}
	/**
	 * LDS Admin fields
	 *
	 * @param mixed $user user id.
	 */
	public function user_admin_fields( $user ) {
		?>
	<h2><?php esc_attr_e( 'Local Delivery', 'iconic' ); ?></h2>
	<table class="form-table" id="iconic-additional-information">
		<tbody>
			<tr>
				<th>
					<label for="billing_gps_lat"><?php esc_attr_e( 'Latitude', 'lds-plugin' ); ?></label>
				</th>
				<td>
					<input type="text" name="billing_gps_lat" id="billing_gps_lat" value="<?php echo floatval( get_user_meta( $user->ID, 'billing_gps_lat', true ) ); ?>" class="regular-text" /><br />
				</td>
			</tr>
			<tr>
				<th>
					<label for="billing_gps_long"><?php esc_attr_e( 'Longitude', 'lds-plugin' ); ?></label>
				</th>
				<td>
					<input type="text" name="billing_gps_long" id="billing_gps_long" value="<?php echo floatval( get_user_meta( $user->ID, 'billing_gps_long', true ) ); ?>" class="regular-text" /><br />
				</td>
			</tr>
			<?php if ( get_user_meta( $user->ID, 'billing_gps_lat', true ) ) { ?>
			<tr>
				<th>
					<label for="google_map"><?php esc_attr_e( 'Google Map', 'lds-plugin' ); ?></label>
				</th>
				<td>
				<a target="_blank" href="https://www.google.com/maps/search/?api=1&query=<?php echo floatval( get_user_meta( $user->ID, 'billing_gps_lat', true ) ); ?>,<?php echo floatval( get_user_meta( $user->ID, 'billing_gps_long', true ) ); ?>">Google Map Link</a>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
		<?php
	}
	/**
	 * LDS Save Admin fields
	 *
	 * @param mixed $user_id user id.
	 */
	public function save_extra_user_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		if ( isset( $_POST['billing_gps_lat'] ) && isset( $_POST['billing_gps_long'] ) ) {
			update_user_meta( $user_id, 'billing_gps_lat', floatval( $_POST['billing_gps_lat'] ) );
			update_user_meta( $user_id, 'billing_gps_long', floatval( $_POST['billing_gps_long'] ) );
		}
	}
	/**
	 * LDS Register Local Delivery Shipping content type
	 */
	public function register_lds_zones_content_type() {
		// Labels for post type.
		$labels = array(
			'name'               => __( 'Delivery Zones', 'lds_plugin' ),
			'menu_name'          => __( 'Delivery Zones', 'lds_plugin' ),
			'name_admin_bar'     => __( 'Delivery Zone Maps', 'lds_plugin' ),
			'all_items'          => __( 'Delivery Zones', 'lds_plugin' ),
			'singular_name'      => __( 'Zone List', 'lds_plugin' ),
			'add_new'            => __( 'New Delivery Zone', 'lds_plugin' ),
			'add_new_item'       => __( 'Add New Zone', 'lds_plugin' ),
			'edit_item'          => __( 'Edit Zone', 'lds_plugin' ),
			'new_item'           => __( 'New Zone', 'lds_plugin' ),
			'view_item'          => __( 'View Zone', 'lds_plugin' ),
			'search_items'       => __( 'Search Zone', 'lds_plugin' ),
			'not_found'          => __( 'Nothing found', 'lds_plugin' ),
			'not_found_in_trash' => __( 'Nothing found in Trash', 'lds_plugin' ),
			'parent_item_colon'  => '',
		);
		$args   = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'query_var'           => true,
			'rewrite'             => false,
			'hierarchical'        => false,
			'supports'            => array(
				'title',
			),
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => 'woocommerce',
			'can_export'          => true,
		);
		// register.
		register_post_type( 'lds_zones', $args );
	}
	/**
	 * LDS adding meta boxes for delivery zones posts
	 */
	public function add_lds_zones_meta_boxes() {

		if ( isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) {
			$lds_zone_coords = get_post_meta( intval( $_GET['post'] ), 'lds_zones_coords', true );
		} else {
			$lds_zone_coords = null;
		}

		if ( $lds_zone_coords ) {
			add_meta_box(
				'lds_map_meta_box', // id.
				__( 'Delivery Zone Map', 'lds_plugin' ), // name.
				array( $this, 'lds_map_meta_box_display' ), // display function.
				'lds_zones', // post type.
				'normal',
				'default'
			);
		}
		add_meta_box(
			'lds_zones_meta_box', // id.
			__( 'Delivery Zone Info', 'lds_plugin' ), // name.
			array( $this, 'lds_zones_meta_box_display' ), // display function.
			'lds_zones', // post type.
			'normal',
			'default'
		);

		remove_meta_box( 'slugdiv', 'lds_zones', 'normal' );
	}
	/**
	 * LDS display map lds_zones meta box
	 *
	 * @param mixed $post post id.
	 */
	public function lds_map_meta_box_display( $post ) {
		if ( ! empty( get_option( 'wc_settings_lds_settings_key' ) ) ) {
			$api_key = '&key=' . get_option( 'wc_settings_lds_settings_key' );
		} else {
			$api_key = '';
		}
		$lds_zone_coords = get_post_meta( $post->ID, 'lds_zones_coords', true );
		$lds_zone_center = get_post_meta( $post->ID, 'lds_zones_center', true );
		$lds_zones_color = get_post_meta( $post->ID, 'lds_zones_color', true );
		?>
	<div id="map" style="height:400px"></div>
	<script>
	function initMap() {
		"use strict";
			  
		var map = new google.maps.Map(document.getElementById('map'), {
		zoom: 13,
		streetViewControl: false,
		scrollwheel: false,
		center: {lat: <?php echo floatval( $lds_zone_center[0] ); ?>, lng: <?php echo floatval( $lds_zone_center[1] ); ?>}
		});

		// Define the LatLng coordinates for the polygon's path.
		var triangleCoords = [
			<?php
			foreach ( $lds_zone_coords as $coord ) {
				echo '{lat: ' . floatval( $coord[0] ) . ', lng: ' . floatval( $coord[1] ) . '},';
			}
			?>
		];

		// Construct the polygon.
		var deliveryPolygon = new google.maps.Polygon({
		paths: triangleCoords,
		strokeColor: '<?php echo $lds_zones_color; ?>',
		strokeOpacity: 1,
		strokeWeight: 2,
		fillColor: '#ffffff',
		fillOpacity: 0
		});
		deliveryPolygon.setMap(map);
	}
	</script>
	<script async defer
	src="https://maps.googleapis.com/maps/api/js?callback=initMap<?php echo esc_attr( $api_key ); ?>">
	</script>
	<p>
	<strong><?php esc_attr_e( 'Shortcode:', 'lds-plugin' ); ?></strong><code> [delivery_map id="<?php echo esc_attr( $post->ID ); ?>"]</code>
	</br>
	<strong><?php esc_attr_e( 'Shortcode +:', 'lds-plugin' ); ?></strong><code> [delivery_map id="<?php echo esc_attr( $post->ID ); ?>" zoom"15" center="lat, long"]</code>
	</p>
		<?php
	}
	/**
	 * LDS display function used for our custom lds_zones meta box
	 *
	 * @param mixed $post post id.
	 */
	public function lds_zones_meta_box_display( $post ) {

		// set nonce field.
		wp_nonce_field( 'lds_zones_nonce', 'lds_zones_nonce_field' );

		// collect variables.
		$lds_zones_placemark = get_post_meta( $post->ID, 'lds_zones_placemark', true );
		$lds_zones_price     = get_post_meta( $post->ID, 'lds_zones_price', true );
		$lds_zones_color     = get_post_meta( $post->ID, 'lds_zones_color', true );
		$lds_zones_free      = get_post_meta( $post->ID, 'lds_zones_free', true );
		$url                 = get_option( 'wc_settings_lds_settings_url' );

		$placemarks = array();
		if ( ! empty( $url ) ) {
			$contents = file_get_contents( $url );
		if (!empty($contents)) {
    try {
        $xml = new SimpleXMLElement($contents);
    } catch (Exception $e) {
        // Manejar el error aquí. Por ejemplo, podrías escribir el error en un archivo de log, o mostrar un mensaje al usuario.
        error_log($e->getMessage());
        $xml = null; // Asegurarse de que $xml es nulo si no se pudo crear el SimpleXMLElement
    }
} else {
    $xml = null; // Asegurarse de que $xml es nulo si $contents está vacío
}

			$value    = $xml->Document->Placemark;
			$p_cnt    = count( $value );
			for ( $i = 0; $i < $p_cnt; $i++ ) {
				$placemarks[] = $xml->Document->Placemark[ $i ];
			}
		}
		?>
	<div id="lds_zones_metabox_form" class="field-container">
		<?php
		// before main form elementst hook.
		do_action( 'lds_zones_admin_form_start' );
		?>
	<table class="form-table">   
	<tbody>
		<tr valign="top">
			<th scope="row">
				<label for="lds_zones_placemark"><?php esc_attr_e( 'KML Map Zone', 'lds_plugin' ); ?></label>
			</th>
			<td>
				<select id="lds_zones_placemark" name="lds_zones_placemark">
					<option value=""><?php esc_attr_e( 'Choose a Zone', 'lds_plugin' ); ?></option>
					<?php $i = 1; foreach ( $placemarks as $placemark ) { ?>
					<option value="<?php echo intval( $i ); ?>" <?php selected( $lds_zones_placemark, $i ); ?>><?php echo esc_attr_e( $placemark->name ); ?></option>
					<?php $i++; } ?>
				</select> 
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="lds_zones_color"><?php esc_attr_e( 'Zone Border Color', 'lds_plugin' ); ?></label>
			</th>
			<td>
				<input type="color" name="lds_zones_color" id="lds_zones_color" value="<?php echo esc_html__( $lds_zones_color ); ?>"/>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="lds_zones_price"><?php esc_attr_e( 'Delivery Price', 'lds_plugin' ); ?></label>
			</th>
			<td>
				<input autocomplete="off" type="number" step="0.01" name="lds_zones_price" id="lds_zones_price" value="<?php echo esc_html__( $lds_zones_price ); ?>"/>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<label for="lds_zones_free"><?php esc_attr_e( 'Minimum Order for Free Delivery', 'lds_plugin' ); ?></label>
			</th>
			<td>
				<input autocomplete="off" type="number" step="0.01" name="lds_zones_free" id="lds_zones_free" value="<?php echo esc_html__( $lds_zones_free ); ?>"/>
			</td>
		</tr>
	</tbody>
	</table>
		<?php
		// after main form elementst hook.
		do_action( 'lds_zones_admin_form_end' );
		?>
	</div>
		<?php

	}
	/**
	 * LDS triggered when adding or editing a lds_zones
	 *
	 * @param mixed $post_id post id.
	 */
	public function save_lds_zones( $post_id ) {

		// check for nonce.
		if ( ! isset( $_POST['lds_zones_nonce_field'] ) ) {
			return $post_id;
		}
		// verify nonce.
		if ( ! wp_verify_nonce( $_POST['lds_zones_nonce_field'], 'lds_zones_nonce' ) ) {
			return $post_id;
		}
		// check for autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// get fields.
		$lds_zones_placemark = isset( $_POST['lds_zones_placemark'] ) ? sanitize_text_field( wp_unslash( $_POST['lds_zones_placemark'] ) ) : '';
		$lds_zones_price     = isset( $_POST['lds_zones_price'] ) ? sanitize_text_field( wp_unslash( $_POST['lds_zones_price'] ) ) : '';
		$lds_zones_color     = isset( $_POST['lds_zones_color'] ) ? sanitize_text_field( wp_unslash( $_POST['lds_zones_color'] ) ) : '';
		$lds_zones_free      = isset( $_POST['lds_zones_free'] ) ? sanitize_text_field( wp_unslash( $_POST['lds_zones_free'] ) ) : '';

		$old_lds_zones_placemark = get_post_meta( $post_id, 'lds_zones_placemark', true );
		$old_lds_zones_price     = get_post_meta( $post_id, 'lds_zones_price', true );

		if ( empty( $lds_zones_placemark ) ) {
			delete_post_meta( $post_id, 'lds_zones_coords' );
			delete_post_meta( $post_id, 'lds_zones_center' );
		}

		// if dropdown option changes and not empty find out coords and save them.
		if ( ( $lds_zones_placemark !== $old_lds_zones_placemark ) && ! empty( $lds_zones_placemark ) ) {
			$url    = get_option( 'wc_settings_lds_settings_url' );
			$coords = $this->kml_get_coordinates( $url, $lds_zones_placemark );

			if ( $coords ) {
				update_post_meta( $post_id, 'lds_zones_coords', $coords['coords'] );
				update_post_meta( $post_id, 'lds_zones_center', $coords['center'] );
			}
		}

		// update fields.
		update_post_meta( $post_id, 'lds_zones_placemark', $lds_zones_placemark );
		update_post_meta( $post_id, 'lds_zones_price', $lds_zones_price );
		update_post_meta( $post_id, 'lds_zones_color', $lds_zones_color );
		update_post_meta( $post_id, 'lds_zones_free', $lds_zones_free );

		// lds_zones save hook.
		// used so you can hook here and save additional post fields added via 'lds_zones_meta_data_output_end' or 'lds_zones_meta_data_output_end'.
		do_action( 'lds_zones_admin_save', $post_id, $_POST );

	}
	/**
	 * LDS get Cordination from KLM file
	 *
	 * @param mixed $url file.
	 * @param mixed $p polygon.
	 */
	public function kml_get_coordinates( $url, $p ) {
		$contents = file_get_contents( $url );
		$xml      = new SimpleXMLElement( $contents );
		$value    = $xml->Document->Placemark[ $p - 1 ]->Polygon->outerBoundaryIs->LinearRing->coordinates;
		$value_ar = explode( ' ', $value );

		$coords = array();
		foreach ( $value_ar as $coord ) {
			$args = explode( ',', $coord );
			if ( isset( $args[0] ) && isset( $args[1] ) ) {
				$coords['coords'][] = array( $args[1], $args[0] );
			}
		}

		// find center of polygon.
		$coorinations = $coords['coords'];
		$i            = 1;
		$minlat       = 0;
		$minlong      = 0;
		$maxlat       = 0;
		$maxlong      = 0;
		foreach ( $coorinations as $coord ) {
			if ( 1 === $i ) {
				$minlat  = $coord[0];
				$minlong = $coord[1];
				$maxlat  = $coord[0];
				$maxlong = $coord[1];
			}

			if ( $maxlat > $coord[0] ) {
				$maxlat = $coord[0];
			}
			if ( $maxlong > $coord[1] ) {
				$maxlong = $coord[1];
			}

			if ( $minlat < $coord[0] ) {
				$minlat = $coord[0];
			}
			if ( $minlong < $coord[1] ) {
				$minlong = $coord[1];
			}
			$i++;
		}
		$nlat             = $minlat + ( ( $maxlat - $minlat ) / 2 );
		$nlong            = $minlong + ( ( $maxlong - $minlong ) / 2 );
		$coords['center'] = array( $nlat, $nlong );

		return $coords;
	}
	/**
	 * LDS enable upload KLM file
	 *
	 * @param mixed $mime_types file type.
	 */
	public function enable_extended_upload( $mime_types ) {
		$mime_types['kml'] = 'text/xml';
		return $mime_types;
	}

} // en of class
?>
