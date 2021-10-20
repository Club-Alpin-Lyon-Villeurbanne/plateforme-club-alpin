<?php

    $id_comment = (int) ($_POST['id_comment']);
    if (!$id_comment) {
        $errTab[] = 'ID commentaire introuvable.';
    }

    include SCRIPTS.'connect_mysqli.php';
    if (!count($errTab)) {
        // recup
        $comment = false;
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
    if (!count($errTab)) {
        if ($comment['user_comment'] != $_SESSION['user']['id_user'] && !allowed('comment_delete_any')) {
            $errTab[] = "<p class='erreur'>Vous n'avez pas les droits pour supprimer ce commentaire.</p>";
        }
    }

    // desactivation
    if (!count($errTab)) {
        $req = "UPDATE caf_comment SET status_comment=2 WHERE id_comment = $id_comment";
        if (!$mysqli->query($req)) {
            $errTab[] = 'Erreur SQL';
        }
    }
    $mysqli->close;
