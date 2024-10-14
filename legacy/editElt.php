<?php

use App\Legacy\LegacyContainer;

$MAX_VERSIONS = LegacyContainer::getParameter('legacy_env_CONTENT_MAX_VERSIONS');

require __DIR__ . '/app/includes.php';

// _________________________________________________
// _____________________________ PAGE
// _________________________________________________

if ($securityHelpers->isAdmin()) {
    // affichage normal : pas de donnees recues
    if ((!isset($_POST['etape'])) || ('enregistrement' != $_POST['etape'])) {
        // récupération du contenu
        $code_content_html = LegacyContainer::get('legacy_mysqli_handler')->escapeString($_GET['p']);
        $id_content_html = isset($_GET['id_content_html']) || array_key_exists('id_content_html', $_GET) ? (int) htmlspecialchars($_GET['id_content_html']) : null;

        if (!$code_content_html) {
            header('HTTP/1.0 404 Not Found');
            echo 'Erreur : code_content_html introuvable.';
            exit;
        }

        // récupération des dernieres versions dans cette langue
        $req = "SELECT * FROM  `caf_content_html` WHERE  `code_content_html` LIKE  '$code_content_html' AND `lang_content_html` LIKE 'fr' ORDER BY  `date_content_html` DESC LIMIT 0 , 10";
        $contentVersionsTab = [];
        $handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {

            $contentVersionsTab[] = $handle;
        }

        // version courante
        $runningVersion = []; // def : empty array
        if (!$id_content_html) {
            if ($contentVersionsTab[0]) {
                $runningVersion = $contentVersionsTab[0];
            }
        }
        // id d'historique précisé
        else {
            foreach ($contentVersionsTab as $handle) {
                if ($handle['id_content_html'] == $id_content_html) {
                    $runningVersion = $handle;
                }
            }
        } ?>
			<html lang="fr">
				<head>
					<meta charset="utf-8">
					<title>Modifier un element</title>
					<!-- jquery -->
					<script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>
					<script language="javascript" type="text/javascript" src="/js/fonctions.js"></script>
					<script language="javascript" type="text/javascript" src="/js/onready-admin.js"></script>
					<!-- persos -->
					<script type="text/javascript" src="/js/fonctions.js"></script>
					<script type="text/javascript" src="/js/fonctionsAdmin.js"></script>
					<!-- tinyMCE -->
					<script language="javascript" type="text/javascript" src="/tools/tinymce/tiny_mce.js"></script>
					<script language="javascript" type="text/javascript" src="/js/jquery.webkitresize.min.js"></script><!-- debug handles -->
					<script language="javascript" type="text/javascript">

						function onchange(inst){
							$("a.annuler img").attr('src', 'img/base/x-up.png');
						}
						tinyMCE.init({
							// debug handles
							init_instance_callback: function () { $(".mceIframeContainer iframe").webkitimageresize().webkittableresize().webkittdresize(); },

							theme : "advanced",
							mode : "exact",
							language : "fr",
							elements : "contenu_content_html",
							entity_encoding : "raw",
							plugins : "safari,spellchecker,style,layer,table,save,advhr,advimage,advlink,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
							remove_linebreaks : false,
							file_browser_callback : 'lpbrowser',

							// forecolor,backcolor,|,
							theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontsizeselect,|,help,|,removeformat,cleanup,code",
							theme_advanced_buttons2 : "cut,copy,paste,pastetext,|,bullist,numlist,|,blockquote,|,undo,redo,|,link,unlink,anchor,|,image,media,|,search,replace",
							theme_advanced_buttons3 : "tablecontrols,|,hr,visualaid,|,sub,sup,|,charmap,iespell,advhr,|,fullscreen",

							theme_advanced_toolbar_location : "top",
							theme_advanced_toolbar_align : "left",
							theme_advanced_statusbar_location : "bottom",
							theme_advanced_resizing : true,

							content_css : "css/base.css,css/style1.css,fonts/stylesheet.css",
							body_id : "bodytinymce",
							body_class : "<?php echo $_GET['class']; ?>",
							theme_advanced_styles : "Entete Article=ArticleEntete;Titre de menu=menutitle;Bleu clair du CAF=bleucaf;Image flottante gauche=imgFloatLeft;Image flottante droite=imgFloatRight;Lien fancybox=fancybox;Mini=mini;Bloc alerte=erreur;Bloc info=info",

							relative_urls : true,
							convert_urls : false,
							remove_script_host : false,
							theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,ul,li",
                                                        height: 500,
							theme_advanced_resize_horizontal : false,
							theme_advanced_resizing : true,
							apply_source_formatting : true,
							spellchecker_languages : "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv",

							onchange_callback : "onchange"
						});


						function lpbrowser(field_name, url, type, win) {
							// alert("Field_Name: " + field_name + "nURL: " + url + "nType: " + type + "nWin: " + win); // debug/testing
							tinyMCE.activeEditor.windowManager.open({
								file : 'admin/lpfibr.php?dossier='+(type=='image'?'images-pages-libres&type=image':'&type=file'),
								title : 'Mini-File Browser',
								width : 700,  // Your dimensions may differ - toy around with them!
								height : 400,
								resizable : "yes",
								inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
								close_previous : "no"
							}, {
								window : win,
								input : field_name
							});
							return false;
						}

						function loadVersion(){
							var id_content_html= $("select[name=versions]").val();
							var url= 'editElt.php?p=<?php echo htmlentities($_GET['p']); ?>&class=<?php echo htmlentities($_GET['class']); ?>&retour=<?php echo isset($_GET['retour']) ? htmlentities($_GET['retour']) : ''; ?>&parent=<?php echo isset($_GET['parent']) ? htmlentities($_GET['parent']) : ''; ?>&id_content_html='+id_content_html;

							if(!id_content_html) alert('erreur id manquant');
							else{
								$("#loading2").fadeIn(500);
								$("#loading1").fadeIn({duration:1500, complete:function(){
									window.location=url;
								}});
							};
						}

						// ONREADY
						$().ready(function(){
							// chargement de versions précédentes
							$("input[name=loadVersion]").click(function(){	loadVersion(); });
						});

					</script>
					<!-- /tinyMCE -->
					<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/css/admin.css">
					<link rel="stylesheet" media="screen" type="text/css" title="Design" href="/css/base.css">
				</head>
				<body style="background:white; text-align:left; border:none;">

					<div class="onglets-admin">

						<div class="onglets-admin-nav">
							<a href="javascript:void(0)" title="" class="">Editeur de contenu</a>
							<a href="javascript:void(0)" title="" class="">Dossier FTP</a>
						</div>

						<div class="onglets-admin-contenu">

							<!-- TINYMCE + OPTIONS -->
							<div class="onglets-admin-item">
								<form action="editElt.php?retour=<?php echo isset($_GET['retour']) ? $_GET['retour'] : ''; ?>&amp;parent=<?php echo isset($_GET['parent']) ? $_GET['parent'] : ''; ?>" method="POST">
									<input type="hidden" name="etape" value="enregistrement" />
									<input type="hidden" name="code_content_html" value="<?php echo htmlentities($_GET['p']); ?>" />
									<input type="hidden" name="linkedtopage_content_html" value="<?php echo isset($_GET['parent']) ? htmlentities($_GET['parent']) : ''; ?>" />
									<input type="hidden" name="vis_content_html" value="<?php echo $runningVersion ? (int) ($runningVersion['vis_content_html']) : 1; ?>" />

									<p class="miniNote" style="margin-bottom:5px; ">
										<?php if (!$runningVersion['vis_content_html']) { ?>
											<span style="color:#974e00">[<img src="/img/base/bullet_key.png" alt="MASQUÉ" title="Cet élément est actuellement masqué aux visiteurs du site" style="vertical-align:middle; position:relative; bottom:2px " />]</span>&nbsp;
										<?php } ?>
										Vous modifiez l'élément <strong style="font-size:13px;"><?php echo $_GET['p']; ?></strong>
										- en langue <b><img src="/img/base/flag-fr-up.gif" alt="" title="" style="height:10px;" /> FR</b>
										- classe <b><?php echo $_GET['class']; ?></b>
									</p>

									<!-- choix versions -->
									<div style="float:right">
										Charger une version précédente (<?php echo $MAX_VERSIONS; ?> max.) :
										<select name="versions" style="font-size:11px; ">
											<?php
                                            foreach ($contentVersionsTab as $version) {
                                                echo '<option value="' . $version['id_content_html'] . '" ' . ($version['id_content_html'] == $id_content_html ? 'selected="selected"' : '') . '>' . jour(date('N', $version['date_content_html'])) . ' ' . date('d/m/y à H:i:s', $version['date_content_html']) . '</option>';
                                            } ?>
										</select>
										<input type="button" name="loadVersion" value="Charger" class="boutonfancy" />
									</div>


									<a href="javascript:void(0)" onclick="$(this).parents('form').submit();" class="boutonfancy">
										<img src="/img/base/save.png" alt="" title="" style="height:15px; vertical-align:bottom;" /> ENREGISTRER</a>

									<a href="javascript:void(0)" onclick="parent.$.fancybox.close();" class="boutonfancy annuler">
										<img src="/img/base/x.png" alt="" title="" style="vertical-align:top; padding-top:2px;" /> ANNULER</a>

									<br /><br />
									<?php
                                    if ($id_content_html) {
                                        echo '<p class="info">Le contenu ci-dessous a été chargé depuis une version antérieure, mais n\'a pas encore été sauvegardé.</p>';
                                    } ?>
									<div style="background:#c0c0c0; ">
										<textarea id="edition1" class="<?php echo $_GET['class']; ?>" name="contenu_content_html" style="width:100%; "><?php
                                            // affichage contenu courant
                                            echo $runningVersion['contenu_content_html']; ?>
										</textarea>
									</div>
								</form>
								&nbsp;
							</div>

							<!-- TIROIR -->
							<div class="onglets-admin-item">
								<iframe src="admin/ftp.php" class="resize" id="frameFtp" frameborder="0" height="600" width="100%"></iframe>
							</div>

						</div>
					</div>


					<!-- Waiters -->
					<div id="loading1" class="mybox-down"></div>
					<div id="loading2" class="mybox-up">
						<p>
							Chargement de l'élément en cours...
							<br /><br />
							<img src="/img/base/loading.gif" alt="" title="" />
						</p>
					</div>
				</body>
			</html>
			<?php
    }
    // / OPERATIONS
    else {
        $vis_content_html = (int) $_POST['vis_content_html'];
        $code_content_html = $_POST['code_content_html'];
        $linkedtopage_content_html = $_POST['linkedtopage_content_html'];
        $contenu_content_html = stripslashes($_POST['contenu_content_html']);
        // eviter un bloc vide si les liens d'édition sont positionnés en absolute
        if ('' == $contenu_content_html) {
            $contenu_content_html = '&nbsp;';
        }

        // Nettoyage
        $contenu_content_html = LegacyContainer::get('legacy_mysqli_handler')->escapeString($contenu_content_html);
        $code_content_html = LegacyContainer::get('legacy_mysqli_handler')->escapeString($code_content_html);

        // compte des nombre d'entrées à supprimer
        $req = "SELECT COUNT(`id_content_html`) FROM  `caf_content_html` WHERE  `code_content_html` LIKE  '$code_content_html' AND  `lang_content_html` LIKE  'fr'";
        $sqlCount = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        $nVersions = getArrayFirstValue($sqlCount->fetch_array(\MYSQLI_NUM));
        $nDelete = $nVersions - $MAX_VERSIONS;
        if ($nDelete > 0) {
            // s'il y en a à supprimer
            $req = "DELETE FROM `caf_content_html` WHERE `code_content_html` LIKE '$code_content_html' AND  `lang_content_html` LIKE  'fr' ORDER BY  `date_content_html` ASC LIMIT $nDelete"; // ASC pour commencer par la fin de ceux a supprimer
            if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
                header('HTTP/1.0 400 Bad Request');
                echo '<br />Erreur SQL clean !';
                exit;
            }
        }

        // Mise à jour des CURRENT
        $req = "UPDATE `caf_content_html` SET `current_content_html` = '0' WHERE `caf_content_html`.`code_content_html` = '$code_content_html' ";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Erreur SQL';
            exit;
        }

        // Enregistrement
        $req = "INSERT INTO  `caf_content_html` (code_content_html ,`lang_content_html` ,`contenu_content_html` ,`date_content_html` ,`linkedtopage_content_html`, `current_content_html`, `vis_content_html`)
															VALUES ('$code_content_html',  'fr',  '$contenu_content_html',  '" . time() . "',  '$linkedtopage_content_html', 1, $vis_content_html);";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            header('HTTP/1.0 400 Bad request');
            echo 'Erreur SQL';
            exit;
        }

        // log
        mylog('edit-html', 'Modif élément : <i>' . $code_content_html . '</i>', false); ?>
		<script>
			parent.$.fancybox.close();
			parent.window.document.contUpdate('<?php echo $code_content_html; ?>');
		</script>
		<?php
    }
} else {
    echo 'Acess denied<br />Votre session administrateur semble avoir expiré.';
}
