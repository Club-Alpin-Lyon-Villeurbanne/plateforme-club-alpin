/* débug utf8 */

// appel en AJAX la page CHRON à chaque visite sur le site, au pageLoad
$(window).load(function(){
	
	function callChron(){
		$.ajax({
			async: true,
			url: "chron.php",
			success: function(jsonMsg){
				// if(typeof(console)!="undefined") console.log('Chron appelé en Ajax');
			},
			error : function(msg){
				if(typeof(console)!="undefined") console.log('Erreur appel chron ajax');
			}
		});
		
		// programme l'appel successifs des chron en laissant siplement la fenêtre ouverte
		setTimeout(callChron, 1000*60*60); // chaque heure
	}
	
	// Premier appel on page load
	callChron();
	
});