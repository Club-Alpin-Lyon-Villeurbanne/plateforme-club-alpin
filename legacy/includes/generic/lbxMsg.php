<?php

global $lbxMsg;

// $lbxMsg peut venir par GET ou par variable brute
if (!$lbxMsg) {
    $lbxMsg = $_GET['lbxMsg'] ?? null;
}

if ($lbxMsg) {
    switch ($lbxMsg) {
        // sorties
        case 'evt_deleted':
            $msg = '<p><center><img src="/img/base/tick.png" alt="" title="" /></center> <br />La sortie vient d\'être supprimée.<br /><center><input type="button" value="continuer" class="nice2" onclick="$.fancybox.close()"/></center></p>';
            break;
        case 'evt_create_success':
            $msg = '<p><center><img src="/img/base/tick.png" alt="" title="" /></center> <br />Votre sortie a bien été enregistrée, elle est actuellement en attente de validation par le responsable de commission.<br /><center><input type="button" value="continuer" class="nice2" onclick="$.fancybox.close()"/></center></p>';
            break;

            // annulation de sortie, alertes nomades
        case 'nomadMsg':
            $tab = explode('****', stripslashes($_GET['nomadMsg']));
            $msg = '<div class="alerte">Attention, ces adhérents nomades n\'ont pas été automatiquement avertis de l\'annulation : <ul><li>' . implode('</li><li>', $tab) . '</div>';
            break;

            // articles
        case 'article_create_success':
            $msg = '<p><center><img src="/img/base/tick.png" alt="" title="" /></center> <br />Votre article a bien été enregistré.<br /><center><input type="button" value="continuer" class="nice2" onclick="$.fancybox.close()"/></center></p>';
            break;
        case 'article_edit_success':
            $msg = '<p><center><img src="/img/base/tick.png" alt="" title="" /></center> <br />Votre article a bien été mis à jour.<br /><center><input type="button" value="&lt; Continuer à modifier cet article" class="nice2" onclick="$.fancybox.close()"/><br /><input type="button" value="Retour à la liste de mes articles &gt;" class="nice2" onclick="window.location.href=\'profil/articles.html\'"/></center></p>';
            break;

            // autres
        case 'goodbye':
            $msg = '<p><b>Au revoir</b></p><p>Vous avez été déconnecté. À bientôt !</p><a class="nice2" href="javascript:void(0)" onclick="parent.$.fancybox.close()" title="">Continuer</a>';
            break;
        default:
            $msg = '<p>...</p>';
    } ?>
<script>
$().ready(function(){
    $.fancybox('<div style="text-align:left; padding:0 10px; background:#ececec"><?php echo addslashes($msg); ?></div>');
});
</script>
<?php
} ?>
