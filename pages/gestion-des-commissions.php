<!-- MAIN -->
<div id="main" role="main" class="bigoo" style="">
	
	<!-- partie gauche -->
	<div id="left1">
		<div class="main-type">
			<?php
            
			if(!allowed('comm_edit')) echo '<p class="erreur">Vous n\'avez pas les droits nécessaires pour afficher cette page</p>';
			else{
                if ($userAllowedTo['comm_edit'] !== 'true') {
                    $comm_edit = explode('|', $userAllowedTo['comm_edit']);
                    foreach ($comm_edit as $c => $cedit) {
                        $tabCedit = explode(':', $cedit);
                        $comm_edit[$c] = $tabCedit[1];
                    }
                }
				?>
				<h1>Gestion des commissions</h1>
				<?php inclure($p1, 'vide'); ?>
				
				<br />
				<div id="commissions-gestion">
					<?php
					// LISTE DES COMMISSIONS
					include SCRIPTS.'connect_mysqli.php';;
					$req="SELECT * FROM caf_commission ORDER BY ordre_commission ASC, id_commission DESC";
					$result=$mysqli->query($req);
					
					
					while($row=$result->fetch_assoc()){	
                    
                        $action = false;
                        if ($userAllowedTo['comm_edit'] === 'true') {
                            $action = true;
                        } else {
                            if (in_array($row['code_commission'], $comm_edit)) {
                                $action = true;
                            } else {
                                $action = false;
                            }
                        }
						
                        if ($action) {
                            // chemin vers grand eimage
                            if(file_exists('ftp/commission/'.$row['id_commission'].'/bigfond.jpg'))
                                $bigImgUrl='ftp/commission/'.$row['id_commission'].'/bigfond.jpg';
                            else
                                $bigImgUrl='ftp/commission/0/bigfond.jpg';
                            
                            echo '<div class="item '.($row['vis_commission']==1?'on':'off').'">'
                                    .'<div class="item-1">'
                                        // pour ajax
                                        .'<input type="hidden" name="id_commission" value="'.intval($row['id_commission']).'" class="id_commission" />'
                                        // bigfond
                                        .'<a href="'.$bigImgUrl.'" title="" class="fancybox"><img style="width:100%" src="'.$bigImgUrl.'" alt="" title="Agrandir" /></a>'
                                        .'<br />'
                                        // pictos
                                        .'<img src="'.comPicto($row['id_commission'], 'dark').'" alt="" title="" /> '
                                        .'<img src="'.comPicto($row['id_commission']).'" alt="" title="" /> '
                                        .'<img src="'.comPicto($row['id_commission'], 'light').'" alt="" title="" /> '
                                    .'</div>'
                                    .'<div class="item-2">';
                                        // reorder
                                        if ($userAllowedTo['comm_edit'] === 'true') echo '<img class="handle" style="float:right; cursor:move; height:30px" src="img/base/move.png" alt="MOVE" title="Réordonner" />';
                                        // titre
                                        echo '<h2>'.html_utf8($row['title_commission']).($row['vis_commission']==1?'':' <span style="color:red; font-size:12px">[invisible]</span>').'</h2>';
                                        // boutons
                                        $groupes = get_groupes($row['id_commission'], true);
                                        if (count($groupes) > 0) {
                                            echo '<p><b>Groupes :</b> ';
                                            $g = 0;
                                            foreach ($groupes as $groupe) {
                                                if ($g > 0) echo ', ';
                                                echo $groupe['nom'];
                                                $g++;
                                            }
                                            echo "</p><br>";
                                        }
                            if (allowed('comm_desactivate', 'commission:'.$row['code_commission'])) echo '<a href="includer.php?p=includes/commission-edit-vis.php&amp;id_commission='.intval($row['id_commission']).'" title="" class="fancyframe nice2">Activer / Désactiver</a> ';
                            if (allowed('comm_edit', 'commission:'.$row['code_commission'])) echo '<a href="commission-edit.html?id_commission='.intval($row['id_commission']).'" title="" class="nice2">Modifier cette commission</a> <br />';
                            if (allowed('comm_read', 'commission:'.$row['code_commission'])) echo '<a href="commission-consulter.html?id_commission='.intval($row['id_commission']).'" title="" class="nice2">Fiche commission</a><br />';
                                        // .'<a href="includer.php?p=includes/commission-edit-text.php&amp;id_commission='.intval($row['id_commission']).'" title="" class="fancyframe nice2">Modifier le titre</a> <br />'
                                        // .'<a href="includer.php?p=includes/commission-edit-bigfond.php&amp;id_commission='.intval($row['id_commission']).'" title="" class="fancyframe nice2">Modifier l\'image de fond</a> '
                                        // .'<a href="includer.php?p=includes/commission-edit-pictos.php&amp;id_commission='.intval($row['id_commission']).'" title="" class="fancyframe nice2">Modifier les pictogrammes</a> <br />'
                            echo '</div>'
                                    .'<br style="clear:both" />'
                                .'</div>';
                        }
					}
					$mysqli->close;
					?>
					<br />
					<br />
					<br />
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<!-- partie droite -->
	<?php
	include INCLUDES.'right-type-agenda.php';
	?>
	
	<br style="clear:both" />
</div>

<!-- un peu d'ergoomie... -->
<script type="text/javascript" src="js/jquery-ui-1.10.2.custom.min.js"></script>
<script type="text/javascript">
	$().ready(function() {
		
		/* */
		$("#commissions-gestion").sortable({
			// placeholder: "placeholder1",
			// forcePlaceholderSize:true,
			tolerance:'pointer',
			items:'.item',
			handle:'.handle',
			stop:function(i){
				$.ajax({
					type: "POST",
					url: "index.php?ajx=operations",
					dataType : "json",
					data: "operation=commission_reorder&"+getDatas(),
					complete: function(jsonMsg){ 
						// console.log(jsonMsg); 
					},
					success: function(jsonMsg){
						if(jsonMsg.success){
							// var htmlMsg = $('<span/>').html(jsonMsg.successmsg).text();
							// $.fancybox('<div class="info" style="text-align:left; max-width:600px; line-height:17px;">'+htmlMsg+'</div>');
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
		
	});
	
	function getDatas(){ // renvoie les id des commissions dans l'ordre visuel
		var ids=Array();
		$('#commissions-gestion .item .id_commission').each(function(){
			ids[ids.length] = $(this).val();
		});
		
		return 'id_commission[]=' + (ids.join('&id_commission[]='));
	}
	
</script>