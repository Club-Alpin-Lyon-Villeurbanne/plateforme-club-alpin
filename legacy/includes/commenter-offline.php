<form action="<?php echo $versCettePage; ?>" method="post" id="comment-form" style="display: flex">
	<input type="hidden" name="operation" value="comment" />
	<input type="hidden" name="unlock1" value="0" />

	<div style="flex: 1; width:75px; padding-top:3px;">
		<img src="/ftp/user/0/pic-profil.jpg" alt="" title="" />
	</div>
	<div style="flex: 9;">
		<textarea name="cont_comment" class="type2" style="width:100%; height:90px;" placeholder="Laisser un commentaire" ></textarea>
		<br />
		<input type="text" class="type2" style="width:215px; margin-right:16px" name="nom_comment" placeholder="Votre nom" />
		<input type="text" class="type2" style="width:215px" name="email_comment" placeholder="Votre e-mail">

		<input type="button" class="rond" value="OK" />
	</div>
	<br style="clear:both" />
	<br />
	<br />
</form>

<script type="text-javascript">
// js pour diminuer le risque de spams autos.
// Deverrouillage du formulaire lors de l'interaction
$('#comment-form input.rond').bind('focus mouseenter', function(){
	$('#comment-form input[name=unlock1]').val('1');
});
// submit
$('#comment-form input.rond').bind('click', function(){
	var form=$(this).parents('form');

	if(!form.is('.running')){
		// vars
		var datas='unlock2=1';
		form.find('input, select, textarea').each(function(){
			if($(this).val() && $(this).attr('name'))
				datas += '&'+$(this).attr('name')+'='+$(this).val();
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
			}
		});
	}


});
</script>
<br />