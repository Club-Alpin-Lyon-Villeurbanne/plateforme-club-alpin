$().ready(function() {
	
	// onglets admin (editElt.php...)
	$('.onglets-admin').each(function(){
		var iLinks=$(this).find('.onglets-admin-nav a').length;
		var iItems=$(this).find('.onglets-admin-item').length;
		if(iLinks != iItems) alert("Attention : nombre d'onglets différents du nombre de contenus");
		
		var iCurrent=$(this).find('.onglets-admin-nav a.up:first').index();
		if(iCurrent<0) iCurrent=0;
		
		$(this).find('.onglets-admin-nav a:eq('+iCurrent+')').addClass('up').siblings().removeClass('up');
		$(this).find('.onglets-admin-contenu .onglets-admin-item:eq('+iCurrent+')').show().siblings().hide();
		
		// interactions :
		$(this).find('.onglets-admin-nav a').click(function(){
			var i=$(this).index();
			$(this).addClass('up').siblings('a').removeClass('up');
			$(this).parents('.onglets-admin:first').find('.onglets-admin-item:eq('+i+')').show().siblings().hide();
			
			// pour le tiroir, resize iframes
			if(typeof(actu_iframe)=='function')	actu_iframe();
		});
		
	});
	
});
