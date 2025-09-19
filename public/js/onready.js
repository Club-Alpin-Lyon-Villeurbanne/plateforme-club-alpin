$().ready(function() {
	
	// débug ie8 et older : placeholders
	if($.browser.msie && $.browser.version < 9){
		$('input[type!=password],textarea').each(function(){
			var phval=$(this).attr('placeholder');
			if(phval){
				if(!$(this).val())
					$(this).val(phval);
			}
		});
	}
	
	// frermer / ouvrir les contenus de fieldset
	$(".legendToggle").click(function(){		$(this).siblings('.toggleZone').fadeToggle(300);	});
	// loadings sur formulaires
	$("form.loading").submit(function(){
		$("#loading1").fadeIn('fast');
		$("#loading2").fadeIn('fast');
	});
	
	// fancybox
	var fancyBoxLock=true;
	$("a.fancybox").fancybox({
		'overlayColor'	:	'#000',
		// 'centerOnScroll':	true,
		// 'titleShow' 	:	false,
		'scrolling' 	:	'no',
		// 'titlePosition'	:	'inside',
		'transitionIn'	:	'fade',
		'transitionOut'	:	'fade',
		'speedIn'		:	350, 
		'speedOut'		:	350
	});
	$("a.fancyframe").fancybox({
		'type'			:	'iframe',
		'transitionIn'	:	'elastic',
		'transitionOut'	:	'elastic',
		'overlayColor'	:	'#000',
		'width'			:	950,
		'height'		:	'80%',
		'minHeight'     :   '98%',
		'speedIn'		:	400, 
		'speedOut'		:	200,
		'autoSize' : false
	});
	$("a.fancyframeadmin").fancybox({
		'hideOnOverlayClick':false,
		// 'hideOnContentClick':false,
		'type'			:	'iframe',
		'transitionIn'	:	'elastic',
		'transitionOut'	:	'elastic',
		'overlayColor'	:	'#000',
		'width'			:	950,
		// 'height'		:	'98%',
		'minHeight'     :   '98%',
		'speedIn'		:	400, 
		'speedOut'		:	200
		// beforeClose: function(){ alert('beforeClose'); return false; }
	});
	// upimage
	if($.browser.msie){	userAgent = $.browser.version -0;	}
	else userAgent=false;
	if(!userAgent || userAgent>7){
		$(".upimage").mouseenter(function(){
			if(!$(this).attr("src").match("-up")){
				var strlen = $(this).attr("src").length; // debug IE
				var ext= $(this).attr("src").substr(strlen-3, strlen);
				var src = $(this).attr("src").match(/[^\.]+/) + "-up."+ext;
				$(this).attr("src", src);
			}
		});
		$(".upimage").mouseleave(function(){
			if($(this).attr("src").match("-up")){
				var src = $(this).attr("src").replace("-up", "");
				$(this).attr("src", src);
			}
		});
	}
	// fermeur de messages
	$(".msgCloser").click(function(){
		$(this).parent().fadeOut(150);
	});
	
	// protection des e-mail
	var spt = $('span.mailme');
	var at = / at /;
	var dot = / dot /g;
	var addr = $(spt).text().replace(at,"@").replace(dot,".");
	$(spt).after('<a href="mailto:'+addr+'" title="Send an email">'+ addr +'</a>')
	.hover(function(){window.status="Send a letter!";}, function(){window.status="";});
	$(spt).remove();

	// modif honteuse des liens blank
	$('a.blank').mouseenter(function(){				$(this).attr("target","blank");			});
	$('a.blank').mouseleave(function(){				$(this).removeAttr("target");			});
});
