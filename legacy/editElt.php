<?php

use App\Legacy\LegacyContainer;
use App\Security\SecurityConstants;

$MAX_VERSIONS = LegacyContainer::getParameter('legacy_env_CONTENT_MAX_VERSIONS');

require __DIR__ . '/app/includes.php';

// _________________________________________________
// _____________________________ PAGE
// _________________________________________________

if (isGranted(SecurityConstants::ROLE_CONTENT_MANAGER)) {
    // affichage normal : pas de donnees recues
    if ((!isset($_POST['etape'])) || ('enregistrement' != $_POST['etape'])) {
        // récupération du contenu
        $code_content_html = $_GET['p'];
        $id_content_html = !empty($_GET['id_content_html']) ? (int) htmlspecialchars($_GET['id_content_html']) : null;

        if (!$code_content_html) {
            header('HTTP/1.0 404 Not Found');
            echo 'Erreur : code_content_html introuvable.';
            exit;
        }

        // récupération des dernieres versions dans cette langue
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT * FROM `caf_content_html` WHERE `code_content_html` = ? AND `lang_content_html` = 'fr' ORDER BY `date_content_html` DESC LIMIT 10");
        $stmt->bind_param('s', $code_content_html);
        $stmt->execute();
        $contentVersionsTab = [];
        $handleSql = $stmt->get_result();
        while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
            $contentVersionsTab[] = $handle;
        }
        $stmt->close();

        // version courante
        $runningVersion = []; // def : empty array
        if (!$id_content_html) {
            if (!empty($contentVersionsTab[0])) {
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
                    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteScriptTags('ckeditor5'); ?>
                    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('admin-styles'); ?>
                    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('base-styles'); ?>
                    <?php echo LegacyContainer::get('legacy_entrypoint_renderer')->renderViteLinkTags('common-styles'); ?>
                    <link rel="stylesheet" href="/js/ckeditor5/ckeditor5.css">
                    <script type="importmap">
                        {
                            "imports": {
                                "ckeditor5": "/js/ckeditor5/ckeditor5.js",
                                "ckeditor5/": "/js/ckeditor5/"
                            }
                        }
                    </script>
                    <script type="module" src="/js/ckeditor5-main.js"></script>
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
										<?php if (empty($runningVersion['vis_content_html'])) { ?>
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
										<textarea id="edition1" class="<?php echo $_GET['class']; ?>" name="contenu_content_html" style="width:100%; min-height:300px"><?php
                                            // affichage contenu courant
                                            echo !empty($runningVersion['contenu_content_html']) ? $runningVersion['contenu_content_html'] : ''; ?>
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

        // Nettoyage non nécessaire avec les requêtes préparées

        // compte des nombre d'entrées à supprimer
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("SELECT COUNT(`id_content_html`) FROM `caf_content_html` WHERE `code_content_html` = ? AND `lang_content_html` = 'fr'");
        $stmt->bind_param('s', $code_content_html);
        $stmt->execute();
        $sqlCount = $stmt->get_result();
        $nVersions = getArrayFirstValue($sqlCount->fetch_array(\MYSQLI_NUM));
        $stmt->close();
        $nDelete = $nVersions - $MAX_VERSIONS;
        if ($nDelete > 0) {
            // s'il y en a à supprimer
            $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("DELETE FROM `caf_content_html` WHERE `code_content_html` = ? AND `lang_content_html` = 'fr' ORDER BY `date_content_html` ASC LIMIT ?");
            $stmt->bind_param('si', $code_content_html, $nDelete);
            if (!$stmt->execute()) {
                header('HTTP/1.0 400 Bad Request');
                echo '<br />Erreur SQL clean !';
                exit;
            }
            $stmt->close();
        }

        // Mise à jour des CURRENT
        $req = "UPDATE `caf_content_html` SET `current_content_html` = '0' WHERE `caf_content_html`.`code_content_html` = '$code_content_html' ";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Erreur SQL';
            exit;
        }

        // Enregistrement
        $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO `caf_content_html` (code_content_html, lang_content_html, contenu_content_html, date_content_html, linkedtopage_content_html, current_content_html, vis_content_html) VALUES (?, 'fr', ?, ?, ?, 1, ?)");
        $current_time = time();
        $stmt->bind_param('ssisi', $code_content_html, $contenu_content_html, $current_time, $linkedtopage_content_html, $vis_content_html);
        if (!$stmt->execute()) {
            header('HTTP/1.0 400 Bad request');
            echo 'Erreur SQL';
            exit;
        }
        $stmt->close();

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
