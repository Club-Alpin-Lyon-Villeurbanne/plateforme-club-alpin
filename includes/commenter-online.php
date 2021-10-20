<form action="<?php echo $versCettePage; ?>#comments" method="post" id="comment-form">
	<input type="hidden" name="operation" value="comment" />
	<input type="hidden" name="unlock1" value="0" />
	<input type="hidden" name="parent_comment" value="<?php echo $parent_comment; ?>" />

	<?php
    // MESSAGES A LA SOUMISSION
    if ('comment' == $_POST['operation'] && count($errTab)) {
        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
    }
    ?>

	<div style="float:left; width:75px; padding-top:3px;">
		<img src="<?php echo userImg($_SESSION['user']['id_user'], 'pic'); ?>" alt="" title="" />
	</div>
	<div style="float:left; width:530px;">
		<textarea name="cont_comment" class="type2" style="width:460px; height:90px;" placeholder="Laisser un commentaire" ></textarea>
		<br />
		<input type="submit" class="rond" value="OK" />
	</div>
	<br style="clear:both" />
	<br />
	<br />
</form>

<script type="text/javascript">
// js pour diminuer le risque de spams autos.
// Deverrouillage du formulaire lors de l'interaction
$().ready(function(){
	$('#comment-form input.rond').bind('focus mouseenter', function(){
		$('#comment-form input[name=unlock1]').val('unlocked');
	});
});
</script>
<br />