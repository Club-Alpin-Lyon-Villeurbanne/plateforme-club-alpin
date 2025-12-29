<?php

use App\Security\SecurityConstants;
use App\Helper\HtmlHelper;

if (!isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    echo 'Votre session administrateur a expiré ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    ?>
	<h2><img src="/img/base/page_white_add.png" /> Création d'une nouvelle page</h2>

	<form action="<?php echo $versCettePage; ?>" method="post">
		<input type="hidden" name="operation" value="pagelibre_add" />

		<?php
        // TABLEAU
        if (isset($_POST['operation']) && 'pagelibre_add' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>- ' . implode('</li><li>- ', $errTab) . '</li></ul></div>';
        }
    if (isset($_POST['operation']) && 'pagelibre_add' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '. Vous pouvez actualiser cette page</p>';
        echo '<script stype="text/javascript">parent.location.href="/admin-pages-libres.html?showmsg=pagelibre_add";</script>';
    } ?>

		<div style="float:left; width:430px; padding-right:20px;">
			<b>Titre de la page :</b><br />
			<p style="width:350px; font-size: 0.7rem; line-height:9px;">META titles : ce titre apparaît dans le menu supérieur du navigateur, et dans les résultats de recherche Google</p>
			<input type="text" name="default_name_page" class="type1 getcodefrom" value="<?php echo HtmlHelper::escape(stripslashes($_POST['default_name_page'] ?? '')); ?>" placeholder="" />
			<br />
			<br />
			<br />
		</div>

		<div style="float:left; width:430px; padding-right:20px;">
			<b>Code de la page, affiché dans l'URL :</b><br />
			<p style="width:350px; font-size: 0.7rem; line-height:9px;">
				En minuscules, lettres, chiffres et tirets<br />
				Utilisez le bouton &laquo;Générer&raquo; pour gagner du temps.
			</p>
			<input type="text" name="code_page" class="type1" value="<?php echo inputVal('code_page', ''); ?>" placeholder="" />.html
			&nbsp;&nbsp;&nbsp; <a href="javascript:void(0)" onclick="generateCode()" class="boutonFancy">&gt; Générer automatiquement</a>
			<br />
			<br />
		</div>

		<hr style="margin:10px 0; clear:both" />

		<b>META Description (facultatif) :</b><br />
		<p style="font-size: 0.7rem; line-height:9px;">
			Ce texte de 160 caractères maximum apparaît dans les résultats des moteurs de recherche et résume le contenu de la page.
			Par défaut, la description du site est utilisée.
		</p>
		<input type="text" name="default_description_page" class="type1" style="width:750px" value="<?php echo inputVal('code_page', ''); ?>" placeholder="" />
		<br />
		<br />

		<hr style="margin:10px 0; clear:both" />

		<b>Priorité au référencement :</b><br />
		Quelle est l'importance de cette page <b>par rapport aux autres pages du site</b> ? (50% = neutre)<br />
		<br />
		<div id="slider" style="width:260px; float:left; padding-right:40px; "></div>
		<input type="text" name="priority_page" readonly="readonly" id="slideamount" value="<?php echo $_POST ? (int) ($_POST['priority_page']) : '50'; ?>" style="border:none; background:none; text-align:right; width:30px"></b><sub>/100 %</sub><br />

		<br />
		<hr style="margin:10px 0; clear:both" />

		<input type="hidden" name="vis_page" value="0" />
		<p style="line-height:25px;">
			<input type="submit" class="bigButton" value="Enregistrer" />
			<img src="/img/base/info.png" style="vertical-align:middle" /> Cette page sera invisible pour l'instant : le temps d'y ajouter du contenu.
		</p>
	</form>


	<!-- JS -->
	<script type="text/javascript" src="/js/jquery.urlEncode.js"></script>
	<script type="text/javascript">
	/* */
	function generateCode(){
		var retour='';
		// code de lui-même
		var val=$('input.getcodefrom').val();
		$.ajax({
			type: "POST",
			async: false,
			dataType: 'json',
			url: "/?ajx=formater",
			data: "type=3&str="+ $.URLEncode(val),
			success: function(jsonMsg){
				// variable retour
				if(jsonMsg.success){
					if(jsonMsg.content != false){
						retour += jsonMsg.content;
						retour=retour.substr(0, 40);
						// modif du "par défaut"
						$('input[name=code_page]').val(retour);
					}
				}
				else{
					alert(jsonMsg.error);
				}
			},
			error : function(msg){
				alert( "Erreur Ajax " + msg );
			}
		});
	}
	/* */
	$().ready(function(){
		// slider
		$("#slider").slider({
			range: "max",
			min: 0,
			max: 100,
			value: <?php echo $_POST ? (int) ($_POST['priority_page']) : '50'; ?>,
			step: 10,
			slide: function(event, ui){		$("#slideamount").val(ui.value);		}
		});
		$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" ) );
	});
	</script>

<?php
}
