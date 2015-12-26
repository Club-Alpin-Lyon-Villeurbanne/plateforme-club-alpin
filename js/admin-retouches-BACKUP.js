var jcrop_api;
var srcImg=false;
var tooBig=false;
var tooBigRatio;
var imgWidth;
var imgHeight;
var maxImgWidth=880;
var maxImgHeight=880;

function majTools(){
	var runningTool = $('.lpie-tools input:checked:first').attr('value');
	
	// JCROP
	if(runningTool=='crop')
		jcrop_api.enable();
	else
		jcrop_api.disable();
	// RESIZE
	if(runningTool=='resize'){
		// affichage de l'outil
		$('#lpie-tool-resize-block').fadeIn();
	}
	else{
		// cacher l'outil
		$('#lpie-tool-resize-block').fadeOut();
	}
	// PREVIEW
	if(runningTool=='preview'){
		// loader
		$('#mybox-loading, .mybox-down').fadeIn(500);
		// creation d'image de démo, meme script que le final, avec var spéciale
		$.ajax({
			url: "retouches-ajax.php",
			processData: false,
			dataType: 'json',
			data: 'preview=true'
				+'&srcImg='+srcImg
				+'&wDest='+$('#lp-image-editor .source .stats2 .wDest').val()
				+'&wDestNocrop='+$('#lp-image-editor .source .stats2 .wDestNocrop').val()
				+'&hDest='+$('#lp-image-editor .source .stats2 .hDest').val()
				+'&hDestNocrop='+$('#lp-image-editor .source .stats2 .hDestNocrop').val()
				+'&xDest='+$('#lp-image-editor .source .stats2 .xDest').val()
				+'&yDest='+$('#lp-image-editor .source .stats2 .yDest').val()
				,
			success: function(responseJSON){
				//create jquery object from the response html
				// if(console.log)	console.log(data);
				if(responseJSON.success){
					if(typeof(newwindow) !="undefined") newwindow.close();
					newwindow=window.open(responseJSON.src,'Aperçu','height='+(parseInt(responseJSON.height)+10)+',width='+(parseInt(responseJSON.width)+30));
					if(window.focus) {
						newwindow.focus();
					}
				}
				else{
					alert('Erreur dans la réponse du serveur');
				}
			},
			complete: function(){
				$('#mybox-loading, .mybox-down').fadeOut(500);
			}
		});
	}
	// SAVE
	if(runningTool=='save'){
		// loader
		$('#mybox-save, .mybox-down').fadeIn(500);
		// vars
		var destImg=false;
		// buttons
		$('#mybox-save input[name=save-cancel]').bind('click', function(){	
			$('#mybox-save, .mybox-down').fadeOut(500);
		});
		$('#mybox-save input[name=save-replace], #mybox-save input[name=save-rename]').bind('click', function(){	
			if($(this).attr('name')=='save-replace')
				destImg=$('#mybox-save input[name=save-replace-filename]').val();
			if($(this).attr('name')=='save-rename')
				destImg=$('#mybox-save input[name=save-rename-filename]').val();
			
			/* */
			// creation d'image finale
			$.ajax({
				url: "retouches-ajax.php",
				processData: false,
				dataType: 'json',
				data: ''
					+'&srcImg='+srcImg
					+'&destImg='+destImg
					+'&wDest='+$('#lp-image-editor .source .stats2 .wDest').val()
					+'&wDestNocrop='+$('#lp-image-editor .source .stats2 .wDestNocrop').val()
					+'&hDest='+$('#lp-image-editor .source .stats2 .hDest').val()
					+'&hDestNocrop='+$('#lp-image-editor .source .stats2 .hDestNocrop').val()
					+'&xDest='+$('#lp-image-editor .source .stats2 .xDest').val()
					+'&yDest='+$('#lp-image-editor .source .stats2 .yDest').val()
					,
				success: function(responseJSON){
					if(responseJSON.success){
						// remimse a zero de la fenetre de modif
						window.location='retouches.php?maj=ok';
						// redirection vers l'onglet adapté et reload
						parent.$(".onglets-admin:eq(0) .onglets-admin-nav:eq(0) a:eq(1)").addClass('up').siblings('a').removeClass('up');
						parent.$(".onglets-admin:eq(0) .onglets-admin-item:eq(1)").show().siblings('div').hide();
						parent.document.getElementById('frameTiroir').contentDocument.location.reload(true);
					}
					else{
						alert('Erreur dans la réponse du serveur');
					}
				}
			});
			/* */
		});
	}
}

// met à jour les valeurs utilisées pour le calcul serveur et visibles à l'utilisateur
function statsFinale(data){
	// dimensions effectives, visuelles
	var tmpImgW=$('.lp-image-editor-source').innerWidth();
	var tmpImgH=$('.lp-image-editor-source').innerHeight();
	var tmpImgx=$('#lp-image-editor .source .stats2 .xDest').val();
	var tmpImgy=$('#lp-image-editor .source .stats2 .yDest').val();
	// apply nocrop
	$('#lp-image-editor .source .stats2 .wDestNocrop').val(tmpImgW);
	$('#lp-image-editor .source .stats2 .hDestNocrop').val(tmpImgH);
	// si des infos sont données
	if(typeof(data)!="undefined"){
		// h=hauteur crop			// w=largeur crop			// x=position départ x			// y=position départ y			// x2=position fin x			// y2=position fin y
		if(data['h']>0 && data['w']>0){
			var tmpImgW=data['w'];
			var tmpImgH=data['h'];
			var tmpImgx=data['x'];
			var tmpImgy=data['y'];
		}
		else{
			var tmpImgx=0;
			var tmpImgy=0;
		}
	}
	// apply cropped
	$('#lp-image-editor .source .stats2 .wDest').val(tmpImgW);
	$('#lp-image-editor .source .stats2 .hDest').val(tmpImgH);
	$('#lp-image-editor .source .stats2 .xDest').val(tmpImgx);
	$('#lp-image-editor .source .stats2 .yDest').val(tmpImgy);
}


// jquery
$(document).ready(function(){
	
	srcImg = $('.lp-image-editor-source').attr('src');
	
	// fancybox
	$("a.fancybox").fancybox();
	
	// TOOLS
	$('.lpie-tools input').bind('click', function(){	majTools(); 	});
	// ui
	$(".lpie-tools").buttonset();
	$(".buttonset input").button();
	// Jcrop
	$('.source img').Jcrop({
			onRelease: statsFinale(false), // remet à zéro les stats de crop
			onSelect: statsFinale,
			onChange: statsFinale
		}
		,function(){ jcrop_api = this; jcrop_api.disable(); } // diseabled by default
	);
	// Slider resize
	$("#lpie-tool-resize-select").slider({
		range: "max",
		value: 100,
		min: 1,
		max: 100,
		slide: function( event, ui ) {
			$("#lpie-tool-resize-show").val(ui.value +"%");
			// toutes images avec src ciblé
			tmpWidth=Math.round(imgWidth*(ui.value/100));
			tmpHeight=Math.round(imgHeight*(ui.value/100));
			$('img[src=\''+srcImg+'\'], .jcrop-holder, .jcrop-tracker').css({'width':tmpWidth+'px', 'height':tmpHeight+'px', 'overflow':'hidden'});
			// affichage infos
			statsFinale();
			// dimensions du cadre
			// if(typeof(toframe)=="undefined")
			// var toframe=setTimeout('window.parent.actu_iframe()', 200);
			window.parent.actu_iframe();
		}
	});
	
	// SOURCE IMAGE STATS ON IMAGE LOAD
	var imgVirtuelle=new Image();
	imgVirtuelle.onload = function() {
		imgWidth=this.width;
		imgHeight=this.height;
		
		// end loader
		$('#mybox-loading, .mybox-down').fadeOut(300);
		
		// stats
		$('#lp-image-editor .source .stats .wOrig').val(imgWidth);
		$('#lp-image-editor .source .stats .hOrig').val(imgHeight);
		tooBig=true;
		if(imgWidth/imgHeight > maxImgWidth/maxImgHeight)	tooBigRatio='w';
		else												tooBigRatio='h';
		
		// message
		if(imgWidth>maxImgWidth || imgHeight>maxImgHeight)
			$('#lp-image-editor .source').prepend("<p style=\"color:#c60000;\">L'image d'origine est trop grande et dépasse de ce cadre. Nous vous conseillons d'utiliser l'outil redimensionner pour alléger la taille et le poids de celle-ci.</p>");
		
		// message image redimensionnée
		statsFinale()
	}
	imgVirtuelle.src = srcImg;
	
});






















