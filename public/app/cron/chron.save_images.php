<?php

header('Cache-Control: max-age=1'); // don't cache ourself

    //_________________________________________________ DEFINITION DES DOSSIERS
    define('DS', \DIRECTORY_SEPARATOR);
    define('ROOT', dirname(__DIR__, 2).DS);				// Racine
    include ROOT.'app'.DS.'includes.php';

    //_________________________________________________ MYSQLi
    include SCRIPTS.'connect_mysqli.php';

/*
    copie des images des articles depuis le repertoire personnel des adherents
    ftp/user/{id}/images
    vers le repertoire de chaque article
    /ftp/articles/{id}/
*/

    // chdir("D:/wamp/www/CAF");

    /*
        selection des articles publiés, agés de plus de 2j et qui ont des images
    */
    $req = 'SELECT id_article, cont_article FROM caf_article WHERE status_article=1 AND tsp_lastedit < TIMESTAMPADD(DAY , -2, NOW()) AND cont_article REGEXP \'src="ftp/user/[[:digit:]]+/images/\' LIMIT 10';
    $result = $mysqli->query($req);
    $images = [];

    while ($article = $result->fetch_assoc()) {
        echo "<hr />traitement de l'article : ".$article['id_article']."<br />\n";

        $matches = [];
        $nb_matches = preg_match_all('@src="ftp/user/\d+/images/[^"]+@', $article['cont_article'], $matches);
        $nb_copies = 0;

        echo "nombre d'images : ".$nb_matches."<br />\n";
        // print_r($matches);
        echo "<br />\n";

        /*
                echo "<hr />";
                next;
        */
        $dest_cont_article = $article['cont_article'];

        /*
            detection des images
        */
        foreach ($matches[0] as $k => $v) {
            $v = str_replace('src="', '', $v);

            if (!is_dir(ROOT.DS.'ftp'.DS.'articles'.DS.$article['id_article'])) {
                mkdir(ROOT.DS.'ftp'.DS.'articles'.DS.$article['id_article']);
            }

            $dest = preg_replace('@ftp/user/(\d+)/images/@', 'ftp/articles/'.$article['id_article'].'/$1_', $v);
            //echo $dest."<hr />";
            // controle si fichier source present
            if (file_exists(ROOT.DS.$v)) {
                // controle si fichier destination deja present
                if (!file_exists(ROOT.DS.$dest)) {
                    // le fichier destination n'a pas deja ete copie, on le copie
                    if (copy(ROOT.DS.$v, ROOT.DS.$dest)) {
                        // copie fichier OK
                        //echo "copie du fichier de '".$v."' vers '".$dest."'<br />\n";
                        ++$nb_copies;
                    }

                    /*
                                        // le fichier destination n'existe pas, on le deplace
                                        if(rename(ROOT.DS.$v, ROOT.DS.$dest)){
                                            $nb_copies++;
                                        }
                    */
                }

                // controle si fichier destination est present et la taille equivalente
                if (file_exists(ROOT.DS.$dest)) {
                    if (filesize(ROOT.DS.$v) == filesize(ROOT.DS.$dest)) {
                        //  on remplace le chemin de l'image dans le texte de l'article
                        //echo "remplacement du chemin de l'image '".$dest."'<br />\n";
                        $dest_cont_article = str_replace($v, $dest, $dest_cont_article);
                    } else {
                        unlink(ROOT.DS.$dest);
                    }
                }
            } else {
                echo 'fichier source absent :'.ROOT.DS.$v."<br />\n";
                --$nb_matches;
            }
        }

        if ($nb_matches > 0) {
            $req = "UPDATE caf_article SET cont_article='".$mysqli->real_escape_string($dest_cont_article)."' WHERE id_article='".$article['id_article']."'";
            if (!$mysqli->query($req)) {
                error_log('Erreur SQL:'.$mysqli->error);
            }
            // efface les fichiers sources dans le rep user qui ont ete copies dans le repoertoire de l'article
                /*
                foreach ($matches[0] as $k=>$v){
                    $v = str_replace ('src="', '', $v);
                    if(file_exists(ROOT.DS.$v))	{
                        unlink (ROOT.DS.$v);
                    }
                }
                */
        }
    }

    $mysqli->close;
