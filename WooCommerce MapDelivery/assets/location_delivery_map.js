// Delivery Location Map Script
"use strict";

let deliveryMap;
const locationGeocoder = new google.maps.Geocoder();
let locationMarker;
const mapSettings = php_config[0];
const deliveryZones = php_config;
let helperGeo = mapSettings.geo;

function setupMap() {
    const mapCenter = new google.maps.LatLng(mapSettings.center[0], mapSettings.center[1]);
    const mapConfig = {
        center: mapCenter,
        streetViewControl: false,
        scrollwheel: false,
        zoom: parseInt(mapSettings.zoom, 10)
    };
    deliveryMap = new google.maps.Map(document.getElementById('delivery-location-canvas'), mapConfig);

    deliveryZones.forEach(zone => {
        const zoneArea = new google.maps.Polygon({
            paths: zone.coords,
            strokeColor: zone.color,
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#000000',
            fillOpacity: 0
        });
        zoneArea.setMap(deliveryMap);
    });

    const savedLat = document.getElementById("billing_gps_lat").value;
    const savedLong = document.getElementById("billing_gps_long").value;
    if (savedLat && savedLong) {
        createMarker(savedLat, savedLong, true);
    }
}

function handleLocationError(isError) {
    const errorMessage = isError ? 'Error: Geolocation service failed.' : 'Error: Browser doesn\'t support geolocation.';
    const defaultPosition = new google.maps.LatLng(mapSettings.center[0], mapSettings.center[1]);
    createMarker(defaultPosition.lat(), defaultPosition.lng(), false, errorMessage);
}

function removeAllMarkers() {
    markersArray.forEach(marker => marker.setMap(null));
    markersArray = [];
}

function findAddress(isShipping) {
    const addressElement = isShipping ? "shipping_address_1" : "billing_address_1";
    const fullAddress = document.getElementById(addressElement).value + ' ' + helperGeo;

    removeAllMarkers();
    locationGeocoder.geocode({ 'address': fullAddress }, (results, status) => {
        if (status === 'OK') {
            createMarker(results[0].geometry.location.lat(), results[0].geometry.location.lng(), true);
        }
    });
}

google.maps.event.addDomListener(window, 'load', setupMap);

jQuery(document).ready($ => {
    $("input[name=billing_address_1]").on('change', () => findAddress(false));
    $("input[name=shipping_address_1]").on('change', () => findAddress(true));

    $('a.locateUser').on('click', e => {
        e.preventDefault();

        if (navigator.geolocation) {
            removeAllMarkers();

            navigator.geolocation.getCurrentPosition(position => {
                createMarker(position.coords.latitude, position.coords.longitude, true);
            }, () => handleLocationError(true));
        } else {
            handleLocationError(false);
        }
    });
});

function createMarker(lat, long, isDraggable, message = '') {
    const markerOptions = {
        map: deliveryMap,
        draggable: isDraggable,
        position: new google.maps.LatLng(lat, long),
    };

    const newMarker = new google.maps.Marker(markerOptions);
    updateCoordinates(newMarker.getPosition().lat(), newMarker.getPosition().lng());
    markersArray.push(newMarker);
    deliveryMap.setCenter(markerOptions.position);

    google.maps.event.addListener(newMarker, "drag", () => {
        document.getElementById("billing_gps_lat").value = newMarker.getPosition().lat();
        document.getElementById("billing_gps_long").value = newMarker.getPosition().lng();
    });

    google.maps.event.addListener(newMarker, 'dragend', () => {
        updateCoordinates(newMarker.getPosition().lat(), newMarker.getPosition().lng());
    });
}

function updateCoordinates(lat, long) {
    jQuery.ajax({
        url: delivery_ajax.url,
        type: 'POST',
        data: {
            action: 'validate_delivery_address',
            security: delivery_ajax.security,
            lat: lat,
            long: long
        },
        success: () => { jQuery('body').trigger('refresh_checkout'); }
    });
}
