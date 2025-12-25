<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;
use App\Helper\HtmlHelper;

if (!isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    echo 'Votre session administrateur a expiré ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    $id_page = (int) $_GET['id_page'];
    $req = "SELECT * FROM caf_page WHERE id_page=$id_page LIMIT 1";
    $page = false;
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $page = $handle;
    }

    if (!$page) {
        echo '<div class="erreur">ID invalide</div>';
        exit;
    } ?>

	<h2><img src="/img/base/page_white_add.png" /> Modifier les METAS de cette page</h2>

	<form action="<?php echo $versCettePage; ?>" method="post">
		<input type="hidden" name="operation" value="pagelibre_edit" />
		<input type="hidden" name="id_page" value="<?php echo $id_page; ?>" />
		<input type="hidden" name="code_page_original" value="<?php echo HtmlHelper::escape($page['code_page']); ?>" />

		<?php
        // TABLEAU
        if (isset($_POST['operation']) && 'pagelibre_edit' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>- ' . implode('</li><li>- ', $errTab) . '</li></ul></div>';
        }
    if (isset($_POST['operation']) && 'pagelibre_edit' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '. Vous pouvez actualiser cette page</p>';
        echo '<script stype="text/javascript">parent.location.href="/admin-pages-libres.html?showmsg=pagelibre_edit";</script>';
    } ?>

		<div style="float:left; width:430px; padding-right:20px;">
			<b>Titre de la page :</b><br />
			<p style="width:350px; font-size: 0.7rem; line-height:9px;">META titles : ce titre apparaît dans le menu supérieur du navigateur, et dans les résultats de recherche Google</p>
			<input type="text" name="default_name_page" class="type1 getcodefrom" value="<?php echo cont('meta-title-' . $page['code_page']); ?>" placeholder="" />
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
			<input type="text" name="code_page" class="type1" value="<?php echo HtmlHelper::escape($page['code_page']); ?>" placeholder="" />.html
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
		<input type="text" name="default_description_page" class="type1" style="width:750px" value="<?php echo cont('meta-description-' . $page['code_page']); ?>" placeholder="" />
		<br />
		<br />

		<hr style="margin:10px 0; clear:both" />

		<b>Priorité au référencement :</b><br />
		Quelle est l'importance de cette page <b>par rapport aux autres pages du site</b> ? (50% = neutre)<br />
		<br />
		<div id="slider" style="width:260px; float:left; padding-right:40px; "></div>
		<input type="text" name="priority_page" readonly="readonly" id="slideamount" value="<?php echo (int) $page['priority_page'] * 100; ?>" style="border:none; background:none; text-align:right; width:30px"></b><sub>/100 %</sub><br />

		<br />
		<hr style="margin:10px 0; clear:both" />

		<input type="hidden" name="vis_page" value="0" />
		<p style="line-height:25px;">
			<input type="submit" class="bigButton" value="Enregistrer" />
			<img src="/img/base/info.png" style="vertical-align:middle" /> Si vous changez <u>le code</u> de la page, elle deviendra invisible le temps d'y ajouter du contenu.
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
			value: <?php echo (int) $page['priority_page'] * 100; ?>,
			step: 10,
			slide: function(event, ui){		$("#slideamount").val(ui.value);		}
		});
		$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" ) );
	});
	</script>



	<?php
}
?>
