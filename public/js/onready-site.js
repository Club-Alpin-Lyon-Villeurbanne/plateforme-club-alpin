$().ready(function() {
	
				
	// ***********
	// *** effet de navigation entre les pages
	
	var pageSelectIndex = 0;
	$('.pageSelect').each(function(){
		var stance = 400;
		var fullstance = 0;
		var go = 0;
		var bloc = $(this);
		var right = bloc.find('.navleftright .right');
		var left = bloc.find('.navleftright .left');
		
		// bind nav
		if(right.length){
			right.bind('click', function(){
				// distance totale des liens 
				fullstance = bloc.find('.pageSelectIn a:last').offset().left - bloc.find('.pageSelectIn a:first').offset().left;
				if((pageSelectIndex +1) *stance < fullstance){
					pageSelectIndex++;
					go = pageSelectIndex * stance;
					bloc.find('.pageSelectIn .pageSelectInWrapper').animate({'right': go +'px'}, {queue:false, duration:950, easing:'easeOutQuad'});
				}
			});
		}
		if(left.length){
			left.bind('click', function(){
				pageSelectIndex--;
				go = pageSelectIndex * stance;
				if(go<0) go=0;
				if(pageSelectIndex<0) pageSelectIndex=0;
				bloc.find('.pageSelectIn .pageSelectInWrapper').animate({'right': go +'px'}, {queue:false, duration:950, easing:'easeOutQuad'});
				
			});
		}
		
		// focaliser au bon endroit au chargement de la page
		var upLink = bloc.find('a.up:first');
		if(upLink.index() >10){
			while(upLink.offset().left > bloc.offset().left + 500){
				pageSelectIndex++;
				go = pageSelectIndex * stance;
				bloc.find('.pageSelectIn .pageSelectInWrapper').css({'right': go +'px'});
			}
		}
		
	});

	// ***********
	// *** belles checkboxes
	// pre set
	$('.check-nice input[type=checkbox]').each(function(){
		if($(this).is(':checked'))	$(this).parents('label').addClass('up').removeClass('down');
		else						$(this).parents('label').addClass('down').removeClass('up');
	});
	// bind
	$('.check-nice input[type=checkbox]').bind('click change', function(){
		var checkbox = $(this);
		if(checkbox.is(':checked'))	checkbox.parents('label').addClass('up').removeClass('down');
		else						checkbox.parents('label').addClass('down').removeClass('up');
	});

	// ***********
	// *** belles radioboxes
	// pre set
	$('.radio-nice input[type=radio]').each(function(){
		if($(this).is(':checked'))	$(this).parents('label').addClass('up').removeClass('down');
		else						$(this).parents('label').addClass('down').removeClass('up');
	});
	// bind
	$('.radio-nice input[type=radio]').bind('click change', function(){
		var radio = $(this);
		if(radio.is(':checked'))	{
			radio.parents('.radio-nice').find('label').addClass('down').removeClass('up');
			radio.parents('label').addClass('up').removeClass('down');
		}
	});
	
	// ***********
	// *** Titres h2 switcher
	$('h2.trigger-h2').bind('click', function(){
		$(this)	.toggleClass('off')
				.nextAll('.trigger-me').first().slideToggle({duration:150, queue:false});
		
	});
    $('h2.trigger-h2.off').nextAll('.trigger-me').first().slideToggle({duration:150, queue:false});
	
	// ***********
	// *** ROLLOVER sur events agenda
	$('#evt-list a').bind('mouseenter focus', function(){
		$(this).animate({'padding-left':'8px'}, {duration:100, queue:false, easing:'easeOutQuad'});
	});
	$('#evt-list a').bind('mouseleave blur', function(){
		$(this).animate({'padding-left':'0px'}, {duration:100, queue:false, easing:'easeOutQuad'});
	});
	
	// ***********
	// *** EFFETS DE FADEIN FADEOUT SUR ELEMENTS DE CLASSSE FADER
	$('.fader').bind('mouseenter focus', function(){
		$(this).animate({'opacity':'1'}, {queue:false, duration:250, easing:'easeOutQuad'});
	});
	$('.fader').bind('mouseleave blur', function(){
		$(this).animate({'opacity':'0.9'}, {queue:false, duration:250, easing:'easeOutQuad'});
	});
	
	// NAVIGATION DANS LE MENU PRINCIPAL
	// setting au debut
	$('#toolbar-commission-hidden, #toolbar-navigation-hidden, #toolbar-user-hidden').css({'display':'none', 'opacity':'0'});
	
	// fonction afficher/masquer du mennu principal
	function switchMenu(id){
		var ids=new Array('toolbar-commission', 'toolbar-navigation', 'toolbar-user');
		var colors=new Array('#50b7e4', '#f7931e', '#8cc63f');
		var oneUp=false; // afficher ou non le fond noir
		var fadeDuration=300; // vitesse du fondu
		var src;
		
		if(typeof(id)=="undefined") id='off';
		
		// pour chaque ID de la liste
		for(i=0; i<ids.length; i++){
			// s'il correspond à un élément hidden
			if(id+'-hidden'==ids[i]+'-hidden'){
				oneUp=true; // l'un des id donné correspond, le fond noir doit etre affiché
				// animation couleur de fond du lien
				$('#'+ids[i]).animate({'backgroundColor':colors[i]}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
				// animation du cache d'ombre
				$('#'+ids[i]+' .shadowcache').css('height','10px');
				// animation couleur du texte du lien
				$('#'+ids[i]+' b').animate({'color':'#fff'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
				// affichage du sous menu
				$('#'+ids[i]+'-hidden').css({'opacity':'1', 'display':'block'});
				// masquage de la fleche 
				$('#top-openers .opener:eq('+i+')').css({'opacity':'0'});
			}
			// sinon : effacement de cet élément
			else{
				// couleur de fond du lien
				$('#'+ids[i]).animate({'backgroundColor':'#fff'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
				// animation du cache d'ombre
				// $('#'+ids[i]+' .shadowcache').animate({'height':'0px'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
				$('#'+ids[i]+' .shadowcache').css('height','0px');
				// couleur du lien
				$('#'+ids[i]+' b').animate({'color':'#000'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
				// masquage sous menu
				$('#'+ids[i]+'-hidden').css({'opacity':'0', 'display':'none'});
				// reaffichage de la fleche
				$('#top-openers .opener:eq('+i+')').css({'opacity':'1'});
			}
		}
		
		// si un element est affiché
		if(oneUp){
			// fond noir
			$('#top-hider')
				.css('height', $('#siteHeight').outerHeight()+'px') // couvrir tte la hauteur de la page
				.fadeIn({duration:fadeDuration, easing:'easeOutQuint'});
			// atténuation des autres liens
			$('#'+id)
				.addClass('up')
				.animate({'opacity':'1'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'})
			.siblings('.toptrigger')
				.removeClass('up')
				.animate({'opacity':'0.4'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
		}
		else{
			// fd noir
			$('#top-hider').fadeOut({duration:fadeDuration, easing:'easeOutQuint'});
			// pas d'atténuation
			$('.toptrigger').removeClass('up').animate({'opacity':'1'}, {queue:false, duration:fadeDuration, easing:'easeOutQuint'});
		}
	}
	
	// Affichage des blocs cachés
	$('#toolbar-commission, #toolbar-navigation, #toolbar-user').bind('focus mouseenter click', function(){
		switchMenu($(this).attr('id'));
	});
	// Masquage de l'ensemble lors du survol de certains éléments
	// $('#logo, #top-hider').bind('focus mouseenter', function(){
	$('#top-hider').bind('focus mouseenter click', function(){
		switchMenu('off');
	});
	
	
	// STYLES DES CHECKBOXES SELECTIONNES
	function updatePickables(input, nocall){
		if(typeof(nocall)=="undefined") var nocall=false;
		
		if(input.is(':checked'))
			input.parent().addClass('up');
		else
			input.parent().removeClass('up');
			
		
		// dans le cas d'un input radio, tous ses cousins doivent etre influencés
		if(!nocall && input.is('[type=radio]'))	$('input[name='+input.attr('name')+']').not(input).each(function(){
			updatePickables($(this), true);
		});
	}
	$('.nice-checkboxes input').each(function(){
		updatePickables($(this));
		$(this).bind('change', function(){
			updatePickables($(this));
		});
	});
	
	// FORMULAIRES EN AJAX
	$(document).on('submit', '.ajaxform', function(){
		var form=$(this);
		form.find('.erreur').fadeOut();
		
		if(!form.is('.running')){
			// vars
			var datas='';
			form.find('input, select, textarea').each(function(){
				if($(this).val() && $(this).attr('name')) {
					datas += (datas?'&':'')+$(this).attr('name')+'='+$(this).val();
				}
			});

			// call
			$.ajax({
				type: "POST",
				dataType : "json",
				url: "/?ajx=operations",
				data: datas,
				beforeSend: function(jsonMsg){ 
					form.addClass('running');
				},
				complete: function(jsonMsg){ 
					// console.log(jsonMsg); 
					form.removeClass('running');
				},
				success: function(jsonMsg){
					if(jsonMsg.success){
						var htmlMsg = $('<span/>').html(jsonMsg.successmsg).text();
						$.fancybox('<div class="info" style="text-align:left; max-width:600px; line-height:17px;">'+htmlMsg+'</div>');
					}
					else{
						// interprétation du html pour chaque erreur
						var htmlMsg;
						for(i=0; i<jsonMsg.error.length; i++){
							jsonMsg.error[i] = $('<span/>').html(jsonMsg.error[i]).text();
						}
						// si un bloc est dédié au message d'erreur dans le formulaire, on l'y affiche
						if(form.find('.erreur').length)
							form.find('.erreur').html(jsonMsg.error.join(',<br />')).fadeIn();
						else
							$.fancybox('<div class="erreur" style="text-align:left; max-width:600px; line-height:17px;">'+jsonMsg.error.join(',<br />')+'</div>');
					}
				},
				error: function(err){
					// alert('Ajax error');
					// console.log(err);
					$.fancybox('<div class="erreur" style="text-align:left; max-width:600px; line-height:17px;">Erreur : '+err.responseText+'</div>');
				}
			});
		}
		
		return false;
	});
	
});