/* débug utf8 */

// //////////////////
// GOOGLE MAP
var geocoder;
var marker;
var map=false;

// déclarer fonction lancement
function gmap_initialize(elt){
    if (elt == undefined) elt = "gmap-creersortie";
	// map pas encore associée à l'API
	if(!map){
		// Styles graphiques  (http://gmaps-samples-v3.googlecode.com/svn/trunk/styledmaps/wizard/index.html)
		var myStyle =[
		  {
			"stylers": [
			  { "hue": "#0077ff" },
			  { "saturation": -41 },
			  { "gamma": 0.74 }
			]
		  },{
			"featureType": "poi",
			"elementType": "geometry",
			"stylers": [
			  { "hue": "#00ffdd" },
			  { "saturation": -40 },
			  { "lightness": 43 }
			]
		  }
		];
		// création des options par défaut
		var myOptions = {
			center: new google.maps.LatLng(45.369514,6.564581),
			zoom: 8,
			// mapTypeId: google.maps.MapTypeId.TERRAIN, // SHIT ! pas de zoom assez précis
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			// mapTypeId: google.maps.MapTypeId.SATELLITE,
			// mapTypeId: google.maps.MapTypeId.HYBRID,
			styles: myStyle,

			zoomControl: true,
			zoomControlOptions:{
				position: google.maps.ControlPosition.LEFT_CENTER
			},
			mapTypeControl: true,
			mapTypeControlOptions:{
				position: google.maps.ControlPosition.RIGHT_BOTTOM
			},

			panControl: false,
			scaleControl: false,
			streetViewControl: false,
			overviewMapControl: false
		};

		// ancrage à la div
		map = new google.maps.Map(document.getElementById(elt), myOptions);

		// create geocoder
		geocoder = new google.maps.Geocoder();
	}
	//
	return true
}

// /////////
// FONCTIONS USER INTERFACE

// RECHERCHE DE LIEU
function codeAddress(address) {

	// par defaut: adresse = donnée dans le champ. Sinon, coordonnées au chargement de la page
	if(typeof(address)=="undefined") {
		if ($('input[name=rdv_evt]').length > 0) {
			var address=$('input[name=rdv_evt]').val();
		} else {
			var address=$('input#lieu').val();
		}
	}
	// console.log(address);

	// nettoyage des markers existants (simplement défini marker au niveau racine)
	if(typeof(marker)=="object") marker.setMap(null);

	// place geocoder
	geocoder.geocode( { 'address': address}, function(results, status){
		if(status == google.maps.GeocoderStatus.OK){
			$('#place_finder_error').fadeOut();
			map.setCenter(results[0].geometry.location);
			map.setZoom(16);
			marker = new google.maps.Marker({
				map: map,
				draggable: true,
				position: results[0].geometry.location
			});
			updatePosition(marker);
			google.maps.event.addListener(marker, 'dragend', function() { updatePosition(marker); } );
			return true;
		}
		else{
			$('#place_finder_error').html("Désolé, Google n'arrive pas à trouver cet endroit. Vous pouvez entrer un nom de ville, et déplacer le marqueur plus précisément.").fadeIn();
		}
	});
	return false;
}

// RECUPERE LA POSITION DU MARKER DONNÉ EN PARAMETRE // gère le passage à la suite du formaulaire, aussi
function updatePosition(marker){
	// $('input[name=lat_ramassage]').val(marker.position.hb);
	// $('input[name=long_ramassage]').val(marker.position.ib);
	if ($('input[name=lat_evt]').length > 0) {
		$('input[name=lat_evt]').val(marker.getPosition().lat());
		$('input[name=long_evt]').val(marker.getPosition().lng());
	} else {
		$('input#lieuLat').val(marker.getPosition().lat());
		$('input#lieuLng').val(marker.getPosition().lng());
	}
}

// /////////
// LAUNCHES
if(typeof(autoLoadMap)!="undefined"){
    if(autoLoadMap.length){
        if(gmap_initialize()){
            var tmpLatLong = new google.maps.LatLng(autoLoadMap[0],autoLoadMap[1]);
            map.setCenter(tmpLatLong);
            map.setZoom(16);
            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                position: tmpLatLong
            });
            // updatePosition(marker);
            google.maps.event.addListener(marker, 'dragend', function() { updatePosition(marker); } );
        }
        // else console.log('impossible de démarrer la carte');
    }
    // else console.log('nolength');
    // console.log(autoLoadMap);
}

function initialiserGmap() {
	if(gmap_initialize()){
		// clic sur le bouton pour placer la marker par rapport à l'adresse donnée
		$('input[name=codeAddress]').click(function(){
			if ($('input[name=rdv_evt]').length > 0)
				codeAddress($('input[name=rdv_evt]').val());
			else
				codeAddress($('input#lieu').val());
		});
		// si les input lat et long contiennent une info, on place le marker dessus
		if ($('input[name=lat_evt]').length > 0) {
			if($('input[name=lat_evt]').val() && $('input[name=long_evt]').val()){
				codeAddress($('input[name=lat_evt]').val()+' '+$('input[name=long_evt]').val());
			}
		} else {
			if($('input#lieuLat').val() && $('input#lieuLng').val()){
				codeAddress($('input#lieuLat').val()+' '+$('input#lieuLng').val());

			}
		}
	}

}


function renderMultipleMarkers(marks, infos) {

    var bounds = new google.maps.LatLngBounds();

    // Display multiple markers on a map
    var infoWindow = new google.maps.InfoWindow(), marker, i;

    var all_markers = [];
    var min = .999999;
    var max = 1.000001;

    // Loop through our array of markers & place each one on the map
    for( i = 0; i < marks.length; i++ ) {

        var offsetLat = marks[i][1] * (i/5 * (max - min) + min);
        var offsetLng = marks[i][2] * (i * (max - min) + min);

        var position = new google.maps.LatLng(offsetLat, offsetLng);
        if (marks[i][3] == 'depose') image = 'img/start.png';
        else if (marks[i][3] == 'reprise') image = 'img/finish.png';
        else  image = 'img/base/user_star.png';
        bounds.extend(position);
        marker = new google.maps.Marker({
            position: position,
            map: map,
            title: marks[i][0],
            icon: image
        });
        all_markers.push(marker);

        // marker.setAnimation(google.maps.Animation.BOUNCE);

        google.maps.event.addListener(marker, 'click', (function(marker, i) {
            return function() {
                infoWindow.setContent(infos[i][0]+'<br /><br /><a href="https://maps.google.fr/maps?q='+marks[i][1]+','+marks[i][2]+'&num=1&t=m&z=17" title="" target="_blank">&gt; Ouvrir dans Google Maps</a>');
                infoWindow.open(map, marker);
            }
        })(marker, i));

        map.fitBounds(bounds);
    }

    // var markerCluster = new MarkerClusterer(map, all_markers);
    // markerCluster.onClick = function() { return multiChoice(markerCluster); }

}



//////////////////////////   ///////////////////////////////////////   //////////////////////////
//////////////////////////   VERSION MULTI ELEMENTS SUR LA MEME PAGE   //////////////////////////
//////////////////////////   ///////        ///////////       //////   //////////////////////////
//////////////////////////   ///////////       /////       /////////   //////////////////////////
//////////////////////////   ///////////////     /      ////////////   //////////////////////////
//////////////////////////   //////////////////      ///////////////   //////////////////////////
//////////////////////////   //////////////////// //////////////////   //////////////////////////
// PLUSIEURS CARTES

var geocoders = {};
var markers = {};
var maps = {};

// Styles graphiques  (http://gmaps-samples-v3.googlecode.com/svn/trunk/styledmaps/wizard/index.html)
var myStyle =[
    {
        "stylers": [
            { "hue": "#0077ff" },
            { "saturation": -41 },
            { "gamma": 0.74 }
        ]
    },{
        "featureType": "poi",
        "elementType": "geometry",
        "stylers": [
            { "hue": "#00ffdd" },
            { "saturation": -40 },
            { "lightness": 43 }
        ]
    }
];
// création des options par défaut
var myOptions = {
    center: new google.maps.LatLng(45.369514,6.564581),
    zoom: 8,
    // mapTypeId: google.maps.MapTypeId.TERRAIN, // SHIT ! pas de zoom assez précis
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    // mapTypeId: google.maps.MapTypeId.SATELLITE,
    // mapTypeId: google.maps.MapTypeId.HYBRID,
    styles: myStyle,

    zoomControl: true,
    zoomControlOptions:{
        position: google.maps.ControlPosition.LEFT_CENTER
    },
    mapTypeControl: true,
    mapTypeControlOptions:{
        position: google.maps.ControlPosition.RIGHT_BOTTOM
    },

    panControl: false,
    scaleControl: false,
    streetViewControl: false,
    overviewMapControl: false
};


// LANCEMENT
function gmap_initializes(elt){
    var id = elt.attr('id');
    if(!maps[id]){
        maps[id] = new google.maps.Map(document.getElementById("gmap-creersortie-"+id), myOptions);
        geocoders[id] = new google.maps.Geocoder();

    }
    return true;
}

// RECHERCHE DE LIEU
function codeAddresses(elt, address) {

    var id = elt.attr('id');

    // par defaut: adresse = donnée dans le champ. Sinon, coordonnées au chargement de la page
    if(typeof(address)=="undefined") {
         var address=$('input#lieu-'+id).val();
    }

    // nettoyage des markers existants (simplement défini marker au niveau racine)
    if(typeof(markers[id])=="object") markers[id].setMap(null);

    // place geocoders
    geocoders[id].geocode( { 'address': address}, function(results, status){
        if(status == google.maps.GeocoderStatus.OK){
            $('.place_finder_error').html('').fadeOut();
            maps[id].setCenter(results[0].geometry.location);
            maps[id].setZoom(16);
            markers[id] = new google.maps.Marker({
                map: maps[id],
                draggable: true,
                position: results[0].geometry.location
            });
            updatePositions(elt, markers[id]);
            google.maps.event.addListener(markers[id], 'dragend', function() { updatePositions(elt, markers[id]); } );
            return true;
        }
        else{
            elt.find($('.place_finder_error')).html("Désolé, Google n'arrive pas à trouver cet endroit. Vous pouvez entrer un nom de ville, et déplacer le marqueur plus précisément.").fadeIn();
        }
    });
    return false;
}

// RECUPERE LA POSITION DU MARKER DONNÉ EN PARAMETRE // gère le passage à la suite du formaulaire, aussi
function updatePositions(elt, marker){

    var id = elt.attr('id');

    elt.find($('input.lieuLat')).val(marker.getPosition().lat());
    elt.find($('input.lieuLng')).val(marker.getPosition().lng());

}

function initialiserBloc(elt) {

    var id = elt.attr('id');

    geocoders[id];
    markers[id];
    maps[id] = false;

    if (gmap_initializes(elt)) {
        // clic sur le bouton pour placer la marker par rapport à l'adresse donnée
        elt.find($('input[name=codeAddress-'+id+']')).click(function(){
            codeAddresses(elt, $('input#lieu-'+id).val());
        });
        var lat = elt.find($('input.lieuLat')).val();
        var lng = elt.find($('input.lieuLng')).val();
        if(lat && lng){
            codeAddresses(elt, lat+' '+lng);
        }
    }
}



$(document).ready(function(){

	if ($('#gmap-creersortie').length > 0) initialiserGmap();
	else if ($('.gmap-creersortie').length > 0) {
        $('.gmap-creersortie').each(function(){
            initialiserBloc($(this).parents('.lieu_map'));
        });
    }
});
