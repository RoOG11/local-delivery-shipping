// JavaScript Document
"use strict";

var map;
var geocoder = new google.maps.Geocoder();
var marker;
var laturl;
var lngurl;
var markersArray = [];
var polygon_zoom = php_vars[0].zoom;
var polygon_center = php_vars[0].center;
var polygons = php_vars;
var geo_help = php_vars[0].geo;

function initialize() {
	var centerOfMap = new google.maps.LatLng(polygon_center[0], polygon_center[1]);
	var mapOptions = {
		center: centerOfMap,
		streetViewControl: false,
		scrollwheel: false,
		zoom: parseInt(polygon_zoom, 10)
	};
	map = new google.maps.Map(document.getElementById('delivery-map-canvas'), mapOptions);

	for (var i = 0; i < polygons.length; i++) {
		// Construct the polygon.
		var deliverymap = new google.maps.Polygon({
			paths: polygons[i].coords,
			strokeColor: polygons[i].color,
			strokeOpacity: 0.8,
			strokeWeight: 2,
			fillColor: '#000000',
			fillOpacity: 0
		});
		deliverymap.setMap(map);
	}
	// get saved lat long if any and make marker
	var latsaved = document.getElementById("billing_gps_lat").value;
	var longsaved = document.getElementById("billing_gps_long").value;
	if (latsaved && longsaved) {
		var options = {
			map: map,
			draggable: true,
			position: new google.maps.LatLng(latsaved, longsaved),
		};

		var marker = new google.maps.Marker(options);
		coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());
		markersArray.push(marker);
		map.setCenter(options.position);

		//gets the new latlong if the marker is dragged		  
		google.maps.event.addListener(marker, "drag", function () {
			document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
			document.getElementById("billing_gps_long").value = marker.getPosition().lng();
		});

		google.maps.event.addListener(marker, 'dragend', function () {
			coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());
		});
	}
}

//if it all fails
function handleNoGeolocation(errorFlag) {
	if (errorFlag) {
		var content = 'Error: The Geolocation service failed.';
	} else {
		var content = 'Error: Your browser doesn\'t support geolocation.';
	}

	var options = {
		map: map,
		position: new google.maps.LatLng(polygon_center[0], polygon_center[1]),
	};

	var marker = new google.maps.Marker(options);
	coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());

	markersArray.push(marker);
	map.setCenter(options.position);

	//gets the pre-drag latlong coordinate
	document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
	document.getElementById("billing_gps_long").value = marker.getPosition().lng();

	//gets the new latlong if the marker is dragged		  
	google.maps.event.addListener(marker, "drag", function () {
		document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
		document.getElementById("billing_gps_long").value = marker.getPosition().lng();
	});

	google.maps.event.addListener(marker, 'dragend', function () {
		coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());
	});
}

function clearOverlays() {
	for (var i = 0; i < markersArray.length; i++) {
		markersArray[i].setMap(null);
	}
	markersArray.length = 0;
}

function codeAddress(shipping) {
	if (shipping) {
		var address = document.getElementById("shipping_address_1").value + ' ' + geo_help;
	} else {
		var address = document.getElementById("billing_address_1").value + ' ' + geo_help;
	}

	clearOverlays();
	geocoder.geocode({ 'address': address }, function (results, status) {
		if (status == 'OK') {
			map.setCenter(results[0].geometry.location);
			var marker = new google.maps.Marker({
				map: map,
				draggable: true,
				position: results[0].geometry.location
			});
			document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
			document.getElementById("billing_gps_long").value = marker.getPosition().lng();
			var laturl = marker.getPosition().lat();
			var lngurl = marker.getPosition().lng();
			coords_changes_callback(laturl, lngurl);
			markersArray.push(marker);

			//gets the new latlong if the marker is dragged		  
			google.maps.event.addListener(marker, "drag", function () {
				document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
				document.getElementById("billing_gps_long").value = marker.getPosition().lng();
			});

			google.maps.event.addListener(marker, 'dragend', function () {
				coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());
			});
		}
	});
}

google.maps.event.addDomListener(window, 'load', initialize);

jQuery(function ($) {
	$("input[name=billing_address_1]").change(function(event) {
		codeAddress(false);
	});
	$("input[name=shipping_address_1]").change(function(event) {
		codeAddress(true);
	});
	$('a.locateme').click(function (e) {
		e.preventDefault();

		// Try HTML5 geolocation to get location
		if (navigator.geolocation) {

			// remove other markers
			clearOverlays();

			navigator.geolocation.getCurrentPosition(function (position) {
				var pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
				var marker = new google.maps.Marker({
					map: map,
					position: pos,
					draggable: true
				});
				coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());
				markersArray.push(marker);
				map.setCenter(pos);

				//gets the pre-drag latlong coordinate
				document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
				document.getElementById("billing_gps_long").value = marker.getPosition().lng();

				//gets the new latlong if the marker is dragged		  
				google.maps.event.addListener(marker, "drag", function () {
					document.getElementById("billing_gps_lat").value = marker.getPosition().lat();
					document.getElementById("billing_gps_long").value = marker.getPosition().lng();
				});

				google.maps.event.addListener(marker, 'dragend', function () {
					coords_changes_callback(marker.getPosition().lat(), marker.getPosition().lng());
				});

			}, function () {
				handleNoGeolocation(true);
			});
		} else {
			// Browser doesn't support Geolocation
			handleNoGeolocation(false);
		}

		return false;
	});

});

function coords_changes_callback(lat, long) {

	jQuery.ajax({
		url: lds_ajax.url,
		type: 'POST',
		data: {
			action: 'check_address_delivery',
			security: lds_ajax.security,
			lat: lat,
			long: long
		},
		success: function () { jQuery('body').trigger('update_checkout'); }
	});
}
