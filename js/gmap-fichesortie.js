/* débug utf8 */

// la var globale latMap et lonMap doit être définies in-page

// $().ready(function() {
$(window).load(function(){

	// //////////////////
	// GOOGLE MAP
	var marker;
	var map=false;
	
	// déclarer fonction lancement
	function gmap_initialize(){
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
				scrollwheel: false,
				center: new google.maps.LatLng(latMap, lonMap),
				zoom: 14,
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
			map = new google.maps.Map(document.getElementById("gmap-fichesortie"), myOptions);
			
			// ajout du marker
			var marker = new google.maps.Marker({
				position: new google.maps.LatLng(latMap, lonMap),
				map: map,
				title:"Point de rdv : "+rdvMap
			});
			
			// ajout de la popup
			var infowindow = new google.maps.InfoWindow();
			infowindow.setContent('<p style="width:200px">Point de rdv : '+rdvMap+'<br /><br /><a href="https://maps.google.fr/maps?q='+latMap+','+lonMap+'&num=1&t=m&z=17" title="" target="_blank">&gt; Ouvrir dans Google Maps</a></p>');
			google.maps.event.addListener(marker, 'click', function() {
				infowindow.open(map, marker);
			});
			
		}
		// 
		return true
	}
	
	// /////////
	// AU LANCMENET
	gmap_initialize();
	
});
