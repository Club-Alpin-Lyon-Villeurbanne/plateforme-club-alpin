<?php
if (user()) {
    ?>
	<div class="main-type">
		<h1>Profil : mes articles</h1>

		<?php inclure('profil-sorties-'.$p3, 'vide'); ?>
		<br />

		<?php
        // AFFICHAGE,

        // MESSAGES d'arreurs
        if ('article_depublier' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
    if ('article_depublier' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Article dépublié à '.date('H:i:s', $p_time).'.</p>';
    }

    if ('article_del' == $_POST['operation'] && count($errTab)) {
        echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
    }
    if ('article_del' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Article supprimé à '.date('H:i:s', $p_time).'.</p>';
    }

    // Rien ?
    if (!count($articleTab)) {
        echo '<p class="info">Vous n\'avez pas encore d\'articles à afficher ici.</p>';
    } else {
        echo '<p class="mini">'.$total.' article'.($total > 1 ? 's' : '').'</p>';
    }

    // Si trouvé
    if (count($articleTab)) {
        // <!-- affichons tout ça comme sur la page d'accueil -->
        for ($i = 0; $i < count($articleTab); ++$i) {
            $article = $articleTab[$i];
            if ($i) {
                echo '<br /><br />';
            }
            include INCLUDES.'article-tools.php';
            include INCLUDES.'article-lien.php';
        }
    }

    // NAV - PAGES
    if ($total > $limite) {
        echo '<hr /><nav class="pageSelect">';
        for ($i = 1; $i <= $nbrPages; ++$i) {
            echo '<a href="'.$p1.'/'.$p2.'.html?pagenum='.$i.'" title="" class="'.($pagenum == $i ? 'up' : '').'">p'.$i.'</a> '.($i < $nbrPages ? '  ' : '');
        }
        echo '</nav>';
    } ?>
	</div>
	<?php
}
?>
