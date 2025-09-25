// page d'accueil
$().ready(function() {
	
	// ***********
	// *** SLIDER
	var sliderIndex=0;
	function slideTo(index){
		if(typeof(index)=="undefined") sliderIndex++; // pas d�fini, incrementation
		else if(index=="plus"){  sliderIndex++; clearInterval(sliderInterval); } // incrementation (bouton vers la droite, ou molette)
		else if(index=="moins"){ sliderIndex--; clearInterval(sliderInterval); } // decrementation (bouton vers la gauche, ou molette)
		else{					 sliderIndex=parseInt(index); clearInterval(sliderInterval); } // index donn� (navigation)
		
		var sliderSpeed=700;
		var sliderEase='easeInOutQuad';
		
		// debug
		if(sliderIndex>=$('#home-slider-nav a').length) sliderIndex=0; // retour tt a gauche
		if(sliderIndex<0) sliderIndex=$('#home-slider-nav a').length-1; // tt a droite
		// class work
		$('#home-slider-nav a').eq(sliderIndex).addClass('up').siblings().removeClass('up');
		// mouvement
		ww=$('#home-slider').innerWidth();
		$('#home-slider-wrapper').animate({'right':parseInt(sliderIndex*ww) +'px'}, {queue:false, duration:sliderSpeed, easing:sliderEase});
	}
	// set bazar
	$('.home-slider-nav:first').addClass('up');
	// bind to links
	$('#home-slider-nav a').bind('click' ,function(){
		slideTo($(this).index()-1); // on donne l'index -1 a cause de l'image qui precede les liens et compte dans le compteur
	});
	// bind to arrows
	$('#home-slider-nav2 .arrow-left').bind('click' ,function(){
		slideTo('moins');
	});
	$('#home-slider-nav2 .arrow-right').bind('click' ,function(){
		slideTo('plus');
	});
	// autostart
	var sliderInterval=setInterval(function(){slideTo()},7000);
});
