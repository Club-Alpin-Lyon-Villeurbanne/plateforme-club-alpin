$().ready(function() {
	// //////////////////
	var geocode;
	var marker=false;
	var map=false;
	
	// déclarer fonction lancement
	function map_initialize() {
		// map pas encore associée à l'API
		if(!map){

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

			var lat            = 45.76476483029371;
			var lon            = 4.879565284189081;
			var zoom           = 16;

			map = L.map('map-creersortie').setView([lat, lon], zoom);
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png?{foo}', 
				{foo: 'bar', attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'}).addTo(map);
		}
		// 
		return true
	}
	
	// /////////
	// FONCTIONS USER INTERFACE
	
	// RECHERCHE DE LIEU
	function codeAddress(address) {
		
		// par defaut: adresse = donnée dans le champ. Sinon, coordonnées au chargement de la page
		if(typeof(address)=="undefined")
			var address=$('input[id=event_rdv]').val();

		geocode = 'https://nominatim.openstreetmap.org/search?format=json&q=' + address;
		$.getJSON(geocode, function(data) {
		  // get lat + lon from first match
			var lat = data[0].lat;
			var lon = data[0].lon;
			var zoom = 16;

			map.setView([lat, lon], zoom);

			if(!marker) {
			    marker = L.marker([lat, lon], 
						{
						    draggable: 'true'
						});
			    map.addLayer(marker);
			} else {
			    marker.setLatLng([lat,lon]);
			}
			updatePosition(marker._latlng);
			marker.on('dragend', function() { updatePosition(marker._latlng, true); });
		});
	}
	
	// RECUPERE LA POSITION DU MARKER DONNÉ EN PARAMETRE // gère le passage à la suite du formulaire, aussi
	function updatePosition(position, reset = false) {
		$('input[id=event_lat]').val(position.lat);
		$('input[id=event_long]').val(position.lng);
		if (reset) {
			$('input[id=event_rdv]').val('');
		}
	}
	
	// /////////
	// LAUNCHES
	autoLoadMap=[45.76476483029371, 4.879565284189081];
	if(typeof(autoLoadMap)!="undefined") {
		if(autoLoadMap.length){
			if(map_initialize()) {
				var lat            = autoLoadMap[0];
				var lon            = autoLoadMap[1];
				var zoom           = 16;

				map.setView([lat, lon], zoom);

				if(!marker) {
				    marker = L.marker([lat, lon],
							{
							    draggable: 'true'
							});
				    marker.on('dragend', function() { 
					var position = marker.getLatLng();
					updatePosition(position); 
					
				    });
				    map.addLayer(marker);
				} else {
				    marker.setLatLng([lat,lon]);
				}
			}
		}
	}
	
	// /////////
	// AU LANCEMENT
	if(map_initialize()) {
		// clic sur le bouton pour placer la marker par rapport à l'adresse donnée
		$('#codeAddress').click(function() {
			codeAddress($('input[id=event_rdv]').val());	
		});
		// si les input lat et long contiennent une info, on place le marker dessus
		if($('input[id=event_lat]').val() && $('input[id=event_long]').val()) {
			codeAddress($('input[id=event_lat]').val()+' '+$('input[id=event_long]').val());
		} else {
			// sinon on reset la carte
			map.removeLayer(marker);
			marker = false;
		}
	}

	// champ texte modifié => on reset la carte
	$('input[id=event_rdv]').on('change', function() {
		map.removeLayer(marker);
		marker = false;
		$('input[id=event_lat]').val('');
		$('input[id=event_long]').val('');
	});
	
});

