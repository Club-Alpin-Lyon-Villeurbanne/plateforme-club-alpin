<?php

use App\Legacy\LegacyContainer;

$id_comment = (int) $_POST['id_comment'];
if (!$id_comment) {
    $errTab[] = 'ID commentaire introuvable.';
}

$comment = null;

if (!isset($errTab) || 0 === count($errTab)) {
    // recup
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT * FROM caf_comment WHERE id_comment = ?');
    $stmt->bind_param('i', $id_comment);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
        $comment = $handle;
    }
    $stmt->close();
    if (!$comment) {
        $errTab[] = 'Commentaire introuvable.';
    }
}

// verif de droits
if (!isset($errTab) || 0 === count($errTab)) {
    if ($comment['user_comment'] != (string) getUser()->getId() && !allowed('comment_delete_any')) {
        $errTab[] = "<p class='erreur'>Vous n'avez pas les droits pour supprimer ce commentaire.</p>";
    }
}

// desactivation
if (!isset($errTab) || 0 === count($errTab)) {
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE caf_comment SET status_comment=2 WHERE id_comment = ?');
    $stmt->bind_param('i', $id_comment);
    if (!$stmt->execute()) {
        $errTab[] = 'Erreur SQL';
    }
    $stmt->close();
}
