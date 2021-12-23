<?php

    /*
    require "_inc.php"; // on charge les paramètres bdd

    $bdd = mysql_connect(BDD_HOST,BDD_LOGIN,BDD_PASS);
    mysql_select_db(BDD_NAME,$bdd);


    $requetes="";

    $sql=file("fichier.sql"); // on charge le fichier SQL
    foreach($sql as $l){ // on le lit
        if (substr(trim($l),0,2)!="--"){ // suppression des commentaires
            $requetes .= $l;
        }
    }

    $reqs = split(";",$requetes);// on sépare les requêtes
    foreach($reqs as $req){	// et on les éxécute
        if (!mysql_query($req,$bdd) && trim($req)!=""){
            die("ERROR : ".$req); // stop si erreur
        }
    }
    echo "base restaurée";*/
