<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

if (!isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
} else {
    $lang_content_inline = 'fr';
    $operation = $_POST['operation'] ?? null;
    // contenus
    $req = "SELECT *
					FROM caf_content_inline, caf_content_inline_group
					WHERE lang_content_inline LIKE '$lang_content_inline'
					AND groupe_content_inline = id_content_inline_group
					ORDER BY ordre_content_inline_group ASC, code_content_inline ASC, date_content_inline DESC
					";
    $contTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $contTab[] = $handle;
    }

    // groupes
    $req = 'SELECT * FROM caf_content_inline_group ORDER BY ordre_content_inline_group ASC';
    $contGroupTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $contGroupTab[] = $handle;
    } ?>
	<h2 style="padding-left:0px">
		Contenus
	</h2>


	<p>
		<b style="color:red">Important :</b> Attention, les contenus d'origine sont conçus pour optimiser le référencement et peuvent contenir des informations techniques importantes.
		<b>Ne modifiez ces contenus que si vous savez ce que vous faites.</b>
		Cliquez sur la disquette pour enregistrer vos modifications sur chaque ligne, ou toute la page.
	</p>

	<p>
		- Un champ rouge correspond à un contenu manquant. <br />
		- Un champ orange correspond à un contenu qui a été modifié mais pas encore sauvegardé<br />
		- Un champ vert est complété, et sauvegardé
	</p>

	<!-- nouveau groupe -->
	<a href="javascript:void(0)" class="boutonFancy2" onclick="$(this).siblings('.toggleForm:not(.addgroup)').slideUp(200); $(this).siblings('.toggleForm.addgroup').slideToggle(200);">
		<img src="/img/base/add.png" alt="" title="" /> Ajouter un groupe de contenus</a>
	<!-- nouvel elt -->
	<a href="javascript:void(0)" class="boutonFancy2" onclick="$(this).siblings('.toggleForm:not(.add)').slideUp(200); $(this).siblings('.toggleForm.add').slideToggle(200);">
		<img src="/img/base/add.png" alt="" title="" /> Ajouter un contenu manquant</a>

	<br />
	<!-- nouvel elt -->
	<form class="toggleForm add" action="<?php echo $versCettePage; ?>" method="post" style="display:<?php if (('addContentInline' === $operation && isset($errTab) && count($errTab) > 0) || 'forceAddContent' === $operation) {
	    echo 'block';
	} ?>">
		<input type="hidden" name="operation" value="addContentInline" />
		<input type="hidden" name="lang_content_inline" value="<?php echo $lang_content_inline; ?>" />
		<?php
	    if (isset($_POST['operation']) && 'addContentInline' == $_POST['operation'] && count($errTab)) {
	        echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
	    } ?>
		<h4>Ajouter un contenu</h4>
		<?php
	    if (!count($contGroupTab)) {
	        echo "Vous devez d'abord créer un groupe";
	    } else {
	        ?>
			Attention, sauvegardez préalablement vos autres modifications avant de cliquer sur OK.<br />

			<table>
				<tr>
					<th>
						Code
					</th>
					<th>
						Contenu
					</th>
					<th>
						Groupe parent - Pour usage admin
					</th>
				</tr>
				<tr>
					<td>
                        (isset($context["token"]) || array_key_exists("token", $context) ?
						<input type="text" name="code_content_inline" value="<?php echo isset($_POST['code_content_inline']) || array_key_exists('code_content_inline', $_POST) ? trim(HtmlHelper::escape(stripslashes(mb_convert_encoding($_POST['code_content_inline'], 'ISO-8859-1', 'UTF-8')))) : 'Copiez le code ici'; ?>"
							onfocus="if($(this).val()=='Copiez le code ici') $(this).val('');"
							onblur="if($(this).val()=='') $(this).val('Copiez le code ici');" />
					</td>
					<td>
						<input type="text" name="contenu_content_inline" value="<?php echo isset($_POST['contenu_content_inline']) || array_key_exists('contenu_content_inline', $_POST) ? trim(HtmlHelper::escape(stripslashes(mb_convert_encoding($_POST['contenu_content_inline'], 'ISO-8859-1', 'UTF-8')))) : ''; ?>" style="width:500px;" placeholder="Contenu..." />
					</td>
					<td>
						<select name="groupe_content_inline" style="min-width:150px;">
							<!--<option value="0">- Aucun, en désordre</option>-->
							<?php
	                        // liste des groupes dans le tableau dessous
	                        $tempGroup = 0; // id groupe
	        for ($i = 0; $i < count($contGroupTab); ++$i) {
	            if ($tempGroup != $contGroupTab[$i]['id_content_inline_group'] && $contGroupTab[$i]['id_content_inline_group']) {
	                echo '<option value="' . $contGroupTab[$i]['id_content_inline_group'] . '">' . $contGroupTab[$i]['nom_content_inline_group'] . '</option>';
	            }
	            $tempGroup = $contGroupTab[$i]['id_content_inline_group'];
	        } ?>
						</select>
					</td>
					<td>
						<input type="submit" value="OK" class="boutonFancy" />
					</td>
				</tr>
			</table>
			<?php
	    } ?>
	</form>


	<!-- nouveau groupe -->
	<form class="toggleForm addgroup" action="<?php echo $versCettePage; ?>" method="post" style="display:<?php if (isset($_POST['operation']) && 'addContentGroup' == $_POST['operation']) {
	    echo 'block';
	} ?>">
		<input type="hidden" name="operation" value="addContentGroup" />
		<?php
	if (isset($_POST['operation']) && 'addContentGroup' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
	    echo '<div class="info">Nouveau groupe créé, et disponible dans la liste.</div>';
	}
    if (isset($_POST['operation']) && 'addContentGroup' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
        echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
    } ?>
		<h4>Ajouter un groupe de contenu</h4>

		<table>
			<tr>
				<th>
					Nom du groupe
				</th>
			</tr>
			<tr>
				<td>
					<input type="text" name="nom_content_inline_group" value="<?php echo trim(HtmlHelper::escape(stripslashes(mb_convert_encoding($_POST['nom_content_inline_group'] ?? null, 'ISO-8859-1', 'UTF-8')))); ?>" />
				</td>
				<td>
					<input type="submit" value="OK" class="boutonFancy" />
				</td>
			</tr>
		</table>
	</form>


	<?php	// TABLEAU DES CONTENUS
    if (count($contTab)) {
        ?>
		<br />
		<table class="cont-table">
			<tr class="th1">
				<th style="text-align:right">Code &nbsp; </th>
				<th>Contenu (<?php echo $lang_content_inline; ?>)</th>
				<th></th>
				<th></th>
			</tr>
			<?php
            $tempGroup = 0; // id groupe
        $tempElt = ''; // code element
        $dejaVus = 0;
        for ($i = 0; $i < count($contTab); ++$i) {
            // GROUPES
            // dev : a l'avenir, sortable grace à TBODY
            if ($tempGroup != $contTab[$i]['id_content_inline_group'] && $contTab[$i]['id_content_inline_group']) {
                echo '<tr><th colspan="3">' . $contTab[$i]['nom_content_inline_group'] . '</th></tr>';
            }

            if ($tempElt == $contTab[$i]['code_content_inline']) {
                ++$dejaVus;
            } else {
                $dejaVus = 0;
            }

            echo '<tr style="' . ($dejaVus ? 'display:none' : '') . '" id="ligne-' . (int) $contTab[$i]['id_content_inline'] . '">';
            echo '<td class="cont-indice">' . $contTab[$i]['code_content_inline'] . '&nbsp;</td>';
            echo '<td class="cont-edit">
						<input type="hidden" class="jId" value="' . (int) $contTab[$i]['id_content_inline'] . '" />
						<input type="text" style="display:none" class="jBase" id="base-' . (int) $contTab[$i]['id_content_inline'] . '" value="' . HtmlHelper::escape($contTab[$i]['contenu_content_inline']) . '" />
						<input type="text"   class="jVal" name="contenu-' . $contTab[$i]['code_content_inline'] . '-' . $dejaVus . '" value="' . HtmlHelper::escape($contTab[$i]['contenu_content_inline']) . '" />
					</td>';
            echo '<td class="cont-save"><a href="javascript:void(0)" title="Sauvegarder cette ligne" rel="' . (int) $contTab[$i]['id_content_inline'] . '"><img src="/img/base/save.png" alt="Sauvegarder cette ligne" title="Sauvegarder cette ligne" class="upimage" style="height:20px; " /></a></td>';
            echo '<td class="cont-versions">' . jour(date('N', $contTab[$i]['date_content_inline'])) . ' ' . date('d/m/y - H:i:s', $contTab[$i]['date_content_inline']) . '</td>';
            echo '</tr>';

            $tempGroup = $contTab[$i]['id_content_inline_group'];
            $tempElt = $contTab[$i]['code_content_inline'];
        } ?>
		</table>

		<a href="javascript:void(0)" title="Tout sauvegarder" style="display:block; float:right; margin-top:5px;" id="saveAll">
			<img src="/img/base/save.png" alt="Tout sauvegarder" title="Tout sauvegarder" class="upimage" style="height:80px; " /></a>
		<?php
    } ?>
	<br />

	<script type="text/javascript" src="/js/jquery.urlEncode.js"></script>
	<script type="text/javascript">
	function majTable(){
		var oneEdited=false; // modif en standby ?

		$(".cont-edit input.jVal").each(function(){

			var thisVal = $(this).val();
			var thisVal = $(this).val();
			var baseVal = $(this).siblings('.jBase:first').val();

			if(!thisVal){
				// champ vide
				$(this).css({backgroundColor:'#e8cbcb'}, {queue:false, duration:1000});
			}
			else if(baseVal!=thisVal){
				// champ modifié
				$(this).css({backgroundColor:'#e8dccb'}, {queue:false, duration:1000});
				oneEdited=true;
			}
			else{
				// champ ok
				$(this).css({backgroundColor:'#e7f3e7'}, {queue:false, duration:1000});
			}
		});
		// bloquage de fenetre
		if(oneEdited){
			$(window).bind('beforeunload', function(){
				return "Un petit instant ! Vous venez de modifier des contenus, voulez-vous vraiment quitter cette page sans sauvegarder ?";
			});
		}
		else{
			$(window).unbind('beforeunload');
		}
	}

	function fetchAscii(obj){
		var convertedObj = '';
		for(i = 0; i < obj.length; i++){
			var asciiChar = obj.charCodeAt(i);
			convertedObj += '&#' + asciiChar + ';';
		}
		return convertedObj;
	}

	function saveLine(id, anim){
		if(typeof(anim)=="undefined") anim=true;
		id=parseInt(id);
		var val = $('tr#ligne-'+id).find('input.jVal').val();
		var valAscii = fetchAscii(val);

		$.ajax({
			type: "POST",
			async: false,
			dataType: 'json',
			url: "/ajax/contenus_save",
			data: "id="+id+"&val="+ $.URLEncode(valAscii),
			success: function(jsonMsg){
				if(anim){
					$("#loading1").fadeIn().fadeOut();
					$("#loading2").fadeIn().fadeOut();
				}
				// variable retour
				if(jsonMsg.success){
					// modif du "par défaut"
					$('#base-'+id).val(val);
					majTable();
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


	$().ready(function(){
		majTable();

		// focus sur un text
		$(".cont-edit input[type=text]").focus(function(){		$(this).parent().prev('td').css({'color':'black'});	});
		$(".cont-edit input[type=text]").blur(function(){		$(this).parent().prev('td').css({'color':'gray'});	});

		// lors de l'entrée de contenus
		$(".cont-edit input[type=text]").keyup(function(){
			// maj affichage couleurs des champs
			majTable();
		});

		// sauvegarde de ligne lors de press enter
		$(".cont-edit input[type=text]").keydown(function(key){
			if(key.keyCode=='13'){
				var thisId=parseInt($(this).parents('tr').find('.cont-save a').attr('rel'));
				saveLine(thisId);
				// focus sur ligne suivante
				$(this).parents('tr').next('tr').find('input[type=text]').focus();
			}
		});

		// sauvegarde de ligne
		$('.cont-save a').click(function(){
			var thisId = parseInt($(this).attr('rel'));
			saveLine(thisId);
		});

		// sauvegarde complète
		$('#saveAll').click(function(){
			$("#loading1, #loading2").fadeIn(1000);
			$('.cont-save a').each(function(){
				var thisId = parseInt($(this).attr('rel'));
				saveLine(thisId, false);
			});
			$("#loading1, #loading2").fadeOut({duration:1000, complete:function(){	majTable()}});
		});

		// si on arrive sur la page depuis un bouton "manque de contenus", on focus sur le champ ciblé
		<?php
        if (isset($_POST['operation']) && 'forceAddContent' == $_POST['operation']) {
            ?>
			$("input[name=contenu_content_inline]:first").focus();
			<?php
        } ?>
	});

	</script>
	<br style="clear:both" />
	<br style="clear:both" />
	<br style="clear:both" />
	<br style="clear:both" />

	<?php
}
