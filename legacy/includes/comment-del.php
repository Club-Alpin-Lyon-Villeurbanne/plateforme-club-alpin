<?php

use App\Legacy\LegacyContainer;
use App\Helper\HtmlHelper;

$id_comment = (int) $_GET['id_comment'];
if (!$id_comment) {
    echo "<p class='erreur'>ID commentaire introuvable.</p>";
} else {
    // recup
    $comment = false;
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT * FROM caf_comment WHERE id_comment = ?');
    $stmt->bind_param('i', $id_comment);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
        $comment = $handle;
    }
    $stmt->close();

    if (!$comment) {
        echo "<p class='erreur'>Commentaire introuvable.</p>";
    }

    // verif de droits
    elseif ((!user() || $comment['user_comment'] != (string) getUser()->getId()) && !allowed('comment_delete_any')) {
        echo "<p class='erreur'>Vous n'avez pas les droits pour supprimer ce commentaire.</p>";
    }

    // si commentaire en ligne
    elseif (1 == $comment['status_comment']) {
        ?>

		<h1>Supprimer un commentaire</h1>
		<form action="<?php echo $versCettePage; ?>" method="post" id="comment-form">
			<input type="hidden" name="operation" value="comment_hide" />
			<input type="hidden" name="id_comment" value="<?php echo $id_comment; ?>" />

			<?php
            // mon commentaire
            if (user() && $comment['user_comment'] == (string) getUser()->getId()) {
                inclure('infos-supprimer-mon-commentaire');
            }
        // ce commentaire (droit special)
        else {
            inclure('infos-supprimer-any-commentaire');
        }

        // MESSAGES A LA SOUMISSION
        if ('comment_hide' == ($_POST['operation'] ?? null) && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
        } ?>
			<br />
			<input type="button" class="nice2" value="Annuler" onclick="top.$.fancybox.close()" />
			<input type="submit" class="nice2 red" value="Supprimer le commentaire ci-dessous" />
			<br />
			<hr />
			Aperçu :
			<div style="background:white; padding:10px; border:1px solid silver">
				<?php
                echo nl2br(HtmlHelper::escape($comment['cont_comment'])); ?>
			</div>
		</form>
		<br />
		<?php
    }

    // si désactivé, redir
    else {
        ?>
		<p class="info">Commentaire désactivé...</p>
		<script type="text/javascript">
		top.location.href = window.location.href;
		top.location.reload();
		</script>
		<?php
    }
}
