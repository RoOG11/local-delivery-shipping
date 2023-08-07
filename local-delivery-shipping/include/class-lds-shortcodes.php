<?php
/**
 * Local Delivery Shipping Shortcodes.
 *
 * @package     LDS\Shortcodes
 * @since       1.0.0
 */

defined( 'ABSPATH' ) || die( 'Nope, not accessing this' );
/**
 * LDS Shortcodes Class
 *
 * @since       1.0.0
 */
class LDS_Shortcodes {
	/**
	 * LDS Shortcodes Hooks
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_link_shortcodes' ) ); // link shortcodes.
	}
	/**
	 * LDS Link shortcodes
	 */
	public function register_link_shortcodes() {
		add_shortcode( 'delivery_map', array( $this, 'shortcode_link_output' ) );
	}
	/**
	 * LDS link shortcode display
	 *
	 * @param mixed $atts shortcode atts.
	 */
	public function shortcode_link_output( $atts ) {

		$atts = ( shortcode_atts(
			array(
				'id'     => '',
				'zoom'   => 18,
				'center' => '',
				'zones'  => false,
			),
			$atts,
			'delivery_map'
		) );

		$coords = array();
		$center = array();
		$color  = array();

		if ( ! $atts['zones'] ) {
			$coords[0] = get_post_meta( $atts['id'], 'lds_zones_coords', true );
			if ( ! empty( $atts['center'] ) ) {
				$center[0] = explode( ',', $atts['center'] );
			} else {
				$center[0] = get_post_meta( $atts['id'], 'lds_zones_center', true );
			}
		} else {
			$posts = get_posts( array( 'post_type' => 'lds_zones' ) );
			if ( $posts ) :
				foreach ( $posts as $post ) :
					$coords[] = get_post_meta( $post->ID, 'lds_zones_coords', true );
					if ( empty( $atts['center'] ) ) {
						$center[] = get_post_meta( $post->ID, 'lds_zones_center', true );
					}
					$color[] = get_post_meta( $post->ID, 'lds_zones_color', true );
				endforeach;
				if ( ! empty( $atts['center'] ) ) {
					$center[ count( $posts ) - 1 ] = explode( ',', $atts['center'] );
				}
			endif;
		}

		$j         = count( $coords );
		$funtionid = uniqid();
		ob_start();

		?>
	<div class="mapcheckout"><div id="map<?php echo esc_attr( $funtionid ); ?>" style="height:400px"></div></div>
	<script>
	function initialize_<?php echo esc_attr( $funtionid ); ?>() {
		"use strict";
		  
		var map = new google.maps.Map(document.getElementById('map<?php echo esc_attr( $funtionid ); ?>'), {
		zoom: <?php echo esc_attr( $atts['zoom'] ); ?>,
		streetViewControl: false,
		scrollwheel: false,
		center: {lat: <?php echo floatval( $center[ $j - 1 ][0] ); ?>, lng: <?php echo floatval( $center[ $j - 1 ][1] ); ?>}
		});
		<?php
		for ( $i = 0; $i < $j; $i++ ) {
			if ( $atts['zones'] ) {
				$fcolor = esc_attr( $color[ $i ] );
			} else {
				$fcolor = get_post_meta( $atts['id'], 'lds_zones_color', true );
			}
			?>
			var triangleCoords<?php echo esc_attr( $i ); ?> = [
				<?php
				foreach ( $coords[ $i ] as $coord ) {
					echo '{lat: ' . floatval( $coord[0] ) . ', lng: ' . floatval( $coord[1] ) . '},';
				}
				?>
			];
			// Construct the polygon.
			var deliveryPolygon = new google.maps.Polygon({
			paths: triangleCoords<?php echo esc_attr( $i ); ?>,
			strokeColor: '<?php echo esc_attr( $fcolor ); ?>',
			strokeOpacity: 1,
			strokeWeight: 2,
			fillColor: '#ffffff',
			fillOpacity: 0,
			map: map
			});
		<?php } ?>
	}
	google.maps.event.addDomListener(window, 'load', initialize_<?php echo esc_attr( $funtionid ); ?>);
	</script>
		<?php
		$html = ob_get_clean();
		return $html;
	}

} // en of class
