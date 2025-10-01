<?php

use App\Legacy\LegacyContainer;

require __DIR__ . '/../../app/includes.php';

$req = 'SELECT id_article, cont_article FROM caf_article WHERE status_article=1 AND updated_at < DATE_SUB(NOW(), INTERVAL -2 DAY)  AND cont_article REGEXP \'src="ftp/user/[[:digit:]]+/images/\' LIMIT 10';
$result = LegacyContainer::get('legacy_mysqli_handler')->query($req);
$images = [];

while ($article = $result->fetch_assoc()) {
    echo "<hr />traitement de l'article : " . $article['id_article'] . "<br />\n";

    $matches = [];
    $nb_matches = preg_match_all('@src="ftp/user/\d+/images/[^"]+@', $article['cont_article'], $matches);
    $nb_copies = 0;

    echo "nombre d'images : " . $nb_matches . "<br />\n";
    echo "<br />\n";

    $dest_cont_article = $article['cont_article'];

    /*
        detection des images
    */
    foreach ($matches[0] as $k => $v) {
        $v = str_replace('src="', '', $v);

        if (!is_dir(__DIR__ . '/../../../public/ftp/articles/' . $article['id_article'])) {
            if (!mkdir($concurrentDirectory = __DIR__ . '/../../../public/ftp/articles/' . $article['id_article']) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        $dest = preg_replace('@ftp/user/(\d+)/images/@', 'ftp/articles/' . $article['id_article'] . '/$1_', $v);
        // echo $dest."<hr />";
        // controle si fichier source present
        $source = __DIR__ . '/../../../public/' . $v;
        $destination = __DIR__ . '/../../../public/' . $dest;
        if (file_exists($source)) {
            // controle si fichier destination deja present
            if (!file_exists($destination)) {
                // le fichier destination n'a pas deja ete copie, on le copie
                if (copy($source, $destination)) {
                    // copie fichier OK
                    // echo "copie du fichier de '".$v."' vers '".$dest."'<br />\n";
                    ++$nb_copies;
                }
            }

            // controle si fichier destination est present et la taille equivalente
            if (file_exists($destination)) {
                if (filesize($source) == filesize($destination)) {
                    //  on remplace le chemin de l'image dans le texte de l'article
                    // echo "remplacement du chemin de l'image '".$dest."'<br />\n";
                    $dest_cont_article = str_replace($v, $dest, $dest_cont_article);
                } else {
                    unlink($destination);
                }
            }
        } else {
            echo 'fichier source absent :' . $source . "<br />\n";
            --$nb_matches;
        }
    }

    if ($nb_matches > 0) {
        $req = "UPDATE caf_article SET cont_article='" . LegacyContainer::get('legacy_mysqli_handler')->escapeString($dest_cont_article) . "' WHERE id_article='" . $article['id_article'] . "'";
        if (!LegacyContainer::get('legacy_mysqli_handler')->query($req)) {
            error_log('Erreur SQL:' . LegacyContainer::get('legacy_mysqli_handler')->lastError());
        }
        // efface les fichiers sources dans le rep user qui ont ete copies dans le repoertoire de l'article
    }
}
