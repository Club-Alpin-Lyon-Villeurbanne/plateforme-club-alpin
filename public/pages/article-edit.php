<script type="text/javascript" src="js/faux-select.js"></script>

<!-- MAIN -->
<div id="main" role="main" class="bigoo">

	<!-- partie gauche -->
	<div id="left1">

		<!-- // Titre. créa ou modif ? -->
		<h1 class="page-h1"><b>Modifier</b> cet article</h1>

		<div style="padding:10px 0 0 30px; line-height:18px; ">
			<?php

            // ID
            $id_article = (int) $p2;
            $article = false;

            include SCRIPTS.'connect_mysqli.php';
            $req = 'SELECT * FROM '.$pbd."article WHERE id_article=$id_article LIMIT 1";
            $result = $mysqli->query($req);
            while ($row = $result->fetch_assoc()) {
                $req = 'SELECT code_commission FROM '.$pbd.'commission WHERE id_commission='.$row['commission_article'].' LIMIT 1';
                $result2 = $mysqli->query($req);
                while ($row2 = $result2->fetch_assoc()) {
                    $row['code_commission'] = $row2['code_commission'];
                }

                $article = $row;
            }
            $mysqli->close;

            // not found
            if (!$article) {
                echo '<p class="erreur">Cet article est introuvable.</p>';
            }
            // pas à moi, et je n'ai pas le droit de tous les modifier
            elseif ($article['user_article'] != $_SESSION['user']['id_user'] && !allowed('article_edit_notmine')) {
                echo '<p class="erreur">Vous n\êtes pas l\'auteur de cet article et n\'y avez pas accès.</p>';
            }
            // je n'ai pas le droit de modifier un article
            elseif (!allowed('article_edit')) {
                echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de rédaction.</p>';
            }
            // je n'ai pas le droit de créer un article pour cette commission (s'il y a une commission, ce qui n'est pas obligé : CLUB=0 ou COMPTE RENDU DE SORTIE=-1 )
            elseif ($article['code_commission'] && !allowed('article_edit', 'commission:'.$article['code_commission'])) {
                echo '<p class="erreur">Vous n\'avez pas l\'autorisation d\'accéder à cette page car vous ne semblez pas avoir les droits de rédaction pour la commission '.html_utf8($article['code_commission']).'.</p>';
            }

            // on a donné une commission pour laquelle j'ai les droits, alors go
            else {
                // si actuellement publié : message d'alerte validation
                if (1 == $article['status_article']) {
                    echo '<p class="alerte">Attention : si vous modifiez cet article, il devra à nouveau être validée par un responsable avant d\'être publié sur le site !</p>';
                } ?>


				<form action="<?php echo $versCettePage; ?>" method="post">
					<input type="hidden" name="operation" value="article_update" />
					<input type="hidden" name="id_article" value="<?php echo (int) $id_article; ?>" />

					<?php
                    // message d'erreur
                    if ($_POST['operation'] && count($errTab)) {
                        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
                    }
                // message d'info : si c'est une modification
                if ('article_update' == $_POST['operation'] && !count($errTab)) {
                    echo '<p class="info"><img src="img/base/tick.png" alt="" title="" /> Mise à jour effectuée à '.date('H:i:s', $p_time).'. <b>Important :</b> cet article doit à présent être validé par un responsable pour être publié sur le site.<a href="profil/articles/self.html" title="">&gt; Retourner à la liste de mes articles</a></p>';
                } ?>



					<h2 class="trigger-h2">Informations principales :</h2>
					<div class="trigger-me">

						<!-- liste des commissions où poster l'article -->

						Type d'article :<br />
						<select name="commission_article" class="type1" style="width:95%">

							<option value="">- Choisissez :</option>
							<option value="0" <?php if ('0' == $article['commission_article']) {
                    echo 'selected="selected"';
                } ?>>Actualité du club : apparait dans toutes les commissions</option>
							<option value="-1" <?php if ('-1' == $article['commission_article']) {
                    echo 'selected="selected"';
                } ?>>Compte rendu de sortie</option>

							<optgroup label="Article lié à une commission :">
								<?php
                                // articles liés aux commissions
                                foreach ($comTab as $code => $data) {
                                    if (allowed('article_create', 'commission:'.$code)) {
                                        echo '<option value="'.$data['id_commission'].'" '.($article['commission_article'] == $data['id_commission'] ? 'selected="selected"' : '').'>Actualité &laquo; '.html_utf8($data['title_commission']).' &raquo;</option>';
                                    }
                                } ?>
							</optgroup>
						</select>
						<br />
						<br />


						Lier cet article à une sortie :<br />
						<p id="id-sortie-obligatoire-trigger" class="mini">
							Champ obligatoire pour un compte rendu de sortie, facultatif dans les autres cas.
						</p>
						<select name="evt_article" class="type1" style="width:95%">
							<option value="0">- Non merci</option>
							<?php

                            // besoin de la liste des sorties publiées
                            include SCRIPTS.'connect_mysqli.php';
                $req = 'SELECT id_evt, commission_evt, tsp_evt, tsp_end_evt, titre_evt, code_evt FROM caf_evt WHERE status_evt =1 ORDER BY tsp_evt DESC LIMIT 0 , 300';
                $handleSql = $mysqli->query($req);
                while ($row = $handleSql->fetch_assoc()) {
                    echo '<option value="'.$row['id_evt'].'" '.($article['evt_article'] == $row['id_evt'] ? 'selected="selected"' : '').'>'
                                        // .jour(date('N', $row['tsp_evt']))
                                        .' '.date('d', $row['tsp_evt'])
                                        .' '.mois(date('m', $row['tsp_evt']))
                                        .' '.date('Y', $row['tsp_evt'])
                                        .' | '.html_utf8($row['titre_evt'])
                                    .'</option>';
                }
                $mysqli->close(); ?>
						</select>
						<br />
						<br />

						Titre :<br />
						<input style="width:94%;" type="text" name="titre_article" class="type1" value="<?php echo html_utf8($article['titre_article']); ?>" placeholder="ex : Escalade du Grand Som, une sortie bien gaillarde !" />
						<br />
						<br />

						<input type="checkbox" class="custom" name="une_article" <?php if ($article['une_article']) {
                    echo 'checked="checked"';
                } ?> />
						Placer cet article à la Une ?
						<p class="mini" style="padding-right:20px;">
							<b>À utiliser avec parcimonie.</b>  Ceci place l'article au sommet de la page d'accueil, dans les actualités défilantes.
							Il reste affiché là jusqu'à ce qu'un autre article à la Une vienne l'en déloger. Utile pour une actualité qui dure dans le temps,
							ou une alerte à mettre en valeur. La photo est alors obligatoire.
						</p>
						<br />
					</div>

					<h2 class="trigger-h2">Photo :</h2>
					<div class="trigger-me" style="width:95%">
						Envoyez une photo horizontale pour illustrer cet article. Format .jpg, 5Mo maximum !
						<p class="mini">
							Une seule photo par article, chaque image envoyée remplace la précédente.
						</p>
						<br />
						<?php
                        // Définition du dossier ou chercher les images
                        $found = false;

                // dans le cas d'une modification
                $dir = 'ftp/articles/'.$id_article.'/';

                if (file_exists($dir.'min-figure.jpg') &&
                            file_exists($dir.'wide-figure.jpg') &&
                            // file_exists($dir.'pic-figure.jpg') &&
                            file_exists($dir.'figure.jpg')
                            ) {
                    $found = true;
                } ?>
						<!-- valums file upload -->
						<link href="tools/valums-file-upload/css/fileuploader-user.css" rel="stylesheet" type="text/css">
						<div id="file-uploader-ftp"><noscript>L'envoi de fichier nécessite javascript</noscript></div>
						<script src="tools/valums-file-upload/js/fileuploader.js" type="text/javascript"></script>
						<script>
							function createUploader(){
								var uploader = new qq.FileUploader({
									sizeLimit: 5 * 1024 * 1024, // 5 Megz
									element: document.getElementById('file-uploader-ftp'),
									// on passe
									action: 'tools/valums-file-upload/server/images-nouvelarticle.php<?php if ($id_article) {
                    echo '?mode=edit&id_article='.$id_article;
                } ?>',
									// pour chaque image envoyée
									onComplete: function(id, fileName, responseJSON){
										// si succes
										if(responseJSON.success){
											// Effacement du loader
											$("li.qq-upload-success:not(.lpedited)").addClass('lpedited').hide();
											// affichage d'une nouvelle ligne
											var dir='<?php echo $dir; ?>';
											var file='wide-'+responseJSON.filename;
											var id=responseJSON.id;
											var ac=new Date(); // anticache
											var html='<img src="'+dir+file+'?ac='+ac+'" alt="" title="" style="width:100%; height:100%; " />';
											$('#chutier1').html(html);
										};
									},
									debug: true
								});
							}
							window.onload = createUploader;
						</script>

						<!-- IMAGES EXISTANTES ET CHUTIER DES UPLOADS -->
						<?php
                        echo '<br /><div id="chutier1" style="width:230px; height:126px; text-align:center;  padding:3px; margin:0 20px 10px 0; background:white; float:left; box-shadow:0 0 10px -5px black; ">';
                if ($found) {
                    echo '<img src="'.$dir.'wide-figure.jpg?ac='.$p_time.'" alt="" title="" style="width:100%; height:100%; " />';
                }
                echo '</div>';

                inclure('nouvel-article-info-photo', 'vide'); ?>

						<br style="clear:both" />
					</div>



					<h2 class="trigger-h2">Contenus :</h2>
					<div class="trigger-me" style="width:95%">
						<p style="padding-right:20px;">
							<b>Attention :</b><br />Si vous copiez-collez du texte depuis un site, ou un document Word, cochez le bouton <img src="img/texte-brut.png" title="Coller en tant que texte brut" alt="T" />
							avant de coller votre contenu. Sinon vous risquez de provoquer des erreurs sur la page.
						</p>
						<p style="padding-right:20px;">
							<b>Des liens et des images :</b><br />
							Utilisez le bouton <img src="img/button-link.png" title="Lien" alt="" /> puis <img src="img/button-parcourir.png" title="Parcourir" alt="" /> pour ajouter un fichier à télécharger (topo, tracé gps...).<br />
							Utilisez le bouton <img src="img/button-img.png" title="Image" alt="" /> puis <img src="img/button-parcourir.png" title="Parcourir" alt="" /> pour ajouter une image.<br />
						</p>
						<p style="padding-right:20px;">
							<b>Taille de l'éditeur de contenus :</b><br />
							Vous pouvez allonger la hauteur du cadre ci-dessous en tirant avec votre souris sur le coin inférieur droit.
						</p><br />

						<div style="position:relative; right:0px; ">
							<textarea name="cont_article" style="width:625px; "><?php echo stripslashes($article['cont_article']); ?></textarea>
						</div>

						<br />
					</div>


					<!--
					<h2 class="trigger-h2">Co-rédacteurs :</h2>
					<div class="trigger-me" style="width:95%">
						<?php
                        inclure('info-coredacteurs', 'vide');

                // liste des personnes autorisées à?>
						[TODO]
					</div>
					COREDACTEURS -->


					<!-- DEMANDER PUBLICATION ?-->
					<h2 class="trigger-h2">Enregistrer / Publier :</h2>
					<div class="trigger-me" style="width:95%">
						<?php inclure('info-topubly-checkbox', 'vide'); ?>
						<div class="check-nice">
							<label for="topubly_article" style="float:none; width:100%">
								<input type="checkbox" name="topubly_article" id="topubly_article" <?php if (1 == $article['topubly_article']) {
                    echo 'checked="checked"';
                } ?>>
								Demander la publication de cet article dès que possible ?
							</label>
							<p class="mini">

							<br style="clear:both">
						</div>
						<br />
						<br />

						<div style="text-align:center">
							<a class="biglink" href="javascript:void(0)" title="Enregistrer" onclick="$(this).parents('form').submit()">
								<span class="bleucaf">&gt;</span>
								ENREGISTRER LES MODIFICATIONS
							</a>
						</div>
					</div>
				</form>


				<br /><br />
				<br /><br />


				<!-- ****************** -->
				<!-- tinyMCE -->
				<script language="javascript" type="text/javascript" src="tools/tinymce/tiny_mce.js"></script>
				<script language="javascript" type="text/javascript" src="js/jquery.webkitresize.min.js"></script><!-- debug handles -->
				<script language="javascript" type="text/javascript">
					tinyMCE.init({
						// debug handles
						init_instance_callback: function () { $(".mceIframeContainer iframe").webkitimageresize().webkittableresize().webkittdresize(); },

						height : 500,
						theme : "advanced",
						mode : "exact",
						language : "fr",
						elements : "cont_article",
						entity_encoding : "raw",
						plugins : "safari,spellchecker,style,layer,table,save,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
						remove_linebreaks : false,
						file_browser_callback : 'userfilebrowser',

						// forecolor,backcolor,|,
						theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,|,removeformat,cleanup,code",
						theme_advanced_buttons2 : "undo,redo,|,cut,copy,paste,pastetext,|,bullist,numlist,|,link,unlink,image,media,|,charmap,sub,sup",
						theme_advanced_buttons3 : "tablecontrols,|,hr,visualaid,|,fullscreen",

						theme_advanced_toolbar_location : "top",
						theme_advanced_toolbar_align : "left",
						theme_advanced_statusbar_location : "bottom",
						theme_advanced_resizing : true,

						document_base_url : '<?php echo $p_racine; ?>',

						content_css : "<?php echo $p_racine; ?>css/base.css,<?php echo $p_racine; ?>css/style1.css,<?php echo $p_racine; ?>fonts/stylesheet.css",
						body_id : "bodytinymce_user",
						body_class : "cont_article",
						theme_advanced_styles : "<?php echo $p_tiny_theme_advanced_styles; ?>",

						relative_urls : true,
						convert_urls : false,
						remove_script_host : false,
						theme_advanced_blockformats : "p,h2,h3,h4,h5,ul,li",

						theme_advanced_resize_horizontal : false,
						theme_advanced_resizing : true,
						apply_source_formatting : true,
						spellchecker_languages : "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv"

						// onchange_callback : "onchange"
					});
					function userfilebrowser(field_name, url, type, win) {
						// alert("Field_Name: " + field_name + "nURL: " + url + "nType: " + type + "nWin: " + win); // debug/testing
						tinyMCE.activeEditor.windowManager.open({
							file : 'includes/user-file-browser.php?type='+type,
							title : 'Mini-File Browser',
							width : 800,  // Your dimensions may differ - toy around with them!
							height : 500,
							resizable : "yes",
							inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
							close_previous : "no"
						}, {
							window : win,
							input : field_name
						});

						return false;
					}
				</script>
				<!-- /tinyMCE -->

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
