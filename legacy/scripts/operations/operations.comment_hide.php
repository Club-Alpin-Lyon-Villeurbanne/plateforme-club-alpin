<?php

global $kernel;

$id_comment = (int) ($_POST['id_comment']);
if (!$id_comment) {
    $errTab[] = 'ID commentaire introuvable.';
}

$mysqli = include __DIR__.'/../../scripts/connect_mysqli.php';

$comment = null;

if (!isset($errTab) || 0 === count($errTab)) {
    // recup
    $req = "SELECT * FROM caf_comment WHERE id_comment = $id_comment";
    $result = $mysqli->query($req);
    while ($handle = $result->fetch_array(\MYSQLI_ASSOC)) {
        $comment = $handle;
    }
    if (!$comment) {
        $errTab[] = 'Commentaire introuvable.';
    }
}

// verif de droits
if (!isset($errTab) || 0 === count($errTab)) {
    if ($comment['user_comment'] != (string) getUser()->getIdUser() && !allowed('comment_delete_any')) {
        $errTab[] = "<p class='erreur'>Vous n'avez pas les droits pour supprimer ce commentaire.</p>";
    }
}

// desactivation
if (!isset($errTab) || 0 === count($errTab)) {
    $req = "UPDATE caf_comment SET status_comment=2 WHERE id_comment = $id_comment";
    if (!$mysqli->query($req)) {
        $kernel->getContainer()->get('legacy_logger')->error(sprintf('SQL error: %s', $mysqli->error), [
            'error' => $mysqli->error,
            'file' => __FILE__,
            'line' => __LINE__,
            'sql' => $req,
        ]);
        $errTab[] = 'Erreur SQL';
    }
}
$mysqli->close;
