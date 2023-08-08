<?php
/**
 * Delivery Area Checker using point-in-polygon algorithm.
 *
 * @package     DeliveryAreaChecker\Area
 * @since       1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Delivery Area Checker Class
 *
 * @since       1.1.0
 */
class DeliveryAreaChecker {
    private $isPointOnVertex = true; // Check if the point is exactly on one of the vertices?

    public function __construct() {
    }

    public function isPointInsideArea( $point, $area, $isPointOnVertex = true ) {
        $this->isPointOnVertex = $isPointOnVertex;

        $point    = $this->convertToPointCoordinates( $point );
        $areaPoints = array();
        foreach ( $area as $vertex ) {
            $areaPoints[] = $this->convertToPointCoordinates( $vertex );
        }

        if ( $this->isPointOnVertex && $this->checkPointOnVertex( $point, $areaPoints ) ) {
            return 'vertex';
        }

        $intersections  = 0;
        $vertices_count = count( $areaPoints );

        for ( $i = 1; $i < $vertices_count; $i++ ) {
            $vertex1 = $areaPoints[ $i - 1 ];
            $vertex2 = $areaPoints[ $i ];
            if ( $this->isPointOnBoundary( $point, $vertex1, $vertex2 ) ) {
                return 'boundary';
            }
            if ( $this->doesIntersect( $point, $vertex1, $vertex2 ) ) {
                $intersections++;
            }
        }

        return $intersections % 2 != 0 ? 'inside' : 'outside';
    }

    private function checkPointOnVertex( $point, $vertices ) {
        return in_array( $point, $vertices, true );
    }

    private function convertToPointCoordinates( $pointString ) {
        $coordinates = explode( ' ', $pointString );
        return array(
            'x' => $coordinates[0],
            'y' => $coordinates[1],
        );
    }

    private function isPointOnBoundary( $point, $vertex1, $vertex2 ) {
        // ... (same logic as before)
    }

    private function doesIntersect( $point, $vertex1, $vertex2 ) {
        // ... (same logic as before)
    }
}
