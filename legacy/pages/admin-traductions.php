<?php

use App\Helper\HtmlHelper;
use App\Legacy\LegacyContainer;

if (!isGranted(SecurityConstants::ROLE_ADMIN)) {
    echo 'Vous n\'êtes pas autorisé à accéder à cette page. Pour toute question, rapprochez-vous du service informatique de votre club.';
} else {
    $lang_content_inline = 'fr';

    // Pour chaque contenu original existant :
    $req = "SELECT *
					FROM caf_content_inline, caf_content_inline_group
					WHERE lang_content_inline LIKE 'fr'
					AND groupe_content_inline = id_content_inline_group
					ORDER BY ordre_content_inline_group ASC, code_content_inline ASC, date_content_inline DESC
					";
    $contTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        // save original
        $handle['original'] = $handle['contenu_content_inline'];
        // RAZ des infos de l'élément en langue étrangere
        $handle['contenu_content_inline'] = '';
        $handle['id_content_inline'] = 0; // very important : val par defaut
        // recuperation de la version en lagnue etrangere
        $req2 = "SELECT contenu_content_inline , id_content_inline
					FROM caf_content_inline
					WHERE code_content_inline LIKE '" . $handle['code_content_inline'] . "'
					AND lang_content_inline LIKE '" . $lang_content_inline . "'
					ORDER BY date_content_inline DESC LIMIT 1
					";
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req2);
        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $handle['contenu_content_inline'] = $handle2['contenu_content_inline'];
            $handle['id_content_inline'] = $handle2['id_content_inline'];
        }

        // push de mon item d'origine, modifié avec les valeurs en langue etrangere
        $contTab[] = $handle;
    }

    // groupes
    $req = 'SELECT * FROM caf_content_inline_group ORDER BY ordre_content_inline_group ASC';
    $contGroupTab = [];
    $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
    while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
        $contGroupTab[] = $handle;
    } ?>

	<h2 style="padding-left:0px">Traduction des contenus</h2>
	<p>
		- Un champ rouge correspond à un contenu manquant. <br />
		- Un champ orange correspond à un contenu qui a été modifié mais pas encore sauvegardé<br />
		- Un champ vert est complété, et sauvegardé
	</p>
	<h2>Langues disponibles :
    <a href="/admin-traductions/fr.html" title="" style="font-size:1.3rem; margin-right:20px; padding:3px; color:black; background:white;">
        <img src="/img/base/flag-fr.png" alt="" title="" style="height:30px; vertical-align:middle;" /> FR </a>
	</h2>
	<br style="clear:both" />
	<br style="clear:both" />


	<?php	// TABLEAU DES CONTENUS
    if (count($contTab)) {
        ?>
		<br />
		<table class="cont-table">
			<tr class="th1">
				<th style="text-align:right">Code</th>
				<th style="text-align:center">Original (FR)</th>
				<th>Traduction (<?php echo $lang_content_inline; ?>)</th>
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

            echo '<tr class="saveAble" style="' . ($dejaVus ? 'display:none' : '') . '" id="ligne-' . (int) $contTab[$i]['id_content_inline'] . '">';
            echo '<td class="cont-indice">' . $contTab[$i]['code_content_inline'] . '&nbsp;</td>';
            echo '<td class="cont-original">' . $contTab[$i]['original'] . '&nbsp;</td>';
            echo '<td class="cont-edit">
						<input type="hidden" class="jId" value="' . (int) $contTab[$i]['id_content_inline'] . '" />
						<input type="hidden" class="jGroupe" value="' . (int) $contTab[$i]['groupe_content_inline'] . '" />
						<input type="text" style="display:none" class="jBase" id="base-' . (int) $contTab[$i]['id_content_inline'] . '" value="' . HtmlHelper::escape($contTab[$i]['contenu_content_inline']) . '" />
						<input type="hidden" class="jCode" value="' . HtmlHelper::escape($contTab[$i]['code_content_inline']) . '" />
						<input type="hidden" class="jLinkedtopage" value="' . HtmlHelper::escape($contTab[$i]['linkedtopage_content_inline']) . '" />

						<input type="text" style="min-width:300px;" class="jVal" name="contenu-' . $contTab[$i]['code_content_inline'] . '-' . $dejaVus . '" value="' . HtmlHelper::escape($contTab[$i]['contenu_content_inline']) . '" />
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

	function saveLine(ligne, anim){
		if(typeof(anim)=="undefined") anim=true;

		var id   =  parseInt(ligne.find('input.jId').val());
		var groupe= parseInt(ligne.find('input.jGroupe').val());
		var val  =  ligne.find('input.jVal').val();
		var valAscii = fetchAscii(val);
		var code =  ligne.find('input.jCode').val();
		var linked= ligne.find('input.jLinkedtopage').val();

		var datas="id_content_inline="+id
					+"&linkedtopage_content_inline="+linked
					+"&groupe_content_inline="+groupe
					+"&code_content_inline="+code
					// +"&contenu_content_inline="+ $.URLEncode(valAscii)
					// +"&contenu_content_inline="+ encodeURI(valAscii)
					+"&contenu_content_inline="+ encodeURIComponent(valAscii)
					+"&lang_content_inline=<?php echo $lang_content_inline; ?>";
		console.log(datas);

		$.ajax({
			type: "POST",
			async: false,
			dataType: 'json',
			url: "/?ajx=traductions_save",
			data: datas,
			success: function(jsonMsg){
				if(anim){
					$("#loading1").fadeIn().fadeOut();
					$("#loading2").fadeIn().fadeOut();
				}
				// variable retour
				if(jsonMsg.success){
					// console.log(jsonMsg.req);
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
		$(".cont-edit input[type=text]").blur(function(){		$(this).parent().prev('td').css({'color':'gray'});;		});

		// lors de l'entrée de contenus
		$(".cont-edit input[type=text]").keyup(function(){
			// maj affichage couleurs des champs
			majTable();
		});

		// sauvegarde de ligne
		$('.cont-save a').click(function(){
			saveLine($(this).parents('tr.saveAble').first());
		});

		// sauvegarde de ligne lors de press enter
		$(".cont-edit input[type=text]").keydown(function(key){
			if(key.keyCode=='13'){
				var line=$(this).parents('tr:first');
				// alert(thisId);
				saveLine(line);
				// focus sur ligne suivante
				$(this).parents('tr').next('tr:first').find('input[type=text]').focus();
			}
		});

		// sauvegarde complète
		$('#saveAll').click(function(){
			$("#loading1, #loading2").show();
			$('tr.saveAble').each(function(){
				saveLine($(this), false);
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
?>