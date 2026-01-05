<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$MAX_ADMINS_SUB_LEVELS = LegacyContainer::getParameter('legacy_env_MAX_ADMINS_SUB_LEVELS');

if (($currentPage['admin_page'] && !isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) || $currentPage['superadmin_page']) {
    echo 'Votre session administrateur a expiré ou vos droits ne sont pas assez élevés pour accéder à cette page';
} else {
    // reqs toutes pages de l'arbo
    $req = 'SELECT * FROM  `caf_page` WHERE  `admin_page` =0 ORDER BY  `parent_page` ASC, `ordre_menu_page` ASC LIMIT 0 , 300';
    $pageTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $pageTab[] = $handle;
    }

    // fonction d'affichage par parent
    function listPages($tab, $parent, $level)
    {
        for ($i = 0; $i < count($tab); ++$i) {
            $page = $tab[$i];
            if ($page['parent_page'] == $parent) {
                echo '<option value="' . $page['id_page'] . '" ' . ($_POST['parent_page'] == $page['id_page'] ? 'selected="selected"' : '') . '>';
                for ($j = 0; $j < $level; ++$j) {
                    echo '&nbsp; &nbsp; ';
                }
                echo '→ ' . $page['default_name_page'] . ' [' . $page['code_page'] . ']
				</option>';
                if ($MAX_ADMINS_SUB_LEVELS > $level + 1) {
                    listPages($tab, $page['id_page'], $level + 1);
                }
            }
        }
    } ?>
	<h2><img src="/img/base/page_white_add.png" /> Création d'une nouvelle page</h2>


	<form action="<?php echo $versCettePage; ?>" method="post" class="loading">
		<input type="hidden" name="operation" value="page_add" />

		<?php
        // TABLEAU
        if (isset($_POST['operation']) && 'page_add' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>- ' . implode('</li><li>- ', $errTab) . '</li></ul></div>';
        }
    if (isset($_POST['operation']) && 'page_add' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Mise à jour effectuée à ' . date('H:i:s', time()) . '. Vous pouvez actualiser cette page</p>';
        echo '<script stype="text/javascript">parent.location.href="/admin-pages.html?showmsg=page_add";</script>';
    } ?>

		<b>Page parente :</b><br />
		<select name="parent_page">
			<option value="0">RACINE DU SITE</option>
			<?php
            listPages($pageTab, 0, 0); ?>
		</select>
		<br />
		<hr style="margin:10px 0; clear:both" />
		<?php
        /*
        <b>Nom par défaut de la page :</b><br />
        <input type="text" name="default_name_page" class="type1" value="<?php echo inputVal('default_name_page', '');?>" placeholder="" />
        <br />
        <input type="radio" name="meta_title_page" id="meta_title_page_0" value="0" <?php if($_POST['meta_title_page']=='0' or !isset($_POST['meta_title_page'])) echo 'checked="checked"';?> />
            <label for="meta_title_page_0"> Utiliser la gestion de contenus textes pour le titre de la page : permet le multi-langue et ignore le titre ci-dessus.</label>
            <br />
        <input type="radio" name="meta_title_page" id="meta_title_page_1" value="1" <?php if($_POST['meta_title_page']=='1') echo 'checked="checked"';?> />
            <label for="meta_title_page_1"> Toujours utiliser le titre ci-dessus quelque soit la langue du site</label>
        <br />
        <br />
        */
    ?>
		<div style="float:left; width:430px; padding-right:20px;">
			<b>Titre de la page pour chaque langue :</b><br />
			<p style="width:350px; font-size: 0.7rem; line-height:9px;">META titles : ce titre apparaît dans le menu supérieur du navigateur, et dans les résultats de recherche Google</p>
			<img src="/img/base/flag-fr.png" title="fr" alt="fr" style="height:28px; vertical-align:top" />
            <input type="text" name="titre[]" class="type1" value="<?php echo HtmlHelper::escape(stripslashes($_POST['titre'][0])); ?>" placeholder="" /><br />

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
		</div>

		<hr style="margin:10px 0; clear:both" />

		<b>Menu principal du site :</b>
		<div class="buttonset">
			<input type="radio" id="menu_page_0" name="menu_page" value="0" <?php if ('0' == $_POST['menu_page'] || !$_POST) {
			    echo 'checked="checked"';
			} ?> /><label for="menu_page_0"> <img src="/img/base/chart_organisation_delete.png" style="border-radius:3px; background:white; padding:3px; float:left; position:relative; bottom:3px; right:3px; " alt="" title="" /> Absent du menu principal</label>
			<input type="radio" id="menu_page_1" name="menu_page" value="1" <?php if ('1' == $_POST['menu_page']) {
			    echo 'checked="checked"';
			} ?> /><label for="menu_page_1"> <img src="/img/base/chart_organisation_add.png" style="border-radius:3px; background:white; padding:3px; float:left; position:relative; bottom:3px; right:3px; " alt="" title="" /> Apparaît dans le menu principal</label>
		</div>
		<br />

		<div id="infomenu" style="display:<?php if (1 == $_POST['menu_page']) {
		    echo 'block';
		} else {
		    echo 'none';
		} ?>">
			<b>Intitulé du lien dans le menu principal :</b><br />
            <img src="/img/base/flag-fr.png" title="fr" alt="fr" style="height:28px; vertical-align:top" />
            <input type="text" name="menuname[]" class="type1" value="<?php echo HtmlHelper::escape(stripslashes($_POST['menuname'][0])); ?>" placeholder="" /><br />

			<img src="/img/base/info.png" style="vertical-align:middle" /> Cette nouvelle page apparaîtra à la fin du menu. Utilisez la flèche <img src="/img/base/move.png" style="height:16px;vertical-align:middle;" /> pour la déplacer après enregistrement.
		</div>
		<br />
		<hr style="margin:10px 0; clear:both" />

		<?php
		/*
		<b>Fichiers javascript liés :</b><br />
		Séparés par un point-virgule<br />
		<input type="text" name="add_js_page" class="type1" value="<?php echo inputVal('add_js_page', '');?>" />
		<br />
		<br />

		<b>Fichiers CSS liés :</b><br />
		Séparés par un point-virgule<br />
		<input type="text" name="add_css_page" class="type1" value="<?php echo inputVal('add_css_page', '');?>" />
		<br />
		<br />
		*/
    ?>

		<b>Priorité au référencement :</b><br />
		Quelle est l'importance de cette page <b>par rapport aux autres pages du site</b> (5=neutre) ?<br />
		<br />
		<div id="slider" style="width:260px; float:left; padding-right:40px; "></div>
		<input type="text" name="priority_page" readonly="readonly" id="slideamount" value="<?php echo $_POST ? (int) ($_POST['priority_page']) : '5'; ?>" style="border:none; background:none; text-align:right; width:30px"></b><sub>/10</sub><br />

		<br />

		<img src="/img/base/info.png" style="vertical-align:middle" /> Cette page sera invisible pour l'instant : le temps d'y ajouter du contenu.
		<br />
		<br />

		<input type="hidden" name="vis_page" value="0" />
		<input type="submit" class="boutonFancy" value="Enregistrer" />
	</form>


	<!-- JS -->
	<script type="text/javascript" src="/js/jquery.urlEncode.js"></script>
	<script type="text/javascript">
	/* */
	function generateCode(){
		var retour='';
		// code du parent
		if($('select[name=parent_page]').val()!='0'){
			var code = $('select[name=parent_page] option:selected').html().match(/\[(.)*\]/)[0];
			retour += code.substr(1, code.length -2)+'-';
		}
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
		$('.buttonset').buttonset();
		$('input[name=menu_page]').bind("change click load ready", function(){
			if($(this).val()==0)	$('#infomenu').fadeOut({queue:false,duration:300});
			else					$('#infomenu').fadeIn({queue:false,duration:300});
		});
		// slider
		$("#slider").slider({
			range: "max",
			min: 0,
			max: 10,
			value: <?php echo $_POST ? (int) ($_POST['priority_page']) : '5'; ?>,
			step: 1,
			slide: function(event, ui){		$("#slideamount").val(ui.value);		}
		});
		$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" ) );
	});
	</script>



	<?php
}
?>
